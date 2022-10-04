<?php

namespace DuelsWorld\Events;

use pocketmine\world\Position;
use pocketmine\player\Player;

class Event {

    /** @var string */
    public string $name;
    /**
     * @var Position
     */
    public Position $spawn;
    /**
     * @var Position
     */
    public Position $pos1;
    /**
     * @var Position
     */
    public Position $pos2;
    /**
     * @var Player[]
     */
    public array $eventPlayers = [];

    public function __construct(string $name, Position $spawn, Position $pos1, Position $pos2){
        $this->name = $name;
        $this->spawn = $spawn;
        $this->pos1 = $pos1;
        $this->pos2 = $pos2;
    }
}