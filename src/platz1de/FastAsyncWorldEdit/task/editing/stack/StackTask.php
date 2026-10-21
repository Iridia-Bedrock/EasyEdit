<?php

namespace platz1de\FastAsyncWorldEdit\task\editing\stack;

use Generator;
use platz1de\FastAsyncWorldEdit\math\BlockOffsetVector;
use platz1de\FastAsyncWorldEdit\math\BlockVector;
use platz1de\FastAsyncWorldEdit\result\EditTaskResult;
use platz1de\FastAsyncWorldEdit\selection\constructor\ShapeConstructor;
use platz1de\FastAsyncWorldEdit\selection\Cube;
use platz1de\FastAsyncWorldEdit\selection\Selection;
use platz1de\FastAsyncWorldEdit\task\editing\cubic\CubicStaticUndo;
use platz1de\FastAsyncWorldEdit\task\editing\EditTaskHandler;
use platz1de\FastAsyncWorldEdit\task\editing\GroupedChunkHandler;
use platz1de\FastAsyncWorldEdit\task\editing\SelectionEditTask;
use platz1de\FastAsyncWorldEdit\task\editing\SingleChunkHandler;
use platz1de\FastAsyncWorldEdit\utils\ExtendedBinaryStream;
use platz1de\FastAsyncWorldEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\math\Axis;
use pocketmine\world\World;
use UnexpectedValueException;

class StackTask extends SelectionEditTask
{
	use CubicStaticUndo;

	private Selection $original;
	private StackingHelper $helper;

	/**
	 * @param Selection         $selection
	 * @param BlockOffsetVector $direction
	 * @param bool              $insert
	 */
	public function __construct(Selection $selection, private BlockOffsetVector $direction, private bool $insert = false)
	{
		parent::__construct($selection);
	}

	public function calculateEffectiveComplexity(): int
	{
		$this->getSelection(); // initialize the original selection
		return $this->original->getVolume() * $this->direction->cubicVolume();
	}

	public function getSelection(): StackingHelper
	{
		if (!isset($this->helper)) {
			$this->helper = new StackingHelper($this->original = parent::getSelection(), $this->direction);
		}
		return $this->helper;
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "stack";
	}

	/**
	 * @param EditTaskhandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator
	{
		$originalSize = $this->original->getSize();
		$sizeX = $originalSize->x;
		$sizeY = $originalSize->y;
		$sizeZ = $originalSize->z;
		$start = $this->original->getPos1();
		$startX = $start->x;
		$startY = $start->y;
		$startZ = $start->z;
		if ($this->insert) {
			$ignore = HeightMapCache::getIgnore();
			yield from $this->getSelection()->asShapeConstructors(function (int $x, int $y, int $z, ?BlockVector $regionStart = null, ?BlockOffsetVector $regionSize = null) use ($ignore, $handler, $sizeX, $sizeY, $sizeZ, $startX, $startY, $startZ): void {
				$regionStart ??= new BlockVector($startX, $startY, $startZ);
				$regionSize ??= new BlockOffsetVector($sizeX, $sizeY, $sizeZ);
				$block = $handler->getBlock($regionStart->x + (($x - $regionStart->x) % $regionSize->x + $regionSize->x) % $regionSize->x, $regionStart->y + (($y - $regionStart->y) % $regionSize->y + $regionSize->y) % $regionSize->y, $regionStart->z + (($z - $regionStart->z) % $regionSize->z + $regionSize->z) % $regionSize->z);
				if ($block !== 0 && in_array($handler->getBlock($x, $y, $z) >> Block::INTERNAL_STATE_DATA_BITS, $ignore, true)) {
					$handler->changeBlock($x, $y, $z, $block);
				}
			}, $this->context);
		} else {
			yield from $this->getSelection()->asShapeConstructors(function (int $x, int $y, int $z, ?BlockVector $regionStart = null, ?BlockOffsetVector $regionSize = null) use ($handler, $sizeX, $sizeY, $sizeZ, $startX, $startY, $startZ): void {
				$regionStart ??= new BlockVector($startX, $startY, $startZ);
				$regionSize ??= new BlockOffsetVector($sizeX, $sizeY, $sizeZ);
				$handler->copyBlock($x, $y, $z, $regionStart->x + (($x - $regionStart->x) % $regionSize->x + $regionSize->x) % $regionSize->x, $regionStart->y + (($y - $regionStart->y) % $regionSize->y + $regionSize->y) % $regionSize->y, $regionStart->z + (($z - $regionStart->z) % $regionSize->z + $regionSize->z) % $regionSize->z);
			}, $this->context);
		}
	}

	/**
	 * @return GroupedChunkHandler
	 */
	public function getChunkHandler(): GroupedChunkHandler
	{
		$selection = $this->getSelection();
		if ($this->original instanceof Cube && count($this->original->getRegions()) > 1) {
			return new SingleChunkHandler($this->getTargetWorld());
		}
		if ($selection->getAxis() === Axis::Y) {
			return new SingleChunkHandler($this->getTargetWorld());
		}
		if ($selection->isCopying()) {
			return new CopyingStackingChunkHandler($this->getTargetWorld(), $this->original, $this->getSelection()->getAxis());
		}
		return new SimpleStackingChunkHandler($this->getTargetWorld(), $this->original, $selection->getAxis());
	}

	protected function sortChunks(array $chunks): array
	{
		if ($this->direction->x !== 0) {
			usort($chunks, static function (int $a, int $b): int {
				World::getXZ($a, $aX, $aZ);
				World::getXZ($b, $bX, $bZ);
				return $aZ - $bZ;
			});
		} else if ($this->direction->z !== 0) {
			usort($chunks, static function (int $a, int $b): int {
				World::getXZ($a, $aX, $aZ);
				World::getXZ($b, $bX, $bZ);
				return $aX - $bX;
			});
		}
		//No sorting needed for y-Axis
		return $chunks;
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putBlockVector($this->direction);
		$stream->putBool($this->insert);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->direction = $stream->getOffsetVector();
		$this->insert = $stream->getBool();
	}
}
