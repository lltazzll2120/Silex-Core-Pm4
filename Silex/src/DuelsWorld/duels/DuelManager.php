<?php

namespace DuelsWorld\duels;

use DuelsWorld\duels\datas\MapInformation;
use DuelsWorld\duels\datas\QueueData;
use DuelsWorld\duels\datas\StartingDuelData;
use DuelsWorld\Main;
use jojoe77777\FormAPI\Form;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\Potion;
use pocketmine\item\Snowball;
use pocketmine\item\Sword;
use pocketmine\item\TieredTool;
use pocketmine\world\Position;
use pocketmine\math\Vector3;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\block\tile\Tile;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\PotionType;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;

class DuelManager implements Listener {

    public static $isQueued = [];
    public static $isStartingDuel = [];
    public static $isDueling = [];
    public static $isEnding = [];

    //.NoDebuff, Combo, Gapple, BuildUHC, Diamond, Sumo
    const NODEBUFF = "NoDebuff";
    const COMBO = "Combo";
    const GAPPLE = "Gapple";
    const BUILD_UHC = "BuildUHC";
    const DIAMOND = "Diamond";
    const SUMO = "Sumo";

    const UNRANKED = "Unranked";
    const RANKED = "Ranked";

//    public static $modeLibrary = [
//        self::NODEBUFF,
//        self::COMBO,
//        self::GAPPLE,
//        self::BUILD_UHC,
//        self::DIAMOND,
//        self::SUMO
//    ];

    public static $id = "";

    /** @var MapInformation[] */
    private static $maps;

    public function __construct(){
        Main::getInstance()->getServer()->getPluginManager()->registerEvents($this, Main::getInstance());
        // Tile::registerTile(BuildUHCTile::class, ["BuildUHCTile"]);

        self::$maps = [
            "duel1" => new MapInformation(new Position(28,101,139,Server::getInstance()->getWorldManager()->getWorldByName("duel1")),
                new Position(97,101,139,Server::getInstance()->getWorldManager()->getWorldByName("duel1"))),

            "duel2" => new MapInformation(new Position(131,102,-3,Server::getInstance()->getWorldManager()->getWorldByName("duel2")),
                new Position(131,102,95,Server::getInstance()->getWorldManager()->getWorldByName("duel2"))),

            "duel3" => new MapInformation(new Position(279,93,262,Server::getInstance()->getWorldManager()->getWorldByName("duel3")),
                new Position(279,93,348,Server::getInstance()->getWorldManager()->getWorldByName("duel3"))),

//            "duel4" => new MapInformation(new Position(131,102,-3,Server::getInstance()->getWorldManager()->getWorldByName("duel4")),
//                new Position(105,100,105,Server::getInstance()->getWorldManager()->getWorldByName("duel4"))),

            "duel5" => new MapInformation(new Position(280,114,257,Server::getInstance()->getWorldManager()->getWorldByName("duel5")),
                new Position(280,114,352,Server::getInstance()->getWorldManager()->getWorldByName("duel5"))),

            "duel6" => new MapInformation(new Position(280,74,259,Server::getInstance()->getWorldManager()->getWorldByName("duel6")),
                new Position(280,74,333,Server::getInstance()->getWorldManager()->getWorldByName("duel6"))),

            "duel7" => new MapInformation(new Position(278,73,264,Server::getInstance()->getWorldManager()->getWorldByName("duel7")),
                new Position(278,73,348,Server::getInstance()->getWorldManager()->getWorldByName("duel7"))),

            "duel8" => new MapInformation(new Position(282,71,259,Server::getInstance()->getWorldManager()->getWorldByName("duel8")),
                new Position(282,71,343,Server::getInstance()->getWorldManager()->getWorldByName("duel8"))),

            "duel9" => new MapInformation(new Position(281,71,344,Server::getInstance()->getWorldManager()->getWorldByName("duel9")),
                new Position(281,71,258,Server::getInstance()->getWorldManager()->getWorldByName("duel9"))),

            "duel10" => new MapInformation(new Position(287,72,343,Server::getInstance()->getWorldManager()->getWorldByName("duel10")),
                new Position(278,72,257,Server::getInstance()->getWorldManager()->getWorldByName("duel10"))),
        ];

        // this is the task that starts activating the queue for players
        Main::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void{
            self::manageQueues();
        }), 20);

