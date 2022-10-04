<?php

namespace DuelsWorld\Events;

use DuelsWorld\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;

class EventCommand extends Command implements PluginOwned {

    public function __construct(){
        parent::__construct("event", "Event Command.", null, []);
        $this->setPermission("event.command");
    }

    public function getOwningPlugin(): Main{
        return Main::getInstance();
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender->hasPermission($this->getPermission())){
            $sender->sendMessage(TextFormat::RED . "You do not have permission to use this command.");
            return;
        }
        if(!isset($args[0])){
            $sender->sendMessage("Usage: /$commandLabel (start|join|setup|list|end|remove)");
            return;
        }
        switch(strtolower($args[0])){
            case "setup":
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "Be a player");
                    return;
                }
                if(EventSetup::isInProcess($sender)) {
                    $sender->sendMessage(TextFormat::GREEN . "You are already in process.");
                    return;
                }
                if(!empty(EventSetup::$setupList)){
                    $sender->sendMessage(TextFormat::RED . "Someone is already in process.");
                    return;
                }
                $sender->sendMessage(TextFormat::GREEN . "say smth in chat - this will be event name");
                EventSetup::startProcess($sender);
                break;
            case "list":
                $events = Main::getInstance()->getEvents();
                if(empty($events)){
                    $sender->sendMessage(TextFormat::RED . "You have no events.");
                    return;
                }
                $names = [];
                foreach($events as $name => $data){
                    $names[] = $name;
                }
                $sender->sendMessage(TextFormat::GREEN . "Events: " . implode(", ", $names));
                break;
            case "remove":
                $events = Main::getInstance()->getEvents();
                if(empty($events)){
                    $sender->sendMessage(TextFormat::RED . "You have no events.");
                    return;
                }
                if(!isset($events[$args[0]])){
                    $sender->sendMessage(TextFormat::RED . "$args[0] does not exist as an event.");
                    return;
                }
                Main::getInstance()->removeEvent($args[0]);
                break;
            case "start":
                if(!empty(EventManagement::getEventsRunning())){
                    $sender->sendMessage(TextFormat::RED . "There is an event that is already running");
                    return;
                }
                if(!isset($args[1])){
                    $sender->sendMessage("Usage: /$commandLabel start (type)");
                    return;
                }
                $events = Main::getInstance()->getEvents();
                if(empty($events)){
                    $sender->sendMessage(TextFormat::RED . "You have no events.");
                    return;
                }
                if(!isset($events[$args[1]])){
                    $sender->sendMessage(TextFormat::RED . "$args[1] does not exist as an event.");
                    return;
                }
                EventManagement::runEvent($args[1]);
                break;
            case "end":
                if(!isset($args[1])){
                    $sender->sendMessage("Usage: /$commandLabel end (type)");
                    return;
                }
                $events = Main::getInstance()->getEvents();
                if(empty($events)){
                    $sender->sendMessage(TextFormat::RED . "You have no events.");
                    return;
                }
                if(!isset($events[$args[1]])){
                    $sender->sendMessage(TextFormat::RED . "$args[1] does not exist as an event.");
                    return;
                }
                if(empty(EventManagement::getEventsRunning())){
                    $sender->sendMessage(TextFormat::RED . "No events are running");
                    return;
                }
                if(!isset(EventManagement::getEventsRunning()[$args[1]])){
                    $sender->sendMessage(TextFormat::RED . "This event isn't running");
                    return;
                }
                EventManagement::endEvent($args[1]);
                break;
            case "join":
                $events = Main::getInstance()->getEvents();
                if(empty($events)){
                    $sender->sendMessage(TextFormat::RED . "You have no events.");
                    return;
                }
                if(!isset($events[$args[1]])){
                    $sender->sendMessage(TextFormat::RED . "$args[1] does not exist as an event.");
                    return;
                }
                if(empty(EventManagement::getEventsRunning())){
                    $sender->sendMessage(TextFormat::RED . "No events are running");
                    return;
                }
                if(!isset(EventManagement::getEventsRunning()[$args[1]])){
                    $sender->sendMessage(TextFormat::RED . "This event isn't running");
                    return;
                }
                if(!EventManagement::isDoorOpen($args[1])){
                    $sender->sendMessage(TextFormat::RED . "You cannot join this event. It already started.");
                    return;
                }
                $sender->sendMessage(TextFormat::GREEN . "Joined!");
                EventManagement::joinEvent($args[1], $sender);
                // EventManagement::joinEvent($sender->getName(), $args[1]);
                break;
        }
    }
}