<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Main
 *
 * @author Salvatore
 */

namespace DuelsWorld;

use DuelsWorld\Commands\BanCommand;
use DuelsWorld\Commands\HubCommand;
use DuelsWorld\Commands\NickCommand;
use DuelsWorld\Commands\PingCommand;
use DuelsWorld\Commands\RekitCommand;
use DuelsWorld\Commands\ShopCommand;
use DuelsWorld\Commands\SpectateCommand;
use DuelsWorld\Commands\StaffModeCommand;
use DuelsWorld\Commands\TokenCommand;
use DuelsWorld\duels\DuelManager;
use DuelsWorld\Entity\EnderPearl;
use DuelsWorld\Entity\SplashPotion;
use DuelsWorld\Events\Event;
use DuelsWorld\Events\EventCommand;
use DuelsWorld\Events\EventManagement;
use DuelsWorld\Events\EventSetup;
use DuelsWorld\Ranks\Rank;
use DuelsWorld\Ranks\RankListener;
use DuelsWorld\Ranks\RankSession;
use DuelsWorld\Ranks\SetRankCommand;
use DuelsWorld\Task\LeaderboardTask;
use DuelsWorld\Task\PlayerTask;
use DuelsWorld\Task\TaskMain;
use DuelsWorld\utils\ScoreboardUtils;
use MongoDB\Driver\Session;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\data\bedrock\PotionTypeIdMap;
use pocketmine\data\bedrock\PotionTypeIds;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Skin;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\permission\PermissionAttachment;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

class Main extends PluginBase {

    /**
     * @var Kits
     */
    protected static Kits $kits;

    /**
     * @var self
     */
    protected static Main $instance;

    //put your code here
    const PREFIX = "§l§8Duels§7World§r";
    public static array $savedPerms = [];

    public $FormAPI = null;
    public $game = array();

    /** @var Config $arenas_config */
    public $arenas_config;

    /** @var Config $config */
    public $config;
    public $arenas;
    public $api;
    public $openedForm = array();

    /**
     * @var ScoreboardUtils
     */
    protected static ScoreboardUtils $scoreboards;

    /**
     * @var Config
     */
    public Config $database;

    /**
     * @var Config
     */
    public Config $punishments;

    /**
     * @var Config
     */
    public Config $other;

    public Config $skinData;

    /**
     * @var FloatingTextParticle
     */
    public FloatingTextParticle $killsLeaderboard;

    /**
     * @var FloatingTextParticle
     */
    public FloatingTextParticle $deathsLeaderboard;

    /**
     * @var FloatingTextParticle
     */
    public FloatingTextParticle $perPlayerText;

    /**
     * @var FloatingTextParticle
     */
    public FloatingTextParticle $topelo;

    /**
     * @var FloatingTextParticle
     */
    public FloatingTextParticle $topkillstreak;

    /**
     * @var array
     */
    public array $staffMode = [];

    /**
     * @var array
     */
    public array $frozen = [];

    /**
     * @var array
     */
    public array $pcooldown = [];

    /**
     * @var array
     */
    public array $combatTag = [];

    /**
     * @var array
     */
    public array $clicks = [];

    /**
     * @var array
     */
    public array $lastHit = [];

    public static array $ffaArenas = [
        "NoDebuff", "Gapple", "Sumo"
    ];
    public static array $duelArenas = [];
    public static array $eventArenas = [];

    private Config $tokenData;
    private Config $tagData;
    private Config $purchasedTagData;
    private Config $purchasedCapeData;
    private Config $eventData;
    private Config $invEventData;
    private Config $rankData;
    /** @var PermissionAttachment[] */
    private array $permission = [];
    private string $sessionFolder;
    private array $sessions = [];
    /** @var Rank[] */
    public array $ranks;
    private Config $deathTagData;
    private Config $purchasedDeathTagData;
    private DuelManager $duelManager;

    public static function stripExtension(string $filename){
        return preg_replace('/.[^.]*$/', '', $filename);
    }

