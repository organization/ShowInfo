<?php
namespace ShowInfo;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as Color;

class ShowInfo extends \pocketmine\plugin\PluginBase{
	public function onEnable(){
		$this->getServer()->getLogger()->info(Color::GREEN . "Find economy plugin...");
		$pluginManager = $this->getServer()->getPluginManager();
		$ik = $this->getServer()->getLanguage()->getName() == "\"한국어\"";
		if(!($this->money = $pluginManager->getPlugin("PocketMoney")) && !($this->money = $pluginManager->getPlugin("EconomyAPI")) && !($this->money = $pluginManager->getPlugin("MassiveEconomy")) && !($this->money = $pluginManager->getPlugin("Money"))){
			$this->getLogger()->info(Color::RED . "[OneShop] " . ($ik ? "경제 플러그인을 찾지 못했습니다." : "Failed find economy plugin..."));
		}else{
			$this->getLogger()->info(Color::GREEN . "[OneShop] " . ($ik ? "경제 플러그인을 찾았습니다. : " : "Finded economy plugin : ") . $this->money->getName());
		}
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this, [$this, "onTick"]), 5);
	}

	public function onTick(){
		@mkdir($folder = $this->getDataFolder());
		$setting = (new Config($folder . "ShowInfo_Setting.yml", Config::YAML, [
			"Enable" => true,
			"DisplayType" => "Tip",
			"PushVolume" => 0,
		]))->getAll();
		if(!file_exists($path = $folder . "ShowInfo_Format.txt")){	
			file_put_contents($path, implode("\r\n", [
				"# {PLAYERS} = Player count in server",
				"# {MAXPLAYERS} = Max player count",
				"# {PLAYER} = Player's Name",
				"# {DISPLAYNAME} = Player's DisplayName",
				"# {MONEY} = Player's Money",
				"# {RANK} = Player's Money Rank",
				"# {HEALTH} = Player's Health",
				"# {MAXHEALTH} = Player's MaxHealth",
				"# {X}, {Y}, {Z} = Player's Positions",
				"# {WORLD} = Player's world name",
				"# {ITEMID} = Item ID in Player's hand",
				"# {ITEMDAMAGE} = Item Damage in Player's hand",
				"# {ITEMNAME} = Item Name in Player's hand",
				Color::DARK_AQUA . "Your Money: " . Color::AQUA . "{MONEY}" . Color::DARK_AQUA . "$  Rank: " . Color::AQUA . "{RANK}",
				Color::DARK_AQUA . "Your Item: " . Color::AQUA . "{ITEMNAME} ({ITEMID}:{ITEMDAMAGE})",
				Color::DARK_AQUA . "X: " . Color::AQUA . "{X}" . Color::DARK_AQUA . "  Y: " . Color::AQUA . "{Y}" . Color::DARK_AQUA . "  Z: " . Color::AQUA . "{Z}"
			]));
		}
		$lines = explode("\n", trim(str_replace("\r\n", "\n", "#\r\n" . file_get_contents($path))));
		if($setting["Enable"]){
			$push = str_repeat(" ", abs($setting["PushVolume"]));
			$str = "";
			foreach($lines as $line){
				if(strpos($line, "#") !== 0){
					if($setting["PushVolume"] < 0){
						$str .= $line . "\n";
					}else{
						$str .= $push . $line . "\n";						
					}
				}
			}
			if($setting["PushVolume"] < 0){
				$str .= $push;
			}
			$ranks = [];
			if($this->money !== null){
				switch($this->money->getName()){
					case "PocketMoney":
						$property = (new \ReflectionClass("\\PocketMoney\\PocketMoney"))->getProperty("users");
						$property->setAccessible(true);
						$moneys = [];
						foreach($property->getValue($this->money)->getAll() as $k => $v)
							$moneys[strtolower($k)] = $v["money"];
					break;
					case "EconomyAPI":
						$moneys = $this->money->getAllMoney()["money"];
					break;
					case "MassiveEconomy":
						$property = (new \ReflectionClass("\\MassiveEconomy\\MassiveEconomyAPI"))->getProperty("data");
						$property->setAccessible(true);
						$moneys = [];
						$dir = @opendir($path = $property->getValue($this->money) . "users/");
						$cnt = 0;
						while($open = readdir($dir)){
							if(strpos($open, ".yml") !== false){
								$moneys[strtolower(explode(".", $open)[0])] = (new Config($path . $open, Config::YAML, ["money" => 0 ]))->get("money");
							}
						}
					break;
					case "Money":
						$moneys = $this->money->getAllMoneys();
					break;
					default:
						$moneys = [];
					break;
				}
				arsort($moneys);
				$num = 1;
				foreach($moneys as $name => $money){
					if($this->getServer()->isOp($name = strtolower($name))){
						$rank[$name] = [$money, "OP"];
					}else{
						if(!isset($same)){
							$same = [$money,$num];
						}
						if($money == $same[0]){
							$rank[$name] = [$money, $same[1]];
						}else{
							$rank[$name] = $same = [$money, $num];
						}
						$num++;
					}
				}
			}
			foreach(($players = $this->getServer()->getOnlinePlayers()) as $player){
				$item = $player->getInventory()->getItemInHand();
				$message = str_ireplace([
						"{PLAYERS}", "{MAXPLAYERS}", "{PLAYER}", 
						"{DISPLAYNAME}", "{MONEY}", "{RANK}", 
						"{HEALTH}", "{MAXHEALTH}", 
						"{X}", "{Y}", "{Z}", "{WORLD}", 
						"{ITEMID}", "{ITEMDAMAGE}", "{ITEMNAME}"
				], [
						count($players), $this->getServer()->getMaxPlayers(), 
						$name = $player->getName(), $player->getDisplayName(),
						isset($rank[$name = strtolower($name)]) ? $rank[$name][0] : "-",
						isset($rank[$name = strtolower($name)]) ? $rank[$name][1] : "-",
						$player->getHealth(), $player->getMaxHealth(), 
						floor(round($player->x, 1) * 10) * 0.1, floor(round($player->y, 1) * 10) * 0.1, floor(round($player->z, 1) * 10) * 0.1, 
						$player->getLevel()->getFolderName(), $item->getID(), $item->getDamage(), $item->getName()
				], $str);
				switch(true){
					case (stripos($setting["DisplayType"], "1") !== false || stripos($setting["DisplayType"], "popup") !== false):
						$player->sendPopup($message);
					case (stripos($setting["DisplayType"], "2") !== false || stripos($setting["DisplayType"], "tip") !== false):
						$player->sendTip($message);
					break;
				}
			}
		}
	}
}