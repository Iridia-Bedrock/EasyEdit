<?php

namespace platz1de\FastAsyncWorldEdit\selection;

use Closure;
use Generator;
use platz1de\FastAsyncWorldEdit\math\BlockVector;
use platz1de\FastAsyncWorldEdit\selection\constructor\PolygonConstructor;
use platz1de\FastAsyncWorldEdit\selection\constructor\ShapeConstructor;
use pocketmine\world\World;

/** A vertical prism whose horizontal cross-section is a player-defined polygon. */
class Polygon extends Selection
{
	/** @var list<BlockVector> */
	private array $vertices = [];

	public function __construct(string $world)
	{
		parent::__construct($world, null, null);
	}

	public function setPos(BlockVector $pos, int $number): void
	{
		if ($number === 1) {
			$this->vertices = [clone $pos];
		} else {
			if ($this->vertices === []) $this->vertices[] = clone $pos;
			if (count($this->vertices) >= 20) return;
			$last = $this->vertices[array_key_last($this->vertices)];
			if ($last->x !== $pos->x || $last->y !== $pos->y || $last->z !== $pos->z) $this->vertices[] = clone $pos;
		}
		$this->refreshBounds();
	}

	/** @return list<BlockVector> */
	public function getVertices(): array
	{
		return $this->vertices;
	}

	public function isValid(): bool
	{
		if (count($this->vertices) < 3 || !isset($this->pos1, $this->pos2)) return false;
		$area = 0;
		$count = count($this->vertices);
		for ($i = 0; $i < $count; ++$i) {
			$a = $this->vertices[$i];
			$b = $this->vertices[($i + 1) % $count];
			$area += $a->x * $b->z - $b->x * $a->z;
		}
		return $area !== 0;
	}

	private function refreshBounds(): void
	{
		if ($this->vertices === []) {
			unset($this->pos1, $this->pos2, $this->selected1, $this->selected2);
			return;
		}
		$minX = $maxX = $this->vertices[0]->x;
		$minY = $maxY = $this->vertices[0]->y;
		$minZ = $maxZ = $this->vertices[0]->z;
		foreach ($this->vertices as $vertex) {
			$minX = min($minX, $vertex->x); $maxX = max($maxX, $vertex->x);
			$minY = min($minY, $vertex->y); $maxY = max($maxY, $vertex->y);
			$minZ = min($minZ, $vertex->z); $maxZ = max($maxZ, $vertex->z);
		}
		$this->pos1 = new BlockVector($minX, $minY, $minZ);
		$this->pos2 = new BlockVector($maxX, $maxY, $maxZ);
		$this->selected1 = clone $this->pos1;
		$this->selected2 = clone $this->pos2;
	}

	public function getVolume(): int
	{
		if (!$this->isValid()) return 0;
		$doubleArea = 0;
		$count = count($this->vertices);
		for ($i = 0; $i < $count; ++$i) {
			$a = $this->vertices[$i];
			$b = $this->vertices[($i + 1) % $count];
			$doubleArea += $a->x * $b->z - $b->x * $a->z;
		}
		return (int) ceil(abs($doubleArea) / 2 * ($this->getPos2()->y - $this->getPos1()->y + 1));
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
			yield new PolygonConstructor($closure, $this->vertices, $this->getPos1()->y, $this->getPos2()->y, $context);
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
