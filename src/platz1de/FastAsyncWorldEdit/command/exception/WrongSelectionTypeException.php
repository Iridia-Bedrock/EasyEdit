<?php

namespace platz1de\FastAsyncWorldEdit\command\exception;

use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\session\Session;

class WrongSelectionTypeException extends CommandException
{
	public function __construct(private string $given, private string $expected)
	{
		parent::__construct("Wrong selection type " . $given . " given, expected " . $expected);
	}

	public function sendWarning(Session $session, FAWECommand $command): void
	{
		$session->sendMessage("wrong-selection", ["{given}" => $this->given, "{expected}" => $this->expected]);
	}
}