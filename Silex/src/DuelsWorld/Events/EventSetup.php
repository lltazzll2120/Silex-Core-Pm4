<?php

namespace DuelsWorld\Events;

use DuelsWorld\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class EventSetup implements Listener{

    /** @var int[] */
    public static array $setupList = [];

    private string $name;
    private string $pos1;
    private string $pos2;
    private string $spawn;

    const PROCESS_NAME = 0;
    const PROCESS_POS1 = 1;
    const PROCESS_POS2 = 2;
    const PROCESS_SPAWN = 3;

    public function onChat(PlayerChatEvent $event): void{
        $player = $event->getPlayer();
        if(self::isInProcess($player) && self::getPlayerProcess($player) !== null){
            if(self::getPlayerProcess($player) === self::PROCESS_NAME){
                $msg = $event->getMessage();
                $player->sendMessage(TextFormat::GREEN . "$msg is the name of event. Tap a block for pos1");
                self::addProcess($player);
                $this->name = $msg;
                $event->cancel();
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event): void{
        $player = $event->getPlayer();
        $block = $event->getBlock();

        if(self::isInProcess($player) && self::getPlayerProcess($player) !== null){
            if(self::getPlayerProcess($player) === self::PROCESS_POS1){
                $msg = TextFormat::GREEN . "You have selected your first pos1" . TextFormat::EOL;
                $msg .= "X: " . intval($block->x) . TextFormat::EOL;
                $msg .= "Y: " . intval($block->y) . TextFormat::EOL;
                $msg .= "Z: " . intval($block->z) . TextFormat::EOL;
                $msg .= "LEVEL: " . strval($block->getPosition()->getWorld()->getDisplayName());
                $this->pos1 = intval($block->z) . ":" . intval($block->y) . ":" . intval($block->z) . ":" . strval($block->getPosition()->getWorld()->getDisplayName());
                $player->sendMessage($msg);
                self::addProcess($player);
                $player->sendMessage(TextFormat::GREEN . "tap another block to set pos2");
            }
            if(self::getPlayerProcess($player) === self::PROCESS_POS2){
                $msg = TextFormat::GREEN . "You have selected your second pos2" . TextFormat::EOL;
                $msg .= "X: " . intval($block->x) . TextFormat::EOL;
                $msg .= "Y: " . intval($block->y) . TextFormat::EOL;
                $msg .= "Z: " . intval($block->z) . TextFormat::EOL;
                $msg .= "LEVEL: " . strval($block->getPosition()->getWorld()->getDisplayName());
                $this->pos2 = intval($block->z) . ":" . intval($block->y) . ":" . intval($block->z) . ":" . strval($block->getPosition()->getWorld()->getDisplayName());
                $player->sendMessage($msg);
                self::addProcess($player);
                $player->sendMessage(TextFormat::GREEN . "tap another block to set spawn. also the next block you tap, 
                your inventory right now will be the kit used for this event.");
            }
            if(self::getPlayerProcess($player) === self::PROCESS_SPAWN){
                $msg = TextFormat::GREEN . "You have selected your spawn" . TextFormat::EOL;
                $msg .= "X: " . intval($block->x) . TextFormat::EOL;
                $msg .= "Y: " . intval($block->y) . TextFormat::EOL;
                $msg .= "Z: " . intval($block->z) . TextFormat::EOL;
                $msg .= "LEVEL: " . strval($block->getPosition()->getWorld()->getDisplayName());
                $player->sendMessage($msg);
                $spawn =
                    intval($block->z) . ":" . intval($block->y) . ":" . intval($block->z) . ":" . strval($block->getPosition()->getWorld()->getDisplayName());
                Main::getInstance()->addEvent($this->name, $this->pos1, $this->pos2, $spawn);
                Main::getInstance()->setKitContentsToFile($this->name, $player->getInventory()->getContents());
                self::endProcess($player);
            }
        }
    }

    public function onQuit(PlayerQuitEvent $event): void{
        $player = $event->getPlayer();
        if(self::isInProcess($player)) self::endProcess($player);
        if(!empty(EventManagement::getEventsRunning())){
            foreach(EventManagement::getEventsRunning() as $name => $data){
                if(isset(EventManagement::getEventsRunning()[$name][$player->getName()])){
                    unset(EventManagement::getEventsRunning()[$name][$player->getName()]);
                    $event = Main::getInstance()->getEvent($name);
                    unset($event->eventPlayers[$player->getName()]);
                }
            }
        }
    }

    public static function getPlayerProcess(Player $player): ?int{
        if(!isset(self::$setupList[$player->getName()])){
            return null;
        }
        return self::$setupList[$player->getName()];
    }

    public static function startProcess(Player $player): void{
        self::$setupList[$player->getName()] = self::PROCESS_POS1;
    }

    public static function addProcess(Player $player): void{
        if(self::getPlayerProcess($player) !== null){
            if(self::getPlayerProcess($player) + 1 > self::PROCESS_SPAWN){
                self::endProcess($player);
            }
            self::$setupList[$player->getName()]++;
        }
    }

    public static function endProcess(Player $player): void{
        if(isset(self::$setupList[$player->getName()])) unset(self::$setupList[$player->getName()]);
    }

    public static function isInProcess(Player $player): bool{
        return isset(self::$setupList[$player->getName()]);
    }

}