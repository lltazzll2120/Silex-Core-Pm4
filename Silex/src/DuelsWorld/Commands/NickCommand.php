<?php

namespace DuelsWorld\Commands;

use DuelsWorld\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;

class NickCommand extends Command implements PluginOwned{

    public function __construct(){
        parent::__construct("nick", "Nick Command", null, []);
        $this->setPermission("nick.command");
    }

    public function getOwningPlugin(): Main {
		return Main::getInstance();
	}

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender->hasPermission($this->getPermission()) || !$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "You do not have permission to use this command");
            return;
        }
        if(!isset($args[0])){
            $sender->sendMessage("Usage: /$commandLabel (nick)");
            return;
        }
        $sender->sendMessage(TextFormat::GREEN . "Your nickname has been changed to $args[0]");
        $sender->setDisplayName($args[0]);
    }
}