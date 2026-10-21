<?php

namespace platz1de\FastAsyncWorldEdit\command;

use Generator;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\session\Session;

class FlagRemapAlias extends FAWECommand
{
	public function __construct(private FAWECommand $parent, private CommandFlag $flag, string $name, array $aliases = [])
	{
		parent::__construct($name, $parent->getPermissions(), $aliases);
	}

	public function process(Session $session, CommandFlagCollection $flags): void
	{
		if ($flags->hasFlag($this->flag->getName())) {
			$flags->removeFlag($this->flag->getName());
		}
		$flags->addFlag($this->flag);
		$this->parent->process($session, $flags);
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return $this->parent->getKnownFlags($session);
	}

	/**
	 * @param CommandFlagCollection $flags
	 * @param Session               $session
	 * @param string[]              $args
	 * @return Generator<CommandFlag>
	 */
	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		yield from $this->parent->parseArguments($flags, $session, $args);
	}
}