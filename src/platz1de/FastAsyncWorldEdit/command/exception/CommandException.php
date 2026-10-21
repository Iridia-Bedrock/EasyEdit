<?php

namespace platz1de\FastAsyncWorldEdit\command\exception;

use InvalidArgumentException;
use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\session\Session;

/**
 * Exceptions thrown if commands could not be executed correctly
 */
abstract class CommandException extends InvalidArgumentException
{
	public function sendWarning(Session $session, FAWECommand $command): void { }
}