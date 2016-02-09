<?php
namespace ShowInfo;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\TranslationContainer as Translation;
use ShowInfo\task\ShowInfoTask;
use ShowInfo\task\ShowInfoAsyncTask;

class ShowInfo extends PluginBase{
	const DEFAULT_FORMAT = Color::DARK_AQUA . "Your Money: " . Color::AQUA . "{MONEY}" . Color::DARK_AQUA . "$  Rank: " . Color::AQUA . "{RANK}\n" . Color::DARK_AQUA . "Your Item: " . Color::AQUA . "{ITEMNAME} ({ITEMID}:{ITEMDAMAGE})\n" . Color::DARK_AQUA . "X: " . Color::AQUA . "{X}" . Color::DARK_AQUA . "  Y: " . Color::AQUA . "{Y}" . Color::DARK_AQUA . "  Z: " . Color::AQUA . "{Z}";

	public $tick = 0;
	private $data = [], $moneyPlugin, $playNoteBlockSongPlugin;

	public function onEnable(){
		$this->getServer()->getLogger()->info(Color::GREEN . "Find economy plugin...");
		$pluginManager = $this->getServer()->getPluginManager();
		$ik = $this->getServer()->getLanguage()->getName() == "\"한국어\"";
		if(!($this->moneyPlugin = $pluginManager->getPlugin("PocketMoney")) && !($this->moneyPlugin = $pluginManager->getPlugin("EconomyAPI")) && !($this->moneyPlugin = $pluginManager->getPlugin("MassiveEconomy")) && !($this->moneyPlugin = $pluginManager->getPlugin("Money"))){
			$this->getLogger()->info(Color::RED . "[ShowInfo] " . ($ik ? "경제 플러그인을 찾지 못했습니다." : "Failed find economy plugin..."));
		}else{
			$this->getLogger()->info(Color::GREEN . "[ShowInfo] " . ($ik ? "경제 플러그인을 찾았습니다. : " : "Finded economy plugin : ") . $this->moneyPlugin->getName());
		}
		if(($this->playNoteBlockSongPlugin = $pluginManager->getPlugin("PlayNoteBlockSong")) !== null){
			$this->getLogger()->info(Color::GREEN . "[ShowInfo] " . ($ik ? "PlayNoteBlockSong 플러그인을 찾았습니다." : "Finded PlayNoteBlockSong plugin."));
		}
		$this->loadData();
 		$this->getServer()->getScheduler()->scheduleRepeatingTask(new ShowInfoTask($this), 20);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		$ik = $this->isKorean();
		if(!isset($sub[0]) || $sub[0] == ""){
			return false;
		}
		switch(strtolower($sub[0])){
			case "on":
				if(!$sender->hasPermission("showinfo.cmd.on")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}else{
					$this->data["Enable"] = !$this->data["Enable"];
					$r = Color::YELLOW . "[ShowInfo] " . ($ik ? "정보표시가 " . ($this->data["Enable"] ? "켜" : "꺼") . "졌습니다." : "ShoInfo is " . ($this->data["Enable"] ? "enabled" : "disabled"));
				}
			break;
			case "type":
			case "t":
				if(!$sender->hasPermission("showinfo.cmd.type")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}elseif(!isset($sub[1]) || $sub[1] == ""){
					$r = Color::RED .  "Usage: /ShowInfo Type(T) " . ($ik ? "<표시타입>" : "<DisplayType>");
				}else{					
					$subs = implode(" ", array_splice($sub, 1));
					$type = [];
					if(stripos($subs, "popup") !== false){
						$type[] = "Popup";
					}
					if(stripos($subs, "tip") !== false){
						$type[] = "Tip";
					}
					if(count($type) == 0){
						$r = Color::RED . "[ShowInfo] $subs" . ($ik ? "는 잘못된 표시형식입니다." : " is invalid display type") . " (Popup | Tip)";
					}else{
						$this->data["DisplayType"] = implode(" & ", $type);;
						$this->saveData();
						$r = Color::YELLOW . "[ShowInfo] " . ($ik ? "표시형식이 " . Color::GOLD . $this->data["DisplayType"] . Color::YELLOW . "로 변경되었습니다." : "Display type is changed to " . Color::GOLD . $this->data["DisplayType"]); 
					}
				}
			break;
			case "push":
			case "p":
				if(!$sender->hasPermission("showinfo.cmd.push")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}elseif(!isset($sub[1]) || $sub[1] == ""){
					$r = Color::RED .  "Usage: /ShowInfo Type(T) " . ($ik ? "<표시타입>" : "<DisplayType>");
				}elseif(!is_numeric($sub[1])){			
					$r = Color::RED . "[ShowInfo] $sub[1]" . ($ik ? "는 잘못된 숫자입니다." : " is invalid number");
				}else{
					$this->data["PushVolume"] = $sub[1];
					$this->saveData();
					$r = Color::YELLOW . "[ShowInfo] " . ($ik ? "밀기 정도가 " . Color::GOLD . $sub[1] . Color::YELLOW . "로 변경되었습니다." : "Push volmue is changed to " . Color::GOLD . $sub[1]); 
				}
			break;
			case "reload":
				if(!$sender->hasPermission("showinfo.cmd.reload")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}else{
					$this->loadData();
					$r = Color::YELLOW . "[ShowInfo] " . ($ik ? "데이터를 로드했습니다." : "Load thedata");
				}
			break;
			case "reset":
			case "리셋":
			case "초기화":
				if(!$sender->hasPermission("showinfo.cmd.reset")){
					$r = new Translation(Color::RED . "%commands.generic.permission");
				}else{
					$this->data["Enable"] = true;
					$this->data["DisplayType"] = "Tip";
					$this->data["PushVolume"] = 0;
					$this->data["Format"] = self::DEFAULT_FORMAT;
					$this->saveData();
					$r = Color::YELLOW . "[ShowInfo] " . ($ik ? "데이터를 리셋했습니다." : "Reset the data");
				}
			break;
			default:
				return false;
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		return true;
	}

	public function onRun(){
		if($this->data["Enable"]){
 			$info = str_ireplace(
 				"{SONG}", 
				!$this->playNoteBlockSongPlugin ? "-" : $this->playNoteBlockSongPlugin->getPlaySongName(), 
				$this->data["Format"]
			);
			$moneys = [];
			if($this->moneyPlugin !== null && (stripos($info, "{MONEY}") || stripos($info, "{RANK}"))){
				switch($this->moneyPlugin->getName()){
					case "PocketMoney":
						$property = (new \ReflectionClass("\\PocketMoney\\PocketMoney"))->getProperty("users");
						$property->setAccessible(true);
						$moneys = [];
						foreach($property->getValue($this->moneyPlugin)->getAll() as $k => $v)
							$moneys[strtolower($k)] = $v["money"];
					break;
					case "EconomyAPI":
						$moneys = $this->moneyPlugin->getAllMoney()["money"];
					break;
					case "MassiveEconomy":
						$property = (new \ReflectionClass("\\MassiveEconomy\\MassiveEconomyAPI"))->getProperty("data");
						$property->setAccessible(true);
						$moneys = [];
						$dir = @opendir($path = $property->getValue($this->moneyPlugin) . "users/");
						$cnt = 0;
						while($open = readdir($dir)){
							if(strpos($open, ".yml") !== false){
								$moneys[strtolower(explode(".", $open)[0])] = (new Config($path . $open, Config::YAML, ["money" => 0 ]))->get("money");
							}
						}
					break;
					case "Money":
						$moneys = $this->moneyPlugin->getAllMoneys();
					break;
					default:
						$moneys = [];
					break;
				}
			}
			$this->getServer()->getScheduler()->scheduleAsyncTask(new ShowInfoAsyncTask($info, $moneys, $this->data, $this->getServer()->getOps()->getAll()));
		}
	}

	public function loadData(){
		@mkdir($folder = $this->getDataFolder());
		$this->data = (new Config($folder . "ShowInfo_Setting.yml", Config::YAML, [
			"Enable" => true,
			"DisplayType" => "Tip",
			"PushVolume" => 0,
			"Format" => ""
		]))->getAll();
		if(!file_exists($path = $folder . "ShowInfo_Format.txt")){	
			file_put_contents($path, self::DEFAULT_FORMAT);
		}
		$this->data["Format"] = file_get_contents($path);
		file_put_contents($folder . "Changes List.txt",
			"# {PLAYERS} = Player count in server \n" . 
			"# {MAXPLAYERS} = Max player count \n" . 
			"# {SONG} = Playing song name \n" . 
			"# {PLAYER} = Player's Name \n" . 
			"# {DISPLAYNAME} = Player's DisplayName \n" . 
			"# {MONEY} = Player's Money \n" . 
			"# {RANK} = Player's Money Rank \n" . 
			"# {HEALTH} = Player's Health \n" . 
			"# {MAXHEALTH} = Player's MaxHealth \n" . 
			"# {X}, {Y}, {Z} = Player's Positions \n" . 
			"# {WORLD} = Player's world name \n" . 
			"# {ITEMID} = Item ID in Player's hand \n" . 
			"# {ITEMDAMAGE} = Item Damage in Player's hand \n" . 
			"# {ITEMNAME} = Item Name in Player's hand"
		);
	}

	public function saveData(){
		@mkdir($folder = $this->getDataFolder());
		$config = new Config($folder . "ShowInfo_Setting.yml", Config::YAML, [
			"Enable" => true,
			"DisplayType" => "Tip",
			"PushVolume" => 0,
			"Format" => ""
		]);
		$data = $config->getAll();
		$data["Enable"] = $this->data["Enable"];
		$data["DisplayType"] = $this->data["DisplayType"];
		$data["PushVolume"] = $this->data["PushVolume"];
		$config->setAll($data);
		$config->save();
		file_put_contents($folder . "ShowInfo_Format.txt", $this->data["Format"]);
	}

	public function isKorean(){
		return $this->getServer()->getLanguage()->getName() == "\"한국어\"";
	}
}