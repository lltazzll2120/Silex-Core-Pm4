<?php

namespace DuelsWorld\duels\datas;

use pocketmine\world\Position;

class MapInformation {

    private $pos1;
    private $pos2;

    public function __construct(Position $pos1, Position $pos2){
        $this->pos1 = $pos1;
        $this->pos2 = $pos2;
    }

    /**
     * @return Position|string
     */
    public function getPos1(bool $asString = false) {
        if($asString){
            return intval($this->pos1->x) . " " . intval($this->pos1->y) . " " . intval($this->pos1->z);
        }
        return $this->pos1;
    }

    /**
     * @return Position|string
     */
    public function getPos2(bool $asString = false){
        if($asString){
            return intval($this->pos2->x) . " " . intval($this->pos2->y) . " " . intval($this->pos2->z);
        }
        return $this->pos2;
    }

}