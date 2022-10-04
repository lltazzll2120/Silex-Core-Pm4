<?php
namespace DuelsWorld\Task;

use DuelsWorld\ListenerDuelsWorld;
use DuelsWorld\Main;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class PlayerTask extends Task {
	/**
	 * @param int $currentTick
	 */
	public function onRun() : void {
		foreach(Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
				$c = Main::getInstance()::getScoreboardManager();
				$c->showScoreboard($player);
				$c->clearLines($player);
				$c->addLine("§cK: §b" . Main::getInstance()->getKills($player) . " §r§cD: §b" . Main::getInstance()->getDeaths($player), $player);
				$kdr = 0;
				if (Main::getInstance()->getKills($player) == 0 or Main::getInstance()->getDeaths($player) == 0) {
					$kdr = 0;
				} else {
					$kdr = round(Main::getInstance()->getKills($player) / Main::getInstance()->getDeaths($player), 2);
				}
				$c->addLine("§cKDR: §b" . $kdr, $player);
				$c->addLine("§cKillStreak: §b" . Main::getInstance()->getKillstreak($player), $player);
				$num = 0;
				$f = Main::getInstance();
				if (isset($f->pcooldown[$player->getName()]) and time() - $f->pcooldown[$player->getName()] < 12) $num++;
				if (time() - Main::getInstance()->combatTag[strtolower($player->getName())] < 15) $num++;
				if ($num != 0) {
					if (isset($f->pcooldown[$player->getName()]) and time() - $f->pcooldown[$player->getName()] < 12) {
						$time = time() - $f->pcooldown[$player->getName()];
						$c->addLine("§fEnderPearl: §b" . (Main::intToString(12 - $time)), $player);
					}
					if (time() - Main::getInstance()->combatTag[strtolower($player->getName())] < 15) {
						$time = time() - Main::getInstance()->combatTag[strtolower($player->getName())];
						$cooldown = 15 - $time;
						$c->addLine("§4Combat: §b" . Main::intToString($cooldown), $player);
					}
				}
				$c->addLine("§o§bSilexpe.club", $player);

				if(in_array($player->getWorld()->getFolderName(), Main::$ffaArenas)){
                    $player->setNameTag(TextFormat::AQUA . $player->getName() . TextFormat::EOL . TextFormat::RED . "♡ " . TextFormat::AQUA . $player->getHealth()
                    . TextFormat::EOL . TextFormat::YELLOW . "Ping: " . TextFormat::GOLD . $player->getNetworkSession()->getPing());
                }else{
                    if(isset(ListenerDuelsWorld::$savednametags[$player->getName()])){
                        $player->setNameTag(ListenerDuelsWorld::$savednametags[$player->getName()]);
                    }

                }
			}
		}
}