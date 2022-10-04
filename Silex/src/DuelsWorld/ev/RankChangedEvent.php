<?php

namespace DuelsWorld\ev;

use pocketmine\event\Event;

class RankChangedEvent extends Event{

    /**
     * @var string
     */
    private string $player;

    public function __construct(string $player){
        $this->player = $player;
    }

    /**
     * @return string
     */
    public function getPlayer(): string{
        return $this->player;
    }
}
