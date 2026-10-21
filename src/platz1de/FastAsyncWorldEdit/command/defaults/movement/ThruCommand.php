<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\movement;

use Generator;
use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\session\Session;
use pocketmine\block\Air;
use pocketmine\math\VoxelRayTrace;

class ThruCommand extends FAWECommand
{
	public function __construct()
	{
		parent::__construct("/thru", [KnownPermissions::PERMISSION_UTIL], ["/t"]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$player = $session->asPlayer();
		$ignore = true;
		foreach (VoxelRayTrace::inDirection($player->getEyePos(), $player->getDirectionVector(), 10) as $vector) {
			if ($ignore && $player->getWorld()->getBlock($vector) instanceof Air) {
				continue;
			}
			$ignore = false;
			if ($player->getWorld()->getBlock($vector) instanceof Air && $player->getWorld()->getBlock($vector->down()) instanceof Air) {
				$player->teleport($vector->down());
				break;
			}
		}
	}

	public function getKnownFlags(Session $session): array
	{
		return [];
	}

	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		yield from [];
	}
}