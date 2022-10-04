<?php

namespace DuelsWorld\Entity;

use pocketmine\entity\projectile\EnderPearl as CustomEnderPearl;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\world\sound\EndermanTeleportSound;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\player\Player;

class EnderPearl extends CustomEnderPearl {

    protected function onHit(ProjectileHitEvent $event) : void {
        $owner = $this->getOwningEntity();
        if ($owner !== null) {
            if ($owner instanceof Player) {
                $this->broadcastLevelSoundEvent($owner, LevelSoundEvent::TELEPORT);
                $this->getWorld()->addSound($owner->getPosition(), new EndermanTeleportSound());
                $owner->teleport($event->getRayTraceResult()->getHitVector());
                $this->getWorld()->addSound($owner->getPosition(), new EndermanTeleportSound());
                $owner->attack(new EntityDamageEvent($owner, EntityDamageEvent::CAUSE_FALL, 7));
            }
        }
    }

    public function broadcastLevelSoundEvent(Player $player, int $sound){
		$pk = LevelSoundEventPacket::create($sound, $player->getPosition()->asVector3(), -1, ":", false, false);
		$player->getNetworkSession()->sendDataPacket($pk);
	}

}