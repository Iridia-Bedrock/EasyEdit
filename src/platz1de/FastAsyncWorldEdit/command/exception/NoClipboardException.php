<?php

namespace platz1de\FastAsyncWorldEdit\command\exception;

use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\session\Session;

class NoClipboardException extends CommandException
{
	public function sendWarning(Session $session, FAWECommand $command): void
	{
		$session->sendMessage("no-clipboard");
	}
}