<?php

namespace DuelsWorld\Commands;

use DuelsWorld\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;

class TokenCommand extends Command implements PluginOwned{

    public function __construct(){
        parent::__construct("givetokens", "Give Tokens", null, []);
        $this->setPermission("givetokens.command");
    }

    public function getOwningPlugin(): Main
    {
        return Main::getInstance();
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$this->testPermission($sender)){
            $sender->sendMessage(TextFormat::RED . "You do not have permission to use this command.");
            return;
        }
        if(!isset($args[1])){
            $sender->sendMessage("Usage: /$commandLabel (player) (amount)");
            return;
        }
        if(!is_numeric($args[1])){
            $sender->sendMessage(TextFormat::RED . "Numeric value required.");
            return;
        }
        $amount = intval($args[1]);
        $sender->sendMessage(TextFormat::GREEN . "Added tokens!");
        Main::getInstance()->addTokens(Main::getInstance()->getPlayer($args[0])->getName(), $amount);
    }
}