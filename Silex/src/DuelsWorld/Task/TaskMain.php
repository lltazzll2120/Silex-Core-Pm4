<?php

namespace DuelsWorld\Task;

use DuelsWorld\Main;
use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\item\Item;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat as C;

class TaskMain extends Task {

    public $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function getPlugin(): Main {
        return $this->plugin;
    }

    public function onRun() : void
    {
        foreach ($this->getPlugin()->game as $arena => $data) {
            if ($data["status"] == "lobby") {
                $player1 = $data["player1"];
                $player2 = $data["player2"];
                if ($player1 != null && $player2 != null) {
                    $pl1 = $this->getPlugin()->getServer()->getPlayerByPrefix($player1);
                    $pl2 = $this->getPlugin()->getServer()->getPlayerByPrefix($player2);
                    $pl3 = $this->getPlugin()->getServer()->getPlayerByPrefix($player1)->getName();
                    $pl4 = $this->getPlugin()->getServer()->getPlayerByPrefix($player2)->getName();
                    $time = $data["timeStart"];
                    $this->getPlugin()->game[$arena]["timeStart"] = $time - 1;
                    $pl1->getInventory()->clearAll();
                    $pl2->getInventory()->clearAll();
                    if ($time > 0) {
                        $pl1->sendPopup(C::YELLOW . $time);
                        $pl2->sendPopup(C::YELLOW . $time);
                    } else {
                        $this->getPlugin()->game[$arena]["status"] = "game";
                        $map = $this->getPlugin()->game[$arena]["levelName"] = $arena;
                        $pl1->sendMessage(C::RED . "--------------------");
                        $pl1->sendMessage(C::YELLOW . "*A match has started!");
                        $pl1->sendMessage(C::YELLOW . "*Oppenent: $pl4");
                        $pl1->sendMessage(C::YELLOW . "*Map: $map");
                        $pl1->sendMessage(C::RED . "--------------------");
                        $pl2->sendMessage(C::RED . "--------------------");
                        $pl2->sendMessage(C::YELLOW . "*A match has started!");
                        $pl2->sendMessage(C::YELLOW . "*Oppenent: $pl3");
                        $pl2->sendMessage(C::YELLOW . "*Map: $map");
                        $pl2->sendMessage(C::RED . "--------------------");
                        //$pl1->sendMessage(C::YELLOW . "Match Started Agasint " . $pl4);
                        //$pl2->sendMessage(C::YELLOW . "Match Started Agasint " . $pl3);
                        $this->onKit($pl1);
                        $this->onKit($pl2);
                        if ($data["effect"] != 0) {
                            $effect = new EffectInstance(EffectIdMap::getInstance()->fromId($data["effect"]), 100000, $data["level_effect"]);
                            $pl1->getEffects()->add($effect);
                            $pl2->getEffects()->add($effect);
                        }
                    }
                }
            } else {
                $time = $data["timeFinish"];
                $this->getPlugin()->game[$arena]["timeFinish"] = $time - 1;
                $player1 = $data["player1"];
                $player2 = $data["player2"];
                if ($time > 0) {
                    if ($player1 == null || $player2 == null) {
                        // finish match

                        $this->getPlugin()->getApi()->finishMatch($arena);
                    }
                } else {
                    $this->sendLobbyItem($player1);
                    $this->sendLobbyItem($player2);
                }
            }
        }
    }

    public function onKit(Player $player) {
        $arena = $this->getPlugin()->getApi()->getArenaByPlayer($player->getName());
        if ($arena) {
            $type = $this->getPlugin()->game[$arena]["type"];
            if ($type == "NoDebuff") {
                Main::getKits()->giveNoDebuffKit($player);
            } elseif($type == "Gapple") {
                Main::getKits()->giveGapple($player);
            } elseif($type == "Sumo") {
            Main::getKits()->giveSumo($player);
            } elseif($type == "Combo") {
            Main::getKits()->giveCombo($player);
            } elseif($type == "Fist") {
                Main::getKits()->giveFist($player);
            }
        }
    }

            public function sendLobbyItem(Player $player)
            {
                $player->getInventory()->clearAll();
                $player->getHungerManager()->setFood(20);
                $player->setHealth(20);
                $player->setGamemode(GameMode::ADVENTURE());
                $player->getEffects()->clear();
                $player->getArmorInventory()->clearAll();
                $player->getInventory()->setItem(0, Item::get(276)->setCustomName("§aFFA §8"));
                $player->getInventory()->setItem(1, Item::get(283)->setCustomName("§aDuels "));
                $player->getInventory()->setItem(4, Item::get(397,3)->setCustomName("§aStats "));
                $player->getInventory()->setItem(6, Item::get(399)->setCustomName("§aToken Shop"));
                $player->getInventory()->setItem(7, Item::get(403)->setCustomName("§aEvents"));
                $player->getInventory()->setItem(8, Item::get(388)->setCustomName("§aBack To Spawn"));

            }
}
