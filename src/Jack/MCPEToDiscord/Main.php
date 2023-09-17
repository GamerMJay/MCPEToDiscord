<?php

namespace Jack\MCPEToDiscord;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as C;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\VersionCommand;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\event\player\{PlayerJoinEvent,PlayerQuitEvent, PlayerDeathEvent, PlayerChatEvent};
use pocketmine\event\server\CommandEvent;
use pocketmine\VersionInfo;
use IvanCraft623\RankSystem\rank\Rank;
use IvanCraft623\RankSystem\RankSystem;

class Main extends PluginBase implements Listener{

    private Config $responses;

    private Config $cfg;
	
    protected array $cfgdata;

    protected array $responsesdata;

    protected bool $enabled;

    protected string $version;
    
    protected string $confversion = "1.2.0";

    public $language = "english";

    private $pp = null;

    private $f = null;

    private $rk = null;
    public function onEnable(): void {
        $this->saveResource("config.yml");
        $this->saveResource("help.txt");
        $this->version = $this->getDescription()->getVersion();
        $this->cfg = new Config($this->getDataFolder()."config.yml", Config::YAML);
        if(!$this->cfg->exists("version") || $this->cfg->get("version") !== $this->confversion){
            $this->getLogger()->error("Your config has an old version, updating your config to a new one. You might set values");
        }
        $this->cfgdata = $this->cfg->getAll();
        $this->language = strtolower($this->cfg->get("language"));
        $os = array('english', 'spanish', 'german', 'traditional_chinese', 'simplified_chinese', 'french', 'portuguese');
        if (in_array($this->language, $os) == false){
            $this->language = "english";
            $this->getLogger()->error("Wrong language put into the config, setting the language to English.");
        };
        $this->saveResource("lang/".$this->language.".yml");
        $this->responses = new Config($this->getDataFolder()."lang/".$this->language.".yml", Config::YAML);
        if($this->cfg->get('debug')){
            $this->getLogger()->info($this->responses->get("enabled_debug"));
        }
        $this->responsesdata = $this->responses->getAll();
        if($this->cfg->get('pureperms')){
            $this->pp = $this->getServer()->getPluginManager()->getPlugin('PurePerms');
            if($this->pp === null){
                if($this->cfg->get('debug')){
                    $this->getLogger()->error($this->responses->get('pureperms_bad'));
                }
            } else {
                if($this->cfg->get('debug')){
                    $this->getLogger()->info($this->responses->get('pureperms_good'));
                }
            }
        }
        if($this->cfg->get('ranksystem')){
            $this->rk = $this->getServer()->getPluginManager()->getPlugin('RankSystem');
            if($this->rk === null){
                if($this->cfg->get('debug')){
                    $this->getLogger()->error($this->responses->get('ranksystem_bad'));
                }
            } else {
                if($this->cfg->get('debug')){
                    $this->getLogger()->info($this->responses->get('ranksystem_good'));
                }
            }
        }
        if($this->cfg->get('factions')){
            $this->f = $this->getServer()->getPluginManager()->getPlugin('FactionsPro');
            if($this->f == null){
                if($this->cfg->get('debug')){
                    $this->getLogger()->error($this->responses->get('factions_bad'));
                }
            } else {
                if($this->cfg->get('debug')){
                    $this->getLogger()->info($this->responses->get('factions_good'));
                }
            }
        }
        $tmp = $this->cfg->get("discord");
        $this->enabled = false;
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if($tmp == false){
            $this->getLogger()->info(C::RED.$this->responses->get('disabled_config'));
            return;
        }
        if($tmp == true){
            $url = $this->cfg->get("webhook_url");
            $query = "https://discord.com/api/webhooks/";
            if(substr($url, 0, strlen($query)) == $query) {
                $this->enabled = true;
                if($this->cfg->get('other_pluginEnabled?') === true){
                    $this->sendMessage($this->cfg->get('other_pluginEnabledFormat'), "Enable");
                }
                return;
            } else {
                $this->getLogger()->warning($this->responses->get('enabled_incomplete'));
		        return;
            }
        }
        $this->getLogger()->warning($this->responses->get('disabled_config'));
        return;
	}
	
	public function onDisable(): void {
        if($this->cfg->get('debug')){
            $this->getLogger()->info($this->responses->get("disabled"));
        }
        if($this->cfg->get('other_pluginDisabled?') === true){
            $this->sendMessage($this->cfg->get('other_pluginDisabledFormat'), "Disabled");
        }
    }

