<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\movement;

use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\command\FlagArgumentParser;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\flags\IntegerCommandFlag;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\session\Session;
use pocketmine\block\Air;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\World;

class UpCommand extends FAWECommand
{
	use FlagArgumentParser;

	public function __construct()
	{
		parent::__construct("/up", [KnownPermissions::PERMISSION_UTIL, KnownPermissions::PERMISSION_EDIT]);
		$this->flagOrder = ["amount" => false];
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$player = $session->asPlayer();
		$pos = $player->getPosition()->up($flags->getIntFlag("amount"));
		$pos->y = min(max($pos->y, World::Y_MIN + 1), World::Y_MAX);
		$player->teleport($pos);
		if ($player->getWorld()->getBlock($pos->floor()->down()) instanceof Air) {
			$player->getWorld()->setBlock($pos->floor()->down(), VanillaBlocks::GLASS());
		}
	}

	public function getKnownFlags(Session $session): array
	{
		return [
			"amount" => IntegerCommandFlag::default(1, "amount", ["count"], "a")
		];
	}
}