<?php
namespace DuelsWorld;

use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

class Kits {

	/**
	 * @param Player $player
	 */
	public function giveNoDebuffKit(Player $player) : void {
		$player->setAllowFlight(false);
		$player->setGamemode(GameMode::SURVIVAL());
		$player->getHungerManager()->setFood(20);
		$player->setHealth(20);
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$helmet = ItemFactory::getInstance()->get(310, 0, 1);
		$helmet->setCustomName("§r§l§cNoDebuff");
		$helmet->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(17), 10));
		$player->getArmorInventory()->setHelmet($helmet);
		$chestplate = ItemFactory::getInstance()->get(311, 0, 1);
		$chestplate->setCustomName("§r§l§cNoDebuff");
		$chestplate->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(17), 10));
		$player->getArmorInventory()->setChestplate($chestplate);
		$leggings = ItemFactory::getInstance()->get(312, 0, 1);
		$leggings->setCustomName("§r§l§cNoDebuff");
		$leggings->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(17), 10));
		$player->getArmorInventory()->setLeggings($leggings);
		$boots = ItemFactory::getInstance()->get(313, 0, 1);
		$boots->setCustomName("§r§l§cNoDebuff");
		$boots->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(17), 10));
		$player->getArmorInventory()->setBoots($boots);
		$sword = ItemFactory::getInstance()->get(276, 0, 1);
		$sword->setCustomName("§r§l§cNoDebuff");
		$sword->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(17), 10));
		$player->getInventory()->setItem(0, $sword);
		$player->getInventory()->setItem(1, ItemFactory::getInstance()->get(368, 0, 16));
		$player->getInventory()->addItem(ItemFactory::getInstance()->get(438, 22, 34));
        $effect = new EffectInstance(VanillaEffects::SPEED(), 999999 * 5, 0);
        $player->getEffects()->add($effect);
	}

    public function giveCombo(Player $p)
    {
        $effect = new EffectInstance(VanillaEffects::SPEED(), 999999 * 5, 0);
        $p->getEffects()->add($effect);
        $p->getArmorInventory()->clearAll();
        $p->getInventory()->clearAll();
        $sword = ItemFactory::getInstance()->get(ItemIds::COOKED_BEEF, 0, 16);
        $helmet = ItemFactory::getInstance()->get(ItemIds::IRON_HELMET, 0, 1);
        $chest = ItemFactory::getInstance()->get(ItemIds::IRON_CHESTPLATE, 0, 1);
        $thicklegs = ItemFactory::getInstance()->get(ItemIds::IRON_LEGGINGS, 0, 1);
        $footfungus = ItemFactory::getInstance()->get(ItemIds::IRON_BOOTS, 0, 1);
        $p->getInventory()->addItem($sword);
        $p->getArmorInventory()->setHelmet($helmet);
        $p->getArmorInventory()->setChestplate($chest);
        $p->getArmorInventory()->setLeggings($thicklegs);
        $p->getArmorInventory()->setBoots($footfungus);
        $p->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 999999, 0));

    }

    public function giveFist(Player $player)
    {
        $effect = new EffectInstance(VanillaEffects::SPEED(), 999999 * 5, 0);
        $player->getEffects()->add($effect);
        $player->getArmorInventory()->clearAll();
        $player->getInventory()->clearAll();
        $sword = ItemFactory::getInstance()->get(ItemIds::COOKED_BEEF, 0, 16);
        $player->getInventory()->addItem($sword);
    }

    public function giveSumo(Player $player)
    {
        $effect = new EffectInstance(VanillaEffects::SPEED(), 999999 * 5, 0);
        $player->getEffects()->add($effect);
        $player->getArmorInventory()->clearAll();
        $player->getInventory()->clearAll();
        $sword = ItemFactory::getInstance()->get(ItemIds::COOKED_BEEF, 0, 16);
        $player->getInventory()->addItem($sword);
    }

    public function sendLobbyItem(Player $player)
    {
        $player->getInventory()->clearAll();
        $player->getHungerManager()->setFood(20);
        $player->setHealth(20);
        $player->setGamemode(GameMode::ADVENTURE());
        $player->getEffects()->clear();
        $player->getArmorInventory()->clearAll();
        $player->getInventory()->setItem(0, ItemFactory::getInstance()->get(276)->setCustomName("§aFreeForAll §8[TAP]"));
        $player->getInventory()->setItem(1, ItemFactory::getInstance()->get(283)->setCustomName("§aDuels §8[TAP]"));
        $player->getInventory()->setItem(4, ItemFactory::getInstance()->get(397,3)->setCustomName("§aYour Stats §8[TAP]"));
        $player->getInventory()->setItem(6, ItemFactory::getInstance()->get(399)->setCustomName("§aTags & Cosmetics §8[TAP]"));
        $player->getInventory()->setItem(7, ItemFactory::getInstance()->get(403)->setCustomName("§aEvents §8[TAP]"));
        $player->getInventory()->setItem(8, ItemFactory::getInstance()->get(388)->setCustomName("§aBackToHub §8[TAP]"));

    }

    public function giveGapple(Player $p)
    {
        $effect = new EffectInstance(VanillaEffects::SPEED(), 999999 * 5, 0);
        $p->getEffects()->add($effect);
        $p->getArmorInventory()->clearAll();
        $p->getInventory()->clearAll();
        $sword = ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD, 0, 1);
        $gapple = ItemFactory::getInstance()->get(ItemIds::GOLDEN_APPLE, 3, 16);
        $pearl = ItemFactory::getInstance()->get(ItemIds::ENDER_PEARL, 2, 16);
        $helmet = ItemFactory::getInstance()->get(ItemIds::DIAMOND_HELMET, 0, 1);
        $chest = ItemFactory::getInstance()->get(ItemIds::DIAMOND_CHESTPLATE, 0, 1);
        $thicklegs = ItemFactory::getInstance()->get(ItemIds::DIAMOND_LEGGINGS, 0, 1);
        $footfungus = ItemFactory::getInstance()->get(ItemIds::DIAMOND_BOOTS, 0, 1);
        $tool = VanillaEnchantments::UNBREAKING();
        $toolenchant = new EnchantmentInstance($tool, 5);
        $armorenchant = VanillaEnchantments::UNBREAKING();
        $enchantarmor = new EnchantmentInstance($armorenchant, 5);
        $helmet->addEnchantment($enchantarmor);
        $chest->addEnchantment($enchantarmor);
        $thicklegs->addEnchantment($enchantarmor);
        $footfungus->addEnchantment($enchantarmor);
        $sword->addEnchantment($toolenchant);
        $p->getInventory()->addItem($sword);
        $p->getArmorInventory()->setHelmet($helmet);
        $p->getArmorInventory()->setChestplate($chest);
        $p->getArmorInventory()->setLeggings($thicklegs);
        $p->getArmorInventory()->setBoots($footfungus);
        $p->getInventory()->addItem($pearl);
        $p->getInventory()->addItem($gapple);
        $p->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 999999, 0));
    }
}