    /**
     * @param CommandSender $sender
     * @param Command $cmd
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{
        if($cmd->getName() == "mcpe"){
        if(!isset($args[0])){
			$sender->sendMessage(C::RED.$this->responsesdata['invalid_command']);
            return false;
	    }
        if(!$sender->hasPermission("mcpetodiscord.use")){
            return false;
        }
	    switch($args[0]){
			case 'version':
			case 'ver':
				if($this->cfg->get('debug')){
					$this->getLogger()->info(C::GOLD."=== DETAILS ===");
					$this->getLogger()->info(C::GREEN."Name     ".C::GOLD.":: ".C::AQUA."MCPEToDiscord");
					$this->getLogger()->info(C::GREEN."Version  ".C::GOLD.":: ".C::AQUA.$this->version);
                    $this->getLogger()->info(C::GREEN."PMMP API ".C::GOLD.":: ".C::AQUA. VersionInfo::VERSION()->getBaseVersion());
					$this->getLogger()->info(C::GOLD.$this->responsesdata['info_note']);
					$sender->sendMessage(C::GOLD.$this->responsesdata['debug_info_response']);
					break;
				} else {
					$sender->sendMessage("Version - ".$this->version);
                    $sender->sendMessage("MCPEToDiscord for PMMP API " . VersionInfo::VERSION()->getBaseVersion());
					break;
				}
				break;
			case 'on':
		    case 'enable':
				if($this->enabled){
					$sender->sendMessage(C::RED.$this->responsesdata['already_enabled']);
					break;
				}
				$this->enabled = true;
				$this->cfg->set('discord', true);
				$this->cfg->save(true);
				$sender->sendMessage(C::GREEN.$this->responsesdata['now_enabled']);
				break;
			case 'disable':
			case 'off':
				if(!$this->enabled){
					$sender->sendMessage(C::RED.$this->responsesdata['already_disabled']);
					break;
				}
				$this->enabled = false;
				$this->cfg->set('discord', false);
				$this->cfg->save(true);
				$sender->sendMessage(C::RED.$this->responsesdata['now_disabled']);;
				break;
		    case 'send':
                if(!$this->enabled) {
                    $sender->sendMessage(C::RED.$this->responsesdata["disabled"]);
                    break;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage(C::RED.$this->responsesdata["args_missing"]);
                    break;
                }
                if(!$sender instanceof Player){
                    $sender->sendMessage(C::RED.$this->responsesdata["ingame"]);
                    break;
                }else{
                    $name = $sender->getName();
                    if($this->enabled == false){ 
                        $sender->sendMessage(C::RED.$this->responses->get["command_disabled"]);
                    break;
                    } else {
                    $this->sendMessage("[".$sender->getNameTag()."] : ".str_replace($args[0]." ", "",implode(" ", $args)), $name);
                    $sender->sendMessage(C::AQUA.$this->responsesdata["send_success"]);
                    }
                }
                break;
		    case 'setlang':
                if(!isset($args[1])){
                    $sender->sendMessage(C::RED.$this->responsesdata["no_language"] . "\n- English\n- Spanish\n- German\n- Simplified_Chinese\n- Traditional_Chinese\n- French\n- Portuguese");
                    break;
                } else {
                    $os = array('english', 'spanish', 'german', 'traditional_chinese', 'simplified_chinese', 'french', 'portuguese');
                        if (in_array(strtolower($args[1]), $os) == false) {
                            $sender->sendMessage(C::RED.$this->responsesdata["invalid_language"]);
                    break;
                        }
                    if($this->language == strtolower($args[1])){
                    $sender->sendMessage(C::RED.$this->responsesdata["language_already"].$args[1]);
                    break;
                    }
                    $this->language = strtolower($args[1]);
                    $this->saveResource("lang/".$this->language.".yml");
                    $this->responses = new Config($this->getDataFolder()."lang/".$this->language.".yml", Config::YAML, []);
                    $this->cfg->set('language', strtolower($args[1]));
                    $this->cfg->save(true);
                    $this->cfgdata = $this->cfg->getAll();
                    $this->responsesdata = $this->responses->getAll();
                    $sender->sendMessage(C::GREEN.$this->responsesdata["success"]);
                    break;
                }
                break;
                
                case 'help':
                    $sender->sendMessage("§7-- §9MCPEToDiscord §7--\n§8- §b/mcpe send\n§8- §b/mcpe reload\n§8- §b/mcpe setlang\n§8- §b/mcpe on/off\n§8- §b/mcpe help\n§8- §b/mcpe credits\n§7-- §9MCPEToDiscord §7--");
                    break;
    
                case 'credits':
                    $sender->sendMessage("§7-- §9MCPEToDiscord Credits §7--\n§bGamerMJay (Author)\n§bJaxkDev (Original Plugin)\n§bNiekertDev (AsyncTasks)\n§7-- §9MCPEToDiscord Credits §7--");
                    break;
                case 'reload':
                    $this->responsesdata = $this->responses->getAll();
                    $this->cfgdata = $this->cfg->getAll();
                    $sender->sendMessage(C::GREEN . "Config and Language output successfully reloaded.");

            default:
                $sender->sendMessage(C::RED.$this->responsesdata["invalid_command"]);
                break;
		}
		return true;
        }
    }
    
    /**
     * @param PlayerJoinEvent $event
     */
	public function onJoin(PlayerJoinEvent $event){
        $playername = $event->getPlayer()->getName(); //START HERE AND FIX THIS *****
        if($this->cfg->get("webhook_playerJoin?") !== true){
            return;
        }
        $format = $this->cfgdata["webhook_playerJoinFormat"];
        $msg = str_replace("{player}",$playername,$format);
        if(!is_null($this->pp)){
            $tmp = $this->pp->getUserDataMgr()->getGroup($event->getPlayer());
            $msg = str_replace("{group}", $tmp->getName(), $msg);
            $msg = str_replace("{nickname}", $event->getPlayer()->getDisplayName(), $msg);
        }
        if(!is_null($this->rk)){
            $tmp = $this->rk->getSessionManager()->get($playername)->getHighestRank()->getName();
            $msg = str_replace("{grouprk}", $tmp, $msg);
            $msg = str_replace("{nickname}", $event->getPlayer()->getDisplayName(), $msg);
        }
        if(!is_null($this->f)){
            $fac = $this->f->getFaction($playername);
            $rank = 'Member';
            if($this->f->isOfficer($playername)){
                $rank = 'Officer';
            }
            if($this->f->isLeader($playername)){
                $rank = 'Leader';
            }
            $msg = str_replace("{fac_rank}", $rank, $msg);
            $msg = str_replace("{faction}", $fac, $msg);
        }
        $this->sendMessage($msg, $playername);
    }

