<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\clipboard;

use Generator;
use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\flags\StringCommandFlag;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\FastAsyncWorldEdit;
use platz1de\FastAsyncWorldEdit\result\EditTaskResult;
use platz1de\FastAsyncWorldEdit\schematic\SchematicFileAdapter;
use platz1de\FastAsyncWorldEdit\session\Session;
use platz1de\FastAsyncWorldEdit\task\schematic\SchematicLoadTask;
use platz1de\FastAsyncWorldEdit\utils\MixedUtils;

class LoadSchematicCommand extends FAWECommand
{
	public function __construct()
	{
		parent::__construct("/loadschematic", [KnownPermissions::PERMISSION_READDISK, KnownPermissions::PERMISSION_CLIPBOARD], ["/load"]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$schematicName = $flags->hasFlag("schematic") ? pathinfo($flags->getStringFlag("schematic"), PATHINFO_FILENAME) : "";
		if ($schematicName === "" || !SchematicFileAdapter::schematicExists(FastAsyncWorldEdit::getSchematicPath() . $schematicName)) {
			$session->sendMessage("unknown-schematic", ["{schematic}" => $schematicName, "{known}" => implode(", ", SchematicFileAdapter::getSchematicList())]);
			return;
		}

		$session->runTask(new SchematicLoadTask(FastAsyncWorldEdit::getSchematicPath() . $schematicName))->then(function (EditTaskResult $result) use ($session) {
			$session->sendMessage("blocks-copied", ["{time}" => $result->getFormattedTime(), "{changed}" => MixedUtils::humanReadable($result->getAffected())]);
			$session->setClipboard($result->getSelection());
		});
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [];
	}

	/**
	 * @param CommandFlagCollection $flags
	 * @param Session               $session
	 * @param string[]              $args
	 * @return Generator<CommandFlag>
	 */
	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		if (isset($args[0])) {
			yield StringCommandFlag::with($args[0], "schematic");
		}
	}
}