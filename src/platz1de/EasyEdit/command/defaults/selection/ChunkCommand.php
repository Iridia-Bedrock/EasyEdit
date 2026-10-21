<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use Generator;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\InvalidUsageException;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\SingularCommandFlag;
use platz1de\EasyEdit\command\flags\StringCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\session\Session;
use pocketmine\world\World;

class ChunkCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/chunk", [KnownPermissions::PERMISSION_SELECT]);
	}

	public function process(Session $session, CommandFlagCollection $flags): void
	{
		if ($flags->hasFlag("selection")) {
			$cube = $session->getCube();
			$world = $cube->getWorldName();
			$regions = [];
			foreach ($cube->getRegions() as [$min, $max]) {
				for ($x = $min->x >> 4; $x <= ($max->x >> 4); ++$x) {
					for ($z = $min->z >> 4; $z <= ($max->z >> 4); ++$z) {
						$hash = World::chunkHash($x, $z);
						$regions[$hash] = [new BlockVector($x << 4, World::Y_MIN, $z << 4), new BlockVector(($x << 4) + 15, World::Y_MAX - 1, ($z << 4) + 15)];
					}
				}
			}
		} else {
			$world = $session->asPlayer()->getWorld()->getFolderName();
			if ($flags->hasFlag("coordinates")) {
				$coordinates = array_map("intval", explode(",", $flags->getStringFlag("coordinates")));
				if ($flags->hasFlag("chunk-coordinates")) {
					[$chunkX, $chunkZ] = $coordinates;
				} else {
					[$blockX, , $blockZ] = $coordinates;
					$chunkX = $blockX >> 4;
					$chunkZ = $blockZ >> 4;
				}
			} else {
				$position = $session->asPlayer()->getPosition();
				$chunkX = $position->getFloorX() >> 4;
				$chunkZ = $position->getFloorZ() >> 4;
			}
			$regions = [[new BlockVector($chunkX << 4, World::Y_MIN, $chunkZ << 4), new BlockVector(($chunkX << 4) + 15, World::Y_MAX - 1, ($chunkZ << 4) + 15)]];
		}
		$session->selectRegions($world, array_values($regions), "chunk-selected");
	}

	public function getKnownFlags(Session $session): array
	{
		return [
			"selection" => new SingularCommandFlag("selection", ["s"], "s"),
			"chunk-coordinates" => new SingularCommandFlag("chunk-coordinates", ["c"], "c")
		];
	}

	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		if ($flags->hasFlag("selection")) {
			if ($args !== []) throw new InvalidUsageException();
			return;
		}
		if ($args === []) return;
		$expected = $flags->hasFlag("chunk-coordinates") ? 2 : 3;
		if (count($args) !== $expected) throw new InvalidUsageException();
		foreach ($args as $arg) if (!is_numeric($arg)) throw new InvalidUsageException();
		yield StringCommandFlag::with(implode(",", $args), "coordinates");
	}
}
