<?php

namespace platz1de\FastAsyncWorldEdit\command\flags;

use platz1de\FastAsyncWorldEdit\session\Session;
use platz1de\FastAsyncWorldEdit\utils\ArgumentParser;

class FacingCommandFlag extends IntegerCommandFlag
{
	/**
	 * @param Session         $session
	 * @param string          $argument
	 * @return FacingCommandFlag
	 */
	public function parseArgument(Session $session, string $argument): self
	{
		$this->setArgument(ArgumentParser::parseFacing($session, $argument));
		return $this;
	}
}