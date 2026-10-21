<?php

namespace platz1de\FastAsyncWorldEdit\selection;

use Closure;
use Generator;
use platz1de\FastAsyncWorldEdit\math\BlockVector;
use platz1de\FastAsyncWorldEdit\selection\constructor\CubicConstructor;
use platz1de\FastAsyncWorldEdit\selection\constructor\ShapeConstructor;
use platz1de\FastAsyncWorldEdit\selection\cubic\CubicChunkLoader;
use pocketmine\math\Facing;
use pocketmine\world\World;

class Cube extends Selection
{
	use CubicChunkLoader;

	/** @param callable(BlockVector, BlockVector): array{BlockVector, BlockVector} $modifier */
	public function modifyRegions(callable $modifier): void
	{
		$regions = [];
		foreach ($this->getRegions() as [$start, $end]) {
			$regions[] = $modifier(clone $start, clone $end);
		}
		$this->setRegions($regions);
	}

	/** @var array<array{BlockVector, ?BlockVector}> */
	private array $additionalRegions = [];

	public function startAdditionalRegion(BlockVector $position): void
	{
		$last = array_key_last($this->additionalRegions);
		if ($last !== null && $this->additionalRegions[$last][1] === null) {
			$this->additionalRegions[$last][0] = clone $position;
			return;
		}
		$this->additionalRegions[] = [clone $position, null];
	}

	public function setPos(BlockVector $pos, int $number): void
	{
		$last = array_key_last($this->additionalRegions);
		if ($last !== null && $this->additionalRegions[$last][1] === null) {
			$this->additionalRegions[$last][$number === 1 ? 0 : 1] = clone $pos;
			return;
		}
		parent::setPos($pos, $number);
	}

	/** @return array<array{BlockVector, BlockVector}> */
	public function getRegions(bool $includeIncomplete = false): array
	{
		$regions = [];
		if ($this->isValid()) {
			$regions[] = [parent::getPos1(), parent::getPos2()];
		}
		foreach ($this->additionalRegions as [$start, $end]) {
			if ($end !== null) {
				$regions[] = [BlockVector::minComponents($start, $end), BlockVector::maxComponents($start, $end)];
			} elseif ($includeIncomplete) {
				$regions[] = [$start, $start];
			}
		}
		return $regions;
	}

	public function getPos1(): BlockVector
	{
		$regions = $this->getRegions();
		if ($regions === []) {
			return parent::getPos1();
		}
		$min = $regions[0][0];
		foreach ($regions as [$start]) {
			$min = BlockVector::minComponents($min, $start);
		}
		return $min;
	}

	public function getPos2(): BlockVector
	{
		$regions = $this->getRegions();
		if ($regions === []) {
			return parent::getPos2();
		}
		$max = $regions[0][1];
		foreach ($regions as [, $end]) {
			$max = BlockVector::maxComponents($max, $end);
		}
		return $max;
	}

	/** @param array<array{BlockVector, BlockVector}> $regions */
	public function setRegions(array $regions): void
	{
		if ($regions === []) {
			unset($this->pos1, $this->pos2, $this->selected1, $this->selected2);
			$this->additionalRegions = [];
			return;
		}
		[$start, $end] = array_shift($regions);
		parent::setPos1($start);
		parent::setPos2($end);
		$this->additionalRegions = [];
		foreach ($regions as [$start, $end]) {
			$this->additionalRegions[] = [clone $start, clone $end];
		}
	}

