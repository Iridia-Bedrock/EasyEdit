<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\brush;

use platz1de\FastAsyncWorldEdit\brush\BrushHandler;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\command\SubCommand;
use platz1de\FastAsyncWorldEdit\session\Session;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

abstract class BrushSubCommand extends SubCommand
{
	protected const BRUSH_TYPE = BrushHandler::BRUSH_SPHERE;

	/**
	 * @param string[]            $names
	 * @param array<string, bool> $flagOrder
	 */
	public function __construct(array $names, array $flagOrder)
	{
		parent::__construct($names, $flagOrder, [KnownPermissions::PERMISSION_BRUSH]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$nbt = CompoundTag::create()->setString("brushType", BrushHandler::identifierToName(static::BRUSH_TYPE));
		$this->applyBrushNbt($nbt, $flags);
		$item = VanillaItems::WOODEN_SHOVEL()->setNamedTag($nbt);
		$lore = [];
		foreach ($nbt->getValue() as $name => $value) {
			$lore[] = $name . ": " . $value;
		}
		$item->setLore($lore);
		$item->setCustomName(TextFormat::GOLD . "Brush");
		$session->asPlayer()->getInventory()->setItem($session->asPlayer()->getInventory()->getHeldItemIndex(), $item);
	}

	/**
	 * @param CompoundTag           $nbt
	 * @param CommandFlagCollection $flags
	 */
	abstract protected function applyBrushNbt(CompoundTag $nbt, CommandFlagCollection $flags): void;
}