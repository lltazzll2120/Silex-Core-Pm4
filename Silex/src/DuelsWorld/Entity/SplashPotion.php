<?php

namespace DuelsWorld\Entity;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\color\Color;
use pocketmine\data\bedrock\LegacyEntityIdToStringIdMap;
use pocketmine\data\bedrock\PotionTypeIdMap;
use pocketmine\entity\effect\InstantEffect;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Throwable;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\Potion;
use pocketmine\item\PotionType;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\player\Player;
use pocketmine\world\particle\PotionSplashParticle;
use pocketmine\world\sound\PotionSplashSound;

use function count;
use function round;
use function sqrt;

class SplashPotion extends Throwable {

    public const NETWORK_ID = EntityIds::SPLASH_POTION;

    protected $gravity = 0.12;
    protected $drag = 0.09;

    /** @var bool */
	protected $linger = false;
	protected PotionType $potionType;

    public function __construct(Location $location, ?Entity $shootingEntity, PotionType $potionType, ?CompoundTag $nbt = null){
		$this->potionType = $potionType;
		parent::__construct($location, $shootingEntity, $nbt);
	}

    public static function getNetworkTypeId() : string{
        return LegacyEntityIdToStringIdMap::getInstance()->legacyToString(self::NETWORK_ID);
    }

    protected function initEntity(CompoundTag $nbt) : void {
        parent::initEntity($nbt);

        $this->setPosition(PotionTypeIdMap::getInstance()->fromId($nbt->getShort("PotionId", 0)));
    }

    public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();
		$nbt->setShort("PotionId", PotionTypeIdMap::getInstance()->toId($this->getPotionType()));

		return $nbt;
	}

    public function getResultDamage() : int{
		return -1; //no damage
	}

    protected function onHit(ProjectileHitEvent $event) : void {
        $effects = $this->getPotionEffects();
		$hasEffects = true;

		if(count($effects) === 0){
			$particle = new PotionSplashParticle(PotionSplashParticle::DEFAULT_COLOR());
			$hasEffects = false;
		}else{
			$colors = [];
			foreach($effects as $effect){
				$level = $effect->getEffectLevel();
				for($j = 0; $j < $level; ++$j){
					$colors[] = $effect->getColor();
				}
			}
			$particle = new PotionSplashParticle(Color::mix(...$colors));
		}

		$this->getWorld()->addParticle($this->location, $particle);
		$this->broadcastSound(new PotionSplashSound());

		if($hasEffects){
			if(!$this->willLinger()){
				foreach($this->getWorld()->getNearbyEntities($this->boundingBox->expandedCopy(4.125, 2.125, 4.125), $this) as $entity){
					if($entity instanceof Living && $entity->isAlive()){
						$distanceSquared = $entity->getEyePos()->distanceSquared($this->location);
						if($distanceSquared > 16){ //4 blocks
							continue;
						}

						$distanceMultiplier = 1 - (sqrt($distanceSquared) / 4);
						if($event instanceof ProjectileHitEntityEvent && $entity === $event->getEntityHit()){
							$distanceMultiplier = 1.0;
						}

						foreach($this->getPotionEffects() as $effect){
							//getPotionEffects() is used to get COPIES to avoid accidentally modifying the same effect instance already applied to another entity

							if(!($effect->getType() instanceof InstantEffect)){
								$newDuration = (int) round($effect->getDuration() * 0.75 * $distanceMultiplier);
								if($newDuration < 20){
									continue;
								}
								$effect->setDuration($newDuration);
								$entity->getEffects()->add($effect);
							}else{
								$effect->getType()->applyEffect($entity, $effect, $distanceMultiplier, $this);
							}
						}
					}
				}
			}else{
				//TODO: lingering potions
			}
		}elseif($event instanceof ProjectileHitBlockEvent && $this->getPotionType()->equals(PotionType::WATER())){
			$blockIn = $event->getBlockHit()->getSide($event->getRayTraceResult()->getHitFace());

			if($blockIn->getId() === BlockLegacyIds::FIRE){
				$this->getWorld()->setBlock($blockIn->getPosition(), VanillaBlocks::AIR());
			}
			foreach($blockIn->getHorizontalSides() as $horizontalSide){
				if($horizontalSide->getId() === BlockLegacyIds::FIRE){
					$this->getWorld()->setBlock($horizontalSide->getPosition(), VanillaBlocks::AIR());
				}
			}
		}
    }

    /**
	 * Returns the meta value of the potion item that this splash potion corresponds to. This decides what effects will be applied to the entity when it collides with its target.
	 */
	public function getPotionType() : PotionType{
		return $this->potionType;
	}

	public function setPotionType(PotionType $type) : void{
		$this->potionType = $type;
		$this->networkPropertiesDirty = true;
	}

    /**
	 * Returns whether this splash potion will create an area-effect cloud when it lands.
	 */
	public function willLinger() : bool{
		return $this->linger;
	}

	/**
	 * Sets whether this splash potion will create an area-effect-cloud when it lands.
	 */
	public function setLinger(bool $value = true) : void{
		$this->linger = $value;
		$this->networkPropertiesDirty = true;
	}

    public function getPotionEffects() : array {
        return $this->potionType->getEffects();
    }
    
    public function broadcastLevelSoundEvent(Player $player, int $sound){
		$pk = LevelSoundEventPacket::create($sound, $player->getPosition()->asVector3(), -1, ":", false, false);
		$player->getNetworkSession()->sendDataPacket($pk);
	}

    protected function syncNetworkData(EntityMetadataCollection $properties) : void{
		parent::syncNetworkData($properties);

		$properties->setShort(EntityMetadataProperties::POTION_AUX_VALUE, PotionTypeIdMap::getInstance()->toId($this->potionType));
		$properties->setGenericFlag(EntityMetadataFlags::LINGER, $this->linger);
    }
    
}