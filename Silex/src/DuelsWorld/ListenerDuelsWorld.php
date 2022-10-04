<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ListenerDuelsWorld
 *
 * @author Salvatore
 */

namespace DuelsWorld;

use DuelsWorld\Commands\ShopCommand;
use DuelsWorld\duels\DuelManager;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\EnderPearl;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Sumo\form\SumoJoinForm;
use Sumo\match\stage\WaitingStage;
use Sumo\session\Session;
use Sumo\Sumo;

//

class ListenerDuelsWorld implements Listener {

    //put your code here
    private $plugin;

    /**
     * @var array
     */
    private array $antispam = [];

    /**
     * @var array
     */
    private array $combatTag = [];

    /**
     * @var array
     */
    private array $interactCooldown = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function getPlugin(): Main {
        return $this->plugin;
    }

    /**
     * @param PlayerLoginEvent $event
     */
    public function onPreJoin(PlayerLoginEvent $event) : void {
        $player = $event->getPlayer();
        Main::getInstance()->combatTag[strtolower($player->getName())]  = 1000;
        $this->antispam[strtolower($player->getName())] = 100;
        $this->interactCooldown[strtolower($player->getName())] = 100;
        if (!Main::getInstance()->hasAccount($player)) {
            Main::getInstance()->createAccount($player);
            return;
        }
        if (Main::getInstance()->isBanned($player)) {
            $player->kick("§cYou Are Banned!\n§cReason: ".Main::getInstance()->punishments->get(strtolower($player->getName()))["ban-reason"]);
            return;
        }
    }

    private array $chatCD = [];

    public function onChat(PlayerChatEvent $event): void{
        $player = $event->getPlayer();
        $message = $event->getMessage();
        if(isset(ShopCommand::$tags[$player->getName()])){
            $msg = TextFormat::colorize($message);
            Main::getInstance()->setTag($player->getName(),$msg);
            $player->sendMessage(TextFormat::GREEN . "Success!");
            if(isset(ShopCommand::$tags[$player->getName()])) unset(ShopCommand::$tags[$player->getName()]);
            $event->cancel();
        }

        // chat cd

        $session = Main::getInstance()->getSession($player->getName());
        if(strtolower($session->getRank()->name) === "guest"){
            if(!isset($this->chatCD[$player->getName()])){
                $this->chatCD[$player->getName()] = time() + 2;
                return;
            }
            $time = $this->chatCD[$player->getName()] - time();
            if($time <= 0){
                unset($this->chatCD[$player->getName()]);
            }else{
                $player->sendMessage(TextFormat::RED . "You have $time more seconds left to go!");
                $event->cancel();
            }
        }
    }

    /**
     * @param PlayerExhaustEvent $event
     */
    public function onHungry(PlayerExhaustEvent $event) : void {
        $event->cancel();
    }

    public static array $savednametags = [];

