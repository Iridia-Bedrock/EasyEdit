<?php

namespace platz1de\FastAsyncWorldEdit\command\exception;

use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\session\Session;
use pocketmine\lang\Translatable;
use UnexpectedValueException;

class InvalidUsageException extends CommandException
{
	public function sendWarning(Session $session, FAWECommand $command): void
	{
		if ($command->getUsage() instanceof Translatable) {
			throw new UnexpectedValueException("FAWE commands should not use translatable usages");
		}
		$session->sendMessage("wrong-usage", ["{usage}" => $command->getUsage()]);
	}
}
