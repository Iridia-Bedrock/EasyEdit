<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\selection;

use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\command\FlagArgumentParser;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\pattern\Pattern;
use platz1de\FastAsyncWorldEdit\session\Session;
use platz1de\FastAsyncWorldEdit\task\editing\SetTask;

abstract class AliasedPatternCommand extends FAWECommand
{
	use FlagArgumentParser;

	/**
	 * @param string   $name
	 * @param bool[]   $flagOrder
	 * @param string[] $aliases
	 */
	public function __construct(string $name, array $flagOrder = [], array $aliases = [])
	{
		parent::__construct($name, [KnownPermissions::PERMISSION_EDIT], $aliases);
		$this->flagOrder = $flagOrder;
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$session->runSettingTask(new SetTask($session->getSelection(), $this->parsePattern($session, $flags)));
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 * @return Pattern
	 */
	abstract public function parsePattern(Session $session, CommandFlagCollection $flags): Pattern;
}