<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\selection;

use Generator;
use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\command\exception\InvalidUsageException;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\flags\IntegerCommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\SingularCommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\VectorCommandFlag;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\math\BlockVector;
use platz1de\FastAsyncWorldEdit\math\OffGridBlockVector;
use platz1de\FastAsyncWorldEdit\selection\Cube;
use platz1de\FastAsyncWorldEdit\session\Session;
use platz1de\FastAsyncWorldEdit\utils\ArgumentParser;
use pocketmine\world\World;

/** Implements FAWE-style cuboid selection adjustments for each selected region. */
class SelectionAdjustmentCommand extends FAWECommand
{
	public function __construct(private string $operation)
	{
		parent::__construct("/$operation", [KnownPermissions::PERMISSION_SELECT]);
	}

	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$cube = $session->getCube();
		switch ($this->operation) {
			case "shift":
				$delta = $flags->getVectorFlag("vector");
				$cube->modifyRegions(static fn(BlockVector $min, BlockVector $max) => [
					new BlockVector($min->x + $delta->x, $min->y + $delta->y, $min->z + $delta->z),
					new BlockVector($max->x + $delta->x, $max->y + $delta->y, $max->z + $delta->z)
				]);
				break;
			case "expand":
				if ($flags->hasFlag("vertical")) {
					$cube->modifyRegions(static fn(BlockVector $min, BlockVector $max) => [
						new BlockVector($min->x, World::Y_MIN, $min->z),
						new BlockVector($max->x, World::Y_MAX - 1, $max->z)
					]);
				} else {
					$this->adjustDirection($cube, $flags->getIntFlag("amount"), $flags->getIntFlag("reverse"), $flags->getVectorFlag("direction"), true);
				}
				break;
			case "contract":
				$this->adjustDirection($cube, $flags->getIntFlag("amount"), $flags->getIntFlag("reverse"), $flags->getVectorFlag("direction"), false);
				break;
			case "inset":
			case "outset":
				$amount = $flags->getIntFlag("amount") * ($this->operation === "inset" ? -1 : 1);
				$horizontal = !$flags->hasFlag("vertical") || $flags->hasFlag("horizontal");
				$vertical = !$flags->hasFlag("horizontal") || $flags->hasFlag("vertical");
				$cube->modifyRegions(static fn(BlockVector $min, BlockVector $max) => [
					new BlockVector($min->x - ($horizontal ? $amount : 0), $min->y - ($vertical ? $amount : 0), $min->z - ($horizontal ? $amount : 0)),
					new BlockVector($max->x + ($horizontal ? $amount : 0), $max->y + ($vertical ? $amount : 0), $max->z + ($horizontal ? $amount : 0))
				]);
				break;
		}
		$session->updateSelectionHighlight();
		$session->sendMessage("selection-adjusted", ["{regions}" => (string) count($cube->getRegions())]);
	}

	private function adjustDirection(Cube $cube, int $amount, int $reverse, OffGridBlockVector $direction, bool $expand): void
	{
		$cube->modifyRegions(static function (BlockVector $min, BlockVector $max) use ($amount, $reverse, $direction, $expand): array {
			foreach (["x", "y", "z"] as $axis) {
				$d = $direction->$axis;
				if ($d === 0) continue;
				if ($d > 0) {
					if ($expand) { $max->$axis += $amount; $min->$axis -= $reverse; }
					else { $min->$axis += $amount; $max->$axis -= $reverse; }
				} else {
					if ($expand) { $min->$axis -= $amount; $max->$axis += $reverse; }
					else { $max->$axis -= $amount; $min->$axis += $reverse; }
				}
			}
			foreach (["x", "y", "z"] as $axis) {
				if ($min->$axis > $max->$axis) $min->$axis = $max->$axis = (int) floor(($min->$axis + $max->$axis) / 2);
			}
			return [new BlockVector($min->x, $min->y, $min->z), new BlockVector($max->x, $max->y, $max->z)];
		});
	}

	public function getKnownFlags(Session $session): array
	{
		return match ($this->operation) {
			"shift" => ["vector" => new VectorCommandFlag("vector", [], "v")],
			"expand" => ["vertical" => new SingularCommandFlag("vertical", ["vert"], "v")],
			"contract" => [],
			default => [
				"horizontal" => new SingularCommandFlag("horizontal", ["h"], "h"),
				"vertical" => new SingularCommandFlag("vertical", ["v"], "v")
			]
		};
	}

	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		if ($this->operation === "shift") {
			if (!$flags->hasFlag("vector")) {
				if (count($args) < 1 || count($args) > 2) throw new InvalidUsageException();
				yield VectorCommandFlag::with(ArgumentParser::parseDirectionVector($session, $args[0], $args[1] ?? null), "vector");
			}
			return;
		}
		if ($this->operation === "expand" && in_array(strtolower($args[0] ?? ""), ["vert", "vertical"], true)) {
			if (count($args) !== 1) throw new InvalidUsageException();
			yield new SingularCommandFlag("vertical", ["vert"], "v");
			return;
		}
		if ($this->operation === "expand" && $flags->hasFlag("vertical") && $args === []) return;
		if (!isset($args[0]) || !is_numeric($args[0]) || (int) $args[0] < 0) throw new InvalidUsageException();
		if (!$flags->hasFlag("amount")) yield IntegerCommandFlag::with((int) $args[0], "amount");
		if ($this->operation === "inset" || $this->operation === "outset") {
			if (count($args) !== 1) throw new InvalidUsageException();
			return;
		}
		if (count($args) > 3) throw new InvalidUsageException();
		$reverse = 0;
		$directionArgument = $args[1] ?? null;
		if (isset($args[1]) && is_numeric($args[1])) {
			if ((int) $args[1] < 0) throw new InvalidUsageException();
			if (count($args) > 3) throw new InvalidUsageException();
			$reverse = (int) $args[1];
			$directionArgument = $args[2] ?? null;
		} elseif (count($args) > 2) {
			throw new InvalidUsageException();
		}
		if (!$flags->hasFlag("reverse")) yield IntegerCommandFlag::with($reverse, "reverse");
		if (!$flags->hasFlag("direction")) {
			yield VectorCommandFlag::with(ArgumentParser::parseDirectionVector($session, "1", $directionArgument), "direction");
		}
	}
}