    /**
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event) : void {
        $player = $event->getPlayer();
        $this->sendLobbyItem($player);
        Main::getInstance()->updateFT($player);
        $player->teleport(Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
        $event->setJoinMessage("§2+ §a".$player->getName());
        $player->extinguish();
        self::$savednametags[$player->getName()] = $player->getNameTag();
    }

    public function onRespawn(PlayerRespawnEvent $event){
        $player = $event->getPlayer();
        $player->teleport(Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
        $this->sendLobbyItem($player);
    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event) : void {
        $player = $event->getPlayer();
        $bool = false;
        if (!$bool) {
            if (time() - Main::getInstance()->combatTag[strtolower($player->getName())] < 15) {
                if (!empty(Main::getInstance()->lastHit[$player->getName()])) {
                    if (Main::getInstance()->getServer()->getPlayerByPrefix(Main::getInstance()->lastHit[$player->getName()]) != null) {
                        $d = Main::getInstance()->getServer()->getPlayerByPrefix(Main::getInstance()->lastHit[$player->getName()]);
                        Main::getInstance()->getServer()->broadcastMessage("§c" . $player->getName() . "§4[" . Main::getInstance()->getKills($player) . "]§e has been slain by §c" . $d->getName() . "§4[" . Main::getInstance()->getKills($d) . "]§e whilst combat logging.");
                    }
                    $player->kill();
                }
            }
        }
        $arena = $this->getPlugin()->getApi()->getArenaByPlayer($player->getName());
        if ($arena) {
            $this->getPlugin()->getApi()->removePlayerToArena($arena, $player->getName());
           unset(Main::getInstance()->lastHit[$player->getName()]);
          $event->setQuitMessage("§4- §c".$player->getName());
        }
    }

    public function onDataPacket(DataPacketReceiveEvent $event) {
        $pk = $event->getPacket();
        if ($pk instanceof ModalFormResponsePacket) {
            $pl = $event->getOrigin()->getPlayer();
            $this->getPlugin()->openedForm[$pl->getName()] = false;
        }
    }


    public function onPlace(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        $arena = $this->getPlugin()->getApi()->getArenaByPlayer($player->getName());
        if ($arena) {
            $date = $this->getPlugin()->game[$arena]["type"];
            if ($date == "BuildUHC") {
                $i = 0;
                if (!empty($this->getPlugin()->game[$arena]["blocks"])) {
                    $i = count($this->getPlugin()->game[$arena]["blocks"]);
                }
                $this->getPlugin()->game[$arena]["blocks"][$i]["x"] = $event->getBlock()->getPosition()->getX();
                $this->getPlugin()->game[$arena]["blocks"][$i]["y"] = $event->getBlock()->getPosition()->getY();
                $this->getPlugin()->game[$arena]["blocks"][$i]["z"] = $event->getBlock()->getPosition()->getZ();
            } else {
                $event->cancel();
            }
        }
    }

    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $arena = $this->getPlugin()->getApi()->getArenaByPlayer($player->getName());
        if ($arena) {
            $date = $this->getPlugin()->game[$arena];
            if ($date["type"] == "BuildUHC" && $date["status"] == "game") {
                //     var_dump($this->getPlugin()->game[$arena]);
                $find = "no";
                foreach ($date["blocks"] as $i => $block) {

                    if ($block["x"] == $event->getBlock()->getPosition()->getX() && $block["y"] == $event->getBlock()->getPosition()->getY() && $block["z"] == $event->getBlock()->getPosition()->getZ()) {
                        $find = "yes";
                    }
                }
                if ($find == "no") {
                    $event->cancel();
                }
            } elseif ($date == "Spleef" && $date["status"] == "game") {
                $block = $event->getBlock();
                if ($block->getId() != 78 && $block->getId() != 80) {
                    $event->cancel();
                }
            } else {
                $event->cancel();
            }
        }
    }

    public function onDamage(EntityDamageEvent $event)
    {
        $player = $event->getEntity();
        if ($player instanceof Player) {
            $cause = $event->getCause();
            switch ($cause) {
                case EntityDamageEvent::CAUSE_FALL:
                case EntityDamageEvent::CAUSE_DROWNING:
                case EntityDamageEvent::CAUSE_SUFFOCATION:
                    $event->cancel();
                    break;
                case EntityDamageEvent::CAUSE_VOID:
                    $event->cancel();
                    $this->sendLobbyItem($player);
                    $player->teleport(Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
                    break;
            }

            if ($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();
                if ($player instanceof Player && $damager instanceof Player) {
                    var_dump(1);
                    if($player->getWorld()->getDisplayName() === Server::getInstance()->getWorldManager()->getDefaultWorld()->getDisplayName()){
                        var_dump(2);
                        $event->cancel();
                        return;
                    }else{
                        var_dump(3);
                    }
                    if ($damager->getInventory()->getItemInHand()->getCustomName() == "§r§bFreeze Player") {
                        if (in_array($player->getName(), Main::getInstance()->frozen)) {
                            unset(Main::getInstance()->frozen[$player->getName()]);
                            $player->sendMessage(TextFormat::GREEN . "You Are No Longer Frozen!");
                            $player->setImmobile(false);
                            $damager->sendMessage(TextFormat::GREEN . "Successfully Thawed The Player!");
                        } else {
                            $player->getInventory()->clearAll();
                            $player->getArmorInventory()->clearAll();
                            Main::getInstance()->frozen[$player->getName()] = true;
                            $player->setImmobile(true);
                            $damager->sendMessage(TextFormat::GREEN . "Successfully Froze The Player!");
                            $player->sendMessage("§7---------------\n§fOh No! You Have Been §bFrozen!\n\n§fBut Dont Worry!\nIf You Listen And Comply With The\nStaff Team, You Could Be Un-Frozen Quickly!\n§7---------------");
                        }
                    }
                    if (in_array($player->getName(), Main::getInstance()->frozen)) {
                        $event->cancel();
                        $damager->sendMessage(TextFormat::RED . "You Cannot Damage Frozen Players!");
                        return;
                    }
                    if ($damager instanceof Player) {
					if ($damager->getPosition()->distance($player->getPosition()) > 5) {
						Main::getInstance()->sendMessageToStaff("§7[§4ANTICHEAT§7] §eThe player §c" . round($player->getName(), 2) . "§e is currently reaching §c" . $damager->getPosition()->distance($player->getPosition()) . "§e blocks! §7(§a" . $damager->getNetworkSession()->getPing() . "ms§7).");
					}
				}
                    $arena = $this->getPlugin()->getApi()->getArenaByPlayer($player->getName());
                    if ($arena) {
                        $data = $this->getPlugin()->game[$arena];
                        if (($data["status"] == "lobby")) {
                            $event->cancel();
                        } else {
                            $damage = $event->getFinalDamage();
                            if ($damage >= $player->getHealth()) {
                                $event->cancel();
                                $this->sendLobbyItem($player);
                                $this->getPlugin()->getApi()->removePlayerToArena($arena, $player->getName());
                            }
                        }
                    }
                    Main::getInstance()->lastHit[$player->getName()] = $damager->getName();
                    Main::getInstance()->combatTag[strtolower($player->getName())] = time();
                    Main::getInstance()->combatTag[strtolower($damager->getName())] = time();
                }
            }
        }
    }

    public function onMove(PlayerMoveEvent $event) {
        $player = $event->getPlayer();
        $arena = $this->getPlugin()->getApi()->getArenaByPlayer($player->getName());
        if ($arena) {
            $data = $this->getPlugin()->game[$arena];
            if ($data["status"] == "lobby") {
                if (($data["player1"] != null && $data["player2"] != null) || $arena == $player->getWorld()->getDisplayName()) {
                    $event->cancel();
                }
            }
        }
    }

    /**
     * @param PlayerDropItemEvent $event
     */
    public function onDrop(PlayerDropItemEvent $event) : void {
        $player = $event->getPlayer();
        if ($player->getGamemode()->equals(GameMode::CREATIVE())) return;
        $arena = DuelManager::isInDuelOrIsQueued($player);
        if($arena){
            return;
        }
        $event->cancel();
    }