//        Main::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void{
//
//        }),20);
    }

    public static function manageQueues(): void{
        $ranked = [
            self::NODEBUFF => 0,
            self::GAPPLE => 0,
            self::SUMO => 0,
            self::COMBO => 0,
            self::DIAMOND => 0,
            self::BUILD_UHC => 0
        ];
        $unranked = [
            self::NODEBUFF => 0,
            self::GAPPLE => 0,
            self::SUMO => 0,
            self::COMBO => 0,
            self::DIAMOND => 0,
            self::BUILD_UHC => 0
        ];
        foreach(self::$isQueued as $name => $value){
            $player = Server::getInstance()->getPlayerByPrefix($name);
            if($player !== null && $player->isOnline()){
                $data = self::getQueueData($player);
                if($data->getDuelRank() === self::UNRANKED){
                    $unranked[$data->getMode()]++;
                }
                if($data->getDuelRank() === self::RANKED){
                    $ranked[$data->getMode()]++;
                }
            }
        }
        foreach($ranked as $mode => $value){
            if(self::isEven($value) && $value > 0){
                $players = [];
                foreach(self::$isQueued as $playername => $datavalue){
                    $players[] = Server::getInstance()->getPlayerByPrefix($playername);
                }
                $player1 = $players[0];
                $player2 = $players[1];
                $data1 = self::getQueueData($player1);
                $data2 = self::getQueueData($player2);
//                 adds duel data
                self::$isStartingDuel[$player1->getName()] = [
                    "Player" => $player1,
                    "Opponent" => $player2,
                    "Mode" => $data1->getMode(),
                    "DuelRank" => $data1->getDuelRank()
                ];
                self::$isStartingDuel[$player2->getName()] = [
                    "Player" => $player2,
                    "Opponent" => $player1,
                    "Mode" => $data2->getMode(),
                    "DuelRank" => $data2->getDuelRank()
                ];
                self::unqueue($player1);
                self::unqueue($player2);
                self::startDuel($player1, $player2);
            }
        }
        foreach($unranked as $mode => $value){
            if(self::isEven($value) && $value > 0){
                $players = [];
                foreach(self::$isQueued as $playername => $datavalue){
                    $players[] = Server::getInstance()->getPlayerByPrefix($playername);
                }
                $player1 = $players[0];
                $player2 = $players[1];
                $data1 = self::getQueueData($player1);
                $data2 = self::getQueueData($player2);
//                 adds duel data
                self::$isStartingDuel[$player1->getName()] = [
                    "Player" => $player1,
                    "Opponent" => $player2,
                    "Mode" => $data1->getMode(),
                    "DuelRank" => $data1->getDuelRank()
                ];
                self::$isStartingDuel[$player2->getName()] = [
                    "Player" => $player2,
                    "Opponent" => $player1,
                    "Mode" => $data2->getMode(),
                    "DuelRank" => $data2->getDuelRank()
                ];
                self::unqueue($player1);
                self::unqueue($player2);
                self::startDuel($player1, $player2);
            }
        }

    }

    public static function getStartingDuelData(Player $player): ?StartingDuelData{
        if(!isset(self::$isStartingDuel[$player->getName()])){
            return null;
        }
        return new StartingDuelData(self::$isStartingDuel[$player->getName()]["Player"],self::$isStartingDuel[$player->getName()]["Opponent"], self::$isStartingDuel[$player->getName()]["Mode"], self::$isStartingDuel[$player->getName()]["DuelRank"]);
    }

    public static function turnArrayIntoAllKeys(array $array): array{
        $values = [];
        foreach($array as $key => $value){
            $values[] = $key;
        }
        return $values;
    }

    public static function startDuel(Player $player1, Player $player2): void{
        $players = [$player1, $player2];
        $mapNames = self::turnArrayIntoAllKeys(self::$maps);
        $mapName = $mapNames[array_rand($mapNames)];
        self::duplicateMap($mapName);
        self::teleportToLevel(self::$id, $player1);
        self::teleportToLevel(self::$id, $player2);
        self::teleportToMapPos($player1, $player2, self::$maps[$mapName]);
        Main::getInstance()->getScheduler()->scheduleRepeatingTask(new DuelStartTask([$player1]), 20);
        Main::getInstance()->getScheduler()->scheduleRepeatingTask(new DuelStartTask([$player2]), 20);
    }

    public function onMove(PlayerMoveEvent $event): void{
        $player = $event->getPlayer();
        if(self::getStartingDuelData($player) !== null){
            $event->cancel();
        }
    }

    public function onEntityDamage(EntityDamageEvent $event): void{
        if($event instanceof EntityDamageByEntityEvent){
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if($entity instanceof Player && $damager instanceof Player && !$event->isCancelled()){
                if(self::getDuelingData($damager) !== null){
                    $mode = self::getDuelingData($damager);
                    if($mode === self::COMBO){
                        $event->setKnockBack(0.36);
                        $event->setAttackCooldown(4);
                    }else{
                        $event->setAttackCooldown(10);
                        $event->setKnockBack(0.4);
                    }
                }
            }
        }
    }

    public function onBreak(BlockBreakEvent $event): void{
        $player = $event->getPlayer();
        if(($data = self::getDuelingData($player)) !== null){
            if($data->getMode() === self::BUILD_UHC){
                $block = $event->getBlock();
                // $tile = $player->getWorld()->getTile($block->getPosition());
                // if(!$tile instanceof BuildUHCTile){
                //     $event->cancel();
                // }
            }else{
                $event->cancel();
            }
        }
    }

    public function onPlace(BlockPlaceEvent $event): void{
        $player = $event->getPlayer();
        if(($data = self::getDuelingData($player)) !== null){
            if($data->getMode() === self::BUILD_UHC) {
                $block = $event->getBlock();
                // $tile = new BuildUHCTile($player->getWorld(), BuildUHCTile::createNBT($block));
                // $tile->spawnToAll();
            }else{
                $event->cancel();
            }
        }else{
            $event->cancel();
        }
    }

    /**
     * I'm using the same duel class because they basically will be the same thing so why not?
     */
    public static function getDuelingData(Player $player): ?StartingDuelData{
        // (it is hacky tho)
        return self::$isDueling[$player->getName()] ?? null;
    }

    public static function won(string $player): void{
        $player = Server::getInstance()->getPlayerByPrefix($player);
        $player->setGamemode(GameMode::SURVIVAL());
        $player->sendMessage("§aSuccessfully Teleported To Spawn!");
        Main::getKits()->sendLobbyItem($player);
        $player->teleport(Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
//        $player->getServer()->dispatchCommand($player, "spawn");
//        $player->sendMessage(TextFormat::GREEN . "You won!");
        $player->setGamemode(GameMode::ADVENTURE());
        Main::getInstance()->addKill($player);
        $data = self::getDuelingData($player);
        if($data->getDuelRank() === self::RANKED) Main::getInstance()->addelo($player);
        if(isset(self::$isDueling[$player->getName()])){
            unset(self::$isDueling[$player->getName()]);
        }
        if(isset(self::$isQueued[$player->getName()])){
            unset(self::$isQueued[$player->getName()]);
        }
        if(isset(self::$isStartingDuel[$player->getName()])){
            unset(self::$isStartingDuel[$player->getName()]);
        }
    }

    public static function lost(string $player): void{
        $player = Server::getInstance()->getPlayerByPrefix($player);
//        $player->sendMessage(TextFormat::RED . "You lost!");
        $player->setGamemode(GameMode::ADVENTURE());
        Main::getInstance()->addDeath($player);
        $data = self::getDuelingData($player);
        if($data->getDuelRank() === self::RANKED) Main::getInstance()->removeelo($player);
        if(isset(self::$isDueling[$player->getName()])){
            unset(self::$isDueling[$player->getName()]);
        }
        if(isset(self::$isQueued[$player->getName()])){
            unset(self::$isQueued[$player->getName()]);
        }
        if(isset(self::$isStartingDuel[$player->getName()])){
            unset(self::$isStartingDuel[$player->getName()]);
        }
    }

    public static function isInDuelOrIsQueued(Player $player): bool{
        return (isset(self::$isQueued[$player->getName()]) ||
                isset(self::$isDueling[$player->getName()]) ||
                isset(self::$isEnding[$player->getName()]) || isset(self::$isStartingDuel[$player->getName()])) &&
            (strpos($player->getWorld()->getFolderName(), "duel") !== false);
    }

    public static function queue(Player $player, string $mode, string $duelRank = self::UNRANKED): void{
        if(isset(self::$isQueued[$player->getName()])){
            $player->sendMessage(TextFormat::RED . "You are already queued.");
            return;
        }
        self::$isQueued[$player->getName()] = [
            "Mode" => $mode,
            "Player" => $player,
            "DuelRank" => $duelRank
        ];
    }

    public static function isEven(int $int): bool{
        return $int % 2 == 0;
    }

    public static function duplicateMap(string $level): void{
        self::$id = uniqid("duel-");
        Server::getInstance()->dispatchCommand(new ConsoleCommandSender(Main::getInstance()->getServer(), Main::getInstance()->getServer()->getLanguage()), "mw duplicate $level " . self::$id);
    }

    public static function teleportToLevel(string $level, Player $player): void{
        Server::getInstance()->dispatchCommand(new ConsoleCommandSender(Main::getInstance()->getServer(), Main::getInstance()->getServer()->getLanguage()), "mw teleport $level " . $player->getName());
    }

    public static function getQueueData(Player $player): ?QueueData{
        if(!isset(self::$isQueued[$player->getName()])) return null;
        return new QueueData(self::$isQueued[$player->getName()]["Mode"], self::$isQueued[$player->getName()]["Player"], self::$isQueued[$player->getName()]["DuelRank"]);
    }

    public static function unqueue(Player $player): void{
        if(isset(self::$isQueued[$player->getName()])) unset(self::$isQueued[$player->getName()]);
    }

    public static function setKit(StartingDuelData $getStartingDuelData): void{
        $mode = $getStartingDuelData->getMode();
        $player = $getStartingDuelData->getPlayer();
        switch($mode){
            case self::NODEBUFF:
                $sword = VanillaItems::DIAMOND_SWORD()->setCustomName(TextFormat::AQUA . "NoDebuff");
                $sword->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 1));
                $sword->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FIRE_ASPECT(), 1));
                $sword->setUnbreakable(true);
                $kit = [
                    0 => $sword,
                    1 => ItemFactory::getInstance()->get(ItemIds::ENDER_PEARL)->setCount(16),
                    2 => ItemFactory::getInstance()->get(ItemIds::POTION, PotionType::LONG_FIRE_RESISTANCE()),
                    3 => ItemFactory::getInstance()->get(ItemIds::POTION, PotionType::LONG_SWIFTNESS()),
                    4 => ItemFactory::getInstance()->get(ItemIds::POTION, PotionType::LONG_SWIFTNESS())
                ];
                for($i = 5; $i <= 38; $i++){
                    $kit[$i] = ItemFactory::getInstance()->get(ItemIds::SPLASH_POTION, PotionType::STRONG_HEALING());
                }
                $helmet = ItemFactory::getInstance()->get(ItemIds::DIAMOND_HELMET);
                $chestplate = ItemFactory::getInstance()->get(ItemIds::DIAMOND_CHESTPLATE);
                $leggings = ItemFactory::getInstance()->get(ItemIds::DIAMOND_LEGGINGS);
                $boots =  ItemFactory::getInstance()->getInstance()->get(ItemIds::DIAMOND_BOOTS);
                $armor = [$helmet,$chestplate,$leggings,$boots];
                /** @var Item $armorPiece */
                foreach($armor as $armorPiece){
                    $armorPiece->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION()));
                }
                $armorKit = [
                    0 => $helmet,
                    1 => $chestplate,
                    2 => $leggings,
                    3 => $boots
                ];
                $player->getInventory()->setContents($kit);
                $player->getArmorInventory()->setContents($armorKit);
                break;
            case self::GAPPLE:
                $sword = VanillaItems::DIAMOND_SWORD()->setCustomName(TextFormat::AQUA . "Gapple");
                $sword->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 1));
                $sword->setUnbreakable(true);
                $kit = [
                    0 => $sword,
                    1 =>  ItemFactory::getInstance()->get(ItemIds::GOLDEN_APPLE)->setCount(16)
                ];
                $helmet =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_HELMET);
                $chestplate =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_CHESTPLATE);
                $leggings =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_LEGGINGS);
                $boots =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_BOOTS);
                $armor = [$helmet,$chestplate,$leggings,$boots];
                /** @var Item $armorPiece */
                foreach($armor as $armorPiece){
                    $armorPiece->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION()));
                }
                $armorKit = [
                    0 => $helmet,
                    1 => $chestplate,
                    2 => $leggings,
                    3 => $boots
                ];
                $player->getInventory()->setContents($kit);
                $player->getArmorInventory()->setContents($armorKit);
                break;
            case self::DIAMOND:
                $sword = VanillaItems::DIAMOND_SWORD()->setCustomName(TextFormat::AQUA . "Diamond");
                $sword->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 1));
                $sword->setUnbreakable(true);
                $kit = [
                    0 => $sword,
                ];
                $helmet =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_HELMET);
                $chestplate =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_CHESTPLATE);
                $leggings =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_LEGGINGS);
                $boots =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_BOOTS);
                $armor = [$helmet,$chestplate,$leggings,$boots];
                /** @var Item $armorPiece */
                foreach($armor as $armorPiece){
                    $armorPiece->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION()));
                }
                $armorKit = [
                    0 => $helmet,
                    1 => $chestplate,
                    2 => $leggings,
                    3 => $boots
                ];
                $player->getInventory()->setContents($kit);
                $player->getArmorInventory()->setContents($armorKit);
                break;
            case self::COMBO:
                $sword = VanillaItems::DIAMOND_SWORD()->setCustomName(TextFormat::AQUA . "Combo");
                $sword->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(),1));
                $sword->setUnbreakable(true);
                $helmet =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_HELMET);
                $chestplate =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_CHESTPLATE);
                $leggings =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_LEGGINGS);
                $boots =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_BOOTS);
                $armor = [$helmet,$chestplate,$leggings,$boots];
                /** @var Item $armorPiece */
                foreach($armor as $armorPiece){
                    $armorPiece->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));
                    $armorPiece->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION()));
                }

                $kit = [
                    0 => $sword,
                    1 =>  ItemFactory::getInstance()->get(ItemIds::ENCHANTED_GOLDEN_APPLE)->setCount(64),
                    2 => $helmet,
                    3 => $chestplate,
                    4 => $leggings,
                    5 => $boots
                ];

                // replicate just in case it don't work
                $helmet =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_HELMET);
                $chestplate =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_CHESTPLATE);
                $leggings =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_LEGGINGS);
                $boots =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_BOOTS);
                $armor = [$helmet,$chestplate,$leggings,$boots];
                /** @var Item $armorPiece */
                foreach($armor as $armorPiece){
                    $armorPiece->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));
                    $armorPiece->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION()));
                }
                $armorKit = $armor;
                $player->getInventory()->setContents($kit);
                $player->getArmorInventory()->setContents($armorKit);
                break;
            case self::SUMO:
                $player->getInventory()->addItem( ItemFactory::getInstance()->get(ItemIds::STEAK)->setCount(64));
                break;
            case self::BUILD_UHC:
                $sword = VanillaItems::DIAMOND_SWORD()->setCustomName(TextFormat::AQUA . "NoDebuff");
                $sword->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 1));
                $sword->setUnbreakable(true);
                $kit = [
                    0 => $sword,
                    1 =>  ItemFactory::getInstance()->get(ItemIds::FISHING_ROD),
                    2 =>  ItemFactory::getInstance()->get(ItemIds::BOW),
                    3 =>  ItemFactory::getInstance()->get(ItemIds::STEAK)->setCount(64),
                    4 =>  ItemFactory::getInstance()->get(ItemIds::GOLDEN_APPLE)->setCount(18),
                    5 =>  ItemFactory::getInstance()->get(ItemIds::DIAMOND_PICKAXE),
                    6 =>  ItemFactory::getInstance()->get(ItemIds::DIAMOND_AXE),
                    7 =>  ItemFactory::getInstance()->get(ItemIds::WOODEN_PLANKS)->setCount(64),
                    9 =>  ItemFactory::getInstance()->get(ItemIds::ARROW)->setCount(32),
                    10 =>  ItemFactory::getInstance()->get(ItemIds::COBBLESTONE)->setCount(64),
                    11 =>  ItemFactory::getInstance()->get(ItemIds::WATER),
                    12 =>  ItemFactory::getInstance()->get(ItemIds::LAVA),
                    13 =>  ItemFactory::getInstance()->get(ItemIds::LAVA),
                    14 =>  ItemFactory::getInstance()->get(ItemIds::CROSSBOW)
                ];
                $helmet =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_HELMET);
                $chestplate =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_CHESTPLATE);
                $leggings =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_LEGGINGS);
                $boots =  ItemFactory::getInstance()->get(ItemIds::DIAMOND_BOOTS);
                $armor = [$helmet,$chestplate,$leggings,$boots];
                /** @var Item $armorPiece */
                foreach($armor as $armorPiece){
                    $armorPiece->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION()));
                }
                $armorKit = [
                    0 => $helmet,
                    1 => $chestplate,
                    2 => $leggings,
                    3 => $boots
                ];
                $player->getInventory()->setContents($kit);
                $player->getArmorInventory()->setContents($armorKit);
                break;
        }


    }

    public function onQuit(PlayerQuitEvent $event): void{
        $player = $event->getPlayer();

        if(isset(self::$isDueling[$player->getName()])){
            unset(self::$isDueling[$player->getName()]);
            $player->kill(); // call death event
        }
        if(isset(self::$isQueued[$player->getName()])){
            unset(self::$isQueued[$player->getName()]);
        }
        if(isset(self::$isStartingDuel[$player->getName()])){
            $player->kill(); // call death event
            unset(self::$isStartingDuel[$player->getName()]);
        }
    }

    public function onDeath(PlayerDeathEvent $event): void{
        $player = $event->getPlayer();
        $cause = $player->getLastDamageCause();
        $opponent = null;
        if($cause instanceof EntityDamageByChildEntityEvent){
            if($cause->getChild() instanceof Snowball || $cause->getChild() instanceof Arrow){
                $owner = $cause->getChild()->getOwningEntity();
                if($owner instanceof Player){
                    $opponent = $owner;
                }
            }
        }
        if($cause instanceof EntityDamageByBlockEvent){
            if (($data = self::getDuelingData($player)) !== null) {
                $opponent = $data->getOpponent();
            } else if (($data = self::getStartingDuelData($player)) !== null) {
                $opponent = $data->getOpponent();
            }
        }
        if($cause instanceof EntityDamageByEntityEvent) {
            if (($data = self::getDuelingData($player)) !== null) {
                $opponent = $data->getOpponent();
            } else if (($data = self::getStartingDuelData($player)) !== null) {
                $opponent = $data->getOpponent();
            }
        }
        if($opponent !== null) self::won($opponent->getName());
        self::lost($player->getName());
    }

    public static function howManyArePlayingRanked(): int{
        $i = 0;
        foreach(self::$isStartingDuel as $name => $value){
            $player = Server::getInstance()->getPlayerByPrefix($name);
            if($player !== null && $player->isOnline()){
                $data = self::getStartingDuelData($player);
                if($data->getDuelRank() === self::RANKED){
                    $i++;
                }
            }
        }
        /**
         * @var StartingDuelData $value
         */
        foreach(self::$isDueling as $name => $value){
            if($value->getDuelRank() === self::RANKED){
                $i++;
            }
        }
        return $i;
    }

    public static function howManyArePlayingUnranked(): int{
        $i = 0;
        foreach(self::$isStartingDuel as $name => $value){
            $player = Server::getInstance()->getPlayerByPrefix($name);
            if($player !== null && $player->isOnline()){
                $data = self::getStartingDuelData($player);
                if($data->getDuelRank() === self::UNRANKED){
                    $i++;
                }
            }
        }
        /**
         * @var StartingDuelData $value
         */
        foreach(self::$isDueling as $name => $value){
            if($value->getDuelRank() === self::UNRANKED){
                $i++;
            }
        }
        return $i;
    }

    public static function howManyAreQueuedRanked(): int{
        $i = 0;
        foreach(self::$isQueued as $name => $value){
            $player = Server::getInstance()->getPlayerByPrefix($name);
            if($player !== null && $player->isOnline()) {
                $data = self::getQueueData($player);
                if($data !== null){
                    if($data->getDuelRank() === self::RANKED){
                        $i++;
                    }
                }
            }
        }
        return $i;
    }

    public static function howManyAreQueuedUnranked(): int{
        $i = 0;
        foreach(self::$isQueued as $name => $value){
            $player = Server::getInstance()->getPlayerByPrefix($name);
            if($player !== null && $player->isOnline()) {
                $data = self::getQueueData($player);
                if($data !== null){
                    if($data->getDuelRank() === self::UNRANKED){
                        $i++;
                    }
                }
            }
        }
        return $i;
    }

    public static function getUnrankedQueuedType(string $type): int{
        $i = 0;
        foreach(self::$isQueued as $name => $value){
            $player = Server::getInstance()->getPlayerByPrefix($name);
            if($player !== null && $player->isOnline()) {
                $data = self::getQueueData($player);
                if($data !== null){
                    if($data->getDuelRank() === self::UNRANKED && $data->getMode() === $type){
                        $i++;
                    }
                }
            }
        }
        return $i;
    }

    public static function getUnrankedPlayingType(string $type): int{
        $i = 0;
        foreach(self::$isStartingDuel as $name => $value){
            $player = Server::getInstance()->getPlayerByPrefix($name);
            if($player !== null && $player->isOnline()){
                $data = self::getStartingDuelData($player);
                if($data->getDuelRank() === self::UNRANKED && $data->getMode() === $type){
                    $i++;
                }
            }
        }
        /**
         * @var StartingDuelData $value
         */
        foreach(self::$isDueling as $name => $value){
            if($value->getDuelRank() === self::UNRANKED && $value->getMode() === $type){
                $i++;
            }
        }
        return $i;
    }

    public static function getRankedQueuedType(string $type): int{
        $i = 0;
        foreach(self::$isQueued as $name => $value){
            $player = Server::getInstance()->getPlayerByPrefix($name);
            if($player !== null && $player->isOnline()) {
                $data = self::getQueueData($player);
                if($data !== null){
                    if($data->getDuelRank() === self::RANKED && $data->getMode() === $type){
                        $i++;
                    }
                }
            }
        }
        return $i;
    }

    public static function getRankedPlayingType(string $type): int{
        $i = 0;
        foreach(self::$isStartingDuel as $name => $value){
            $player = Server::getInstance()->getPlayerByPrefix($name);
            if($player !== null && $player->isOnline()){
                $data = self::getStartingDuelData($player);
                if($data->getDuelRank() === self::RANKED && $data->getMode() === $type){
                    $i++;
                }
            }
        }
        /**
         * @var StartingDuelData $value
         */
        foreach(self::$isDueling as $name => $value){
            if($value->getDuelRank() === self::RANKED && $value->getMode() === $type){
                $i++;
            }
        }
        return $i;
    }

    public static function openDuelsForm(Player $player): void{
        $form = new SimpleForm(function (Player $player, $data): void{
            if($data !== null){
                switch($data){
                    case "unranked": // unranked
                        $form = new SimpleForm(function(Player $player, $data): void{
                            if($data !== null){
                                switch($data){
                                    case 0: // nodebuff
                                        self::queue($player, self::NODEBUFF);
                                        break;
                                    case 1: // combo
                                        self::queue($player, self::COMBO);
                                        break;
                                    case 2: // gapple
                                        self::queue($player, self::GAPPLE);
                                        break;
                                    case 3: // diamond
                                        self::queue($player, self::DIAMOND);
                                        break;
//                                    case 4: // sumo
//                                        self::queue($player, self::SUMO);
//                                        break;
                                    case 4: // builduhc
                                        self::queue($player, self::BUILD_UHC);
                                        break;
                                    case 5: // back
                                        self::openDuelsForm($player);
                                        break;

                                }
                            }
                        });
                        $form->addButton("NoDebuff" . TextFormat::EOL . "Playing: " . self::getUnrankedPlayingType(self::NODEBUFF) . " Queued: " . self::getUnrankedQueuedType(self::NODEBUFF));
                        $form->addButton("Combo" . TextFormat::EOL . "Playing: " . self::getUnrankedPlayingType(self::COMBO) . " Queued: " . self::getUnrankedQueuedType(self::COMBO));
                        $form->addButton("Gapple" . TextFormat::EOL . "Playing: " . self::getUnrankedPlayingType(self::GAPPLE) . " Queued: " . self::getUnrankedQueuedType(self::GAPPLE));
                        $form->addButton("Diamond" . TextFormat::EOL . "Playing: " . self::getUnrankedPlayingType(self::DIAMOND) . " Queued: " . self::getUnrankedQueuedType(self::DIAMOND));
                        $form->addButton("BuildUHC" . TextFormat::EOL . "Playing: " . self::getUnrankedPlayingType(self::BUILD_UHC) . " Queued: " . self::getUnrankedQueuedType(self::BUILD_UHC));
                        //                        $form->addButton("Sumo" . TextFormat::EOL . "Playing: " . self::getUnrankedPlayingType(self::SUMO) . " Queued: " . self::getUnrankedQueuedType(self::SUMO));
                        $form->addButton("Back");
                        $form->setTitle("Select a Type!");
                        self::sendForm($player, $form);
                        break;
                    case "ranked": // ranked
                        $form = new SimpleForm(function(Player $player, $data): void{
                            if($data !== null){
                                switch($data){
                                    case 0: // nodebuff
                                        self::queue($player, self::NODEBUFF, self::RANKED);
                                        break;
                                    case 1: // combo
                                        self::queue($player, self::COMBO, self::RANKED);
                                        break;
                                    case 2: // gapple
                                        self::queue($player, self::GAPPLE, self::RANKED);
                                        break;
                                    case 3: // diamond
                                        self::queue($player, self::DIAMOND, self::RANKED);
                                        break;
//                                    case 4: // sumo
//                                        self::queue($player, self::SUMO, self::RANKED);
//                                        break;
                                    case 4: // builduhc
                                        self::queue($player, self::BUILD_UHC, self::RANKED);
                                        break;
                                    case 5: // back
                                        self::openDuelsForm($player);
                                        break;
                                }
                            }
                        });
                        $form->addButton("NoDebuff" . TextFormat::EOL . "Playing: " . self::getRankedPlayingType(self::NODEBUFF) . " Queued: " . self::getRankedQueuedType(self::NODEBUFF));
                        $form->addButton("Combo" . TextFormat::EOL . "Playing: " . self::getRankedPlayingType(self::COMBO) . " Queued: " . self::getRankedQueuedType(self::COMBO));
                        $form->addButton("Gapple" . TextFormat::EOL . "Playing: " . self::getRankedPlayingType(self::GAPPLE) . " Queued: " . self::getRankedQueuedType(self::GAPPLE));
                        $form->addButton("Diamond" . TextFormat::EOL . "Playing: " . self::getRankedPlayingType(self::DIAMOND) . " Queued: " . self::getRankedQueuedType(self::DIAMOND));
                        $form->addButton("BuildUHC" . TextFormat::EOL . "Playing: " . self::getRankedPlayingType(self::BUILD_UHC) . " Queued: " . self::getRankedQueuedType(self::BUILD_UHC));

//                        $form->addButton("Sumo" . TextFormat::EOL . "Playing: " . self::getRankedPlayingType(self::SUMO) . " Queued: " . self::getRankedQueuedType(self::SUMO));
                        $form->addButton("Back");
                        $form->setTitle("Select a Type!");
                        self::sendForm($player, $form); 
                        break;
                    case "unqueue":
                        $player->sendMessage(TextFormat::RED . "You have left queue.");
                        self::unqueue($player);
                        break;
                }
            }
        });
        $form->setTitle("Choose a Duel Type");
        $form->addButton("Unranked" . TextFormat::EOL . "Playing: " . self::howManyArePlayingUnranked() . " Queued: " . self::howManyAreQueuedUnranked(), -1, "", "unranked");
        $form->addButton("Ranked" . TextFormat::EOL . "Playing: " . self::howManyArePlayingRanked() . " Queued: " . self::howManyAreQueuedRanked(), -1, "", "ranked");
        if(self::getQueueData($player) !== null){
            $form->addButton("Leave Queue", -1, "", "unqueue");
        }
        self::sendForm($player, $form);
    }
    public static function sendForm(Player $player, $form): void{
        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $form): void{
            $player->sendForm($form);
        }), 6);
    }

    public static function teleportToMapPos(Player $player1, Player $player2, MapInformation $mapInformation): void{
        Server::getInstance()->dispatchCommand(new ConsoleCommandSender(Main::getInstance()->getServer(), Main::getInstance()->getServer()->getLanguage()), "tp {$player1->getName()} " . $mapInformation->getPos1(true));
        Server::getInstance()->dispatchCommand(new ConsoleCommandSender(Main::getInstance()->getServer(), Main::getInstance()->getServer()->getLanguage()), "tp {$player2->getName()} " . $mapInformation->getPos2(true));
    }
}