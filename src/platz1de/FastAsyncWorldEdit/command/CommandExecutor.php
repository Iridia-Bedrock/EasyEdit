<?php

namespace platz1de\FastAsyncWorldEdit\command;

use platz1de\FastAsyncWorldEdit\command\flags\CommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\session\Session;
use Generator;

interface CommandExecutor
{
	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void;

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array;

	/**
	 * @param CommandFlagCollection $flags
	 * @param Session               $session
	 * @param string[]              $args
	 * @return Generator<CommandFlag>
	 */
	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator;
}