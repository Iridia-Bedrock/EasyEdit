<?php

namespace platz1de\FastAsyncWorldEdit\task\expanding;

use BadMethodCallException;
use platz1de\FastAsyncWorldEdit\math\BlockVector;
use platz1de\FastAsyncWorldEdit\pattern\block\StaticBlock;
use platz1de\FastAsyncWorldEdit\task\CancelException;
use platz1de\FastAsyncWorldEdit\task\editing\EditTaskHandler;
use platz1de\FastAsyncWorldEdit\utils\ConfigManager;
use platz1de\FastAsyncWorldEdit\utils\ExtendedBinaryStream;
use platz1de\FastAsyncWorldEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\math\Axis;
use pocketmine\math\Facing;
use pocketmine\world\World;
use SplPriorityQueue;

class FillTask extends ExpandingTask
{
	/**
	 * @param string      $world
	 * @param BlockVector $start
	 * @param int         $direction
	 * @param StaticBlock $block
	 */
	public function __construct(string $world, BlockVector $start, private int $direction, private StaticBlock $block)
	{
		parent::__construct($world, $start);
	}

	/**
	 * @param EditTaskHandler     $handler
	 * @param ManagedChunkHandler $loader
	 * @throws CancelException
	 */
	public function executeEdit(EditTaskHandler $handler, ManagedChunkHandler $loader): void
	{
		$ignore = HeightMapCache::getIgnoreLookup();
		unset($ignore[$this->block->getTypeId()]);

		$queue = new SplPriorityQueue();
		$scheduled = [];
		$id = $this->block->get();
		$startX = $this->start->x;
		$startY = $this->start->y;
		$startZ = $this->start->z;
		if (!isset(Facing::OFFSET[$this->direction])) throw new BadMethodCallException("Invalid direction");
		$directionAxis = Facing::axis($this->direction);
		$directionPositive = Facing::isPositive($this->direction);
		$startCoordinate = match ($directionAxis) {
			Axis::X => $startX,
			Axis::Y => $startY,
			Axis::Z => $startZ,
		};
		$limit = ConfigManager::getFillDistance();

		$queue->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
		$queue->insert(World::blockHash($startX, $startY, $startZ), 0);
		while (!$queue->isEmpty()) {
			/** @var array{data: int, priority: int} $current */
			$current = $queue->extract();
			if (-$current["priority"] > $limit) {
				break;
			}
			World::getBlockXYZ($current["data"], $x, $y, $z);
			$chunk = World::chunkHash($x >> 4, $z >> 4);
			$this->updateProgress(-$current["priority"], $limit);
			$loader->checkRuntimeChunk($chunk);
			if (!isset($ignore[$handler->getResultingBlock($x, $y, $z) >> Block::INTERNAL_STATE_DATA_BITS])) {
				$loader->checkUnload($handler, $chunk);
				continue;
			}
			$handler->changeBlock($x, $y, $z, $id);
			foreach (Facing::ALL as $facing) {
				[$offsetX, $offsetY, $offsetZ] = Facing::OFFSET[$facing];
				$sideX = $x + $offsetX;
				$sideY = min(World::Y_MAX - 1, max(World::Y_MIN, $y + $offsetY));
				$sideZ = $z + $offsetZ;
				$sideCoordinate = match ($directionAxis) {
					Axis::X => $sideX,
					Axis::Y => $sideY,
					Axis::Z => $sideZ,
				};
				$valid = $directionPositive ? $sideCoordinate >= $startCoordinate : $sideCoordinate <= $startCoordinate;
				if ($valid && !isset($scheduled[$hash = World::blockHash($sideX, $sideY, $sideZ)])) {
					$scheduled[$hash] = true;
					$loader->registerRequestedChunks(World::chunkHash($sideX >> 4, $sideZ >> 4));
					$queue->insert($hash, $facing === Facing::DOWN || $facing === Facing::UP ? $current["priority"] : $current["priority"] - 1);
				}
			}
			$loader->checkUnload($handler, $chunk);
		}
	}

	public function getTaskName(): string
	{
		return "fill";
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putByte($this->direction);
		$stream->putInt($this->block->get());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->direction = $stream->getByte();
		$this->block = new StaticBlock($stream->getInt());
	}
}
