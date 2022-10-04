<?php

namespace DuelsWorld\Task;

use DuelsWorld\Main;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;

class LeaderboardTask extends Task {
	/**
	 * @param int $tick
	 */
	public function onRun() : void {

		Main::getInstance()->killsLeaderboard->setTitle(Main::getInstance()->getKillsLeaderboard());
		Main::getInstance()->getServer()->getWorldManager()->getWorldByName("Hub")->addParticle(new Vector3(-232, 47, -427), Main::getInstance()->killsLeaderboard);
		Main::getInstance()->deathsLeaderboard->setTitle(Main::getInstance()->getDeathsLeaderboard());
		Main::getInstance()->getServer()->getWorldManager()->getWorldByName("Hub")->addParticle(new Vector3(-222, 47, -437), Main::getInstance()->deathsLeaderboard);
        Main::getInstance()->topelo->setTitle(Main::getInstance()->geteloLeaderboard());
        Main::getInstance()->getServer()->getWorldManager()->getWorldByName("Hub")->addParticle(new Vector3(-231, 47, -436), Main::getInstance()->topelo);

		foreach(Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
			Main::getInstance()->updateFT($player);
		}
	}
}