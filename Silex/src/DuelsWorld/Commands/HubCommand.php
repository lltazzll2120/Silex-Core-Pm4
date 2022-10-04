<?php

namespace DuelsWorld\Commands;


use DuelsWorld\duels\DuelManager;
use DuelsWorld\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;


class HubCommand extends Command implements PluginOwned
{

	/**
	 * HubCommand constructor.
	 * @param Main $plugin
	 */
	public function __construct(private Main $plugin)
	{
		parent::__construct("hub", "Hub Command", null, []);
		$this->setPermission("hub.command");
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
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool
	{
	    if($sender instanceof Player){
            $arena = DuelManager::isInDuelOrIsQueued($sender);
            if($arena){
                $sender->sendMessage(TextFormat::RED . "You can't use this command in a duel!");
                return false;
            }
	        $sender->setGamemode(GameMode::SURVIVAL());
            $sender->sendMessage("§aSuccessfully Teleported To Spawn!");
            Main::getKits()->sendLobbyItem($sender);
            $sender->teleport(Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
            return true;
        }
	}

}