    /**
     * @param EntityItemPickupEvent  $event
     */
    public function onPickup(EntityItemPickupEvent $event) : void {
        $viewer = $event->getEntity();
        if($viewer instanceof Player){
            $arena = DuelManager::isInDuelOrIsQueued($viewer);
            if($arena){
                return;
            }
            $event->cancel();
        }
    }

    public function onTransaction(InventoryTransactionEvent $event): void{
        $player = $event->getTransaction()->getSource();

        $arena = DuelManager::isInDuelOrIsQueued($player);
        if($arena){
            return;
        }
        if($player->getWorld()->getFolderName() === "Hub" || $player->getGamemode()->equals(GameMode::SPECTATOR())){
            $event->cancel();
        }
    }

    // this shit removes potion when drank
    public function onItemConsume(PlayerItemConsumeEvent $event): void{
        $item = $event->getItem();
        if($item->getId() === ItemIds::POTION){
            $player = $event->getPlayer();
            self::pop($player);
        }
    }

    public static function pop(Player $player): void{
        $index = $player->getInventory()->getHeldItemIndex();
        $item = $player->getInventory()->getItemInHand();
        $player->getInventory()->setItem($index, $item->setCount($item->getCount() - 0.5));
    }

    public function onHunger(PlayerExhaustEvent $event){
        $event->cancel();
    }

