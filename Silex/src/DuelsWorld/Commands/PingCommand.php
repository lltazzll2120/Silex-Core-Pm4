<?php

namespace DuelsWorld\Commands;


use DuelsWorld\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;


class PingCommand extends Command implements PluginOwned
{

    public function __construct(){
        parent::__construct("ping", "Ping Command", null, []);
        $this->setPermission("ping.command");
    }

    public function getOwningPlugin(): Main {
		return Main::getInstance();
	}


    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return bool|mixed|void
     */

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Use this command in-game");
            return;
        }
        if(!isset($args[0])){
            $sender->getServer()->dispatchCommand($sender, "ping {$sender->getName()}");
            return;
        }
        if(!$p = $sender->getServer()->getPlayerByPrefix($args[0])){
            $sender->sendMessage(TextFormat::RED . "Player not found");
            return;
        }
        $ping = $p->getNetworkSession()->getPing();
        $p->sendMessage(TextFormat::GREEN . $p->getName() . "'s Ping: " . TextFormat::AQUA . $ping . "ms");
    }

}