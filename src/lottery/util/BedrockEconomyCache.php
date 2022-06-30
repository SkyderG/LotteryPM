<?php

namespace lottery\util;

use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\api\version\LegacyBEAPI;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

final class BedrockEconomyCache
{
    use SingletonTrait;

    private array $cache = [];

    public function getPlayerCache(string $player): int
    {
        return max(0, $this->cache[strtolower($player)] ?? 0);
    }

    public function initializePlayerCache(string $player): void
    {
        BedrockEconomyAPI::legacy()->getPlayerBalance($player, ClosureContext::create(
            function (?int $balance) use ($player): void {
                $this->cache[strtolower($player)] = $balance;
            }
        ));
    }

    public function updatePlayerCache(string $player): void
    {
        $player = Server::getInstance()->getPlayerByPrefix($player);

        if (!$player?->isConnected()) {
            return;
        }
        BedrockEconomyAPI::legacy()->getPlayerBalance($player->getName(), ClosureContext::create(
            function (?int $balance) use ($player): void {
                if (!$player?->isConnected()) {
                    return;
                }
                $this->cache[strtolower($player->getName())] = $balance;
            }
        ));
    }

    public function removePlayerCache(string $player): bool
    {
        if (!$this->isPlayerCached($player)) {
            return false;
        }
        unset($this->cache[strtolower($player)]);
        return true;
    }

    public function isPlayerCached(string $player): bool
    {
        return isset($this->cache[strtolower($player)]);
    }

    public function getHandler(): LegacyBEAPI
    {
        return BedrockEconomyAPI::legacy();
    }
}
