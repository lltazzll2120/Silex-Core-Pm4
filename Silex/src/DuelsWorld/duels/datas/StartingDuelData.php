<?php

namespace DuelsWorld\duels\datas;

use DuelsWorld\duels\DuelManager;
use pocketmine\player\Player;

class StartingDuelData {

    private $player;
    private $opponent;
    private $mode;
    private $duelRank;
    private $opponentName;

    /**
     * This gonna be used for starting duel, during duel, and ending duel
     */
    public function __construct(Player $player, Player $opponent, string $mode, string $duelRank = DuelManager::UNRANKED){
        $this->player = $player;
        $this->opponent = $opponent;
        $this->mode = $mode;
        $this->duelRank = $duelRank;
        $this->opponentName = $opponent->getName();
    }

    /**
     * in case of saving name loll
     * @return string
     */
    public function getOpponentName(): string
    {
        return $this->opponentName;
    }

    /**
     * @return string
     */
    public function getDuelRank(): string
    {
        return $this->duelRank;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @return Player
     */
    public function getOpponent(): Player
    {
        return $this->opponent;
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }
}