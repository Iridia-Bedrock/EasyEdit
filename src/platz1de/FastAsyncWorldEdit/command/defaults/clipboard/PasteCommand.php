<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\clipboard;

use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\command\FlagArgumentParser;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\flags\IntegerCommandFlag;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\math\OffGridBlockVector;
use platz1de\FastAsyncWorldEdit\session\Session;
use platz1de\FastAsyncWorldEdit\task\editing\DynamicPasteTask;

class PasteCommand extends FAWECommand
{
	use FlagArgumentParser;

	public function __construct()
	{
		parent::__construct("/paste", [KnownPermissions::PERMISSION_CLIPBOARD, KnownPermissions::PERMISSION_EDIT]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$session->runEditTask("blocks-pasted", new DynamicPasteTask($session->asPlayer()->getWorld()->getFolderName(), $session->getClipboard(), OffGridBlockVector::fromVector($session->asPlayer()->getPosition()), $flags->getIntFlag("mode")));
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"default" => IntegerCommandFlag::with(DynamicPasteTask::MODE_REPLACE_ALL, "mode", [], "d", true),
			"insert" => IntegerCommandFlag::with(DynamicPasteTask::MODE_REPLACE_AIR, "mode", [], "i"),
			"merge" => IntegerCommandFlag::with(DynamicPasteTask::MODE_ONLY_SOLID, "mode", ["solid"], "m"),
			"replace" => IntegerCommandFlag::with(DynamicPasteTask::MODE_REPLACE_SOLID, "mode", [], "r")
		];
	}
}