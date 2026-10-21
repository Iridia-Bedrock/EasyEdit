<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\utility;

use Generator;
use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\session\Session;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;

class WandCommand extends FAWECommand
{
	public function __construct()
	{
		parent::__construct("/wand", [KnownPermissions::PERMISSION_UTIL]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$player = $session->asPlayer();
		// FAWE's default wand is a plain wooden axe; mark ours so sneak + drop is safe.
		$item = VanillaItems::WOODEN_AXE()->setNamedTag(CompoundTag::create()->setByte("isFAWEWand", 1));
		$player->getInventory()->setItem($player->getInventory()->getHeldItemIndex(), $item);
		$session->sendMessage("wand-given");
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [];
	}

	/**
	 * @param CommandFlagCollection $flags
	 * @param Session               $session
	 * @param string[]              $args
	 * @return Generator<CommandFlag>
	 */
	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		yield from [];
	}
}
