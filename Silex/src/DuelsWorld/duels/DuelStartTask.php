<?php

namespace DuelsWorld\duels;

use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class DuelStartTask extends Task{

    // because of time lags
    private $countdown = 6;

    /**
     * @var Player[]
     */
    protected $players = [];

    /**
     * @param Player[] $players
     */
    public function __construct(array $players){
        $this->players = $players;
    }

    public function onRun(): void{
        foreach($this->players as $player){
            if($player !== null && $player->isOnline()){
                if($this->countdown === 5 || $this->countdown === 6){
                    $player->setGamemode(GameMode::SURVIVAL());
                    DuelManager::setKit(DuelManager::getStartingDuelData($player));
                }
                $color = TextFormat::AQUA;
                $player->sendTitle($color . $this->countdown);
                $this->countdown--;
                if($this->countdown === 0){
                    $player->sendMessage(TextFormat::GREEN . "Duel has started!");
                    DuelManager::$isDueling[$player->getName()] = DuelManager::getStartingDuelData($player);
                    if(isset(DuelManager::$isStartingDuel[$player->getName()])) unset(DuelManager::$isStartingDuel[$player->getName()]);
                    if($this !== null){
                        if(!$this->getHandler()->isCancelled()) $this->getHandler()->cancel();
                    }
                }
            }
        }
    }

}