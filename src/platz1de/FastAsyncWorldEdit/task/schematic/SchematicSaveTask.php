<?php

namespace platz1de\FastAsyncWorldEdit\task\schematic;

use platz1de\FastAsyncWorldEdit\result\SelectionManipulationResult;
use platz1de\FastAsyncWorldEdit\schematic\SchematicFileAdapter;
use platz1de\FastAsyncWorldEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\FastAsyncWorldEdit\task\EditThreadExclusive;
use platz1de\FastAsyncWorldEdit\task\ExecutableTask;
use platz1de\FastAsyncWorldEdit\thread\modules\StorageModule;
use platz1de\FastAsyncWorldEdit\utils\ExtendedBinaryStream;

/**
 * @extends ExecutableTask<SelectionManipulationResult>
 */
class SchematicSaveTask extends ExecutableTask
{
	use EditThreadExclusive;

	/**
	 * @param StoredSelectionIdentifier $saveId
	 * @param string                    $schematicPath
	 */
	public function __construct(private StoredSelectionIdentifier $saveId, private string $schematicPath)
	{
		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "schematic_save";
	}

	protected function executeInternal(): SelectionManipulationResult
	{
		$selection = StorageModule::mustGetDynamic($this->saveId);
		SchematicFileAdapter::createFromSelection($this->schematicPath, $selection);
		return new SelectionManipulationResult($selection->getIterator()->getReadBlockCount(), StoredSelectionIdentifier::invalid());
	}

	public function attemptRecovery(): SelectionManipulationResult
	{
		return new SelectionManipulationResult(0, StoredSelectionIdentifier::invalid());
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->schematicPath);
		$stream->putString($this->saveId->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->schematicPath = $stream->getString();
		$this->saveId = StoredSelectionIdentifier::fastDeserialize($stream->getString());
	}
}