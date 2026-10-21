<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use Generator;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use pocketmine\math\Facing;
use pocketmine\world\World;

class Cube extends Selection
{
	use CubicChunkLoader;

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
		$volume = 0;
		foreach ($this->getRegions() as [$start, $end]) {
			$volume += $end->diff($start)->cubicSize()->volume();
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
			$covered = $previousRegions;
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
					$chunks[] = World::chunkHash($x, $z);
				}
			}
		}
		return array_values(array_unique($chunks));
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
