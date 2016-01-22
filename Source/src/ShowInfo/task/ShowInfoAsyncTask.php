<?php
namespace ShowInfo\task;

use pocketmine\scheduler\AsyncTask;

class ShowInfoAsyncTask extends AsyncTask{
	public function __construct(){
	}

	public function onCompletion(\pocketmine\Server $server){
		$server->getPluginManager()->getPlugin("ShowInfo")->onAsyncRun();
	}

	public function onRun(){
	}
}