<?php

namespace lottery;

use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;
use lottery\task\CheckTimer;
use lottery\task\RunnerTask;
use lottery\util\BedrockEconomyCache;
use lottery\util\MessageTranslator;
use lottery\util\PercentageCalculator;
use onebone\economyapi\EconomyAPI;
use pocketmine\player\Player;

class PluginData
{

    public int $time = 0;
    public array $players = [];
    public bool $enabled = false;
    public int $totalPrize = 0;
    public string $timezone;

    private Loader $plugin;

    public function __construct(Loader $plugin, string $timezone, int $time = 0, bool $enabled = false)
    {
        $this->plugin = $plugin;
        $this->timezone = $timezone;
        $this->time = $time;
        $this->enabled = $enabled;

        date_default_timezone_set($timezone);
        $this->getPlugin()->getLogger()->info("Timezone changed to: " . $timezone);
        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new CheckTimer($this->getPlugin()), 20);
    }

    /**
     * @return Loader
     */
    private function getPlugin(): Loader
    {
        return $this->plugin;
    }

    /**
     * @return int
     */
    public function getTime(): int
    {
        return $this->time;
    }

    /**
     * @param int $time
     */
    public function setTime(int $time): void
    {
        $this->time = $time;
    }

    /**
     * @return array
     */
    public function getPlayers(): array
    {
        return $this->players;
    }

    /**
     * @param array $players
     */
    public function setPlayers(array $players): void
    {
        $this->players = $players;
    }

    /**
     * @return int
     */
    public function getTotalPrize(): int
    {
        return $this->totalPrize;
    }

    /**
     * @param int $totalPrize
     */
    public function setTotalPrize(int $totalPrize): void
    {
        $this->totalPrize = $totalPrize;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getPlayerCount(): int
    {
        return count($this->getPlayers());
    }

    public function checkTimer(): void
    {
        if ($this->isEnabled()) return;

        foreach ($this->getPlugin()->getConfig()->getNested("config.starting-times") as $hour) {
            $time = strtotime($hour);

            if (strtotime('now') == $time) {
                $this->setEnabled(true);

                $message = MessageTranslator::translateNested("announce.enabled", [
                    gmdate("H:i:s", $this->getTime())
                ]);

                $this->getPlugin()->getServer()->broadcastMessage($message);
                $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new RunnerTask($this->getPlugin()), 20);
            }
        }
    }

    public function broadcastAnnounce(): void
    {
        if (!$this->isEnabled()) return;

        $target = (int)$this->getPlugin()->getConfig()->getNested("config.time");

        $message = MessageTranslator::translateNested("announce.running", [
            $this->getTotalPrize(),
            $this->getPlayerCount(),
            gmdate("H:i:s", $this->getTime() - $target),
        ]);

        $this->getPlugin()->getServer()->broadcastMessage($message);
    }

    public function getRandomPlayers(int $amount = 1): array|string
    {
        return array_rand($this->getPlayers(), $amount);
    }

    public function broadcastWinner(): void
    {
        if (!$this->isEnabled()) return;

        if ($this->getPlayerCount() < 0 || $this->getTotalPrize() === 0) {
            $message = MessageTranslator::translateNested("announce.no-winner", [
                $this->getRandomPlayers(1),
                $this->getTotalPrize()
            ]);
        } else {
            $message = MessageTranslator::translateNested("announce.winner", [
                $this->getRandomPlayers(1),
                $this->getTotalPrize()
            ]);
        }

        $this->getPlugin()->getServer()->broadcastMessage($message);
        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new CheckTimer($this->getPlugin()), 20);
        $this->reset();
    }

    public function checkRunner(): void
    {
        if (!$this->isEnabled()) return;

        $this->setTime($this->getTime() + 1);

        $target = (int)$this->getPlugin()->getConfig()->getNested("config.time");
        $calc = PercentageCalculator::run(0, $target, $this->getTime());

        switch ($calc) {
            case 30:
            case 60:
            case 90:
                $this->broadcastAnnounce();
                break;
            case 100:
                $this->broadcastWinner();
                break;
        }
    }

    public function getEconomyHandler(): BedrockEconomyCache|EconomyAPI|null
    {
        $handler = $this->getPlugin()->getConfig()->getNested("config.economy");

        return match ($handler) {
            "EconomyAPI" => EconomyAPI::getInstance(),
            "BedrockEconomy" => $this->getPlugin()->getBedrockEconomyCache(),
            default => null
        };
    }

    public function refundAll(): void
    {
        $handler = $this->getEconomyHandler();

        if ($this->getTotalPrize() > 0) {
            if (!empty($this->getPlayers())) {
                foreach ($this->getPlayers() as $player => $amount) {
                    if ($handler instanceof BedrockEconomyCache) {
                        $handler->getHandler()->addToPlayerBalance(
                            $player,
                            $amount,
                            ClosureContext::create(
                                function (?bool $wasUpdated) use ($player) {
                                    $serverPlayer = $this->getPlugin()->getServer()->getPlayerExact($player);

                                    if (!is_null($serverPlayer) && $wasUpdated) {
                                        $message = $this->getPlugin()->getConfig()->getNested("announce.cancelled");
                                        $serverPlayer->sendMessage($message);
                                    }
                                }
                            ));
                    }

                    if ($handler instanceof EconomyAPI) {
                        $handler->addMoney($player, $amount, true);

                        $serverPlayer = $this->getPlugin()->getServer()->getPlayerExact($player);

                        if (!is_null($serverPlayer)) {
                            $message = $this->getPlugin()->getConfig()->getNested("announce.cancelled");
                            $serverPlayer->sendMessage($message);
                        }
                    }
                }

                $this->reset();
            }
        }
    }

    public function addPlayer(Player $player, int $amount): void
    {
        $handler = $this->getEconomyHandler();

        if ($handler instanceof BedrockEconomyCache) {
            $handler->getHandler()->subtractFromPlayerBalance(
                $player->getName(),
                $amount,
                ClosureContext::create(
                    function (?bool $wasUpdated) use ($player, $amount) {

                        if (!is_null($player) && $wasUpdated) {
                            $message = MessageTranslator::translateNested("success.bet-success", [
                                $amount
                            ]);
                            $player->sendMessage($message);
                        }
                    }
                ));
        }

        if ($handler instanceof EconomyAPI) {
            $handler->reduceMoney($player, $amount, true);

            $message = MessageTranslator::translateNested("success.bet-success", [
                $amount
            ]);
            $player->sendMessage($message);
        }
    }

    public function reset(): void
    {
        $this->setEnabled(false);
        $this->setTime(0);
        $this->setTotalPrize(0);
        $this->setPlayers([]);
    }
}
