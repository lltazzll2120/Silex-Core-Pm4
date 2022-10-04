<?php

namespace DuelsWorld\Commands;



use DuelsWorld\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;


class BanCommand extends Command implements PluginOwned
{

	/**
	 * BanCommand constructor.
	 * @param Main $plugin
	 */
	public function __construct(private Main $plugin)
	{
		parent::__construct("ban", "Ban Command", null, []);
		$this->setPermission("ban.command");
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
        if (!$this->testPermission($sender)) {
			if (empty($args[0])) {
				$sender->sendMessage(TextFormat::RED."Usage: /ban (player) (reason).");
				return true;
			}
			$player = Main::getInstance()->getServer()->getPlayerByPrefix($args[0]);
			if ($player == null) {
				$sender->sendMessage(TextFormat::RED."This Player Is Not Online Or Doesn't Exist.");
				return true;
			}
			unset($args[0]);
			unset($args[1]);
			$reason = implode(" ", $args);
			if (empty($reason)) {
				$reason = "Not Set.";
			}
			$sender->sendMessage(TextFormat::RED.TextFormat::ITALIC."Successfully Banned.");
			Main::getInstance()->setBanned($player, $reason, $sender->getName());
		} else {
			$sender->sendMessage(TextFormat::RED."You Do Not Have Permission To Use This Command!");
		}
		return true;
	}

}