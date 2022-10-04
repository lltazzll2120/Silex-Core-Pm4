<?php

namespace DuelsWorld\Commands;

//use czechpmdevs\multiworld\form\CustomForm;
use DuelsWorld\Main;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ShopCommand extends Command implements PluginOwned {

    const HAS_PURCHASED = 0;
    const HASNT_PURCHASED = 1;

    public static array $tags = [];

    public function __construct(){
        parent::__construct("shop", "Shop Command.", null, []);
        $this->setPermission("shop.command");
    }

    public function getOwningPlugin(): MaiN {
		return Main::getInstance();
	}

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Use this command in-game");
            return;
        }
        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->setName(TextFormat::GREEN . "Your Tokens: " . TextFormat::WHITE . Main::getInstance()->getTokens($sender->getName()));
        $inv = $menu->getInventory();
        $tags =  ItemFactory::getInstance()->get(ItemIds::MOB_HEAD,4);
        $capes =  ItemFactory::getInstance()->get(ItemIds::MOB_HEAD,5);
        $misc =  ItemFactory::getInstance()->get(ItemIds::MOB_HEAD,3);
        $deathTags =  ItemFactory::getInstance()->get(ItemIds::MOB_HEAD);
        $tags->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Tags");
        $capes->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "Capes");
        $misc->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Misc");
        $deathTags->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Death Tags");
        $inv->setItem(10,$capes);
        $inv->setItem(13,$misc);
        $inv->setItem(16,$tags);
        $inv->setItem(37,$deathTags);
        $menu->setListener(function(InvMenuTransaction $transaction) use ($sender): InvMenuTransactionResult{
            $itemClicked = $transaction->getItemClicked();
            if($itemClicked->getId() === ItemIds::MOB_HEAD){
                $itemname = $itemClicked->getName();
                $this->removeInventory($transaction);
                switch($itemname){
                    case TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Death Tags":
                        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
                        $menu->setName(TextFormat::GREEN . "Your Tokens: " . TextFormat::WHITE . Main::getInstance()->getTokens($sender->getName()));
                        $inv = $menu->getInventory();

                        $dtags = [
                            "§l§0S§1l§2a§3m§4m§5e§9d§r",
                            "§bObliterated§r",
                            "§cClap§4ped§r",
                            "§dSh*t §bon§r",
                            "§l§eSplit §r§eopened§r",
                            "§o§4Blended§r",
                            "§9Ripped §aapart§r",
                            "§bSplashed §6on§r"
                        ];
                        $new = [];
                        foreach($dtags as $dtag){
                            $new[] = TextFormat::RESET . TextFormat::colorize($dtag);
                        }

                        foreach($new as $newdtag){
                            /** @var \pocketmine\item\Item $sword */
                            $sword = ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD);
                            // $sword->setNamedTagEntry(new ListTag("ench",[]));
                            $sword->getNamedTag()->setTag("ench", new ListTag([]));
                            $sword->setCustomName($newdtag);
                            $hasPurchased = Main::getInstance()->hasPurchasedDeathTag($sender->getName(),$newdtag) ?
                            TextFormat::RESET . TextFormat::GREEN . "Purchased" : "";
                            $namedtagPurchased = Main::getInstance()->hasPurchasedDeathTag($sender->getName(),$newdtag) ?
                                self::HAS_PURCHASED : self::HASNT_PURCHASED;
                            $sword->setLore([
                                TextFormat::RESET . TextFormat::LIGHT_PURPLE . "Price: " . TextFormat::WHITE . "300 Tokens",
                                $hasPurchased
                            ]);
                            // $sword->setNamedTagEntry(new IntTag("purchased", $namedtagPurchased));
                            $sword->getNamedTag()->setInt("purchased", $namedtagPurchased);
                            $inv->addItem($sword);
                        }

                        $menu->setListener(function(InvMenuTransaction $transaction) use ($sender): InvMenuTransactionResult{
                            $itemClicked = $transaction->getItemClicked();
                            if($itemClicked->getNamedTag()->hasTag("purchased")){
                                $val = $itemClicked->getNamedTag()->getTag("purchased")->getValue();
                                $itemname = $itemClicked->getName();
                                if($val === self::HAS_PURCHASED){
                                    Main::getInstance()->setDeathTag($sender->getName(),$itemname);
                                    $this->removeInventory($transaction);
                                }
                                if($val === self::HASNT_PURCHASED){
                                    $price = 300;
                                    if(Main::getInstance()->getTokens($sender->getName()) < $price){
                                        $sender->sendMessage(TextFormat::RED . "You do not have enough tokens to purchase this item.");
                                        $this->removeInventory($transaction);
                                        return $transaction->discard();
                                    }
                                    Main::getInstance()->removeTokens($sender->getName(), $price);
                                    Main::getInstance()->setDeathTag($sender->getName(),$itemname);
                                    Main::getInstance()->purchaseDeathTag($sender->getName(),$itemname);
                                    $this->removeInventory($transaction);
                                }
                            }
                            return $transaction->discard();
                        });

                        $menu->send($sender);
                        break;
                    case TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Tags":
                        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
                        $menu->setName(TextFormat::GREEN . "Your Tokens: " . TextFormat::WHITE . Main::getInstance()->getTokens($sender->getName()));
                        $inv = $menu->getInventory();
                        $tags = [
                            "§bPot§6God",
                            "§l§cSilex",
                            "§1E§2z§3P§4z",
                            "§6Best§4§lWW",
                            "§7Gamer",
                            "§eClipped",
                            "§4Railed",
                            "§3ComboGod",
                            "§6Sumo§bGod"
                        ];
                        $new = [];
                        foreach($tags as $tag){
                            $new[] = TextFormat::RESET . TextFormat::colorize($tag);
                        }
                        $new[] = TextFormat::LIGHT_PURPLE . TextFormat::BOLD . TextFormat::ITALIC . "Custom Tag";
                        foreach($new as $newTag){
                            $paper =  ItemFactory::getInstance()->get(ItemIds::PAPER);
                            // $paper->setNamedTagEntry(new ListTag("ench", []));
                            $paper->getNamedTag()->setTag("ench", new ListTag([]));
                            $paper->setCustomName($newTag);
                            $hasPurchased = Main::getInstance()->hasPurchasedTag($sender->getName(),$newTag) ?
                                TextFormat::RESET . TextFormat::GREEN . "Purchased" : "";
                            $namedtagPurchased = Main::getInstance()->hasPurchasedTag($sender->getName(), $newTag) ? self::HAS_PURCHASED :
                                self::HASNT_PURCHASED;
                            // $paper->setNamedTagEntry(new IntTag("purchased", $namedtagPurchased));
                            $paper->getNamedTag()->setInt("purchased", $namedtagPurchased);
                            if(TextFormat::clean($newTag) !== "Custom Tag"){
                                $paper->setLore([
                                    TextFormat::RESET . TextFormat::LIGHT_PURPLE . "Price: " . TextFormat::WHITE . "1000 Tokens",
                                    $hasPurchased
                                ]);
                            }else{
                                $paper->setLore([
                                    TextFormat::RESET . TextFormat::LIGHT_PURPLE . "Price: " . TextFormat::WHITE . "1500 Tokens",
                                    $hasPurchased
                                ]);
                            }
                            $inv->addItem($paper);
                        }
                        $menu->setListener(function(InvMenuTransaction $transaction) use ($sender): InvMenuTransactionResult{
                            $itemClicked = $transaction->getItemClicked();
                            if($itemClicked->getNamedTag()->hasTag("purchased")){
                                $val = $itemClicked->getNamedTag()->getTag("purchased")->getValue();
                                if($val === self::HAS_PURCHASED){
                                    if($itemClicked->getId() === ItemIds::PAPER){
                                        $itemname = $itemClicked->getName();
                                        if(TextFormat::clean($itemname) !== "Custom Tag"){
                                            Main::getInstance()->setTag($sender->getName(), $itemname);
                                            $this->removeInventory($transaction);
                                        }else{
                                            $this->removeInventory($transaction);
                                            $sender->sendMessage(TextFormat::GREEN . "Enter your new tag in the chat. You are allowed to use color codes by using &");
                                            self::$tags[$sender->getName()] = $sender;
                                            return $transaction->discard();
                                        }
                                    }
                                }
                                if($val === self::HASNT_PURCHASED){
                                    if($itemClicked->getId() === ItemIds::PAPER){
                                        $itemname = $itemClicked->getName();
                                        if(TextFormat::clean($itemname) !== "Custom Tag"){
                                            $price = 1000;
                                        }else{
                                            $price = 1500;
                                        }
                                        if(Main::getInstance()->getTokens($sender->getName()) < $price){
                                            $sender->sendMessage(TextFormat::RED . "You do not have enough tokens to purchase this item.");
                                            $this->removeInventory($transaction);
                                            return $transaction->discard();
                                        }
                                        if(TextFormat::clean($itemname) === "Custom Tag"){
                                            $this->removeInventory($transaction);
                                            $sender->sendMessage(TextFormat::GREEN . "Enter your new tag in the chat. You are allowed to use color codes by using &");
                                            self::$tags[$sender->getName()] = $sender;
                                            return $transaction->discard();
                                        }
                                        Main::getInstance()->removeTokens($sender->getName(), $price);
                                        Main::getInstance()->setTag($sender->getName(),$itemname);
                                        Main::getInstance()->purchasedTag($sender->getName(),$itemname);
                                        $this->removeInventory($transaction);
                                    }
                                }
                            }
                            return $transaction->discard();
                        });
                        $menu->send($sender);

                        break;
                    case TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "Capes":
                        $sender->sendMessage(TextFormat::RED . "Coming soon!");
                        $this->removeInventory($transaction);
//                        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
//                        $menu->setName(TextFormat::GREEN . "Your Tokens: " . TextFormat::WHITE . Main::getInstance()->getTokens($sender->getName()));
//                        $inv = $menu->getInventory();
//                        $capes = [
//                            "Calvin",
//                            "RKY",
//                            "SammyGreen",
//                            "SpeedSilver",
//                            "Stipmy",
//                            "Vampire+",
//                            "Vampire",
//                            "Wisp",
//                            "Witch",
//                            "Wolf"
//                        ];
//                        foreach($capes as $cape){
//                            $paper =  ItemFactory::getInstance()->get(ItemIds::PAPER);
//                            $paper->setNamedTagEntry(new ListTag("ench", []));
//                            $paper->setCustomName(TextFormat::RESET . TextFormat::colorize($cape));
//                            $paper->setNamedTagEntry(new StringTag("capePage", TextFormat::clean($cape) . ".png"));
//                            $hasPurchased = Main::getInstance()->hasPurchasedCape($sender->getName(), $cape) ? self::HAS_PURCHASED :
//                                self::HASNT_PURCHASED;
//                            $paper->setNamedTagEntry(new IntTag("purchased", $hasPurchased));
//                            $addition = $hasPurchased === self::HAS_PURCHASED ? TextFormat::RESET . TextFormat::GREEN . "Purchased" : "";
//                            $paper->setLore([
//                                TextFormat::RESET . TextFormat::LIGHT_PURPLE . "Price: " . TextFormat::WHITE . "750 Tokens",
//                                $addition
//                            ]);
//                            $inv->addItem($paper);
//                        }
//                        $menu->setListener(function(InvMenuTransaction $transaction) use ($sender): InvMenuTransactionResult{
//                            $itemClicked = $transaction->getItemClicked();
//                            $val = $itemClicked->getNamedTag()->getTag("purchased")->getValue();
//                            if($val === self::HAS_PURCHASED){
//                                if($itemClicked->getNamedTag()->hasTag("capePage")) {
//                                    $capeValue = $itemClicked->getNamedTag()->getTag("capePage")->getValue();
//                                    Main::getInstance()->setCape($sender,$capeValue);
//                                    $this->removeInventory($transaction);
//                                }
//                            }
//                            if($val === self::HASNT_PURCHASED){
//                                $price = 750;
//                                if($itemClicked->getNamedTag()->hasTag("capePage")){
//                                    $capeValue = $itemClicked->getNamedTag()->getTag("capePage")->getValue();
//                                    if(Main::getInstance()->getTokens($sender->getName()) < $price){
//                                        $sender->sendMessage(TextFormat::RED . "You do not have enough tokens to purchase this item.");
//                                        $this->removeInventory($transaction);
//                                        return $transaction->discard();
//                                    }
//                                    Main::getInstance()->removeTokens($sender->getName(), $price);
//                                    Main::getInstance()->purchasedCape($sender->getName(),$capeValue);
//                                    Main::getInstance()->setCape($sender,$capeValue);
//                                    $sender->sendMessage(TextFormat::GREEN . "You have successfully bought this item!");
//                                    $this->removeInventory($transaction);
//                                }
//                            }
//                            return $transaction->discard();
//                        });
//                        $menu->send($sender);
                        break;
                    case TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Misc":
                        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
                        $inv = $menu->getInventory();
                        $paper =  ItemFactory::getInstance()->get(ItemIds::PAPER);
                        // $paper->setNamedTagEntry(new ListTag("ench", []));
                        $paper->getNamedTag()->setTag("ench", new ListTag([]));
                        $paper->setCustomName(TextFormat::GREEN . "$5 PayPal Voucher");
                        $paper->setLore([
                            TextFormat::RESET . TextFormat::LIGHT_PURPLE . "Price: " . TextFormat::WHITE . "10,000 Tokens",
                        ]);
                        $inv->addItem($paper);
                        $menu->setListener(function (InvMenuTransaction $transaction) use ($sender): InvMenuTransactionResult{
                            $itemClicked = $transaction->getItemClicked();
                            if($itemClicked->getId() === ItemIds::PAPER){
                                $price = 10000;
                                if(Main::getInstance()->getTokens($sender->getName()) < $price){
                                    $sender->sendMessage(TextFormat::RED . "You do not have enough tokens to purchase this item.");
                                    $this->removeInventory($transaction);
                                    return $transaction->discard();
                                }
                                Main::getInstance()->removeTokens($sender->getName(), $price);
                                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(Main::getInstance()->getServer(), Main::getInstance()->getServer()->getLanguage()), "tell {$sender->getName()} Message 
                                PackPorted#9558 to claim on Discord! Take a screenshot of this message. Code: " . uniqid($sender->getName() . time()));
                                $this->removeInventory($transaction);
                            }
                            return $transaction->discard();
                        });
                        $menu->send($sender);
                        break;
                }
            }
            return $transaction->discard();
        });
        $menu->send($sender);
    }

    public function removeInventory(InvMenuTransaction $transaction): void{
        /** @var Player $player */
        $player = $transaction->getPlayer();
        $player->removeCurrentWindow();
        // $transaction->getPlayer()->removeWindow($transaction->getAction()->getInventory());
    }

}