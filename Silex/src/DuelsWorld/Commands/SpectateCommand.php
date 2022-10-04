<?php

namespace DuelsWorld\Commands;

use DuelsWorld\Main;
use DuelsWorld\Managment;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class SpectateCommand extends Command implements PluginOwned {

    public function __construct(){
        parent::__construct("spectate", "", null, []);
        $this->setPermission("spectate.command");
    }

    public function getOwningPlugin(): Main
    {
        return Main::getInstance();
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof Player) return;
//        if(!isset($args[0])){
//            $sender->sendMessage("Usage: /$commandLabel (player)");
//            return;
//        }
        if(empty(Main::getInstance()->getApi()->getDuels())){
            $sender->sendMessage(TextFormat::RED . "No active duels, sorry.");
            return;
        }
        $form = new SimpleForm(function(Player $player, $data): void{
            if($data !== null){
                $duelPlayer = Server::getInstance()->getPlayerByPrefix($data);
                if($duelPlayer === null || !$duelPlayer->isOnline()){
                    $player->sendMessage(TextFormat::RED . "The duel has ended.");
                    return;
                }
                if(in_array($duelPlayer->getWorld()->getDisplayName(), Main::$duelArenas)) {
                    $player->sendMessage(TextFormat::RED . "The duel has ended.");
                    return;
                }
                $player->teleport($duelPlayer->getPosition());
                $player->setGamemode(3);
                $player->sendMessage(TextFormat::GREEN . "Spectating duel!");
                $item = ItemFactory::getInstance()->get(ItemIds::DYE, 13);
                $item->setCustomName(TextFormat::BOLD . TextFormat::RED . "Back To Hub");
                $player->getInventory()->setItem(8,$item);
            }
        });
        foreach(Main::getInstance()->getApi()->getDuels() as $arena => $data){
            $level = Server::getInstance()->getWorldManager()->getWorldByName($data["levelName"] ?? Server::getInstance()->getWorldManager()->getDefaultWorld()->getFolderName());
            $player1 = $data["player1"] ?? null;
            $player2 = $data["player2"] ?? null;
            if($player1 !== null && $player2 !== null && $level !== null && Server::getInstance()->getWorldManager()->isWorldLoaded($data["levelName"])){
                $form->addButton($player1->getName() . " vs " . $player2->getName(),-1,"",$player1->getName());
            }
        }
        $sender->sendForm($form);

//        if(!$p = Server::getInstance()->getPlayer($args[0])){
//            $sender->sendMessage(TextFormat::RED . "Can't find player!");
//            return;
//        }
//        if(!$this->isInDuel($p)){
//            $sender->sendMessage(TextFormat::RED . "{$p->getName()} is not in duel.");
//            return;
//        }
//        $sender->teleport($p);
//        $sender->setGamemode(3);
//        $sender->sendMessage(TextFormat::GREEN . "Spectating {$p->getName()}'s duel.");
//        $item = ItemFactory::get(ItemIds::DYE, 13);
//        $item->setCustomName(TextFormat::BOLD . TextFormat::RED . "Back To Hub");
//        $sender->getInventory()->setItem(8,$item);
    }

    public function isInDuel(Player $player): bool{
        $pl = Main::getInstance();
        return in_array($player->getWorld()->getFolderName(), $pl::$duelArenas);
    }
}
