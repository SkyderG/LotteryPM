<?php

namespace lottery\command;

use lottery\Loader;
use lottery\util\MessageTranslator;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class BaseCommand extends Command
{

    public function __construct(
        private Loader $plugin
    )
    {
        parent::__construct("lottery", "Lottery command");
        $this->setPermission("lottery.perm");
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender instanceof Player) return false;

        if (!isset($args[0])) {
            if ($sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                $sender->sendMessage(MessageTranslator::translateNested("usage.admin"));
            } else {
                $sender->sendMessage(MessageTranslator::translateNested("usage.player"));
            }

            return false;
        }

        switch ($args[0]) {
            case "bet":
            case "buy":
                if (!isset($args[1])) {
                    $sender->sendMessage(MessageTranslator::translateNested("error.no-tip-value"));
                    return false;
                }

                $this->plugin->getPluginData()->addPlayer($sender, $args[1]);
                break;

            case "activate":
            case "start":
                if (!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                    $sender->sendMessage(MessageTranslator::translateNested("error.no-permission"));
                } else {
                    $this->plugin->getPluginData()->forceStart();
                    $sender->sendMessage(MessageTranslator::translateNested("success.admin-activate"));
                }
                break;

            case "cancel":
            case "end":
                if (!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                    $sender->sendMessage(MessageTranslator::translateNested("error.no-permission"));
                } else {
                    $this->plugin->getPluginData()->refundAll();
                    $sender->sendMessage(MessageTranslator::translateNested("success.admin-cancel"));
                }
                break;

            case "time":
            case "status":
                if (!$this->plugin->getPluginData()->isEnabled()) {
                    $sender->sendMessage(MessageTranslator::translateNested("error.no-activated"));
                    return false;
                }

                $data = $this->plugin->getPluginData();

                $message = MessageTranslator::translateNested("announce.running", [
                    $data->getTotalPrize(),
                    $data->getPlayerCount(),
                    gmdate("H:i:s", $data->getTime()),
                ]);

                $sender->sendMessage($message);
                break;
        }

        return false;
    }
}
