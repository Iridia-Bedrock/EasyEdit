<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\movement;

use Generator;
use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\session\Session;

class UnstuckCommand extends FAWECommand
{
	public function __construct()
	{
		parent::__construct("/unstuck", [KnownPermissions::PERMISSION_UTIL], ["/u"]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$player = $session->asPlayer();
		$player->teleport($player->getWorld()->getSafeSpawn($player->getPosition()));
	}

	public function getKnownFlags(Session $session): array
	{
		return [];
	}

	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		yield from [];
	}
}