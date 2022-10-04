<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DuelsWorld;

/**
 * Description of Managment
 *
 * @author Salvatore
 */

use onebone\economyapi\EconomyAPI;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;
use pocketmine\utils\TextFormat as TF;

class Managment {

    public $players = [];

    public $player1 = null;

    public $player2 = null;

    public function __construct(
        public Main $plugin
    ) {
        
    }

    public function startMatchs() {
        foreach ($this->plugin->getArenasConfig() as $arena => $data) {
            $this->startMatch($arena);
        }
    }


    public function getPlugin() {
        return $this->plugin;
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $this->sendLobbyItem($player);
    }

    public function resetLevel($levelName, $backupPath) {
        //    $lv = $this->getPlugin()->getServer()->getWorldManager()->getWorldByName($levelName);
        $lv = null;
        foreach ($this->getPlugin()->getServer()->getWorldManager()->getWorlds() as $level) {
            if ($level->getFolderName() == $levelName) {
                $lv = $level;
            }
        }
        if ($lv != null) {
            $this->getPlugin()->getServer()->getWorldManager()->unloadWorld($lv);
            $worldPath = $this->getPlugin()->getServer()->getDataPath() . "worlds/" . $levelName;
            self::file_delDir($worldPath);
            mkdir($worldPath);
            $zip = new \ZipArchive;
            // ($backupPath);
            //  $path = $this->getPlugin()->getServer()->getDataPath();
            $zip->open($this->getPlugin()->getServer()->getDataPath() . "worlds/" . $backupPath);
            $zip->extractTo($worldPath);
            $this->getPlugin()->getServer()->getWorldManager()->loadWorld($levelName);
        } else {
            $this->getPlugin()->getServer()->getLogger()->warning("ATTENZIONE NESSUN LIVELLO TROVATO CON NOME " . $levelName . " INSERIRE IL LIVELLO E RIAVVIARE IL SERVER ");
        }
    }

