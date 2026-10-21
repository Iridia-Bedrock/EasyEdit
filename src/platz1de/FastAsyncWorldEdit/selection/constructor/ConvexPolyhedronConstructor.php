<?php

namespace platz1de\FastAsyncWorldEdit\selection\constructor;

use Closure;
use platz1de\FastAsyncWorldEdit\math\BlockOffsetVector;
use platz1de\FastAsyncWorldEdit\math\BlockVector;
use platz1de\FastAsyncWorldEdit\selection\SelectionContext;
use platz1de\FastAsyncWorldEdit\utils\VectorUtils;
use pocketmine\world\World;

class ConvexPolyhedronConstructor extends ShapeConstructor
{
	/** @param list<BlockVector> $vertices
	 * @param list<array{float, float, float, float, list<int>}> $planes
	 */
	public function __construct(Closure $closure, private array $vertices, private array $planes, private BlockVector $min, private BlockVector $max, private SelectionContext $context)
	{
		parent::__construct($closure);
	}

	public function getBlockCount(): int
	{
		return $this->max->diff($this->min)->cubicSize()->volume();
	}

	public function moveTo(int $chunk): void
	{
		$chunkMin = VectorUtils::getChunkPosition($chunk);
		$minX = max($this->min->x, $chunkMin->x); $maxX = min($this->max->x, $chunkMin->x + 15);
		$minZ = max($this->min->z, $chunkMin->z); $maxZ = min($this->max->z, $chunkMin->z + 15);
		$minY = max($this->min->y, World::Y_MIN); $maxY = min($this->max->y, World::Y_MAX - 1);
		$closure = $this->closure;
		if ($this->context->includesCenter()) {
			$centerX = (int) floor(($this->min->x + $this->max->x) / 2);
			$centerY = (int) floor(($this->min->y + $this->max->y) / 2);
			$centerZ = (int) floor(($this->min->z + $this->max->z) / 2);
			if ($centerX >= $minX && $centerX <= $maxX && $centerY >= $minY && $centerY <= $maxY && $centerZ >= $minZ && $centerZ <= $maxZ && $this->contains($centerX, $centerY, $centerZ)) $closure($centerX, $centerY, $centerZ);
		}
		for ($x = $minX; $x <= $maxX; ++$x) {
			for ($z = $minZ; $z <= $maxZ; ++$z) {
				for ($y = $minY; $y <= $maxY; ++$y) {
					if (!$this->contains($x, $y, $z)) continue;
					$surface = $this->onSurface($x, $y, $z);
					if ($this->context->isFull() || $this->context->includesFilling() || ($surface && ($this->context->includesWalls() || $this->context->includesVerticals()))) $closure($x, $y, $z);
				}
			}
		}
	}

	private function contains(int $x, int $y, int $z): bool
	{
		foreach ($this->planes as [$nx, $ny, $nz, $d]) {
			if ($nx * $x + $ny * $y + $nz * $z + $d > 1.0e-6) return false;
		}
		return true;
	}

	private function onSurface(int $x, int $y, int $z): bool
	{
		foreach ($this->planes as [$nx, $ny, $nz, $d]) {
			if (abs($nx * $x + $ny * $y + $nz * $z + $d) <= 0.5) return true;
		}
		return false;
	}

	public function needsChunk(int $chunk): bool
	{
		$min = VectorUtils::getChunkPosition($chunk);
		return $min->x <= $this->max->x && $min->x + 15 >= $this->min->x && $min->z <= $this->max->z && $min->z + 15 >= $this->min->z;
	}

	public function offset(BlockOffsetVector $offset): ShapeConstructor
	{
		$vertices = array_map(static fn(BlockVector $point) => $point->offset($offset), $this->vertices);
		$planes = array_map(static function (array $plane) use ($offset): array {
			$plane[3] -= $plane[0] * $offset->x + $plane[1] * $offset->y + $plane[2] * $offset->z;
			return $plane;
		}, $this->planes);
		return new self($this->closure, $vertices, $planes, $this->min->offset($offset), $this->max->offset($offset), $this->context);
	}
}
