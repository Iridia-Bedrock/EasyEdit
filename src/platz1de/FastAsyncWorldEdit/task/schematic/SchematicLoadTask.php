<?php

namespace platz1de\FastAsyncWorldEdit\task\schematic;

use platz1de\FastAsyncWorldEdit\result\EditTaskResult;
use platz1de\FastAsyncWorldEdit\schematic\SchematicFileAdapter;
use platz1de\FastAsyncWorldEdit\selection\DynamicBlockListSelection;
use platz1de\FastAsyncWorldEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\FastAsyncWorldEdit\task\EditThreadExclusive;
use platz1de\FastAsyncWorldEdit\task\ExecutableTask;
use platz1de\FastAsyncWorldEdit\utils\ExtendedBinaryStream;

/**
 * @extends ExecutableTask<EditTaskResult>
 */
class SchematicLoadTask extends ExecutableTask
{
	use EditThreadExclusive;

	/**
	 * @param string $schematicPath
	 */
	public function __construct(private string $schematicPath)
	{
		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "schematic_load";
	}

	protected function executeInternal(): EditTaskResult
	{
		$selection = DynamicBlockListSelection::empty();
		SchematicFileAdapter::readIntoSelection($this->schematicPath, $selection);
		return new EditTaskResult($selection->getIterator()->getWrittenBlockCount(), $selection);
	}

	public function attemptRecovery(): EditTaskResult
	{
		return new EditTaskResult(0, StoredSelectionIdentifier::invalid());
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->schematicPath);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->schematicPath = $stream->getString();
	}
}