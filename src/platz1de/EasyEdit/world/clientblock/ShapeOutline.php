<?php

namespace platz1de\EasyEdit\world\clientblock;

use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\selection\Ellipsoid;
use platz1de\EasyEdit\selection\Polygon;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\utils\PacketUtils;
use platz1de\EasyEdit\world\ReferencedWorldHolder;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\World;

/** Renders non-cuboid selections as a sampled glass outline on the client. */
class ShapeOutline extends ClientSideBlock
{
	use ReferencedWorldHolder;

	/** @var array<string, Vector3> */
	private array $points = [];
	/** @var array<int, array<string, Vector3>> */
	private array $pointsByChunk = [];
	/** @var array<int, true> */
	private array $sentChunks = [];

	public function __construct(string $world, Selection $selection)
	{
		$this->world = $world;
		parent::__construct();
		$this->buildPoints($selection);
		foreach ($this->points as $key => $position) {
			$chunk = World::chunkHash($position->getFloorX() >> 4, $position->getFloorZ() >> 4);
			$this->pointsByChunk[$chunk][$key] = $position;
		}
	}

	private function buildPoints(Selection $selection): void
	{
		$min = $selection->getPos1();
		$max = $selection->getPos2();
		$spanX = $max->x - $min->x + 1;
		$spanY = $max->y - $min->y + 1;
		$spanZ = $max->z - $min->z + 1;
		$surfaceEstimate = max(1, 2 * ($spanX * $spanY + $spanY * $spanZ + $spanX * $spanZ));
		$sampleEvery = max(1, (int) ceil($surfaceEstimate / 12000));
		if ($selection instanceof Ellipsoid) {
			$this->buildEllipsoidPoints($selection, $sampleEvery);
			return;
		}
		if ($selection instanceof Polygon) {
			$this->buildPolygonPoints($selection, $sampleEvery);
			return;
		}
		if (!$selection instanceof Sphere && !$selection instanceof Cylinder) return;

		$count = 0;
		$add = function (int $x, int $y, int $z) use (&$count, $sampleEvery): void {
			if (($count++ % $sampleEvery) === 0) {
				$this->points["$x:$y:$z"] = new Vector3($x, $y, $z);
			}
		};
		foreach ($selection->asShapeConstructors($add, SelectionContext::hollow()) as $constructor) {
			foreach ($selection->getNeededChunks() as $chunk) {
				if ($constructor->needsChunk($chunk)) $constructor->moveTo($chunk);
			}
		}
	}

	private function buildPolygonPoints(Polygon $selection, int $sampleEvery): void
	{
		$vertices = $selection->getVertices();
		$count = count($vertices);
		if ($count < 3) return;
		$minY = $selection->getPos1()->y;
		$maxY = $selection->getPos2()->y;
		$visited = 0;
		for ($i = 0; $i < $count; ++$i) {
			$a = $vertices[$i];
			$b = $vertices[($i + 1) % $count];
			$edgeSteps = max(1, max(abs($b->x - $a->x), abs($b->z - $a->z)));
			for ($step = 0; $step <= $edgeSteps; ++$step) {
				if (($visited++ % $sampleEvery) !== 0) continue;
				$t = $step / $edgeSteps;
				$x = (int) round($a->x + ($b->x - $a->x) * $t);
				$z = (int) round($a->z + ($b->z - $a->z) * $t);
				$this->points["$x:$minY:$z"] = new Vector3($x, $minY, $z);
				$this->points["$x:$maxY:$z"] = new Vector3($x, $maxY, $z);
			}
			for ($y = $minY; $y <= $maxY; ++$y) {
				if (($visited++ % $sampleEvery) !== 0) continue;
				$this->points[$a->x . ":" . $y . ":" . $a->z] = new Vector3($a->x, $y, $a->z);
			}
		}
	}

	private function buildEllipsoidPoints(Ellipsoid $selection, int $sampleEvery): void
	{
		$rx = $selection->getRadiusX();
		$ry = $selection->getRadiusY();
		$rz = $selection->getRadiusZ();
		$steps = max(8, min(180, (int) ceil(max($rx, $ry, $rz) * 2)));
		$phiSteps = $steps * 2;
		$count = 0;
		$center = $selection->getPoint();
		for ($i = 0; $i <= $steps; ++$i) {
			$theta = M_PI * $i / $steps;
			for ($j = 0; $j < $phiSteps; ++$j) {
				$phi = 2 * M_PI * $j / $phiSteps;
				$x = (int) round($center->x + $rx * sin($theta) * cos($phi));
				$y = (int) round($center->y + $ry * cos($theta));
				$z = (int) round($center->z + $rz * sin($theta) * sin($phi));
				if (($count++ % $sampleEvery) === 0) $this->points["$x:$y:$z"] = new Vector3($x, $y, $z);
			}
		}
	}

	public function send(Player $player): void
	{
		if ($player->getWorld()->getFolderName() !== $this->getWorldName()) return;
		$glass = VanillaBlocks::GLASS();
		foreach ($this->pointsByChunk as $chunk => $points) {
			World::getXZ($chunk, $chunkX, $chunkZ);
			if (!$player->isUsingChunk($chunkX, $chunkZ)) continue;
			foreach ($points as $position) PacketUtils::sendFakeBlockAt($player, $position, $glass);
			$this->sentChunks[$chunk] = true;
		}
	}

	public function remove(Player $player): void
	{
		if ($player->getWorld()->getFolderName() !== $this->getWorldName()) return;
		foreach ($this->pointsByChunk as $chunk => $points) {
			World::getXZ($chunk, $chunkX, $chunkZ);
			if (!$player->isUsingChunk($chunkX, $chunkZ)) continue;
			foreach ($points as $position) PacketUtils::resendBlock($position, $player->getWorld(), $player);
		}
		$this->sentChunks = [];
	}

	public function checkResend(Player $player): void
	{
		$this->send($player);
	}

	public function update(Player $player): void
	{
		if ($player->getWorld()->getFolderName() !== $this->getWorldName()) return;
		foreach ($this->pointsByChunk as $chunk => $points) {
			World::getXZ($chunk, $chunkX, $chunkZ);
			if (!$player->isUsingChunk($chunkX, $chunkZ)) {
				unset($this->sentChunks[$chunk]);
				continue;
			}
			if (isset($this->sentChunks[$chunk])) continue;
			foreach ($points as $position) PacketUtils::sendFakeBlockAt($player, $position, VanillaBlocks::GLASS());
			$this->sentChunks[$chunk] = true;
		}
	}
}
