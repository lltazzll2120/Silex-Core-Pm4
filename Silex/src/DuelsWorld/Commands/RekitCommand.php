<?php

namespace DuelsWorld\Commands;


use DuelsWorld\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;


class RekitCommand extends Command implements PluginOwned
{

	/**
	 * StatsCommand constructor.
	 * @param Main $plugin
	 */
	public function __construct(private Main $plugin)
	{
		parent::__construct("rekit", "Rekit Command", null, []);
		$this->setPermission("rekit.command");
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
	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
		if(!$sender instanceof Player){
			return false;
		}

		switch($sender->getWorld()->getFolderName()) {
			case "NoDebuff":
				Main::getKits()->giveNoDebuffKit($sender);
				$sender->sendMessage(TextFormat::GREEN."Successfully Re-Kitted!");
				break;
            case "Gapple":
                Main::getKits()->giveGapple($sender);
                $sender->sendMessage(TextFormat::GREEN."Successfully Re-Kitted!");
                break;
            case "Sumo":
                Main::getKits()->giveSumo($sender);
                $sender->sendMessage(TextFormat::GREEN."Successfully Re-Kitted!");
                break;
			default:
				$sender->sendMessage(TextFormat::RED."You Arent In An Arena");
		}
		return true;
	}

}