    public static function file_delDir($dir) {
        $dir = rtrim($dir, "/\\") . "/";

        foreach (scandir($dir) as $file) {
            if ($file === "." or $file === "..") {
                continue;
            }
            $path = $dir . $file;
            if (is_dir($path)) {
                self::file_delDir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    public function getDuels(): array{
        return $this->getPlugin()->game;
    }

    public function startMatch($arena) {
        $data = $this->plugin->arenas[$arena];
        $this->getPlugin()->game[$arena]["levelName"] = $arena;

        $this->getPlugin()->game[$arena]["spawnLocation"] = $data["spawnLocation"];
        $this->getPlugin()->game[$arena]["kit"] = $data["kit"];
        $this->getPlugin()->game[$arena]["type"] = $data["type"];
        // 0 unranked 1 ranked
        $this->getPlugin()->game[$arena]["typeArena"] = null;
        $this->getPlugin()->game[$arena]["timeStart"] = 10;
        $this->getPlugin()->game[$arena]["timeFinish"] = 300;
        $this->getPlugin()->game[$arena]["status"] = "lobby";
        $this->getPlugin()->game[$arena]["knockBack"] = $data["knockBack"];
        $this->getPlugin()->game[$arena]["player1"] = null;
        $this->getPlugin()->game[$arena]["player2"] = null;
        $this->getPlugin()->game[$arena]["blocks"] = array();
        if ($data["type"] == "BuildUHC" || $data["type"] == "Spleef") {
            $this->resetLevel($this->getPlugin()->game[$arena]["levelName"], "backup/" . $this->getPlugin()->game[$arena]["levelName"] . ".zip");
        }
        $this->getPlugin()->getServer()->getWorldManager()->loadWorld($arena);
        $this->getPlugin()->game[$arena]["level"] = $this->getPlugin()->getServer()->getWorldManager()->getWorldByName($arena);
        $this->getPlugin()->game[$arena]["effect"] = $data["effect"];
        $this->getPlugin()->game[$arena]["level_effect"] = $data["level_effect"];
    }

    public function finishMatch($arena) {
        $data = $this->getPlugin()->game[$arena];
        $pl = null;
        if ($data["player1"] != null) {
            $pl = $data["player1"];
        }
        if ($data["player2"] != null) {
            $pl = $data["player2"];
        }
        $player = null;
        if ($pl) {
            $player = $this->getPlugin()->getServer()->getPlayerByPrefix($pl);
        }
        if ($player) {
            $level = $this->getPlugin()->getServer()->getWorldManager()->getWorldByName($this->getPlugin()->getDefaultConfig()["lobbyWorld"]);
            $player->teleport($level->getSpawnLocation());
            $player->sendTitle(C::YELLOW . "You Won");
            Main::getInstance()->addKill($player);
            $this->sendLobbyItem($player);
            if ($data["typeArena"] == 1) {
                Main::getInstance()->addelo($player);
                $level = $this->getPlugin()->getServer()->getWorldManager()->getWorldByName($this->getPlugin()->getDefaultConfig()["lobbyWorld"]);
                $player->teleport($level->getSpawnLocation());
                $player->setHealth(20);
                $this->sendLobbyItem($player);
            }
        }
        // reset
        $this->startMatch($arena);
    }

    public function searchRandomGamePlayer($type, $typeArena) {
        foreach ($this->getPlugin()->game as $arena => $data) {
            var_dump($type, $typeArena, $data["type"], $data["typeArena"]);
            if ($data["status"] == "lobby" && $data["type"] == $type && ($data["typeArena"] == $typeArena) && ($data["player2"] == null || $data["player1"] == null)) {
                return $arena;
            }
        }
        foreach ($this->getPlugin()->game as $arena => $data) {
            if ($data["status"] == "lobby" && $data["type"] == $type && ($data["typeArena"] == null) && ($data["player1"] == null && $data["player2"] == null)) {
                return $arena;
            }
        }
        return null;
    }

    // arena datas 
    public function getLevelByArena($arena): World {
        return $this->getPlugin()->game[$arena]["level"];
    }

    public function getSpawnLocationByIndex($arena, $i): Position {
        $coord = $this->getPlugin()->game[$arena]["spawnLocation"][$i];
        return new Position($coord["x"], $coord["y"], $coord["z"], $this->getLevelByArena($arena));
    }

    ///////////////////////////

    public function splitter($string): array {
        $splitte = explode(":", $string);
        return $splitte;
    }

    public function getItemByString($string) {
        $arr = $this->splitter($string);
        $item = ItemFactory::getInstance()->get($arr[0], $arr[1], $arr[2]);
        if (isset($arr[3]) && $arr[3] != 0) {
            $ench = EnchantmentIdMap::getInstance()->fromId($arr[3]);
            $item->addEnchantment(new EnchantmentInstance($ench, $arr[4]));
        }
        return $item;
    }

    public function setKitArena(Player $player, $arena) {
        $kit = $this->getPlugin()->game[$arena]["kit"];
        $armor = $kit["Armor"];
        $player->getArmorInventory()->setHelmet($this->getItemByString($armor[0]));
        $player->getArmorInventory()->setChestplate($this->getItemByString($armor[1]));
        $player->getArmorInventory()->setLeggings($this->getItemByString($armor[2]));
        $player->getArmorInventory()->setBoots($this->getItemByString($armor[3]));
        $itemBar = $kit["ItemBar"];

        for ($i = 0; $i < count($itemBar); $i++) {
            $item = $this->getItemByString($itemBar[$i]);

            $player->getInventory()->setItem($i, $item);
        }
        $inventory = $kit["Inventory"];
        for ($i = 0; $i < count($inventory); $i++) {
            $item = $this->getItemByString($inventory[$i]);
            //$player->getInventory()->addItem($item);
        }
    }

    ///////////////////////////
    public function getArenaByPlayer($pl) {
        foreach ($this->getPlugin()->game as $arena => $data) {
            if ($data["player1"] == $pl || $data["player2"] == $pl) {
                return $arena;
            }
        }
        return null;
    }

    public function addPlayerToArena($arena, $pl) {
        $data = $this->getPlugin()->game[$arena];
        if ($data["player1"] == null) {
            $this->getPlugin()->game[$arena]["player1"] = $pl;
            return 0;
        } else {
            $this->getPlugin()->game[$arena]["player2"] = $pl;
            return 1;
        }
        // ok
    }

    public function removePlayerToArena($arena, $pl) {
        $data = $this->getPlugin()->game[$arena];

        if ($data["player1"] == $pl) {
            $this->getPlugin()->game[$arena]["player1"] = null;
        } else {
            $this->getPlugin()->game[$arena]["player2"] = null;
        }
        if ($this->getPlugin()->game[$arena]["player1"] == null && $this->getPlugin()->game[$arena]["player2"] == null) {
            $this->getPlugin()->game[$arena]["typeArena"] = null;
        }
        $level = $this->getLevelByArena($arena);
        foreach($level->getEntities() as $entity){
            if($entity instanceof Player && $entity->getGamemode()->equals(GameMode::SPECTATOR())){
                $entity->setGamemode(GameMode::SURVIVAL());
                $entity->sendMessage("§aSuccessfully Teleported To Spawn!");
                Main::getKits()->sendLobbyItem($entity);
                $entity->teleport(Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
            }
        }
        $this->getPlugin()->game[$arena]["timeStart"] = 10;
        if ($data["status"] == "game") {
            $player = $this->getPlugin()->getServer()->getPlayerByPrefix($pl);
            $typeArena = $data["typeArena"];
            if ($player) {
                $this->sendLobbyItem($player);
                $player->sendTitle(C::RED . "You Lost!");
                $this->sendLobbyItem($player);
                Main::getInstance()->addDeath($player);

                $level = $this->getPlugin()->getServer()->getWorldManager()->getWorldByName($this->getPlugin()->getDefaultConfig()["lobbyWorld"]);
                $player->teleport($level->getSpawnLocation());
                $this->sendLobbyItem($player);
                if ($typeArena == 1) {
                    Main::getInstance()->removeelo($player);
                    $this->sendLobbyItem($player);
                    $player->teleport($level->getSpawnLocation());
                }
                $player->getEffects()->clear();
                $player->setHealth(20);
                $this->sendLobbyItem($player);
            }
        }
    }

    public function joinPlayerToRandomArena(Player $player, $type, $typeArena) {
        $pl = $player->getName();
        if ($this->getArenaByPlayer($pl) == null) {
            $arena = $this->searchRandomGamePlayer($type, $typeArena);
            if ($arena) {
                $data = $this->getPlugin()->game[$arena];
                // var_dump($data["player1"] == null && $data["player2"] == null);
                if ($data["player1"] == null && $data["player2"] == null) {
                    $this->getPlugin()->game[$arena]["typeArena"] = $typeArena;
                }
                $pos = $this->addPlayerToArena($arena, $pl);


                $data = $this->getPlugin()->game[$arena];
                if ($data["player1"] != null && $data["player2"] != null) {
                    $player->teleport($this->getSpawnLocationByIndex($arena, $pos));
                    $pl = $pos + 1;
                    $player2 = $this->getPlugin()->getServer()->getPlayerByPrefix($data["player$pos"]);
                    $coord = null;

                    if ($pos == 0) {
                        $coord = $this->getPlugin()->game[$arena]["spawnLocation"][1];
                    } else {
                        $coord = $this->getPlugin()->game[$arena]["spawnLocation"][0];
                    }
                    // ok
                    $player2->setHealth(20);
                    $player2->teleport(new Position($coord["x"], $coord["y"], $coord["z"], $this->getLevelByArena($arena)));
                }
                //        $player->getInventory()->clearAll();
                //       $this->setKitArena($player, $arena);
                $player->setHealth(20);
                $player->sendMessage(C::YELLOW . "You joined the queue for " . $type);
                //$this->leavequeue($player);
                //    var_dump( $this->getPlugin()->game[$arena]);
            } else {
                $player->sendMessage(C::YELLOW  . "There is no arenas for " . $type);
            }
        }
    }

    public function getRankedUnRankedPlaying($type) {
        $count = 0;
        foreach ($this->getPlugin()->game as $arena => $data) {
            if ($data["status"] == "game" && $data["typeArena"] == $type) {
                $count ++;
            }
        }
        return $count;
    }

    public function getRankedUnRankedLobby($type) {
        $count = 0;
        foreach ($this->getPlugin()->game as $arena => $data) {
            if ($data["status"] == "lobby" && $data["typeArena"] == $type && ($data["player1"] != null || $data["player2"] != null)) {
                $count ++;
            }
        }
        return $count;
    }

    public function getRankedUnRankedPlayingStyle($type, $style) {
        $count = 0;
        foreach ($this->getPlugin()->game as $arena => $data) {
            if ($data["status"] == "game" && $data["typeArena"] == $type && $data["type"] == $style) {
                $count ++;
            }
        }
        return $count;
    }

    public function getRankedUnRankedLobbyStyle($type, $style) {
        $count = 0;
        foreach ($this->getPlugin()->game as $arena => $data) {
            if ($data["status"] == "lobby" && $data["typeArena"] == $type && ($data["player1"] != null || $data["player2"] != null) && $data["type"] == $style) {
                $count ++;
            }
        }
        return $count;
    }

    public function createGuiCompass(Player $sender) {
        if(isset($this->getPlugin()->openedForm[$name = $sender->getName()]) and $this->getPlugin()->openedForm[$name] === false){
            $this->getPlugin()->openedForm[$sender->getName()] = true;
            $form = $this->getPlugin()->FormAPI->createSimpleForm(function (Player $sender, $data) {
                $arena = $this->getArenaByPlayer($sender->getName());
                if (isset($data[0])) {
                    switch ($data) {
                        case "ranked_case":
                            $this->rankedCompass($sender);
                            break;
                        case "unranked_case":
                            $this->UnrankedCompass($sender);
                            break;
                        case "quit_queque":
                            if ($arena) {
                                $this->removePlayerToArena($arena, $sender->getName());
                            }
                            break;
                    }
                    return true;
                }
                return false;
            });
            $arena = $this->getArenaByPlayer($sender->getName());
            $form->setTitle("Choose a Duel Type");
            $qu_un = $this->getRankedUnRankedLobby(0);
            $pl_un = $this->getRankedUnRankedPlaying(0);
            $form->addButton("Unranked \n Playing: " . $pl_un . " Queued: " . $qu_un, -1, "", "unranked_case");
            $qu_ra = $this->getRankedUnRankedLobby(1);
            $pl_ra = $this->getRankedUnRankedPlaying(1);
            $form->addButton("Ranked \n Playing: " . $pl_ra . " Queued: " . $qu_ra, -1, "", "ranked_case");
            if ($arena) {
                $form->addButton("Quit Queque ", 1, "http://worldartsme.com/images/free-x-clipart-1.jpg", "quit_queque");
            }
            $form->sendToPlayer($sender);
        }
    }

    public function UnrankedCompass(Player $sender) {
        $form = $this->getPlugin()->FormAPI->createSimpleForm(function (Player $sender, $data) {
            $arena = $this->getArenaByPlayer($sender->getName());
            if (isset($data[0])) {
                switch ($data) {
                    case "NoDebuff":
                    case "Combo":
                    case "Gapple":
                    case "BuildUHC":
                    case "Diamond":
                    case "Sumo":
                    case "Spleef":
                        if ($arena == null) {
                            $this->joinPlayerToRandomArena($sender, $data, 0);
                        } else {
                            $this->removePlayerToArena($arena, $sender->getName());
                            $this->joinPlayerToRandomArena($sender, $data, 0);
                        }
                        break;
                    case "back":
                        $this->createGuiCompass($sender);
                        break;
                    case "quit_queque":
                        if ($arena) {
                            $this->removePlayerToArena($arena, $sender->getName());
                        }
                        break;
                }
            }
        });
        $arena = $this->getArenaByPlayer($sender->getName());
        $form->setTitle("Select a type!");
        $form->addButton("NoDebuff \n Playing: " . $this->getRankedUnRankedPlayingStyle(0, "NoDebuff") . " Queued: " . $this->getRankedUnRankedLobbyStyle(0, "NoDebuff"), 0, "textures/items/potion_bottle_splash_heal", "NoDebuff");
        $form->addButton("Combo \n Playing: " . $this->getRankedUnRankedPlayingStyle(0, "Combo") . " Queued: " . $this->getRankedUnRankedLobbyStyle(0, "Combo"), 0, "textures/items/fish_pufferfish_raw", "Combo");
        $form->addButton("Gapple \n Playing: " . $this->getRankedUnRankedPlayingStyle(0, "Gapple") . " Queued: " . $this->getRankedUnRankedLobbyStyle(0, "Gapple"), 0, "textures/items/apple_golden", "Gapple");
        $form->addButton("BuildUHC \n Playing: " . $this->getRankedUnRankedPlayingStyle(0, "BuildUHC") . " Queued: " . $this->getRankedUnRankedLobbyStyle(0, "BuildUHC"), 0, "textures/items/diamond_sword", "BuildUHC");
        $form->addButton("Diamond \n Playing: " . $this->getRankedUnRankedPlayingStyle(0, "Diamond") . " Queued: " . $this->getRankedUnRankedLobbyStyle(0, "Diamond"), 0, "textures/items/diamond_chestplate", "Diamond");
        $form->addButton("Sumo \n Playing: " . $this->getRankedUnRankedPlayingStyle(0, "Sumo") . " Queued: " . $this->getRankedUnRankedLobbyStyle(0, "Sumo"), 0, "textures/items/beef_cooked", "Sumo");
        $form->addButton("Spleef \n Playing: " . $this->getRankedUnRankedPlayingStyle(0, "Spleef") . " Queued: " . $this->getRankedUnRankedLobbyStyle(0, "Spleef"), 0, "textures/items/golden_shovel", "Spleef");
        if ($arena) {
            $form->addButton("Quit Queque ", 1, "http://worldartsme.com/images/free-x-clipart-1.jpg", "quit_queque");
        }
        $form->addButton("Back", -1, "", "back");
        $form->sendToPlayer($sender);
    }

    public function rankedCompass(Player $sender) {
        $form = $this->getPlugin()->FormAPI->createSimpleForm(function (Player $sender, $data) {
            $arena = $this->getArenaByPlayer($sender->getName());
            if (isset($data[0])) {
                var_dump("porco dio");
                switch ($data) {
                    case "NoDebuff":
                    case "Combo":
                    case "Gapple":
                    case "BuildUHC":
                    case "Diamond":
                    case "Sumo":
                    case "Spleef":
                        if ($arena == null) {
                            $this->joinPlayerToRandomArena($sender, $data, 1);
                        } else {
                            $this->removePlayerToArena($arena, $sender->getName());
                            $this->joinPlayerToRandomArena($sender, $data, 1);
                        }
                        break;
                    case "back":
                        $this->createGuiCompass($sender);
                        break;
                    case "quit_queque":
                        if ($arena) {
                            $this->removePlayerToArena($arena, $sender->getName());
                        }
                        break;
                }
            }
        });
        $arena = $this->getArenaByPlayer($sender->getName());
        $form->setTitle("Select a type!");
        $form->addButton("NoDebuff \n Playing: " . $this->getRankedUnRankedPlayingStyle(0, "NoDebuff") . " Queued: " . $this->getRankedUnRankedLobbyStyle(0, "NoDebuff"), 0, "textures/items/potion_bottle_splash_heal", "NoDebuff");
        $form->addButton("Combo \n Playing: " . $this->getRankedUnRankedPlayingStyle(0, "Combo") . " Queued: " . $this->getRankedUnRankedLobbyStyle(0, "Combo"), 0, "textures/items/fish_pufferfish_raw", "Combo");
        $form->addButton("Gapple \n Playing: " . $this->getRankedUnRankedPlayingStyle(0, "Gapple") . " Queued: " . $this->getRankedUnRankedLobbyStyle(0, "Gapple"), 0, "textures/items/apple_golden", "Gapple");
        $form->addButton("BuildUHC \n Playing: " . $this->getRankedUnRankedPlayingStyle(0, "BuildUHC") . " Queued: " . $this->getRankedUnRankedLobbyStyle(0, "BuildUHC"), 0, "textures/items/diamond_sword", "BuildUHC");
        $form->addButton("Diamond \n Playing: " . $this->getRankedUnRankedPlayingStyle(0, "Diamond") . " Queued: " . $this->getRankedUnRankedLobbyStyle(0, "Diamond"), 0, "textures/items/diamond_chestplate", "Diamond");
        $form->addButton("Sumo \n Playing: " . $this->getRankedUnRankedPlayingStyle(0, "Sumo") . " Queued: " . $this->getRankedUnRankedLobbyStyle(0, "Sumo"), 0, "textures/items/beef_cooked", "Sumo");
        $form->addButton("Spleef \n Playing: " . $this->getRankedUnRankedPlayingStyle(0, "Spleef") . " Queued: " . $this->getRankedUnRankedLobbyStyle(0, "Spleef"), 0, "textures/items/golden_shovel", "Spleef");
        if ($arena) {
            $form->addButton("Quit Queque ", 1, "http://worldartsme.com/images/free-x-clipart-1.jpg", "quit_queque");
        }
        $form->addButton("Back", -1, "", "back");
        $form->sendToPlayer($sender);
    }

    public function sendLobbyItem(Player $player)
    {
        $player->getInventory()->clearAll();
        $player->getHungerManager()->setFood(20);
        $player->setHealth(20);
        $player->setGamemode(2);
        $player->getEffects()->clear();
        $player->getArmorInventory()->clearAll();
        $player->getInventory()->setItem(0, ItemFactory::getInstance()->get(276)->setCustomName("§aFreeForAll"));
        $player->getInventory()->setItem(1, ItemFactory::getInstance()->get(283)->setCustomName("§aDuels"));
        $player->getInventory()->setItem(4, ItemFactory::getInstance()->get(397,3)->setCustomName("§aStats"));
        $player->getInventory()->setItem(6, ItemFactory::getInstance()->get(399)->setCustomName("§aShop"));
        $player->getInventory()->setItem(7, ItemFactory::getInstance()->get(403)->setCustomName("§aEvents"));
        $player->getInventory()->setItem(8, ItemFactory::getInstance()->get(388)->setCustomName("§aBack To Spawn"));

    }

    public function openStatsForm(Player $player, $statsBoi) {
        $form = Main::getInstance()->getServer()->getPluginManager()->getPlugin("FormAPI")->createModalForm(function (Player $sender, bool $data) {
            if (!$data) {

            }
        });
        $cnf = Main::getInstance()->database->get(strtolower($statsBoi));
        $form->setTitle($statsBoi."'s Stats");
        $form->setContent("\nKills: ".$cnf["kills"]."\nDeaths: ".$cnf["deaths"]."\nCurrent Killstreak: ".$cnf["killstreak"]."\nBest Killstreak: ".$cnf["best-killstreak"]."\nRank: ".Main::getInstance()->getSession($player->getName())->getRank()->name);
        $form->setButton1("Ok");
        $player->sendForm($form);
    }

    public function openFFAForm(Player $player) : void {
        $api = Main::getInstance()->getServer()->getPluginManager()->getPlugin("FormAPI");
        if($api === null){
            return;
        }
        $form = $api->createSimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if($result === null){
                return;
            }
            switch($result){
                case 0:
                    Main::getKits()->giveNoDebuffKit($player);
                    $player->teleport(Main::getInstance()->getServer()->getWorldManager()->getWorldByName("NoDebuff")->getSpawnLocation());
                    $player->sendMessage("§aSuccessfully Warped To NoDebuff.");
                    return;
                case 1:
                    Main::getKits()->giveGapple($player);
                    $player->teleport(Main::getInstance()->getServer()->getWorldManager()->getWorldByName("Gapple")->getSpawnLocation());
                    $player->sendMessage("§aSuccessfully Warped To Gapple.");
                    return;
                case 2:
                    Main::getKits()->giveSumo($player);
                    $player->teleport(Main::getInstance()->getServer()->getWorldManager()->getWorldByName("Sumo")->getSpawnLocation());
                    $player->sendMessage("§aSuccessfully Warped To Sumo.");
                    return;
            }
        });
        $form->setTitle("§bFree For All");
        $form->setContent("§bChoose An Arena To Play In!");
        $form->addButton("§bNoDebuff\n§fPlaying: ".count(Main::getInstance()->getServer()->getWorldManager()->getWorldByName("NoDebuff")->getPlayers()), 0, "textures/items/potion_bottle_splash_heal");
        $form->addButton("§bGapple\n§fPlaying: ".count(Main::getInstance()->getServer()->getWorldManager()->getWorldByName("Gapple")->getPlayers()), 0, "textures/items/apple_golden");
        $form->addButton("§bSumo\n§fPlaying: ".count(Main::getInstance()->getServer()->getWorldManager()->getWorldByName("Sumo")->getPlayers()), 0, "textures/items/beef_cooked");
        $form->sendToPlayer($player);
    }

    public function leavequeue(Player $player)
    {
        $player->getArmorInventory()->clearAll();
        $player->getInventory()->clearAll();
        $player->getInventory()->setItem(4, ItemFactory::getInstance()->get(331)->setCustomName("§l§7> §aLeave Queue §7<"));
    }

    public function onInteractInfo(PlayerInteractEvent $event)
    {
        $sender = $event->getPlayer();
        $item = $sender->getInventory()->getItemInHand();
        $arena = $this->getArenaByPlayer($sender->getName());
        if($item->getId() == "331" && $item->getCustomName() === TF::RED . "§l§7> §aLeave Queue §7<"){
            $this->removePlayerToArena($arena, $sender->getName());
            $sender->sendMessage(C::RED . "You left the Queue");
            }
        }
}
