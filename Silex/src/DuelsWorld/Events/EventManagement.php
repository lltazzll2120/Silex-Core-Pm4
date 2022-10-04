<?php

namespace DuelsWorld\Events;

use DuelsWorld\Main;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class EventManagement implements Listener {

    const OPEN_DOOR = "openDoor2r392ydncgvb";

    private static array $eventsRunning = [];

    public function onDeath(PlayerDeathEvent $event): void{
        $player = $event->getPlayer();
        $cause = $player->getLastDamageCause();
        if($cause instanceof EntityDamageByEntityEvent){
            $damager = $cause->getDamager();
            if($damager instanceof Player){
                
            }
        }
    }

    public static function getEventsRunning(): array{
        return self::$eventsRunning;
    }

    public static function runEvent(string $name): void{
        self::$eventsRunning[$name] = [
            self::OPEN_DOOR => true
        ];
        Server::getInstance()->broadcastMessage(TextFormat::GREEN . "Event $name has started! 
        Do " . TextFormat::AQUA . "/event join " . TextFormat::GREEN . "to join! You have a minute to join!");
    }

    public static function closeDoor(string $name): void{
        if(isset(self::$eventsRunning[$name])) if(isset(self::$eventsRunning[$name][self::OPEN_DOOR])){
            self::$eventsRunning[$name][self::OPEN_DOOR] = false;
        }
    }

    public static function isDoorOpen(string $name): bool{
        if(isset(self::$eventsRunning[$name])) if(isset(self::$eventsRunning[$name][self::OPEN_DOOR])){
            if(self::$eventsRunning[$name][self::OPEN_DOOR] === true){
                return true;
            }
        }
        return false;
    }

    public static function endEvent(string $name): void{
        if(isset(self::$eventsRunning[$name])) unset(self::$eventsRunning[$name]);
        foreach(self::$eventsRunning[$name] as $player){
            if($player !== null && $player instanceof Player && $player->isOnline()){
                $player->sendMessage(TextFormat::RED . "Event ended.");
            }
            return;
        }
    }

    public static function joinEvent(string $name, Player $player): void{
        if(isset(self::$eventsRunning[$name])){
            self::$eventsRunning[$name][$player->getName()] = $player;
            $event = Main::getInstance()->getEvent($name);
            $spawn = $event->spawn;
            $player->sendMessage(TextFormat::GREEN . "Joined!");
            $player->teleport($spawn);
        }
    }

    public static function initPlayers(string $name): void{
        $event = Main::getInstance()->getEvent($name);
        foreach(self::$eventsRunning[$name] as $player){
            if($player !== null && $player instanceof Player && $player->isOnline()){
                $event->eventPlayers[$player->getName()] = $player;
            }
        }
    }
}