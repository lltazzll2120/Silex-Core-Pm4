<?php

namespace DuelsWorld\Events;

use pocketmine\scheduler\Task;

class EventTask extends Task {

    /** @var string */
    private string $name;

    private int $eventPreparation = 60;

    public function __construct(string $name){
        $this->name = $name;
    }

    public function onRun(): void{
        $this->eventPreparation--;
        if($this->eventPreparation <= 0){
            EventManagement::closeDoor($this->name);
            EventManagement::initPlayers($this->name);
        }



    }
}