    public function onQuit(PlayerQuitEvent $event){
        $playername = $event->getPlayer()->getName();
        if($this->cfgdata["webhook_playerLeave?"] !== true){
            return;
        }
        $format = $this->cfgdata["webhook_playerLeaveFormat"];
        $msg = str_replace("{player}",$playername,$format);
        if(!is_null($this->pp)){
            $tmp = $this->pp->getUserDataMgr()->getGroup($event->getPlayer());
            $msg = str_replace("{group}", $tmp->getName(), $msg);
            $msg = str_replace("{nickname}", $event->getPlayer()->getDisplayName(), $msg);
        }
        if(!is_null($this->rk)){
            $tmp = $this->rk->getSessionManager()->get($playername)->getHighestRank()->getName();
            $msg = str_replace("{grouprk}", $tmp, $msg);
            $msg = str_replace("{nickname}", $event->getPlayer()->getDisplayName(), $msg);
        }
        if(!is_null($this->f)){
            $fac = $this->f->getFaction($playername);
            $rank = 'Member';
            if($this->f->isOfficer($playername)){
                $rank = 'Officer';
            }
            if($this->f->isLeader($playername)){
                $rank = 'Leader';
            }
            $msg = str_replace("{fac_rank}", $rank, $msg);
            $msg = str_replace("{faction}", $fac, $msg);
        }
        $this->sendMessage($msg, $playername);
    }

    public function onDeath(PlayerDeathEvent $event){
        $playername = $event->getPlayer()->getName();
        if($this->cfgdata["webhook_playerDeath?"] !== true){
            return;
        }
        $format = $this->cfgdata["webhook_playerDeathFormat"];
        $msg = str_replace("{player}",$playername,$format);
        if(!is_null($this->pp)){
            $tmp = $this->pp->getUserDataMgr()->getGroup($event->getPlayer());
            $msg = str_replace("{group}", $tmp->getName(), $msg);
            $msg = str_replace("{nickname}", $event->getPlayer()->getDisplayName(), $msg);
        }
        if(!is_null($this->rk)){
            $tmp = $this->rk->getSessionManager()->get($playername)->getHighestRank()->getName();
            $msg = str_replace("{grouprk}", $tmp, $msg);
            $msg = str_replace("{nickname}", $event->getPlayer()->getDisplayName(), $msg);
        }
        if(!is_null($this->f)){
            $fac = $this->f->getFaction($playername);
            $rank = 'Member';
            if($this->f->isOfficer($playername)){
                $rank = 'Officer';
            }
            if($this->f->isLeader($playername)){
                $rank = 'Leader';
            }
            $msg = str_replace("{fac_rank}", $rank, $msg);
            $msg = str_replace("{faction}", $fac, $msg);
        }
        $this->sendMessage($msg, $playername);
    }

