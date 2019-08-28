<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace Crossbow\item;

use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

class Crossbow extends Tool {
	public function __construct(int $meta = 0) {
		parent::__construct(self::CROSSBOW, $meta, "Crossbow");
	}

	public function getFuelTime() : int {
		return 200;
	}

	public function getMaxDurability() : int {
		return 326;
	}
	
	public function setChargedItem(Item $item) : Item {
		
		$list = $this->getNamedTagEntry("chargedItem");
		
		if($list instanceof CompoundTag) {
			
			$this->removeNamedTagEntry("chargedItem");
			$list = $item->nbtSerialize(-1, "chargedItem");
			
		} else {
			
			$list = $item->nbtSerialize(-1, "chargedItem");
			
		}
		
		$this->setNamedTagEntry($list);
		
		return $this;
	}
	
	public function onReleaseUsing(Player $player) : bool {
		
		if(!($this->getNamedTagEntry("chargedItem") instanceof CompoundTag)) {
			return false;
		}
		
		$nbt = Entity::createBaseNBT(
			$player->add(0, $player->getEyeHeight(), 0),
			$player->getDirectionVector(),
			($player->yaw > 180 ? 360 : 0) - $player->yaw,
			-$player->pitch
		);
		
		$nbt->setShort("Fire", $player->isOnFire() ? 45 * 60 : 0);
		
		$critical = false;
		
		if(!$player->isSprinting() and !$player->isFlying() and $player->fallDistance > 0 and !$player->hasEffect(Effect::BLINDNESS) and !$player->isUnderwater()) {
			
			$critical = true;
			
		}
		
		$entity = Entity::createEntity("Arrow", $player->getLevel(), $nbt, $player, $critical);
		
		if($entity instanceof Projectile) {
			
			if($critical) {
				
				$entity->setBaseDamage(2.5);
				
			} else {
				
				$entity->setBaseDamage(2.25);
				
			}
			
			$ev = new EntityShootBowEvent($player, $this, $entity, 3); //force = 2 ???
			
			$ev->call();
			
			$entity = $ev->getProjectile();
			
			if($ev->isCancelled()) {
				
				$entity->flagForDespawn();
				$player->getInventory()->sendContents($player);
				
			} else {
				
				$entity->setMotion($entity->getMotion()->multiply($ev->getForce()));
				$this->removeNamedTagEntry("chargedItem");
				
				if($entity instanceof Projectile) {
					
					$projectileEv = new ProjectileLaunchEvent($entity);
					$projectileEv->call();
					
					if($projectileEv->isCancelled()) {
						
						$ev->getProjectile()->flagForDespawn();
						
					} else {
						
						$ev->getProjectile()->spawnToAll();
						$player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_CROSSBOW_SHOOT);
						
					}
					
				} else {
					
					$entity->spawnToAll();
					
				}
				
			}
		} else {
			
			$entity->spawnToAll();
			
		}
		
		return true;
		
	}
	
}
