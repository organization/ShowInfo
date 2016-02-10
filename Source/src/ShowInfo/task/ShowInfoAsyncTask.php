<?php
namespace ShowInfo\task;

use pocketmine\Server;
use pocketmine\scheduler\AsyncTask;

class ShowInfoAsyncTask extends AsyncTask{
	private $info = "", $moneys, $data, $operators, $rank;
	private $beforeMoneys;

	public function __construct($info, $moneys, $data, $operators){
		$this->info = $info;
		$this->data = $data;
		$this->operators = $operators;
		$this->beforeMoneys = $moneys;
		if(version_compare("7.0", PHP_VERSION) <= 0){
	 		$this->rank = new \Threaded();
	 		$this->moneys = new \Threaded();
	 	}
	}

	public function onCompletion(Server $server){
		$this->info = str_ireplace([
			"{PLAYERS}", "{MAXPLAYERS}"
		], [
			count($server->getOnlinePlayers()), 
			$server->getMaxPlayers(), 
		], $this->info);
		foreach($server->getOnlinePlayers() as $player){
			$name = $player->getName();
			$iname = strtolower($name);
 			$item = $player->getInventory()->getItemInHand();
			$message = str_ireplace([
				"{PLAYER}", "{DISPLAYNAME}", 
				"{MONEY}", "{RANK}", 
				"{HEALTH}", "{MAXHEALTH}", 
				"{X}", "{Y}", "{Z}", "{WORLD}", 
				"{ITEMID}", "{ITEMDAMAGE}", "{ITEMNAME}"
			], [
				$name, $player->getDisplayName(),
				isset($this->moneys[$iname]) ? $this->moneys[$iname] : "-",
				isset($this->rank[$iname]) ? $this->rank[$iname] : "-",
				$player->getHealth(), $player->getMaxHealth(), 
				floor(round($player->x, 1) * 10) * 0.1, floor(round($player->y, 1) * 10) * 0.1, floor(round($player->z, 1) * 10) * 0.1, 
				$player->level->getFolderName(), $item->getID(), $item->getDamage(), $item->getName()
			], $this->info);
			switch(true){
				case stripos($this->data["DisplayType"], "popup") !== false:
					$player->sendPopup($message);
				case stripos($this->data["DisplayType"], "tip") !== false:
					$player->sendTip($message);
				break;
			}
		}
 	}

	public function onRun(){
		$push = str_repeat(" ", abs($this->data["PushVolume"]));
		if($this->data["PushVolume"] < 0){
			$this->info =	$push . str_replace("\n", "$push\n", $this->info);
		}else{
			$this->info = str_replace("\n", "\n$push", $this->info) . $push;
		}
		$num = 1;
		$moneys = (array) $this->beforeMoneys;
		arsort($moneys);
		if(version_compare("7.0", PHP_VERSION) > 0){
			$rank = [];
			$this->moneys = $moneys;
		}
		foreach($moneys as $name => $money){
			if(version_compare("7.0", PHP_VERSION) <= 0){
				$this->moneys[$name] = $money;
			}
			if(isset($this->operators[$name = strtolower($name)])){
				if(version_compare("7.0", PHP_VERSION) > 0){
					$rank[$name] = "OP";
				}else{
					$this->rank[$name] = "OP";
				}
			}else{
				if(!isset($same)){
					$same = [$money,$num];
				}
				$same = $money == $same[0] ? [$money, $same[1]] : [$money, $num];
				$num++;
				if(version_compare("7.0", PHP_VERSION) > 0){
					$rank[$name] = $same[1];
				}else{
					$this->rank[$name] = $same[1];
				}
			}
		}
		if(version_compare("7.0", PHP_VERSION) > 0){
			$this->rank = $rank;
		}
	}
}