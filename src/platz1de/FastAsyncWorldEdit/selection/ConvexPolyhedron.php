<?php

namespace platz1de\FastAsyncWorldEdit\selection;

use Closure;
use Generator;
use platz1de\FastAsyncWorldEdit\math\BlockVector;
use platz1de\FastAsyncWorldEdit\selection\constructor\ConvexPolyhedronConstructor;
use platz1de\FastAsyncWorldEdit\selection\constructor\ShapeConstructor;
use pocketmine\world\World;

/** A convex 3D hull made from player-selected vertices. */
class ConvexPolyhedron extends Selection
{
	/** @var list<BlockVector> */
	private array $vertices = [];
	/** @var list<array{float, float, float, float, list<int>}> */
	private array $planes = [];

	public function __construct(string $world)
	{
		parent::__construct($world, null, null);
	}

	public function setPos(BlockVector $pos, int $number): void
	{
		if ($number === 1) {
			$this->vertices = [clone $pos];
		} else {
			foreach ($this->vertices as $vertex) {
				if ($vertex->x === $pos->x && $vertex->y === $pos->y && $vertex->z === $pos->z) return;
			}
			if (count($this->vertices) >= 20) return;
			$this->vertices[] = clone $pos;
		}
		$this->refreshBounds();
	}

	/** @return list<BlockVector> */
	public function getVertices(): array
	{
		return $this->vertices;
	}

	/** @return list<array{float, float, float, float, list<int>}> */
	public function getPlanes(): array
	{
		return $this->planes;
	}

	/** @return list<array{BlockVector, BlockVector}> */
	public function getHullEdges(): array
	{
		$edgeFaces = [];
		foreach ($this->planes as $planeIndex => $plane) {
			foreach ($plane[4] as $i => $a) {
				for ($j = $i + 1; $j < count($plane[4]); ++$j) {
					$b = $plane[4][$j];
					$key = min($a, $b) . ":" . max($a, $b);
					$edgeFaces[$key][$planeIndex] = [$a, $b];
				}
			}
		}
		$edges = [];
		foreach ($edgeFaces as $faces) {
			if (count($faces) >= 2) {
				[$a, $b] = reset($faces);
				$edges[] = [$this->vertices[$a], $this->vertices[$b]];
			}
		}
		return $edges;
	}

	public function isValid(): bool
	{
		return count($this->vertices) >= 4 && count($this->planes) >= 4 && isset($this->pos1, $this->pos2);
	}

	private function refreshBounds(): void
	{
		if ($this->vertices === []) {
			unset($this->pos1, $this->pos2, $this->selected1, $this->selected2);
			$this->planes = [];
			return;
		}
		$first = $this->vertices[0];
		$minX = $maxX = $first->x; $minY = $maxY = $first->y; $minZ = $maxZ = $first->z;
		foreach ($this->vertices as $point) {
			$minX = min($minX, $point->x); $maxX = max($maxX, $point->x);
			$minY = min($minY, $point->y); $maxY = max($maxY, $point->y);
			$minZ = min($minZ, $point->z); $maxZ = max($maxZ, $point->z);
		}
		$this->pos1 = new BlockVector($minX, $minY, $minZ);
		$this->pos2 = new BlockVector($maxX, $maxY, $maxZ);
		$this->selected1 = clone $this->pos1;
		$this->selected2 = clone $this->pos2;
		$this->planes = self::buildPlanes($this->vertices);
	}

	/** @param list<BlockVector> $vertices
	 * @return list<array{float, float, float, float, list<int>}>
	 */
	private static function buildPlanes(array $vertices): array
	{
		$planes = [];
		$count = count($vertices);
		for ($i = 0; $i < $count - 2; ++$i) {
			for ($j = $i + 1; $j < $count - 1; ++$j) {
				for ($k = $j + 1; $k < $count; ++$k) {
					$a = $vertices[$i]; $b = $vertices[$j]; $c = $vertices[$k];
					$abX = $b->x - $a->x; $abY = $b->y - $a->y; $abZ = $b->z - $a->z;
					$acX = $c->x - $a->x; $acY = $c->y - $a->y; $acZ = $c->z - $a->z;
					$nx = $abY * $acZ - $abZ * $acY;
					$ny = $abZ * $acX - $abX * $acZ;
					$nz = $abX * $acY - $abY * $acX;
					$length = sqrt($nx * $nx + $ny * $ny + $nz * $nz);
					if ($length < 1.0e-9) continue;
					$nx /= $length; $ny /= $length; $nz /= $length;
					$d = -($nx * $a->x + $ny * $a->y + $nz * $a->z);
					$positive = false; $negative = false;
					foreach ($vertices as $point) {
						$distance = $nx * $point->x + $ny * $point->y + $nz * $point->z + $d;
						if ($distance > 1.0e-6) $positive = true;
						if ($distance < -1.0e-6) $negative = true;
					}
					if ($positive && $negative) continue;
					if ($positive) { $nx = -$nx; $ny = -$ny; $nz = -$nz; $d = -$d; }
					$key = implode(":", array_map(static fn(float $value) => (string) round($value, 6), [$nx, $ny, $nz, $d]));
					if (isset($planes[$key])) continue;
					$face = [];
					foreach ($vertices as $index => $point) {
						if (abs($nx * $point->x + $ny * $point->y + $nz * $point->z + $d) <= 1.0e-6) $face[] = $index;
					}
					$planes[$key] = [$nx, $ny, $nz, $d, $face];
				}
			}
		}
		return array_values($planes);
	}

	public function getVolume(): int
	{
		return $this->isValid() ? parent::getVolume() : 0;
	}

	public function getNeededChunks(): array
	{
		if ($this->vertices === []) return [];
		$chunks = [];
		for ($x = $this->pos1->x >> 4; $x <= ($this->pos2->x >> 4); ++$x) {
			for ($z = $this->pos1->z >> 4; $z <= ($this->pos2->z >> 4); ++$z) $chunks[] = World::chunkHash($x, $z);
		}
		return $chunks;
	}

	public function asShapeConstructors(Closure $closure, SelectionContext $context): Generator
	{
		if ($this->isValid() && !$context->isEmpty()) {
			yield new ConvexPolyhedronConstructor($closure, $this->vertices, $this->planes, $this->getPos1(), $this->getPos2(), $context);
		}
	}

	public function __serialize(): array
	{
		return [$this->world, $this->vertices];
	}

	public function __unserialize(array $data): void
	{
		$this->world = $data[0];
		$this->vertices = $data[1] ?? [];
		$this->refreshBounds();
	}
}
