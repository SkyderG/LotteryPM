<?php

namespace lottery\event;

use cooldogedev\BedrockEconomy\event\transaction\TransactionProcessEvent;
use cooldogedev\BedrockEconomy\transaction\types\UpdateTransaction;
use lottery\Loader;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

class EventListener implements Listener
{
    public function __construct(
        private Loader $plugin
    )
    {
        $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
    }

    /**
     * @return Loader
     */
    public function getPlugin(): Loader
    {
        return $this->plugin;
    }

    public function onJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        $cache = $this->getPlugin()->getBedrockEconomyCache();

        if (!$cache->isPlayerCached($player->getName())) $cache->initializePlayerCache($player->getName());
    }

    public function onQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();
        $cache = $this->getPlugin()->getBedrockEconomyCache();

        if ($cache->isPlayerCached($player->getName())) $cache->removePlayerCache($player->getName());
    }

    public function onTransactionProcess(TransactionProcessEvent $event)
    {
        $transaction = $event->getTransaction();

        if ($transaction instanceof UpdateTransaction) {
            $sender = $transaction->getTarget();

            if ($event->isSuccessful()) {
                $this->getPlugin()->getBedrockEconomyCache()->updatePlayerCache($sender);
            }
        }
    }

}