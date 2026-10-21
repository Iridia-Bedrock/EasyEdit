<?php

namespace platz1de\FastAsyncWorldEdit\task\expanding;

use platz1de\FastAsyncWorldEdit\FastAsyncWorldEdit;
use platz1de\FastAsyncWorldEdit\math\BlockVector;
use platz1de\FastAsyncWorldEdit\result\EditTaskResult;
use platz1de\FastAsyncWorldEdit\selection\BlockListSelection;
use platz1de\FastAsyncWorldEdit\selection\ExpandingStaticBlockListSelection;
use platz1de\FastAsyncWorldEdit\task\CancelException;
use platz1de\FastAsyncWorldEdit\task\editing\EditTaskHandler;
use platz1de\FastAsyncWorldEdit\task\EditThreadExclusive;
use platz1de\FastAsyncWorldEdit\task\ExecutableTask;
use platz1de\FastAsyncWorldEdit\thread\EditThread;
use platz1de\FastAsyncWorldEdit\thread\modules\StorageModule;
use platz1de\FastAsyncWorldEdit\utils\ExtendedBinaryStream;
use platz1de\FastAsyncWorldEdit\world\HeightMapCache;
use pocketmine\world\World;

/**
 * @extends ExecutableTask<EditTaskResult>
 */
abstract class ExpandingTask extends ExecutableTask
{
	use EditThreadExclusive;

	protected BlockListSelection $undo;
	protected EditTaskHandler $handler;

	/**
	 * @param string      $world
	 * @param BlockVector $start
	 */
	public function __construct(private string $world, protected BlockVector $start)
	{
		parent::__construct();
	}

	protected function executeInternal(): EditTaskResult
	{
		$this->undo = $this->createUndoBlockList();
		$this->handler = new EditTaskHandler($this->world, $this->undo);
		$loader = new ManagedChunkHandler($this->handler);
		FastAsyncWorldEdit::getEnv()->initChunkHandler($loader);
		$loader->request(World::chunkHash($this->start->x >> 4, $this->start->z >> 4));

		HeightMapCache::prepare();

		$this->executeEdit($this->handler, $loader);
		EditThread::getInstance()->debug("Task " . $this->getTaskName() . ":" . $this->getTaskId() . " was executed successful, changing " . $this->handler->getChangedBlockCount() . " blocks (" . $this->handler->getReadBlockCount() . " read, " . $this->handler->getWrittenBlockCount() . " written)");

		$this->handler->finish();

		return $this->toTaskResult();
	}

	protected function toTaskResult(): EditTaskResult
	{
		return new EditTaskResult($this->handler->getChangedBlockCount(), $this->undo);
	}

	public function attemptRecovery(): EditTaskResult
	{
		return $this->toTaskResult();
	}

	/**
	 * @param EditTaskHandler     $handler
	 * @param ManagedChunkHandler $loader
	 * @throws CancelException
	 */
	abstract public function executeEdit(EditTaskHandler $handler, ManagedChunkHandler $loader): void;

	/**
	 * @return BlockListSelection
	 */
	public function createUndoBlockList(): BlockListSelection
	{
		return new ExpandingStaticBlockListSelection($this->world, $this->start);
	}

	public function updateProgress(int $current, int $max): void
	{
		EditThread::getInstance()->getStats()->updateProgress($current / $max);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->world);
		$stream->putBlockVector($this->start);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->world = $stream->getString();
		$this->start = $stream->getBlockVector();
	}

	/**
	 * @return string
	 */
	public function getWorld(): string
	{
		return $this->world;
	}
}