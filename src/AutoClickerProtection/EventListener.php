<?php

namespace AutoClickerProtection;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;

/**
 * Class EventListener
 * @package AutoClickerProtection
 */
class EventListener implements Listener
{
    /** @var Main */
    private $plugin;

    /**
     * EventListener constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param DataPacketReceiveEvent $event
     */
    public function onDataPacketReceive(DataPacketReceiveEvent $event)
    {
        $player = $event->getPlayer();
        $packet = $event->getPacket();
        if ($packet instanceof InventoryTransactionPacket) {
            $transactionType = $packet->transactionType;
            if ($transactionType === InventoryTransactionPacket::TYPE_USE_ITEM || $transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) {
                $this->plugin->addClick($player);
                if ($this->plugin->getClicks($player) > $this->plugin->getConfig()->getNested("allowed-clicks-per-second") && !$player->isClosed()) {
                    $this->plugin->getServer()->broadcastMessage(str_replace(["{CLICKCOUNT}", "{PLAYER}"], [$this->plugin->getClicks($player), $player->getName()], $this->plugin->translateColorTags($this->plugin->getConfig()->getNested("autoclick-detected-message"))));
                    switch ($this->plugin->getConfig()->getNested("autoclick-detected-punishment")) {
                        case "ban":
                            $this->plugin->getServer()->getNameBans()->addBan($player->getName(), $this->plugin->translateColorTags($this->plugin->getConfig()->getNested("autoclick-punishment-ban-message")));
                            $player->kick($this->plugin->translateColorTags($this->plugin->getConfig()->getNested("autoclick-punishment-ban-message")), false);
                            break;
                        case "kick":
                        default:
                            $player->kick($this->plugin->translateColorTags($this->plugin->getConfig()->getNested("autoclick-punishment-kick-message")), false);
                            break;
                    }
                    $event->setCancelled();
                }
            }
        }
    }
}