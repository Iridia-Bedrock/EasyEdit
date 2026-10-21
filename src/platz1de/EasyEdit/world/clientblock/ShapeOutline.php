<?php

namespace platz1de\EasyEdit\world\clientblock;

use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\selection\ConvexPolyhedron;
use platz1de\EasyEdit\selection\Ellipsoid;
use platz1de\EasyEdit\selection\Polygon;
use platz1de\EasyEdit\selection\Selection;
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
		if ($selection instanceof ConvexPolyhedron) {
			$this->buildConvexPoints($selection, $sampleEvery);
			return;
		}
		if ($selection instanceof Sphere) $this->buildSpherePoints($selection);
		elseif ($selection instanceof Cylinder) $this->buildCylinderPoints($selection);
	}

	private function buildPolygonPoints(Polygon $selection, int $sampleEvery): void
	{
		$vertices = $selection->getVertices();
		$count = count($vertices);
		if ($count < 3) return;
		$minY = $selection->getPos1()->y;
		$maxY = $selection->getPos2()->y;
		for ($i = 0; $i < $count; ++$i) {
			$a = $vertices[$i];
			$b = $vertices[($i + 1) % $count];
			$edgeSteps = max(1, max(abs($b->x - $a->x), abs($b->z - $a->z)));
			for ($step = 0; $step <= $edgeSteps; $step += $sampleEvery) {
				$t = $step / $edgeSteps;
				$x = (int) round($a->x + ($b->x - $a->x) * $t);
				$z = (int) round($a->z + ($b->z - $a->z) * $t);
				$this->points["$x:$minY:$z"] = new Vector3($x, $minY, $z);
				$this->points["$x:$maxY:$z"] = new Vector3($x, $maxY, $z);
			}
			if ($edgeSteps % $sampleEvery !== 0) {
				$x = (int) round($b->x);
				$z = (int) round($b->z);
				$this->points["$x:$minY:$z"] = new Vector3($x, $minY, $z);
				$this->points["$x:$maxY:$z"] = new Vector3($x, $maxY, $z);
			}
			for ($y = $minY; $y <= $maxY; $y += $sampleEvery) {
				$this->points[$a->x . ":" . $y . ":" . $a->z] = new Vector3($a->x, $y, $a->z);
			}
			if (($maxY - $minY) % $sampleEvery !== 0) {
				$this->points[$a->x . ":" . $maxY . ":" . $a->z] = new Vector3($a->x, $maxY, $a->z);
			}
		}
	}

	private function buildConvexPoints(ConvexPolyhedron $selection, int $sampleEvery): void
	{
		foreach ($selection->getHullEdges() as [$a, $b]) {
			$steps = max(1, max(abs($b->x - $a->x), abs($b->y - $a->y), abs($b->z - $a->z)));
			for ($step = 0; $step <= $steps; $step += $sampleEvery) {
				$t = $step / $steps;
				$x = (int) round($a->x + ($b->x - $a->x) * $t);
				$y = (int) round($a->y + ($b->y - $a->y) * $t);
				$z = (int) round($a->z + ($b->z - $a->z) * $t);
				$this->points["$x:$y:$z"] = new Vector3($x, $y, $z);
			}
			if ($steps % $sampleEvery !== 0) {
				$this->points[$b->x . ":" . $b->y . ":" . $b->z] = new Vector3($b->x, $b->y, $b->z);
			}
		}
	}

	private function buildSpherePoints(Sphere $selection): void
	{
		$center = $selection->getPoint();
		$radius = max(0.0, $selection->getRadius());
		$steps = max(8, min(64, (int) ceil($radius * 2)));
		$segments = $steps * 2;
		for ($ring = 0; $ring <= $steps; ++$ring) {
			$theta = M_PI * $ring / $steps;
			for ($segment = 0; $segment < $segments; ++$segment) {
				$phi = 2 * M_PI * $segment / $segments;
				$this->addPoint(
					(int) round($center->x + $radius * sin($theta) * cos($phi)),
					(int) round($center->y + $radius * cos($theta)),
					(int) round($center->z + $radius * sin($theta) * sin($phi))
				);
			}
		}
		for ($rail = 0; $rail < 8; ++$rail) {
			$phi = 2 * M_PI * $rail / 8;
			for ($step = 0; $step <= $steps; ++$step) {
				$theta = M_PI * $step / $steps;
				$this->addPoint(
					(int) round($center->x + $radius * sin($theta) * cos($phi)),
					(int) round($center->y + $radius * cos($theta)),
					(int) round($center->z + $radius * sin($theta) * sin($phi))
				);
			}
		}
	}

	private function buildCylinderPoints(Cylinder $selection): void
	{
		$center = $selection->getPoint();
		$radius = max(0.0, $selection->getRadius());
		$height = max(1, $selection->getHeight());
		$segments = max(8, min(512, (int) ceil($radius * 2)));
		$minY = (int) floor($center->y);
		$maxY = $minY + $height - 1;
		for ($segment = 0; $segment < $segments; ++$segment) {
			$angle = 2 * M_PI * $segment / $segments;
			$x = (int) round($center->x + $radius * cos($angle));
			$z = (int) round($center->z + $radius * sin($angle));
			$this->addPoint($x, $minY, $z);
			$this->addPoint($x, $maxY, $z);
		}
		for ($rail = 0; $rail < 16; ++$rail) {
			$angle = 2 * M_PI * $rail / 16;
			$x = (int) round($center->x + $radius * cos($angle));
			$z = (int) round($center->z + $radius * sin($angle));
			for ($y = $minY; $y <= $maxY; ++$y) $this->addPoint($x, $y, $z);
		}
	}

	private function addPoint(int $x, int $y, int $z): void
	{
		$this->points["$x:$y:$z"] = new Vector3($x, $y, $z);
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
