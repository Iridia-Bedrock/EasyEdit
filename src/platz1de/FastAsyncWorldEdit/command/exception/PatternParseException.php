<?php

namespace platz1de\FastAsyncWorldEdit\command\exception;

use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\pattern\parser\ParseError;
use platz1de\FastAsyncWorldEdit\session\Session;

class PatternParseException extends CommandException
{
	public function __construct(ParseError $error)
	{
		parent::__construct($error->getMessage());
	}

	public function sendWarning(Session $session, FAWECommand $command): void
	{
		$session->sendMessage("pattern-invalid", ["{message}" => $this->getMessage()]);
	}
}