    public function onChat(PlayerChatEvent $event){
	    $playername = $event->getPlayer()->getName();
        $message = $event->getMessage();
        $ar = getdate();
        $time = $ar['hours'].":".$ar['minutes'];
        if($this->cfgdata["webhook_playerChat?"] !== true){
            return;
        }
        $format = $this->cfgdata["webhook_playerChatFormat"];
        $msg = str_replace("{msg}",$message, str_replace("{time}",$time, str_replace("{player}",$playername,$format)));
        if(!is_null($this->pp)){
            $tmp = $this->pp->getUserDataMgr()->getGroup($event->getPlayer());
            $msg = str_replace("{group}", $tmp->getName(), $msg);
            $msg = str_replace("{nickname}", $event->getPlayer()->getDisplayName(), $msg);
        }
        if(!is_null($this->rk)){
            $tmp = $this->rk->getSessionManager()->get($playername)->getHighestRank()->getName();
            $msg = str_replace("{grouprk}", $tmp, $msg);
            $msg = str_replace("{nickname}", $event->getPlayer()->getDisplayName(), $msg);
        }
        if(!is_null($this->f)){
            $fac = $this->f->getFaction($playername);
            $rank = 'Member';
            if($this->f->isOfficer($playername)){
                $rank = 'Officer';
            }
            if($this->f->isLeader($playername)){
                $rank = 'Leader';
            }
            $msg = str_replace("{fac_rank}", $rank, $msg);
            $msg = str_replace("{faction}", $fac, $msg);
        }
        $this->sendMessage($msg, $playername);
    }

    public function onCmdProcess(CommandEvent $event){
        $player = $event->getSender();
        $message = $event->getCommand();
        $ar = getdate();
        $time = $ar["hours"] . ":" . $ar["minutes"];
        if(!isset($message) || $message == "") return;
        if($this->cfgdata["webhook_playerCommand?"] !== true) return;
        $format = $this->cfg->get("webhook_playerCommandFormat");
        $msg = str_replace("{cmd}", "/" . $message, str_replace("{time}",$time, str_replace("{player}",$player->getName(),$format)));

        $this->sendMessage($msg, $player->getName());
    }

    public function backFromAsync($player, $result){
        if($player === "nolog"){
            return;
        }
        elseif ($player === "CONSOLE"){
            $player = new ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage());
        }
        else{
            $playerinstance = $this->getServer()->getPlayerExact($player);
            if (!($playerinstance instanceof Player)){
                return;
            }else{
                $player = $playerinstance;
            }
        }
        if($result["success"]) {
            $player->sendMessage(C::AQUA."[MCPE->Discord] ".C::GREEN.$this->responsesdata["send_success"]);
        }
        else{
            $this->getLogger()->error(C::RED."Error: ".$result["Error"]);
            $player->sendMessage(C::AQUA."[MCPE->Discord]] ".C::GREEN.$this->responsesdata["send_fail"]);
        }
    }

    /**
     * @param $message
     */
    // Heavy thanks to NiekertDev !

    public function sendMessage(string $msg, string $player = "nolog"){
        if(!$this->enabled){
            return;
        }
        $name = $this->cfgdata["webhook_name"];
        $webhook = $this->cfgdata["webhook_url"];
		$cleanMsg = $this->cleanMessage($msg);
        $curlopts = [
	    "content" => $cleanMsg,
            "username" => $name
        ];

        if($cleanMsg === ""){
            $this->getLogger()->warning(C::RED."Warning: Empty message cannot be sent to discord.");
            return;
        }

        $this->getServer()->getAsyncPool()->submitTask(new tasks\SendAsync($player, $webhook, serialize($curlopts)));
		return;
    }
	
	public function cleanMessage(string $msg) : string{
		$banned = $this->cfgdata["banned_list"];
		return str_replace($banned,'',$msg); 
	}
}
