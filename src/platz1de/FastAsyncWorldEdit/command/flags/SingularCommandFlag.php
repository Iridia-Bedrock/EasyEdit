<?php

namespace platz1de\FastAsyncWorldEdit\command\flags;

use InvalidArgumentException;
use platz1de\FastAsyncWorldEdit\session\Session;
use platz1de\FastAsyncWorldEdit\utils\ArgumentParser;

class SingularCommandFlag extends CommandFlag
{
	public function needsArgument(): bool
	{
		return false;
	}

	public function parseArgument(Session $session, string $argument): self
	{
		throw new InvalidArgumentException("This flag does not need an argument");
	}

	public function fits(string $argument): bool
	{
		return in_array(strtolower($argument), ["true", "t", "yes", "y", "1", "+"], true);
	}
}