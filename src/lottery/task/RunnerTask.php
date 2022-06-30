<?php

namespace lottery\task;

use lottery\Loader;
use pocketmine\scheduler\Task;

class RunnerTask extends Task
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

    public function onRun(): void
    {
        $this->getPlugin()->getPluginData()->checkRunner();
    }

}