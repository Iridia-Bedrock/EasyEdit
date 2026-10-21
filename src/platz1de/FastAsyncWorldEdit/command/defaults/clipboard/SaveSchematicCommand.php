<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\clipboard;

use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\command\exception\InvalidUsageException;
use platz1de\FastAsyncWorldEdit\command\FlagArgumentParser;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\flags\StringCommandFlag;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\FastAsyncWorldEdit;
use platz1de\FastAsyncWorldEdit\result\SelectionManipulationResult;
use platz1de\FastAsyncWorldEdit\session\Session;
use platz1de\FastAsyncWorldEdit\task\schematic\SchematicSaveTask;
use platz1de\FastAsyncWorldEdit\utils\MixedUtils;

class SaveSchematicCommand extends FAWECommand
{
	use FlagArgumentParser;

	public function __construct()
	{
		parent::__construct("/saveschematic", [KnownPermissions::PERMISSION_WRITEDISK, KnownPermissions::PERMISSION_CLIPBOARD], ["/save"]);
		$this->flagOrder = ["schematic" => true];
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$schematicName = pathinfo($flags->getStringFlag("schematic"), PATHINFO_FILENAME);
		if ($schematicName === "") {
			throw new InvalidUsageException();
		}

		$session->runTask(new SchematicSaveTask($session->getClipboard(), FastAsyncWorldEdit::getSchematicPath() . $schematicName))->then(function (SelectionManipulationResult $result) use ($schematicName, $session): void {
			if ($result->getChanged() === 0) {
				return;
			}
			$session->sendMessage("schematic-created", ["{time}" => $result->getFormattedTime(), "{changed}" => MixedUtils::humanReadable($result->getChanged()), "{name}" => basename($schematicName)]);
		});
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"schematic" => new StringCommandFlag("schematic", ["schem"], "s")
		];
	}
}