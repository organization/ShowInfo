<?php
namespace ShowInfo\task;

use pocketmine\scheduler\PluginTask;

class ShowInfoTask extends PluginTask{
	protected $owner;

 	public function onRun($currentTick){
		$this->owner->onRun();
	}
}