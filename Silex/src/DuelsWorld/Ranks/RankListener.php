<?php

namespace DuelsWorld\Ranks;

use DuelsWorld\ev\RankChangedEvent;
use DuelsWorld\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class RankListener implements Listener{

    public function onChat(PlayerChatEvent $event): void{
        $player = $event->getPlayer();
        $session = Main::getInstance()->getSession($player->getName());
        $rank = $session->getRank();
        $search = ["{player}", "{msg}", "&", "{tag}"];
        $replace = [$player->getName(), $event->getMessage(), TextFormat::ESCAPE,Main::getInstance()->getTag($player->getName())];
        $event->setFormat(Main::translate($rank->chatFormat, $search, $replace));
    }


    public function onJoin(PlayerJoinEvent $event): void{
        $player = $event->getPlayer();
        $session = Main::getInstance()->getSession($player->getName());
        $rank = $session->getRank();
        $nametag = $rank->nameTag;
        $player->setNameTag(Main::translate($nametag,["&","{player}","{rank}","{tag}"],[TextFormat::ESCAPE,$player->getDisplayName(),$rank->name,Main::getInstance()->getTag($player->getName())]));
        $this->initPermission($player, $rank);
    }

    public function onQuit(PlayerQuitEvent $event): void{
        $player = $event->getPlayer();
        $session = Main::getInstance()->getSession($player->getName());
        $session->save();
    }

    public function initPermission(Player $player, Rank $rank): void{
        $permission = Main::getInstance()->getPermission($player);
        $permissions = $rank->permissions;

        if(isset(Main::$savedPerms[$player->getName()])){
            foreach(Main::$savedPerms[$player->getName()] as $perm){
                $permission->unsetPermission($perm);
            }
            unset(Main::$savedPerms[$player->getName()]);
        }
        if(!empty($permissions)){
            foreach($permissions as $foreachedPerm){
                $permission->setPermission($foreachedPerm,true);
            }
            Main::$savedPerms[$player->getName()] = $permissions;
        }
    }

    public function onRankChange(RankChangedEvent $event): void{
        $playername = $event->getPlayer();
        $player = Server::getInstance()->getPlayerByPrefix($playername);
        if($player !== null){
            if($player->isOnline()){

                // ----- prevent recursive -----
                if(!Main::getInstance()->hasSession($playername)){
                    return;
                }
                // ----- prevent recursive -----

                $session = Main::getInstance()->getSession($player->getName());
                $rank = $session->getRank();

                $nametag = $rank->nameTag;
                $player->setNameTag(Main::translate($nametag,["&","{player}","{rank}","{tag}"],[TextFormat::ESCAPE,$player->getName(),$rank->name,Main::getInstance()->getTag($player->getName())]));

                $this->initPermission($player,$rank);
            }
        }
    }

}