<?php

namespace FactionsPro;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class FactionCommands extends Command {

    public $plugin;

    /**
     * FactionCommands constructor.
     * @param FactionMain $pg
     */
    public function __construct(FactionMain $plugin) {
        $this->plugin = $plugin;
        parent::__construct("f", "main faction command", "Usage: /f", ["faction"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if ($sender instanceof Player) {
            if (isset($args[0])) {
                switch ($args[0]) {
                    ////////////////////////////// HELP ////////////////////////////////
                    case "help":
                        if (isset($args[1])) {
                            switch ($args[1]) {
                                case "1":
                                    $sender->sendMessage(TF::DARK_GRAY . "-------------------------------");
                                    $sender->sendMessage(TF::RED . TF::BOLD . "           Faction Help");
                                    $sender->sendMessage(TF::DARK_GRAY . "-------------------------------");
                                    $sender->sendMessage(TF::RED . "/f create " . TF::YELLOW . ">> " . TF::GRAY . "Create you own faction");
                                    $sender->sendMessage(TF::RED . "/f who " . TF::YELLOW . ">> " . TF::GRAY . "Show factions info");
                                    $sender->sendMessage(TF::DARK_GRAY . "-------" . TF::RED . "/f help 2 for more" . TF::DARK_GRAY . "--------");
                                    break;
                            }
                        } else {
                            $sender->sendMessage(TF::DARK_GRAY . "-------------------------------");
                            $sender->sendMessage(TF::RED . TF::BOLD . "           Faction Help");
                            $sender->sendMessage(TF::DARK_GRAY . "-------------------------------");
                            $sender->sendMessage(TF::RED . "/f create " . TF::YELLOW . ">> " . TF::GRAY . "Create you own faction");
                            $sender->sendMessage(TF::RED . "/f who " . TF::YELLOW . ">> " . TF::GRAY . "Show factions info");
                            $sender->sendMessage(TF::DARK_GRAY . "-------" . TF::RED . "/f help 2 for more" . TF::DARK_GRAY . "--------");
                        }
                        break;
                    ////////////////////////////// CREATE ////////////////////////////////
                }
            } else {
                $sender->sendMessage(TF::DARK_GRAY . "-------------------------------");
                $sender->sendMessage(TF::RED . TF::BOLD . "           Faction Help");
                $sender->sendMessage(TF::DARK_GRAY . "-------------------------------");
                $sender->sendMessage(TF::RED . "/f create " . TF::YELLOW . ">> " . TF::GRAY . "Create you own faction");
                $sender->sendMessage(TF::RED . "/f who " . TF::YELLOW . ">> " . TF::GRAY . "Show factions info");
                $sender->sendMessage(TF::DARK_GRAY . "-------" . TF::RED . "/f help 2 for more" . TF::DARK_GRAY . "--------");
            }
        }
    }
}