    /**
     * @param PlayerDeathEvent $event
     */
    public function onDeath(PlayerDeathEvent $event) : void {
        $player = $event->getPlayer();
        Main::getInstance()->combatTag[strtolower($player->getName())] = 1000;
        $cause = $player->getLastDamageCause();
        $event->setDrops([]);
        Main::getInstance()->addDeath($player);
        if ($cause instanceof EntityDamageByEntityEvent) {
            $d = $cause->getDamager();
            if ($d instanceof Player) {
                $pots = 0;
                foreach($d->getInventory()->getContents() as $item){
                    if($item->getId() === ItemIds::SPLASH_POTION){
                        $pots++;
                    }
                }

                $deathTag = Main::getInstance()->getDeathTag($d->getName());

                if($player->getWorld()->getFolderName() === "NoDebuff"){
                    $event->setDeathMessage("§c" . $player->getName() . "§4[" . Main::getInstance()->getKills($player) . "]§e has been $deathTag by §c" . $d->getName() . "§4[" . Main::getInstance()->getKills($d) . "]§e." . TextFormat::GREEN . " [" . TextFormat::GRAY . $pots . TextFormat::GREEN . "]");
                }else{
                    $event->setDeathMessage("§c" . $player->getName() . "§4[" . Main::getInstance()->getKills($player) . "]§e has been $deathTag by §c" . $d->getName() . "§4[" . Main::getInstance()->getKills($d) . "]§e.");
                }
                $d->sendMessage("§aYou Are Now On A KillStreak Of " . Main::getInstance()->getKillstreak($d) . "!");

                // Rekits Killer
                Main::getInstance()->getServer()->dispatchCommand($d, "rekit");
                // Adds Kills To Killer
                Main::getInstance()->addKill($d);
                // Tokens added for duels and FFA
                Main::getInstance()->addTokensAccordingly($d);
                // Remove combat from killer
                Main::getInstance()->combatTag[strtolower($d->getName())] = 1000;
//                    $event->setDeathMessage("");
                Main::getInstance()->restartKillstreak($player);
                return;

            }


        }
    }

    /**
     * @param PlayerInteractEvent $event
     */
    public function onEnderPearl(PlayerItemUseEvent $event){
        $item = $event->getItem();
        if($item instanceof EnderPearl) {
            $cooldown = 12;
            $player = $event->getPlayer();
            if (isset(Main::getInstance()->pcooldown[$player->getName()]) and time() - Main::getInstance()->pcooldown[$player->getName()] < $cooldown) {
                $event->cancel();
                $time = time() - Main::getInstance()->pcooldown[$player->getName()];
                $message = "§cYou Are On Cooldown For {cooldown} More Seconds.";
                $message = str_replace("{cooldown}", ($cooldown - $time), $message);
                $player->sendMessage($message);
            } else {
                Main::getInstance()->pcooldown[$player->getName()] = time();
            }
        }
    }

    /**
     * @param PlayerCommandPreprocessEvent $event
     */
    public function onCommandPreProccessEvent(PlayerCommandPreprocessEvent $event) : void {
        $message = $event->getMessage();
        $player = $event->getPlayer();
        if (strstr($message, "/")) {
            if (time() - Main::getInstance()->combatTag[strtolower($player->getName())] < 15) {
                $event->cancel();
                $player->sendTip("§cYou Are Still Combat Tagged!");
                return;
            }
        }
    }


    public function ass24(EntityTeleportEvent $event): void {
        $p = $event->getEntity();
        $from = $event->getFrom();
        $to = $event->getTo();

        if($from->getWorld()->getFolderName() !== $to->getWorld()->getFolderName() && $to->getWorld()->getFolderName() === "Hub"){
            if ($p instanceof Player) {
                $this->sendLobbyItem($p);
            }
        }

    }

