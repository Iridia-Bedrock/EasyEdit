<?php

namespace platz1de\FastAsyncWorldEdit\selection\constructor;

use Closure;
use platz1de\FastAsyncWorldEdit\math\BlockOffsetVector;
use platz1de\FastAsyncWorldEdit\math\BlockVector;
use platz1de\FastAsyncWorldEdit\selection\SelectionContext;
use platz1de\FastAsyncWorldEdit\utils\VectorUtils;
use pocketmine\world\World;

class PolygonConstructor extends ShapeConstructor
{
	/** @param list<BlockVector> $vertices */
	public function __construct(Closure $closure, private array $vertices, private int $minY, private int $maxY, private SelectionContext $context)
	{
		parent::__construct($closure);
	}

	public function getBlockCount(): int
	{
		$minX = min(array_map(static fn(BlockVector $v) => $v->x, $this->vertices));
		$maxX = max(array_map(static fn(BlockVector $v) => $v->x, $this->vertices));
		$minZ = min(array_map(static fn(BlockVector $v) => $v->z, $this->vertices));
		$maxZ = max(array_map(static fn(BlockVector $v) => $v->z, $this->vertices));
		return ($maxX - $minX + 1) * ($maxZ - $minZ + 1) * ($this->maxY - $this->minY + 1);
	}

	public function moveTo(int $chunk): void
	{
		$chunkMin = VectorUtils::getChunkPosition($chunk);
		$chunkMaxX = $chunkMin->x + 15;
		$chunkMaxZ = $chunkMin->z + 15;
		$minX = max($chunkMin->x, min(array_map(static fn(BlockVector $v) => $v->x, $this->vertices)));
		$maxX = min($chunkMaxX, max(array_map(static fn(BlockVector $v) => $v->x, $this->vertices)));
		$minZ = max($chunkMin->z, min(array_map(static fn(BlockVector $v) => $v->z, $this->vertices)));
		$maxZ = min($chunkMaxZ, max(array_map(static fn(BlockVector $v) => $v->z, $this->vertices)));
		$minY = max($this->minY, World::Y_MIN);
		$maxY = min($this->maxY, World::Y_MAX - 1);
		$closure = $this->closure;
		if ($this->context->includesCenter()) {
			$centerX = (int) floor(array_sum(array_map(static fn(BlockVector $v) => $v->x, $this->vertices)) / count($this->vertices));
			$centerZ = (int) floor(array_sum(array_map(static fn(BlockVector $v) => $v->z, $this->vertices)) / count($this->vertices));
			if ($centerX >= $chunkMin->x && $centerX <= $chunkMaxX && $centerZ >= $chunkMin->z && $centerZ <= $chunkMaxZ && $this->contains($centerX, $centerZ)) {
				$closure($centerX, (int) floor(($minY + $maxY) / 2), $centerZ);
			}
		}
		for ($x = $minX; $x <= $maxX; ++$x) {
			for ($z = $minZ; $z <= $maxZ; ++$z) {
				$inside = $this->contains($x, $z);
				if (!$inside) continue;
				$edge = $this->onBoundary($x, $z);
				for ($y = $minY; $y <= $maxY; ++$y) {
					$topOrBottom = $y === $minY || $y === $maxY;
					$onRequestedSurface = ($edge && $this->context->includesWalls()) || ($topOrBottom && $this->context->includesVerticals());
					if ($this->context->isFull() || $this->context->includesFilling() || $onRequestedSurface) {
						$closure($x, $y, $z);
					}
				}
			}
		}
	}

	private function contains(int $x, int $z): bool
	{
		$inside = false;
		$count = count($this->vertices);
		for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
			$xi = $this->vertices[$i]->x; $zi = $this->vertices[$i]->z;
			$xj = $this->vertices[$j]->x; $zj = $this->vertices[$j]->z;
			if (($zi > $z) !== ($zj > $z) && $x < ($xj - $xi) * ($z - $zi) / (($zj - $zi) ?: 1) + $xi) $inside = !$inside;
		}
		return $inside || $this->onBoundary($x, $z);
	}

	private function onBoundary(int $x, int $z): bool
	{
		$count = count($this->vertices);
		for ($i = 0; $i < $count; ++$i) {
			$a = $this->vertices[$i]; $b = $this->vertices[($i + 1) % $count];
			$cross = ($x - $a->x) * ($b->z - $a->z) - ($z - $a->z) * ($b->x - $a->x);
			if ($cross === 0 && $x >= min($a->x, $b->x) && $x <= max($a->x, $b->x) && $z >= min($a->z, $b->z) && $z <= max($a->z, $b->z)) return true;
		}
		return false;
	}

	public function needsChunk(int $chunk): bool
	{
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		return $min->x <= max(array_map(static fn(BlockVector $v) => $v->x, $this->vertices)) && $max->x >= min(array_map(static fn(BlockVector $v) => $v->x, $this->vertices))
			&& $min->z <= max(array_map(static fn(BlockVector $v) => $v->z, $this->vertices)) && $max->z >= min(array_map(static fn(BlockVector $v) => $v->z, $this->vertices));
	}

	public function offset(BlockOffsetVector $offset): ShapeConstructor
	{
		$vertices = array_map(static fn(BlockVector $v) => $v->offset($offset), $this->vertices);
		return new self($this->closure, $vertices, $this->minY + $offset->y, $this->maxY + $offset->y, $this->context);
	}
}