	public function getVolume(): int
	{
		$regions = $this->getRegions();
		$disjoint = true;
		for ($i = 0; $i < count($regions) && $disjoint; ++$i) {
			for ($j = $i + 1; $j < count($regions); ++$j) {
				[$aMin, $aMax] = $regions[$i];
				[$bMin, $bMax] = $regions[$j];
				if ($aMin->x <= $bMax->x && $aMax->x >= $bMin->x && $aMin->y <= $bMax->y && $aMax->y >= $bMin->y && $aMin->z <= $bMax->z && $aMax->z >= $bMin->z) {
					$disjoint = false;
					break;
				}
			}
		}
		if ($disjoint) {
			$volume = 0;
			foreach ($regions as [$min, $max]) $volume += $max->diff($min)->cubicSize()->volume();
			return $volume;
		}
		$xEdges = [];
		foreach ($regions as [$min, $max]) {
			$xEdges[] = $min->x;
			$xEdges[] = $max->x + 1;
		}
		$xEdges = array_values(array_unique($xEdges));
		sort($xEdges);
		$volume = 0;
		for ($i = 0; $i < count($xEdges) - 1; ++$i) {
			$x = $xEdges[$i];
			$widthX = $xEdges[$i + 1] - $x;
			$active = array_values(array_filter($regions, static fn(array $region) => $region[0]->x <= $x && $region[1]->x >= $x));
			$yEdges = [];
			foreach ($active as [$min, $max]) {
				$yEdges[] = $min->y;
				$yEdges[] = $max->y + 1;
			}
			$yEdges = array_values(array_unique($yEdges));
			sort($yEdges);
			for ($j = 0; $j < count($yEdges) - 1; ++$j) {
				$y = $yEdges[$j];
				$widthY = $yEdges[$j + 1] - $y;
				$intervals = [];
				foreach ($active as [$min, $max]) {
					if ($min->y <= $y && $max->y >= $y) $intervals[] = [$min->z, $max->z + 1];
				}
				sort($intervals);
				$coveredZ = 0;
				$intervalStart = null;
				$intervalEnd = null;
				foreach ($intervals as [$startZ, $endZ]) {
					if ($intervalStart === null) {
						$intervalStart = $startZ;
						$intervalEnd = $endZ;
					} elseif ($startZ <= $intervalEnd) {
						$intervalEnd = max($intervalEnd, $endZ);
					} else {
						$coveredZ += $intervalEnd - $intervalStart;
						$intervalStart = $startZ;
						$intervalEnd = $endZ;
					}
				}
				if ($intervalStart !== null) $coveredZ += $intervalEnd - $intervalStart;
				$volume += $widthX * $widthY * $coveredZ;
			}
		}
		return $volume;
	}

	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @return Generator<ShapeConstructor>
	 */
	public function asShapeConstructors(Closure $closure, SelectionContext $context): Generator
	{
		$previousRegions = [];
		foreach ($this->getRegions() as [$start, $end]) {
			$region = new self($this->world, $start, $end);
			$covered = [];
			foreach ($previousRegions as [$previousStart, $previousEnd]) {
				if ($previousStart->x <= $end->x && $previousEnd->x >= $start->x && $previousStart->y <= $end->y && $previousEnd->y >= $start->y && $previousStart->z <= $end->z && $previousEnd->z >= $start->z) {
					$covered[] = [$previousStart, $previousEnd];
				}
			}
			if ($covered === []) {
				yield from $region->asSingleShapeConstructors($closure, $context);
				$previousRegions[] = [$start, $end];
				continue;
			}
			$regionClosure = static function (int $x, int $y, int $z) use ($closure, $covered): void {
				foreach ($covered as [$min, $max]) {
					if ($min->x <= $x && $x <= $max->x && $min->y <= $y && $y <= $max->y && $min->z <= $z && $z <= $max->z) {
						return;
					}
				}
				$closure($x, $y, $z);
			};
			yield from $region->asSingleShapeConstructors($regionClosure, $context);
			$previousRegions[] = [$start, $end];
		}
	}

	private function asSingleShapeConstructors(Closure $closure, SelectionContext $context): Generator
	{
		if ($context->isEmpty()) {
			return;
		}

		if ($context->isFull()) {
			yield new CubicConstructor($closure, $this->getPos1(), $this->getPos2());
			return;
		}

		if ($context->includesFilling()) {
			//This can also make the selection larger (1x1 -> -3x-3), so we are not allowed to actually check for the smaller/larger position
			yield new CubicConstructor($closure, $this->getPos1()->add(1, 1, 1), $this->getPos2()->add(-1, -1, -1));
		}

		if ($context->includesAllSides()) {
			yield from CubicConstructor::forSides($this->getPos1(), $this->getPos2(), Facing::ALL, (int) $context->getSideThickness(), $closure);
		} elseif ($context->includesWalls()) {
			yield from CubicConstructor::forSides($this->getPos1(), $this->getPos2(), Facing::HORIZONTAL, (int) $context->getSideThickness(), $closure);
		}

		if ($context->includesCenter()) {
			yield new CubicConstructor($closure, $this->getFloorCenter(), $this->getCeilCenter());
		}
	}

	public function getNeededChunks(): array
	{
		$chunks = [];
		foreach ($this->getRegions() as [$start, $end]) {
			for ($x = $start->x >> 4; $x <= $end->x >> 4; ++$x) {
				for ($z = $start->z >> 4; $z <= $end->z >> 4; ++$z) {
					$chunks[World::chunkHash($x, $z)] = true;
				}
			}
		}
		return array_keys($chunks);
	}

	public function __serialize(): array
	{
		return [$this->world, $this->additionalRegions];
	}

	public function __unserialize(array $data): void
	{
		$this->world = $data[0];
		$this->additionalRegions = $data[1] ?? [];
	}
}
