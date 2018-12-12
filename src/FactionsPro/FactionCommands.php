<?php

namespace FactionsPro;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;
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
                                    $sender->sendMessage(TF::RED . "/f invite " . TF::YELLOW . ">> " . TF::GRAY . "Invite a player to your faction");
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
                                $sender->sendMessage(TF::RED . "You must be the leader to delete this faction.");
                            }
                        }
                        break;
                    /////////////////////////////// LEAVE ///////////////////////////////
                    case "leave":
                        if ($this->plugin->isLeader($playerName) == false) {
                            $remove = $sender->getPlayer()->getNameTag();
                            $faction = $this->plugin->getPlayerFaction($playerName);
                            $name = $sender->getName();
                            $this->plugin->db->query("DELETE FROM master WHERE player='$name';");
                            $sender->sendMessage(TF::YELLOW . "You left " . TF::GREEN . $faction);
                            $this->plugin->subtractFactionPower($faction, $this->plugin->prefs->get("PowerGainedPerPlayerInFaction"));
                            $this->plugin->updateTag($sender->getName());
                            unset($this->plugin->factionChatActive[$playerName]);
                            unset($this->plugin->allyChatActive[$playerName]);
                        } else {
                            $sender->sendMessage($this->plugin->formatMessage("You must delete the faction or give\nleadership to someone else first"));
                        }
                        break;
                    /////////////////////////////// INVITE ///////////////////////////////
                    case "invite":
                        if (!isset($args[1])) {
                            $sender->sendMessage(TF::GREEN . "/f invite <player>");
                            return true;
                        }
                        if ($this->plugin->isFactionFull($this->plugin->getPlayerFaction($playerName))) {
                            $sender->sendMessage(TF::RED . "Faction is full, please kick players to make room");
                            return true;
                        }
                        $invited = $this->plugin->getServer()->getPlayerExact($args[1]);
                        if (!($invited instanceof Player)) {
                            $sender->sendMessage(TF::RED . "Player not online");
                            return true;
                        }
                        if ($this->plugin->isInFaction($invited->getName()) == true) {
                            $sender->sendMessage(TF::RED . "Player is currently in a faction");
                            return true;
                        }
                        if ($this->plugin->prefs->get("OnlyLeadersAndOfficersCanInvite")) {
                            if (!($this->plugin->isOfficer($playerName) || $this->plugin->isLeader($playerName))) {
                                $sender->sendMessage(TF::RED . "Only your faction leader/officers can invite");
                                return true;
                            }
                        }
                        if ($invited->getName() == $playerName) {
                            $sender->sendMessage(TF::RED . "You can't invite yourself to your own faction");
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($playerName);
                        $invitedName = $invited->getName();
                        $rank = "Member";
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO confirm (player, faction, invitedby, timestamp) VALUES (:player, :faction, :invitedby, :timestamp);");
                        $stmt->bindValue(":player", $invitedName);
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":invitedby", $sender->getName());
                        $stmt->bindValue(":timestamp", time());
                        $result = $stmt->execute();
                        $sender->sendMessage(TF::GREEN . "$invitedName has been invited");
                        $invited->sendMessage(TF::GREEN . "You have been invited to $factionName. Type '/f accept' or '/f deny' into chat to accept or deny!");
                        break;
                    /////////////////////////////// ACCEPT ///////////////////////////////
                    case "accept":
                        $lowercaseName = strtolower($playerName);
                        $result = $this->plugin->db->query("SELECT * FROM confirm WHERE player='$lowercaseName';");
                        $array = $result->fetchArray(SQLITE3_ASSOC);
                        if (empty($array) == true) {
                            $sender->sendMessage(TF::RED . "You have not been invited to any factions");
                            return true;
                        }
                        $invitedTime = $array["timestamp"];
                        $currentTime = time();
                        if (($currentTime - $invitedTime) <= 60) { //This should be configurable
                            $faction = $array["faction"];
                            $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                            $stmt->bindValue(":player", ($playerName));
                            $stmt->bindValue(":faction", $faction);
                            $stmt->bindValue(":rank", "Member");
                            $result = $stmt->execute();
                            $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                            $sender->sendMessage(TF::GREEN . "You successfully joined $faction");
                            $this->plugin->addFactionPower($faction, $this->plugin->prefs->get("PowerGainedPerPlayerInFaction"));
                            $this->plugin->getServer()->getPlayerExact($array["invitedby"])->sendMessage($this->plugin->formatMessage("$playerName joined the faction", true));
                            $this->plugin->updateTag($sender->getName());
                        } else {
                            $sender->sendMessage(TF::RED . "Invite has timed out");
                            $this->plugin->db->query("DELETE FROM confirm WHERE player='$playerName';");
                        }
                        break;
                    /////////////////////////////// DENY ///////////////////////////////
                    case "deny":
                        $lowercaseName = strtolower($playerName);
                        $result = $this->plugin->db->query("SELECT * FROM confirm WHERE player='$lowercaseName';");
                        $array = $result->fetchArray(SQLITE3_ASSOC);
                        if (empty($array) == true) {
                            $sender->sendMessage($this->plugin->formatMessage("You have not been invited to any factions"));
                            return true;
                        }
                        $invitedTime = $array["timestamp"];
                        $currentTime = time();
                        if (($currentTime - $invitedTime) <= 60) { //This should be configurable
                            $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                            $sender->sendMessage(TF::RED . "Invite declined");
                            $this->plugin->getServer()->getPlayerExact($array["invitedby"])->sendMessage(TF::YELLOW . "$playerName declined the invitation");
                        } else {
                            $sender->sendMessage(TF::RED . "Invite has timed out");
                            $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                        }
                        break;
                    /////////////////////////////// LEADER ///////////////////////////////
                    case "leader":
                        if (!isset($args[1])) {
                            $sender->sendMessage(TF::YELLOW . "/f leader <player>");
                            return true;
                        }
                        if (!$this->plugin->isInFaction($sender->getName())) {
                            $sender->sendMessage(TF::RED . "You must be in a faction to use this");
                            return true;
                        }
                        if (!$this->plugin->isLeader($playerName)) {
                            $sender->sendMessage(TF::RED . "You must be leader to use this");
                            return true;
                        }
                        if ($this->plugin->getPlayerFaction($playerName) != $this->plugin->getPlayerFaction($args[1])) {
                            $sender->sendMessage(TF::RED . "Add player to faction first");
                            return true;
                        }
                        if (!($this->plugin->getServer()->getPlayerExact($args[1]) instanceof Player)) {
                            $sender->sendMessage(TF::YELLOW . "Player not online");
                            return true;
                        }
                        if ($args[1] == $sender->getName()) {
                            $sender->sendMessage(TF::YELLOW . "You can't transfer the leadership to yourself");
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($playerName);
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", $playerName);
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Member");
                        $result = $stmt->execute();
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                        $stmt->bindValue(":player", $args[1]);
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":rank", "Leader");
                        $result = $stmt->execute();
                        $sender->sendMessage(TF::RED . "You are no longer leader of " . $factionName);
                        $this->plugin->getServer()->getPlayerExact($args[1])->sendMessage(TF::GREEN . "You are now leader \nof $factionName!");
                        $this->plugin->updateTag($sender->getName());
                        $this->plugin->updateTag($this->plugin->getServer()->getPlayerExact($args[1])->getName());
                        break;
                    /////////////////////////////// SETHOME ///////////////////////////////
                    case "sethome":
                        if (!$this->plugin->isInFaction($playerName)) {
                            $sender->sendMessage(TF::RED . "You must be in a faction to do this");
                            return true;
                        }
                        if (!$this->plugin->isLeader($playerName)) {
                            $sender->sendMessage(TF::RED . "You must be leader to set home");
                            return true;
                        }
                        $factionName = $this->plugin->getPlayerFaction($sender->getName());
                        $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO home (faction, x, y, z, world) VALUES (:faction, :x, :y, :z, :world);");
                        $stmt->bindValue(":faction", $factionName);
                        $stmt->bindValue(":x", $sender->getX());
                        $stmt->bindValue(":y", $sender->getY());
                        $stmt->bindValue(":z", $sender->getZ());
                        $stmt->bindValue(":world", $sender->getLevel()->getName());
                        $result = $stmt->execute();
                        $sender->sendMessage(TF::GREEN . "Home set");
                        break;
                    /////////////////////////////// HOME ///////////////////////////////
                    case "home":
                        if (!$this->plugin->isInFaction($playerName)) {
                            $sender->sendMessage($this->plugin->formatMessage("You must be in a faction to do this"));
                            return true;
                        }
                        $faction = $this->plugin->getPlayerFaction($sender->getName());
                        $result = $this->plugin->db->query("SELECT * FROM home WHERE faction = '$faction';");
                        $array = $result->fetchArray(SQLITE3_ASSOC);
                        if (!empty($array)) {
                            if ($array['world'] === null || $array['world'] === "") {
                                $sender->sendMessage(TF::RED . "Home is missing world name, please delete and make it again");
                                return true;
                            }
                            if (Server::getInstance()->loadLevel($array['world']) === false) {
                                $sender->sendMessage(TF::RED . "The world '" . $array['world'] . "'' could not be found");
                                return true;
                            }
                            $level = Server::getInstance()->getLevelByName($array['world']);
                            $sender->getPlayer()->teleport(new Position($array['x'], $array['y'], $array['z'], $level));
                            $sender->sendMessage(TF::GREEN . "Teleported home", true);
                        } else {
                            $sender->sendMessage(TF::RED . "Home is not set");
                        }
                        break;
                    /////////////////////////////// UNSETHOME ///////////////////////////////
                    case "unsethome":
                        if (!$this->plugin->isInFaction($playerName)) {
                            $sender->sendMessage(TF::RED . "You must be in a faction to do this");
                            return true;
                        }
                        if (!$this->plugin->isLeader($playerName)) {
                            $sender->sendMessage(TF::RED . "You must be leader to unset home");
                            return true;
                        }
                        $faction = $this->plugin->getPlayerFaction($sender->getName());
                        $this->plugin->db->query("DELETE FROM home WHERE faction = '$faction';");
                        $sender->sendMessage(TF::GREEN . "Home unset", true);
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

    public function alphanum($string) {
        if (function_exists('ctype_alnum')) {
            $return = ctype_alnum($string);
        } else {
            $return = preg_match('/^[a-z0-9]+$/i', $string) > 0;
        }
        return $return;
    }
}