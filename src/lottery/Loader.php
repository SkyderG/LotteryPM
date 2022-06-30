<?php

namespace lottery;

use lottery\event\EventListener;
use lottery\util\BedrockEconomyCache;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

class Loader extends PluginBase implements Listener
{
    private PluginData $pluginData;
    private BedrockEconomyCache $bedrockEconomyCache;

    use SingletonTrait;

    protected function onLoad(): void
    {
        $this->saveDefaultConfig();
    }

    protected function onEnable(): void
    {
        self::setInstance($this);

        $config = $this->getConfig();

        $this->bedrockEconomyCache = new BedrockEconomyCache();

        $this->pluginData = new PluginData(
            $this,
            $config->getNested("config.timezone"),
            $config->getNested("config.time")
        );

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        new EventListener($this);

        if (!$this->getServer()->isRunning()) $this->getPluginData()->refundAll();
    }

    protected function onDisable(): void
    {
        $this->getPluginData()->refundAll();
    }

    /**
     * @return PluginData
     */
    public function getPluginData(): PluginData
    {
        return $this->pluginData;
    }

    /**
     * @return BedrockEconomyCache
     */
    public function getBedrockEconomyCache(): BedrockEconomyCache
    {
        return $this->bedrockEconomyCache;
    }
}
