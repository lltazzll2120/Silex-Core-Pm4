<?php

namespace DuelsWorld\Ranks;

use DuelsWorld\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;

class SetRankCommand extends Command implements PluginOwned {

    public function __construct(){
        parent::__construct("setrank", TextFormat::AQUA . TextFormat::ITALIC . "/setrank", null, []);
        $this->setPermission("sinksranksystem.setrank.command");
    }

    public function getOwningPlugin(): Main{
        return Main::getInstance();
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender->hasPermission($this->getPermission())){
            $sender->sendMessage(TextFormat::RED . "You do not have permission to use this command");
            return;
        }
        if(!isset($args[0])){
            $sender->sendMessage("Usage: /setrank (player) (rank)");
            return;
        }
        if(!$p = $this->getPlayer($args[0])){
            $sender->sendMessage(TextFormat::RED . "Invalid player.");
            return;
        }
        $ranks = [];
        foreach(Main::getInstance()->ranks as $rank){
            $ranks[] = $rank->name;
        }
        if(!isset($args[1]) || !in_array($args[1], $ranks)){
            $msg = TextFormat::AQUA . TextFormat::BOLD . "AVAILABLE RANKS: " . TextFormat::RESET . TextFormat::WHITE;
            $msg .= implode(", ", $ranks);
            $sender->sendMessage($msg);
            return;
        }
        $session = Main::getInstance()->getSession($p->getName());
        $session->setRank(Main::getInstance()->getRank($args[1]));
        $sender->sendMessage(TextFormat::GREEN . "You have set " . $p->getName() . "'s rank to " . TextFormat::AQUA . $args[1] . TextFormat::GREEN . "!");
    }

    public function getPlayer(string $name) {
        return Main::getInstance()->getPlayer($name);
    }
}