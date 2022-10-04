<?php

namespace DuelsWorld\Commands;


use DuelsWorld\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;


class StaffModeCommand extends Command implements PluginOwned
{

	/**
	 * StaffModeCommand constructor.
	 * @param Main $plugin
	 */
	public function __construct(private Main $plugin)
	{
		parent::__construct("staffmode", "StaffMode Command", null, []);
        $this->setPermission("staffmode.command");
	}

    public function getOwningPlugin(): Main
    {
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
                $sender->sendMessage(TextFormat::RED . "Usage: /staffmode (on/off)");
            } else {
                if ($args[0] == "on") {
                    Main::getInstance()->staffMode[$sender->getName()] = true;
                    Main::setStaffMode($sender, "on");
                } else if ($args[0] == "off") {
                    unset(Main::getInstance()->staffMode[$sender->getName()]);
                    Main::setStaffMode($sender, "off");
                } else {
                    $sender->sendMessage(TextFormat::RED . "Usage: /staffmode (on/off)");
                }
            }
        } else {
            $sender->sendMessage(TextFormat::RED."You Do Not Have Permission To Use This Command.");
        }
        return true;
    }

}