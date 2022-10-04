<?php

namespace DuelsWorld\Ranks;

use DuelsWorld\ev\RankChangedEvent;
use DuelsWorld\Main;
use pocketmine\player\OfflinePlayer;
use pocketmine\player\Player;
use pocketmine\Server;

class RankSession {

    /** @var string */
    private string $name;

    /** @var array */
    private array $data = [];

    public function __construct(string $name){
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    public function getOfflinePlayer(): ?OfflinePlayer{
        return Server::getInstance()->getOfflinePlayer($this->name);
    }

    public function getPlayer(): ?Player{
        return Server::getInstance()->getPlayerByPrefix($this->name);
    }

    public function getRank(): Rank{
        if(!isset($this->data["rank"])){
            $this->data["rank"] = Main::getInstance()->getDefaultRank()->name;
        }
        return Main::getInstance()->getRank($this->data["rank"]);
    }

    public function setRank(Rank $rank): void{
        $this->data["rank"] = $rank->name;
        $ev = new RankChangedEvent($this->getName());
        $ev->call();
        $this->save();
    }

    public function save(): void{
        yaml_emit_file(Main::getInstance()->getSessionPath() . $this->name . ".yml",$this->data);
    }

}