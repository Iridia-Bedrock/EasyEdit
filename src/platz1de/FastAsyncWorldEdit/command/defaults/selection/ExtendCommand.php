<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\selection;

use Generator;
use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\flags\SingularCommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\VectorCommandFlag;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\math\BlockVector;
use platz1de\FastAsyncWorldEdit\math\OffGridBlockVector;
use platz1de\FastAsyncWorldEdit\session\Session;
use platz1de\FastAsyncWorldEdit\utils\ArgumentParser;
use pocketmine\world\World;

class ExtendCommand extends FAWECommand
{
	public function __construct()
	{
		parent::__construct("/extend", [KnownPermissions::PERMISSION_SELECT]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$selection = $session->getCube();
		$regions = [];

		if ($flags->hasFlag("vertical")) {
			foreach ($selection->getRegions() as [$pos1, $pos2]) {
				$regions[] = [new BlockVector($pos1->x, World::Y_MIN, $pos1->z), new BlockVector($pos2->x, World::Y_MAX - 1, $pos2->z)];
			}
		} else {
			$min = $flags->getVectorFlag("min")->diff(OffGridBlockVector::zero());
			$max = $flags->getVectorFlag("max")->diff(OffGridBlockVector::zero());
			foreach ($selection->getRegions() as [$pos1, $pos2]) {
				$regions[] = [$pos1->offset($min), $pos2->offset($max)];
			}
		}

		$selection->setRegions($regions);
		$session->updateSelectionHighlight();
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"min" => new VectorCommandFlag("min", [], "v"),
			"max" => new VectorCommandFlag("max", [], "v"),
			"vertical" => new SingularCommandFlag("vertical", ["vert"], "v")
		];
	}

	/**
	 * @param CommandFlagCollection $flags
	 * @param Session               $session
	 * @param string[]              $args
	 * @return Generator<CommandFlag>
	 */
	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		if (($args[0] ?? "") === "vert" || ($args[0] ?? "") === "vertical") {
			yield $this->getKnownFlags($session)["vertical"];
			return;
		}
		if (!$flags->hasFlag("min") && !$flags->hasFlag("max")) {
			$offset = ArgumentParser::parseDirectionVector($session, $args[0] ?? null, $args[1] ?? null, $count);
			if ($count < 0 xor ($offset->x >= 0 && $offset->y >= 0 && $offset->z >= 0)) {
				yield VectorCommandFlag::with($offset, "max");
			} else {
				yield VectorCommandFlag::with($offset, "min");
			}
		}
		if (!$flags->hasFlag("min")) {
			yield VectorCommandFlag::with(OffGridBlockVector::zero(), "min");
		}
		if (!$flags->hasFlag("max")) {
			yield VectorCommandFlag::with(OffGridBlockVector::zero(), "max");
		}
	}
}
