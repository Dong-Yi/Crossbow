<?php

namespace Crossbow;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\CompoundTag;

use Crossbow\item\Crossbow;

class Main extends PluginBase implements Listener {

    private $chargeds = [];
	
	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		ItemFactory::registerItem(new Crossbow(), true);
		Item::initCreativeItems();
	}
	
	public function onDataPacketReceive(DataPacketReceiveEvent $ev) {
		$packet = $ev->getPacket();
		if($packet instanceof InventoryTransactionPacket) {
			$player = $ev->getPlayer();
			if($player->getInventory()->getItemInHand() instanceof Crossbow) {
				$level = $player->getLevel();
				$item = $player->getInventory()->getItemInHand();
                if($packet->transactionType == 2) {
                    $inv = $player->getInventory();
                    if ($packet->trData->actionType == 1) {
                        if ($item->onReleaseUsing($player)) {
                            if ($player->isSurvival()) {
                                $item->applyDamage(1);
                                if ($item->isBroken()) {
                                    $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_BREAK);
                                }
                            }
                            $item->removeNamedTagEntry("chargedItem");
                            $inv->setItemInHand($item); //bug ???
                        } elseif(isset($this->chargeds[$player->getId()])) {
                            if ($player->isSurvival()) {
                                if(!$inv->contains(ItemFactory::get(Item::ARROW, 0, 1))) {
                                    $inv->sendContents($player);
                                    return;
                                } else {
                                    $inv->removeItem(ItemFactory::get(Item::ARROW, 0, 1));
                                }
                            }
                            $level->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_CROSSBOW_LOADING_END);
                            $inv->setItemInHand($item->setChargedItem(ItemFactory::get(Item::ARROW)));
                            $inv->setHeldItemIndex($inv->getHeldItemIndex() + 1); //    :(
                            unset($this->chargeds[$player->getId()]);
                        } else {
                            if ($player->isSurvival() and !$inv->contains(ItemFactory::get(Item::ARROW, 0, 1))) {
                                $inv->sendContents($player);
                                return;
                            }
                            $level->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_CROSSBOW_LOADING_START);
                            $level->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_CROSSBOW_LOADING_MIDDLE);
                            $this->chargeds[$player->getId()] = true;
                        }
                    }
                } elseif($packet->transactionType == 4) {
                    unset($this->chargeds[$player->getId()]);
                }
			}
		}
	}
	
}