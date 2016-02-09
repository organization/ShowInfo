<?php
namespace ShowInfo\task;

use pocketmine\Server;
use pocketmine\scheduler\AsyncTask;

class ShowInfoAsyncTask extends AsyncTask{
	private $info = "", $moneys = [], $data = [], $operators, $rank = [];

	public function __construct($info, $moneys, $data, $operators){
		$this->info = $info;
		$this->moneys = $moneys;
		$this->data = $data;
		$this->operators = $operators;
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
		arsort($this->moneys);
		$num = 1;
		$rank = [];
		foreach($this->moneys as $name => $money){
			if(isset($this->operators[$name = strtolower($name)])){
				$rank[$name] = "OP";
			}else{
				if(!isset($same)){
					$same = [$money,$num];
				}
				$same = $money == $same[0] ? [$money, $same[1]] : [$money, $num];
				$num++;
				$rank[$name] = $same[1];
			}
		}
		$this->rank = $rank;
 	}
}

class Rank{
	public $rank = [];

	public function set($name, $rank){
		$this->rank[$name] = $rank;
	}

	public function get($name){
		return $this->rank[$name];
	}

	public function exists($name){
		return isset($this->rank[$name]);
	}

	public function getAll(){
		return $this->rank;
	}
}