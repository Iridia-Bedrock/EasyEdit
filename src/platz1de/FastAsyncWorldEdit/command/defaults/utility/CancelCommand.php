<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\utility;

use Generator;
use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\session\Session;
use platz1de\FastAsyncWorldEdit\thread\input\task\CancelTaskData;
use platz1de\FastAsyncWorldEdit\thread\ThreadStats;

class CancelCommand extends FAWECommand
{
	public function __construct()
	{
		parent::__construct("/cancel", [KnownPermissions::PERMISSION_MANAGE]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		if (!ThreadStats::getInstance()->hasTask()) {
			$session->sendMessage("no-task");
			return;
		}
		CancelTaskData::from($session->getIdentifier());
		$session->sendMessage("task-cancelled");
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
		yield from [];
	}
}