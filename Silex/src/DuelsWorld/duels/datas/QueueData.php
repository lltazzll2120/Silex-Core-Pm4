<?php

namespace DuelsWorld\duels\datas;

use DuelsWorld\duels\DuelManager;
use pocketmine\player\Player;

class QueueData {

    private $player;
    private $mode;
    private $duelRank;

    public function __construct(string $mode, Player $player, string $duelRank = DuelManager::UNRANKED){
        $this->mode = $mode;
        $this->player = $player;
        $this->duelRank = $duelRank;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @return string
     */
    public function getDuelRank(): string
    {
        return $this->duelRank;
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }
}