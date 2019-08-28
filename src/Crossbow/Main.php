<?php

namespace Crossbow;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;

use Crossbow\item\Crossbow;

class Main extends PluginBase implements Listener {
	
	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		ItemFactory::registerItem(new Crossbow());
		Item::initCreativeItems();
	}
	
	public function onDataPacketReceive(DataPacketReceiveEvent $ev) {
		$packet = $ev->getPacket();
		if($packet instanceof InventoryTransactionPacket) {
			$player = $ev->getPlayer();
			if($player->getInventory()->getItemInHand() instanceof Crossbow) {
				$level = $player->getLevel();
				$item = $player->getInventory()->getItemInHand();
				if($packet->transactionType == 4) {
					if($packet->trData->actionType == 1) {
						$level->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_CROSSBOW_LOADING_MIDDLE);
						$level->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_CROSSBOW_LOADING_END);
						if($player->isSurvival()) {
							$player->getInventory()->removeItem(ItemFactory::get(Item::ARROW, 0, 1));
							$item->applyDamage(1);
							if($item->isBroken()) {
								$player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_BREAK);
								$player->getInventory()->setItemInHand($item);
								return;
							}
						}
						$player->getInventory()->setItemInHand($item->setChargedItem(ItemFactory::get(Item::ARROW)));
					}
				} elseif($packet->transactionType == 2) {
					if($item->onReleaseUsing($player)) {
						$player->getInventory()->setItemInHand($item);
					} else {
						if($player->isSurvival() and !$player->getInventory()->contains(ItemFactory::get(Item::ARROW, 0, 1))) {
							$player->getInventory()->sendContents($player);
							return;
						}
						$level->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_CROSSBOW_LOADING_START);
					}
				}
			}
		}
	}
	
}