    public function onInteract(PlayerInteractEvent $event) : void {
        $player = $event->getPlayer();
        $arena = DuelManager::isInDuelOrIsQueued($player);
        $item = $event->getItem();
        if($item->getId() === ItemIds::DYE && $item->getName() === TextFormat::BOLD . TextFormat::RED . "Back To Hub"){
            $player->setGamemode(GameMode::SURVIVAL());
            $player->sendMessage("§aSuccessfully Teleported To Spawn!");
            Main::getKits()->sendLobbyItem($player);
            $player->teleport(Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
            return;
        }
        if ($player->getWorld()->getFolderName() == "Hub") {
            $itemname = $event->getItem()->getCustomName();
            switch ($itemname) {
                case "§aDuels §8[TAP]":
                    DuelManager::openDuelsForm($player);
//                    $this->getPlugin()->getApi()->createGuiCompass($player);
                    break;
                case "§aFreeForAll §8[TAP]":
                    if($arena){
                        $player->sendMessage(TextFormat::RED . "You can't access this item because you are queued.");
                        return;
                    }
                    $this->getPlugin()->getApi()->openFFAForm($player);
                    break;
                case "§r§bRandom Teleport":
                    if($arena){
                        $player->sendMessage(TextFormat::RED . "You can't access this item because you are queued.");
                        return;
                    }
                    $array = [];
                    foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $p) {
                        $array[] = $p;
                    }
                    $count = count($array);
                    $random = $array[mt_rand(0, $count - 1)];
                    $player->teleport($random);
                    break;
                case "§aBackToHub §8[TAP]":
                    if($arena){
                        $player->sendMessage(TextFormat::RED . "You can't access this item because you are queued.");
                        return;
                    }
                    $player->sendMessage(TextFormat::GREEN . "Welcome to Hub!");
                    $player->teleport(Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
                    break;
                case "§aFight a Bot §8[TAP]":
                    if($arena){
                        $player->sendMessage(TextFormat::RED . "You can't access this item because you are queued.");
                        return;
                    }
                    Main::getInstance()->getServer()->dispatchCommand($player, "botpvp");
                    break;
                case "§aShop §8[TAP]":
                    Main::getInstance()->getServer()->dispatchCommand($player, "shop");
                    break;
                case "§aYour Stats §8[TAP]":
                    Main::getInstance()->getApi()->openStatsForm($player,$player->getName());
                    break;
                case "§aEvents §8[TAP]":
                    // TODO: you should add sumo plugin ... \Laith98Dev/
                    // $session = Sumo::getInstance()->getSessionManager()->getSession($player);
                    // $this->executeJoin($session);
                    break;
            }

        }
    }

    // TODO: you should add sumo plugin ... \Laith98Dev/
    /* public function executeJoin(Session $session): void {
        foreach(Sumo::getInstance()->getMatchManager()->getMatches() as $match) {
            if($match->getStage() instanceof WaitingStage) {
                $session->getPlayer()->sendForm(SumoJoinForm::getForm());
                return;
            }
        }
        $session->sendTranslatedMessage("{AQUA}There are no sumo events being hosted right now!");
    } */

        // the now queque
    public function sendLobbyItem(Player $player)
    {
        $player->getInventory()->clearAll();
        $player->getHungerManager()->setFood(20);
        $player->setHealth(20);
        $player->setGamemode(GameMode::ADVENTURE());
        $player->getEffects()->clear();
        $player->getArmorInventory()->clearAll();
        $player->getInventory()->setItem(0, ItemFactory::getInstance()->get(276)->setCustomName("§aFreeForAll §8[TAP]"));
        $player->getInventory()->setItem(1, ItemFactory::getInstance()->get(283)->setCustomName("§aDuels §8[TAP]"));
//        $player->getInventory()->setItem(2, ItemFactory::getInstance()->get(267)->setCustomName("§aBotDuels §8[TAP]"));
        $player->getInventory()->setItem(4, ItemFactory::getInstance()->get(397,3)->setCustomName("§aYour Stats §8[TAP]"));
        $player->getInventory()->setItem(6, ItemFactory::getInstance()->get(399)->setCustomName("§aShop §8[TAP]"));
        $player->getInventory()->setItem(7, ItemFactory::getInstance()->get(403)->setCustomName("§aEvents §8[TAP]"));
        $player->getInventory()->setItem(8, ItemFactory::getInstance()->get(388)->setCustomName("§aBackToHub §8[TAP]"));

    }

    public function sendBotItems(Player $player)
    {
        $player->getInventory()->clearAll();
        $player->getHungerManager()->setFood(20);
        $player->setHealth(20);
        $player->setGamemode(GameMode::ADVENTURE());
        $player->getEffects()->clear();
        $player->getArmorInventory()->clearAll();
        $player->getInventory()->setItem(0, ItemFactory::getInstance()->get(276)->setCustomName("§aFight a Bot §8[TAP]"));
        $player->getInventory()->setItem(4, ItemFactory::getInstance()->get(397,3)->setCustomName("§aYour Stats §8[TAP]"));
        $player->getInventory()->setItem(6, ItemFactory::getInstance()->get(399)->setCustomName("§aShop §8[TAP]"));
        $player->getInventory()->setItem(7, ItemFactory::getInstance()->get(403)->setCustomName("§aEvents §8[TAP]"));
        $player->getInventory()->setItem(8, ItemFactory::getInstance()->get(388)->setCustomName("§aBackToHub §8[TAP]"));

    }

}
