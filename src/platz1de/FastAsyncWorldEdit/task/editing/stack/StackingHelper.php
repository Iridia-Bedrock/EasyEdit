<?php

namespace platz1de\FastAsyncWorldEdit\task\editing\stack;

use BadMethodCallException;
use Closure;
use Generator;
use platz1de\FastAsyncWorldEdit\math\BlockOffsetVector;
use platz1de\FastAsyncWorldEdit\math\BlockVector;
use platz1de\FastAsyncWorldEdit\selection\Cube;
use platz1de\FastAsyncWorldEdit\selection\constructor\ShapeConstructor;
use platz1de\FastAsyncWorldEdit\selection\constructor\StackedConstructor;
use platz1de\FastAsyncWorldEdit\selection\Selection;
use platz1de\FastAsyncWorldEdit\selection\SelectionContext;
use platz1de\FastAsyncWorldEdit\thread\chunk\ChunkRequestManager;
use pocketmine\math\Axis;
use pocketmine\world\World;

class StackingHelper extends Selection
{
	private Selection $parent;
	private int $axis;
	private int $amount;

	/**
	 * @param Selection         $selection
	 * @param BlockOffsetVector $direction
	 */
	public function __construct(Selection $selection, BlockOffsetVector $direction)
	{
		if ($selection instanceof self) {
			throw new BadMethodCallException("Cannot stack a stacked selection");
		}
		$this->parent = $selection;
		if ($direction->y !== 0) {
			$this->axis = Axis::Y;
		} elseif ($direction->x !== 0) {
			$this->axis = Axis::X;
		} else {
			$this->axis = Axis::Z;
		}
		$this->amount = $direction->getComponent($this->axis);
		$offset = $selection->getSize()->getComponent($this->axis);
		if ($this->amount > 0) {
			parent::__construct($selection->getWorldName(), $selection->getPos1()->addComponent($this->axis, $offset), $selection->getPos2()->addComponent($this->axis, $offset * $this->amount));
		} else {
			parent::__construct($selection->getWorldName(), $selection->getPos1()->addComponent($this->axis, $offset * $this->amount), $selection->getPos2()->addComponent($this->axis, -$offset));
		}
	}

	/**
	 * @return int[]
	 */
	public function getNeededChunks(): array
	{
		$regions = $this->getRegions();
		$chunks = ($this->axis === Axis::Y || count($regions) > 1) ? $this->parent->getNeededChunks() : [];
		foreach ($this->getDestinationRegions() as [$min, $max]) {
			for ($x = $min->x >> 4; $x <= $max->x >> 4; $x++) {
				for ($z = $min->z >> 4; $z <= $max->z >> 4; $z++) {
					$chunks[] = World::chunkHash($x, $z);
				}
			}
		}
		return array_values(array_unique($chunks));
	}

	public function getPos1(): BlockVector
	{
		$regions = $this->getDestinationRegions();
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
		$regions = $this->getDestinationRegions();
		if ($regions === []) {
			return parent::getPos2();
		}
		$max = $regions[0][1];
		foreach ($regions as [, $end]) {
			$max = BlockVector::maxComponents($max, $end);
		}
		return $max;
	}

	/** @return array<array{BlockVector, BlockVector}> */
	private function getDestinationRegions(): array
	{
		$destinations = [];
		foreach ($this->getRegions() as [$start, $end]) {
			$size = $end->diff($start)->cubicSize();
			if ($this->amount > 0) {
				$min = $start->addComponent($this->axis, $size->getComponent($this->axis));
				$max = $end->addComponent($this->axis, $size->getComponent($this->axis) * $this->amount);
			} else {
				$min = $start->addComponent($this->axis, $size->getComponent($this->axis) * $this->amount);
				$max = $end->addComponent($this->axis, -$size->getComponent($this->axis));
			}
			$destinations[] = [BlockVector::minComponents($min, $max), BlockVector::maxComponents($min, $max)];
		}
		return $destinations;
	}

	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @return Generator<ShapeConstructor>
	 */
	public function asShapeConstructors(Closure $closure, SelectionContext $context): Generator
	{
		foreach ($this->getRegions() as [$start, $end]) {
			$region = new Cube($this->parent->getWorldName(), $start, $end);
			$size = $region->getSize();
			$regionClosure = static function (int $x, int $y, int $z) use ($closure, $start, $size): void {
				$closure($x, $y, $z, $start, $size);
			};
			yield new StackedConstructor($regionClosure, $region, $context, $this->axis, $this->amount);
		}
	}

	/** @return array<array{BlockVector, BlockVector}> */
	private function getRegions(): array
	{
		return $this->parent instanceof Cube ? $this->parent->getRegions() : [[$this->parent->getPos1(), $this->parent->getPos2()]];
	}

	/**
	 * @return bool
	 */
	public function isCopying(): bool
	{
		foreach ($this->getRegions() as [$start, $end]) {
			if ($this->axis !== Axis::Y && $end->diff($start)->cubicSize()->getComponent($this->axis) > ChunkRequestManager::MAX_REQUEST * 8) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return int
	 */
	public function getAxis(): int
	{
		return $this->axis;
	}

	/**
	 * @return int
	 */
	public function getAmount(): int
	{
		return $this->amount;
	}
}
