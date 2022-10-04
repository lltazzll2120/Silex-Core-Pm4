<?php

namespace DuelsWorld\duels;

use pocketmine\block\tile\Spawnable;
use pocketmine\nbt\tag\CompoundTag;

class BuildUHCTile extends Spawnable{

    protected function addAdditionalSpawnData(CompoundTag $nbt): void
    {
        // TODO: Implement addAdditionalSpawnData() method.
    }

    public function readSaveData(CompoundTag $nbt) : void
    {
        // TODO: Implement readSaveData() method.
    }

    protected function writeSaveData(CompoundTag $nbt): void
    {
        // TODO: Implement writeSaveData() method.
    }
}