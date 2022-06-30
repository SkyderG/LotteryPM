<?php

namespace lottery\task;

use lottery\Loader;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\Task;

class CheckTimer extends Task
{

    private Loader $plugin;

    public function __construct(Loader $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @return Loader
     */
    private function getPlugin(): Loader
    {
        return $this->plugin;
    }

    /**
     * @inheritDoc
     */
    public function onRun(): void
    {
        $this->getPlugin()->getPluginData()->checkTimer();
    }
}