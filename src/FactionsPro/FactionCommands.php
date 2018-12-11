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
            $playerName = $sender->getPlayer()->getName();
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
                                    $sender->sendMessage(TF::RED . "/f del " . TF::YELLOW . ">> " . TF::GRAY . "Delete your faction");
                                    $sender->sendMessage(TF::DARK_GRAY . "-------" . TF::RED . "/f help 2 for more" . TF::DARK_GRAY . "--------");
                                    break;
                            }
                        } else {
                            $sender->sendMessage(TF::DARK_GRAY . "-------------------------------");
                            $sender->sendMessage(TF::RED . TF::BOLD . "           Faction Help");
                            $sender->sendMessage(TF::DARK_GRAY . "-------------------------------");
                            $sender->sendMessage(TF::RED . "/f create " . TF::YELLOW . ">> " . TF::GRAY . "Create you own faction");
                            $sender->sendMessage(TF::RED . "/f who " . TF::YELLOW . ">> " . TF::GRAY . "Show factions info");
                            $sender->sendMessage(TF::RED . "/f del " . TF::YELLOW . ">> " . TF::GRAY . "Delete your faction");
                            $sender->sendMessage(TF::DARK_GRAY . "-------" . TF::RED . "/f help 2 for more" . TF::DARK_GRAY . "--------");
                        }
                        break;
                    ////////////////////////////// CREATE ////////////////////////////////
                    case "create":
                        if (!isset($args[1])) {
                            $sender->sendMessage(TF::GREEN . "/f create <faction name>");
                            return true;
                        }
                        if (!($this->alphanum($args[1]))) {
                            $sender->sendMessage(TF::RED . "You may only use letters and numbers");
                            return true;
                        }
                        if ($this->plugin->isNameBanned($args[1])) {
                            $sender->sendMessage(TF::RED . "This name is not allowed");
                            return true;
                        }
                        if ($this->plugin->factionExists($args[1])) {
                            $sender->sendMessage(TF::RED . "The Faction already exists");
                            return true;
                        }
                        if (strlen($args[1]) > $this->plugin->prefs->get("MaxFactionNameLength")) {
                            $sender->sendMessage(TF::RED . "That name is too long, please try again");
                            return true;
                        }
                        if ($this->plugin->isInFaction($sender->getName())) {
                            $sender->sendMessage(TF::RED . "You must leave your current faction first.");
                            return true;
                        } else {
                            $factionName = $args[1];
                            $rank = "Leader";
                            $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                            $stmt->bindValue(":player", $playerName);
                            $stmt->bindValue(":faction", $factionName);
                            $stmt->bindValue(":rank", $rank);
                            $result = $stmt->execute();
                            $this->plugin->updateAllies($factionName);
                            $this->plugin->setFactionPower($factionName, $this->plugin->prefs->get("TheDefaultPowerEveryFactionStartsWith"));
                            $this->plugin->updateTag($sender->getName());
                            $sender->sendMessage(TF::GREEN . "You created a new faction " . TF::YELLOW . $factionName);
                        }
                        break;
                    ////////////////////////////// DELETE ////////////////////////////////
                    case "delete":
                    case "del":
                        if ($this->plugin->isInFaction($playerName) == true) {
                            if ($this->plugin->isLeader($playerName)) {
                                $faction = $this->plugin->getPlayerFaction($playerName);
                                $this->plugin->db->query("DELETE FROM plots WHERE faction='$faction';");
                                $this->plugin->db->query("DELETE FROM master WHERE faction='$faction';");
                                $this->plugin->db->query("DELETE FROM allies WHERE faction1='$faction';");
                                $this->plugin->db->query("DELETE FROM allies WHERE faction2='$faction';");
                                $this->plugin->db->query("DELETE FROM strength WHERE faction='$faction';");
                                $this->plugin->db->query("DELETE FROM motd WHERE faction='$faction';");
                                $this->plugin->db->query("DELETE FROM home WHERE faction='$faction';");
                                $sender->sendMessage(TF::YELLOW . "You left your faction.");
                                $this->plugin->updateTag($sender->getName());
                                unset($this->plugin->factionChatActive[$playerName]);
                                unset($this->plugin->allyChatActive[$playerName]);
                            } else {
                                $sender->sendMessage($this->plugin->formatMessage("You are not leader!"));
                            }
                            break;
                        }
                }
            } else {
                $sender->sendMessage(TF::DARK_GRAY . "-------------------------------");
                $sender->sendMessage(TF::RED . TF::BOLD . "           Faction Help");
                $sender->sendMessage(TF::DARK_GRAY . "-------------------------------");
                $sender->sendMessage(TF::RED . "/f create " . TF::YELLOW . ">> " . TF::GRAY . "Create you own faction");
                $sender->sendMessage(TF::RED . "/f who " . TF::YELLOW . ">> " . TF::GRAY . "Show factions info");
                $sender->sendMessage(TF::RED . "/f del " . TF::YELLOW . ">> " . TF::GRAY . "Delete your faction");
                $sender->sendMessage(TF::DARK_GRAY . "-------" . TF::RED . "/f help 2 for more" . TF::DARK_GRAY . "--------");
            }
        }
    }

    public
    function alphanum($string) {
        if (function_exists('ctype_alnum')) {
            $return = ctype_alnum($string);
        } else {
            $return = preg_match('/^[a-z0-9]+$/i', $string) > 0;
        }
        return $return;
    }
}