    /**
     * @return Config
     */
    public function getRankData(): Config
    {
        return $this->rankData;
    }

    public function onLoad(): void
    {
        self::$instance = $this;
    }

    /**
     * @return DuelManager
     */
    public function getDuelManager(): DuelManager
    {
        return $this->duelManager;
    }

    public function onEnable(): void {
        $capes = [
            "Calvin.png",
            "RKY.png",
            "SammyGreen.png",
            "SpeedSilver.png",
            "Stipmy.png",
            "Vampire+.png",
            "Vampire.png",
            "Wisp.png",
            "Witch.png",
            "Wolf.png"
        ];
        
        foreach($capes as $cape){
            $this->saveResource($cape);
        }

        if(!InvMenuHandler::isRegistered()) InvMenuHandler::register($this);

        @mkdir(($this->sessionFolder = $this->getDataFolder() . "sessions/"));

        $this->saveResource("ranks.yml");
        $this->rankData = new Config($this->getDataFolder() . "ranks.yml", Config::YAML);
        foreach($this->rankData->getAll() as $rankName => $data){
            $this->ranks[$rankName] = new Rank($rankName,$data["chat-format"], $data["permissions"], $data["nametag"], isset($data["default"]) ? $data["default"] : false, isset($data["tpk"]) ? $data["tpk"] : 1);
        }

        foreach(array_diff(scandir($this->sessionFolder),[".", ".."]) as $pathName){
            $data = yaml_parse_file($this->sessionFolder . $pathName);
            $session = new RankSession(self::stripExtension($pathName));
            $session->setRank($this->getRank($data["rank"]));
            $this->sessions[self::stripExtension($pathName)] = $session;
        }

        $this->duelManager = new DuelManager();

        $this->getServer()->getPluginManager()->registerEvents(new RankListener(),$this);
        self::$kits = new Kits();
        $this->getServer()->getPluginManager()->registerEvents(new ListenerDuelsWorld($this), $this);
        $this->getScheduler()->scheduleRepeatingTask(new LeaderboardTask(), 20 * 20);//CHANGE THE COOLDOWN! (done)
        $this->getLogger()->info("DUELS ONLINE");
        $this->saveResource("arenas.yml");
        $this->arenas_config = new Config($this->getDataFolder() . "arenas.yml", Config::YAML);
        $this->arenas = $this->arenas_config->getAll()["arenas"];
        
        foreach($this->arenas as $nameWorld => $data){
            self::$duelArenas[] = $nameWorld;
        }

        $this->saveResource("config.yml");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        
        CreativeInventory::reset();
        
        EntityFactory::getInstance()->register(SplashPotion::class, function(World $world, CompoundTag $nbt) : SplashPotion{
			$potionType = PotionTypeIdMap::getInstance()->fromId($nbt->getShort("PotionId", PotionTypeIds::WATER));
			if($potionType === null){
				throw new SavedDataLoadingException("No such potion type");
			}
			return new SplashPotion(EntityDataHelper::parseLocation($nbt, $world), null, $potionType, $nbt);
		}, ['ThrownPotion', 'minecraft:potion', 'thrownpotion'], EntityLegacyIds::SPLASH_POTION);
        
        EntityFactory::getInstance()->register(EnderPearl::class, function(World $world, CompoundTag $nbt) : EnderPearl{
			return new EnderPearl(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, ['ThrownEnderpearl', 'minecraft:ender_pearl'], EntityLegacyIds::ENDER_PEARL);

        for($i = 37; $i <= 42; $i++) {
            CreativeInventory::getInstance()->remove(ItemFactory::getInstance()->get(ItemIds::SPLASH_POTION, $i));
        }

        $this->FormAPI = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        
        if ($this->FormAPI == null) {
            $this->getServer()->getLogger()->info(self::PREFIX . " FormAPI non è stato trovato");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }

        $this->getServer()->getLogger()->info(self::PREFIX . "§7§l Il plugin è stato avviato con successo dipendenze caricate al 100%");

        $this->api = new Managment($this);
        $this->api->startMatchs();

        $task = new TaskMain($this);
        $this->getScheduler()->scheduleRepeatingTask($task, 22);

        $this->getServer()->getWorldManager()->loadWorld("Gapple");
        $this->getServer()->getWorldManager()->loadWorld("NoDebuff");
        $this->getServer()->getWorldManager()->loadWorld("Sumo");

        $this->killsLeaderboard = new FloatingTextParticle("");
        $this->deathsLeaderboard = new FloatingTextParticle("");
        $this->perPlayerText = new FloatingTextParticle("");
        $this->topelo = new FloatingTextParticle("");

        $this->saveResource("config.yml");

        self::$scoreboards = new ScoreboardUtils();
        $this->database = new Config($this->getDataFolder() . "PlayerData.yml", Config::YAML);
        $this->other = new Config($this->getDataFolder() . "Other.yml", Config::YAML);
        $this->punishments = new Config($this->getDataFolder() . "PunishmentData.yml", Config::YAML);
        $this->skinData = new Config($this->getDataFolder() . "SkinData.yml", Config::YAML);
        $this->tokenData = new Config($this->getDataFolder() . "tokenData.yml", Config::YAML);
        $this->tagData = new Config($this->getDataFolder() . "tagData.yml", Config::YAML);
        $this->purchasedTagData = new Config($this->getDataFolder() . "purchasedtagdata.yml", Config::YAML);
        $this->purchasedCapeData = new Config($this->getDataFolder() . "purchasedcapedata.yml", Config::YAML);
        $this->eventData = new Config($this->getDataFolder() . "eventdata.yml", Config::YAML);
        $this->invEventData = new Config($this->getDataFolder() . "invEventData.yml", Config::YAML);
        $this->deathTagData = new Config($this->getDataFolder() . "deathTagData.yml",Config::YAML);
        $this->purchasedDeathTagData = new Config($this->getDataFolder() . "purchaseddeathtagdata.yml",Config::YAML);

        if (!$this->other->exists("serverinfo")) {
            $this->other->set("serverinfo", ["new-players-joined" => 0]);
            $this->other->save();
        }
        $this->loadcommands();

        $this->getScheduler()->scheduleRepeatingTask(new PlayerTask(), 20);

        $this->getServer()->getPluginManager()->registerEvents(new EventSetup(),$this);
        $this->getServer()->getPluginManager()->registerEvents(new EventManagement(),$this);

        //$this->getServer()->getCommandMap()->register("arena", new ArenaCommand($this));
    }

    public function getDeathTag(string $player): string{
        return strval($this->deathTagData->get($player) ?? "Killed");
    }

    public function setDeathTag(string $player,string $deathTag): void{
        $this->deathTagData->set($player, $deathTag);
        $this->deathTagData->save();
    }

    public function purchaseDeathTag(string $player, string $deathTag): void{
        if(!$this->purchasedDeathTagData->exists($player)){
            $this->purchasedDeathTagData->set($player,[]);
            $this->purchasedDeathTagData->save();
        }
        $data = $this->purchasedDeathTagData->get($player);
        $data[] = $deathTag;
        $this->purchasedDeathTagData->set($player,$data);
        $this->purchasedDeathTagData->save();
    }

    public function hasPurchasedDeathTag(string $player, string $deathTag): bool{
        if($this->purchasedDeathTagData->exists($player)){
            $data = $this->purchasedDeathTagData->get($player);
            if(in_array($deathTag,$data)){
                return true;
            }
        }
        return false;
    }

    public function getSessionPath(): string{
        return $this->sessionFolder;
    }

    public static function translate(string $chatFormat, array $search = [], array $replace = []): string{
        return str_replace($search, $replace, $chatFormat);
    }

    public function getSession(string $player): RankSession{
        if(!isset($this->sessions[$player])){
            $session = new RankSession($player);
            $session->setRank($this->getDefaultRank());
            $this->sessions[$player] = $session;
        }
        return $this->sessions[$player];
    }

    public function hasSession(string $player): bool{
        return isset($this->sessions[$player]);
    }

    public function getRank(string $name): ?Rank{
        if(!isset($this->ranks[$name])){
            return $this->getDefaultRank();
        }
        return $this->ranks[$name];
    }

    public function getDefaultRank(): Rank{
        foreach($this->ranks as $rank){
            if($rank->default === true){
                return $rank;
            }
        }
        return $this->ranks[array_rand($this->ranks)];
    }

    public function getPermission(Player $player): PermissionAttachment{
        if(!isset($this->permission[$player->getName()])){
            $this->permission[$player->getName()] = $player->addAttachment($this);
        }
        return $this->permission[$player->getName()];
    }

    public function loadcommands(){
        $this->getServer()->getCommandMap()->register($this->getName(), new RekitCommand($this));
        $this->getServer()->getCommandMap()->register($this->getName(), new HubCommand($this));
        $this->getServer()->getCommandMap()->register($this->getName(), new BanCommand($this));
        $this->getServer()->getCommandMap()->register($this->getName(), new StaffModeCommand($this));
        $this->getServer()->getCommandMap()->register($this->getName(), new ShopCommand());
        $this->getServer()->getCommandMap()->register($this->getName(), new EventCommand());
        $this->getServer()->getCommandMap()->register($this->getName(), new TokenCommand());
        $this->getServer()->getCommandMap()->register($this->getName(), new SetRankCommand());
        $this->getServer()->getCommandMap()->register($this->getName(), new PingCommand());
        $this->getServer()->getCommandMap()->register($this->getName(), new SpectateCommand());
        $this->getServer()->getCommandMap()->register($this->getName(), new NickCommand());
    }

    public function addEvent(string $name, string $pos1, string $pos2, string $spawn): void{
        $this->eventData->set($name, [
            "pos1" => $pos1,
            "pos2" => $pos2,
            "spawn" => $spawn
        ]);
        $this->eventData->save();
    }

    public function getEvent(string $name): ?Event{
        if(!isset($this->getEvents()[$name])){
            return null;
        }
        return new Event(
            $name,
            $this->translateToPos($this->eventData->get($name)["spawn"]),
            $this->translateToPos($this->eventData->get($name)["pos1"]),
            $this->translateToPos($this->eventData->get($name)["pos2"])
        );
    }

    public function translateToPos(string $data): Position{
        $exp = explode(":", $data);
        return new Position(intval($exp[0]), intval($exp[1]), intval($exp[2]), Server::getInstance()->getWorldManager()->getWorldByName(
            strval($exp[3])
        ));
    }

    public function getInvContents(string $name): array{
        $data = $this->invEventData->get($name);
        foreach($data as $key => $value){
            $data[$key] = Item::jsonDeserialize($value);
        }
        return $data;
    }

    public function setKitContentsToFile(string $name, array $contents): void{
        foreach($contents as $key => $value){
            /** @var Item $value */
            $contents[$key] = $value->jsonSerialize();
        }
        $this->invEventData->set($name, $contents);
        $this->invEventData->save();
    }

    public function removeEvent(string $name): void{
        $this->eventData->remove($name);
        $this->eventData->save();
    }

    public function getEvents(): array{
        return $this->eventData->getAll();
    }

    public static function getSkinFromPNG(string $path): string{
        $img = @imagecreatefrompng($path);
        $skinbytes = "";
        $s = (int)@getimagesize($path)[1];
        for($y = 0; $y < $s; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $colorat = @imagecolorat($img, $x, $y);
                $a = ((~((int)($colorat >> 24))) << 1) & 0xff;
                $r = ($colorat >> 16) & 0xff;
                $g = ($colorat >> 8) & 0xff;
                $b = $colorat & 0xff;
                $skinbytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($img);
        return $skinbytes;
    }

    public function setCape(Player $player, string $capePath): void{
        $skin = new Skin($player->getSkin()->getSkinId(), $player->getSkin()->getSkinData(),
            self::getSkinFromPNG($this->getDataFolder() . $capePath));
        $player->setSkin($skin);
        $player->sendSkin(null);
    }

    public function hasPurchasedCape(string $player, string $capePath): bool{
        if($this->purchasedCapeData->exists($player)){
            $data = $this->purchasedCapeData->get($player);
            if(in_array($capePath,$data)){
                return true;
            }
        }
        return false;
    }

    public function purchasedCape(string $player, string $cape): void{
        if(!$this->purchasedCapeData->exists($player)){
            $this->purchasedCapeData->set($player, []);
            $this->purchasedCapeData->save();
        }
        $data = $this->purchasedCapeData->get($player);
        $data[] = $cape;
        $this->purchasedCapeData->set($player, $data);
        $this->purchasedCapeData->save();
    }

    public function purchasedTag(string $player, string $tag): void{
        if(!$this->purchasedTagData->exists($player)){
            $this->purchasedTagData->set($player, []);
            $this->purchasedTagData->save();
        }
        $data = $this->purchasedTagData->get($player);
        $data[] = $tag;
        $this->purchasedTagData->set($player, $data);
        $this->purchasedTagData->save();
    }

    public function hasPurchasedTag(string $player, string $tag): bool{
        if($this->purchasedTagData->exists($player)){
            $data = $this->purchasedTagData->get($player);
            if(in_array($tag, $data)) {
                return true;
            }
        }
        return false;
    }

    public function getTag(string $player): string{
        if(!$this->tagData->exists($player)){
            return "";
        }
        return strval($this->tagData->get($player));
    }

    public function setTag(string $player, string $tag): void{
        $this->tagData->set($player,$tag);
        $this->tagData->save();
    }

    public function getTokens(string $player): int{
        if(!$this->tokenData->exists($player)){
            return 0;
        }
        return intval($this->tokenData->get($player));
    }

    public function addTokensAccordingly(Player $player): void{
//        if(in_array($player->getLevel()->getFolderName(), self::$duelArenas)) {
//            $this->tokenData->set($player->getName(), $this->getTokens($player->getName()) + 5);
//        }
//        if(in_array($player->getLevel()->getFolderName(), self::$ffaArenas)) {
//            $this->tokenData->set($player->getName(), $this->getTokens($player->getName()) + 1);
//        }
        $this->tokenData->set($player->getName(), self::getInstance()->getSession($player->getName())->getRank()->tpk);
        $this->tokenData->save();
    }

    public function addTokens(string $player, int $tokens): void{
        $this->tokenData->set($player, $this->getTokens($player) + $tokens);
        $this->tokenData->save();
    }

    public function getPlayer(string $name) {
        return $this->getServer()->getPlayerByPrefix($name) ?? $this->getServer()->getOfflinePlayer($name);
    }

    public function removeTokens(string $player, int $tokens): void{
        $amount = $this->getTokens($player) - $tokens;
        if($amount <= 0){
            $amount = 0;
        }
        $this->tokenData->set($player, $amount);
        $this->tokenData->save();
    }


    public function getApi(): Managment {
        return $this->api;
    }

    public function getArenasConfig() {

        return $this->arenas_config->getAll()["arenas"];
    }

    /**
     * @return static
     */
    public static function getInstance() : self {
        return self::$instance;
    }

    /**
     * @return Kits
     */
    public static function getKits() : Kits {
        return self::$kits;
    }

    public function getDefaultConfig() {
        return $this->config->getAll();
    }

    public function onDisable(): void {
        $this->getLogger()->info(self::PREFIX . " il server verrà spento");
    }

    /**
     * @return ScoreboardUtils
     */
    public static function getScoreboardManager() : ScoreboardUtils {
        return self::$scoreboards;
    }

    /**
     * @param Player $player
     */
    public function updateFT(Player $player) : void {
        $this->perPlayerText->setTitle("§l§bNA Practice!\n\n\n§r§fWelcome! §b".$player->getName()." §fTo §aSilex Practice!\n§fHere Are Your Current Statistics!\n\n§fK: §b".$this->getKills($player)." §fD: §b".$this->getDeaths($player)."\n§fKillstreak: §b".$this->getKillstreak($player)."\n§fBest Killstreak: §b".$this->getBestKillstreak($player)."\n\n§bHope You Enjoy Your Time On Silex Practice!");
        $this->getServer()->getWorldManager()->getWorldByName("Hub")->addParticle(new Vector3(-224, 47, -427), $this->perPlayerText, [$player]);
    }

    /**
     * @param Player $player
     */
    public function updateNametag(Player $player) : void {
        $player->setNameTag(TextFormat::GREEN.$player->getName()."\n".TextFormat::RED."♥".round($player->getHealth()).TextFormat::GRAY." | ".TextFormat::YELLOW."CPS".TextFormat::DARK_GRAY.": ".TextFormat::LIGHT_PURPLE.$this->getCps($player)." | ".TextFormat::YELLOW."Kills ".$this->getKills($player));
    }


    public static function setStaffMode($player, $type) {
        switch($type) {
            case "on":
                $player->getInventory()->clearAll();
                $player->getArmorInventory()->clearAll();
                $player->setGamemode(3);
                $player->getInventory()->setItem(0, ItemFactory::getInstance()->get(ItemIds::WOOL)->setCustomName("§r§bRandom Teleport"));
                $player->getInventory()->setItem(1, ItemFactory::getInstance()->get(ItemIds::PACKED_ICE)->setCustomName("§r§bFreeze Player"));
                $player->getInventory()->setItem(2, ItemFactory::getInstance()->get(ItemIds::COMPASS)->setCustomName("§r§Ban Player"));
                $player->getInventory()->setItem(3, ItemFactory::getInstance()->get(ItemIds::LADDER)->setCustomName("§r§bKick Player"));
                break;
            case "off":
                Main::getKits()->sendLobbyItem($player);
                break;

        }
    }
    
        public function sendMessageToStaff(string $message) : void {
        foreach($this->getServer()->getOnlinePlayers() as $player) {
            if($this->getServer()->isOp($player->getName())){
            $player->sendMessage($message);
            }
        }
    }

    /**
     * @param Player $player
     * @param string $reason
     * @param string $banner
     */
    public function setBanned(Player $player, string $reason, string $banner) : void {
        $this->punishments->setNested(strtolower($player->getName()).".banned", true);
        $this->punishments->save();
        $this->punishments->setNested(strtolower($player->getName()).".ban-reason", $reason);
        $this->punishments->save();
        $this->getServer()->broadcastMessage("§fThe Player §c".$player->getName()." §fHas Been Banned By §c".$banner."\n§fReason: §c".$reason);
        $player->kick("§cYou Are Now §lBANNED!\n§r§cReason: ".$reason, false);
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function isBanned(Player $player) : bool {
        return $this->punishments->get(strtolower($player->getName()))["banned"];
    }

    /**
     * @param Player $player
     * @param string $reason
     * @param string $kicker
     */
    public function setKicked(Player $player, string $reason, string $kicker) {
        $this->getServer()->broadcastMessage("§fThe Player §c".$player->getName()." §fHas Been Kicked By §c".$kicker."\n§fReason: §c".$reason);
        $player->kick("§cYou Have Been Kicked!\n§r§cReason: ".$reason, false);
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function hasAccount(Player $player) : bool {
        return $this->database->exists(strtolower($player->getName()));
    }

    /**
     * @param Player $player
     */
    public function createAccount(Player $player) : void {
        $this->database->set(strtolower($player->getName()), ["kills" => 0, "deaths" => 0, "killstreak" => 0, "best-killstreak" => 0, "elo" => 1000]);
        $this->database->save();
        $this->other->setNested("serverinfo.new-players-joined", $this->other->get("serverinfo")["new-players-joined"] + 1);
        $this->other->save();
        $this->punishments->setNested(strtolower($player->getName()).".banned", false);
        $this->punishments->save();
        $this->punishments->setNested(strtolower($player->getName()).".ban-reason", "");
        $this->punishments->save();
        $this->getServer()->broadcastMessage("§2+ §l§aNEW! §r§a".$player->getName(). " §7(§5#".Main::getInstance()->other->get("serverinfo")["new-players-joined"]."§7).");
    }


    /**
     * @param Player $player
     * @return int
     */
    public function getKills(Player $player) : int {
        if($this->database->get(strtolower($player->getName()))["kills"] == 0) return 0;
        return $this->database->get(strtolower($player->getName()))["kills"];
    }

        /**
         * @param Player $player
         * @return int
         */
    public function getELO(Player $player) : int {
        if($this->database->get(strtolower($player->getName()))["elo"] == 0) return 0;
        return $this->database->get(strtolower($player->getName()))["elo"];
    }

    /**
     * @param Player $player
     * @return int
     */
    public function getKillstreak(Player $player) : int {
        return $this->database->get(strtolower($player->getName()))["killstreak"];
    }

    public function restartKillstreak(Player $player): void{
        $this->database->setNested(strtolower($player->getName()) . ".killstreak", 0);
        $this->database->save();
    }

    /**
     * @param Player $player
     * @return int
     */
    public function getBestKillstreak(Player $player) : int {
        return $this->database->get(strtolower($player->getName()))["best-killstreak"];
    }

    /**
     * @param Player $player
     */
    public function addKill(Player $player) : void {
        $this->database->setNested(strtolower($player->getName()). ".kills", $this->getKills($player) + 1);
        $this->database->save();
        $this->database->setNested(strtolower($player->getName()). ".killstreak", $this->getKillstreak($player) + 1);
        $this->database->save();
        if ($this->getBestKillstreak($player) < $this->getKillstreak($player)) $this->database->setNested(strtolower($player->getName()). ".best-killstreak", $this->getKillstreak($player));
        $this->database->save();
    }

        /**
         * @param Player $player
         */
    public function addelo(Player $player) : void {
        $randomelo = array_rand(["4", "9", "12", "15"]);
        $this->database->setNested(strtolower($player->getName()). ".elo", $this->getELO($player) + $randomelo);
        $this->database->save();
        $player->sendMessage(TextFormat::GRAY . "-----------------------------");
        $player->sendMessage(TextFormat::GREEN . "You`ve gained $randomelo elo");
        $player->sendMessage(TextFormat::GRAY . "-----------------------------");
    }

    /**
     * @param Player $player
     */
    public function removeelo(Player $player) : void {
        $randomelo = array_rand(["4", "9", "12", "15"]);
        $this->database->setNested(strtolower($player->getName()). ".elo", $this->getELO($player) - $randomelo);
        $this->database->save();
        $player->sendMessage(TextFormat::GRAY . "-----------------------------");
        $player->sendMessage(TextFormat::RED  .  "You`ve lost $randomelo elo");
        $player->sendMessage(TextFormat::GRAY . "-----------------------------");
    }


    /**
     * @return string
     */
    public function getKillsLeaderboard() : string {
        $array = [];
        for ($i=0;$i<count($this->database->getAll());$i++) {
            $b = $this->database->getAll(true)[$i];
            if (empty($this->database->get($b)["kills"])) continue;
            $array[$this->database->getAll(true)[$i]] = $this->database->get($b)["kills"];
        }
        arsort($array);
        $string = "§bTop Kills Overall.\n";
        $num = 1;
        foreach($array as $name => $kills) {
            if ($num > 10) break;
            $string .= "§7{$num}§f. {$name}§7: §b{$kills}\n";
            $num++;
        }
        return $string;
    }


    /**
     * @return string
     */
    public function geteloLeaderboard() : string {
        $array = [];
        for ($i=0;$i<count($this->database->getAll());$i++) {
            $b = $this->database->getAll(true)[$i];
            if (empty($this->database->get($b)["elo"])) continue;
            $array[$this->database->getAll(true)[$i]] = $this->database->get($b)["elo"];
        }
        arsort($array);
        $string = "§bTop Elo Overall.\n";
        $num = 1;
        foreach($array as $name => $kills) {
            if ($num > 10) break;
            $string .= "§7{$num}§f. {$name}§7: §b{$kills}\n";
            $num++;
        }
        return $string;
    }

    /**
     * @param int $int
     * @return string
     */
    public static function intToString(int $int) : string {
        $m = floor($int / 60);
        $s = floor($int % 60);
        return (($m < 10 ? "0" : "").$m.":".((float)$s < 10 ? "0" : "").(float)$s);

    }

    /**
     * @return string
     */
    public function getDeathsLeaderboard() : string {
        $array = [];
        for ($i=0;$i<count($this->database->getAll());$i++) {
            $b = $this->database->getAll(true)[$i];
            if (empty($this->database->get($b)["deaths"])) continue;
            $array[$this->database->getAll(true)[$i]] = $this->database->get($b)["deaths"];
        }
        arsort($array);
        $string = "§bTop Deaths Overall.\n";
        $num = 1;
        foreach($array as $name => $kills) {
            if ($num > 10) break;
            $string .= "§7{$num}§f. {$name}§7: §b{$kills}\n";
            $num++;
        }
        return $string;
    }

    /**
     * @return string
     */
    public function gettopkillstreaks() : string {
        $array = [];
        for ($i=0;$i<count($this->database->getAll());$i++) {
            $b = $this->database->getAll(true)[$i];
            if (empty($this->database->get($b)["best-killstreak"])) continue;
            $array[$this->database->getAll(true)[$i]] = $this->database->get($b)["best-killstreak"];
        }
        arsort($array);
        $string = "§bTop Killstreaks overall.\n";
        $num = 1;
        foreach($array as $name => $kills) {
            if ($num > 10) break;
            $string .= "§7{$num}§f. {$name}§7: §b{$kills}\n";
            $num++;
        }
        return $string;
    }


    /**
     * @param Player $player
     * @return int
     */
    public function getDeaths(Player $player) : int {
        if ($this->database->get(strtolower($player->getName()))["deaths"] == 0) return 0;
        return $this->database->get(strtolower($player->getName()))["deaths"];
    }

    /**
     * @param Player $player
     */
    public function addDeath(Player $player) : void {
        $this->database->setNested(strtolower($player->getName()). ".deaths", $this->getDeaths($player) + 1);
        $this->database->save();
    }


    /**
     * @param $player
     * @return bool
     */
    public function isInArray($player):bool{
        $name=$player->getName();
        return ($name !== null) and isset($this->clicks[$name]);
    }

    /**
     * @param Player $player
     */
    public function addToArray(Player $player){
        if(!$this->isInArray($player)){
            $this->clicks[$player->getName()]=[];
        }
    }

    /**
     * @param Player $player
     */
    public function removeFromArray(Player $player){
        if($this->isInArray($player)){
            unset($this->clicks[$player->getName()]);
        }
    }

    /**
     * @param Player $player
     */
    public function addClick(Player $player){
        array_unshift($this->clicks[$player->getName()], microtime(true));
        if(count($this->clicks[$player->getName()]) >= 100){
            array_pop($this->clicks[$player->getName()]);
        }
        $player->sendTip("§b".$this->getCps($player));
    }

    /**
     * @param Player $player
     * @param float $deltaTime
     * @param int $roundPrecision
     * @return float
     */
    public function getCps(Player $player, float $deltaTime=1.0, int $roundPrecision=1):float{
        if(!$this->isInArray($player) or empty($this->clicks[$player->getName()])){
            return 0.0;
        }
        $mt=microtime(true);
        return round(count(array_filter($this->clicks[$player->getName()], static function(float $t) use ($deltaTime, $mt):bool{
                return ($mt - $t) <= $deltaTime;
            })) / $deltaTime, $roundPrecision);
    }

}
