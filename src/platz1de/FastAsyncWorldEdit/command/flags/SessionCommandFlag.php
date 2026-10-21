<?php

namespace platz1de\FastAsyncWorldEdit\command\flags;

use BadMethodCallException;
use platz1de\FastAsyncWorldEdit\command\exception\InvalidUsageException;
use platz1de\FastAsyncWorldEdit\session\Session;
use platz1de\FastAsyncWorldEdit\session\SessionManager;

/**
 * @extends ValuedCommandFlag<Session>
 */
class SessionCommandFlag extends ValuedCommandFlag
{
	/**
	 * @param Session         $session
	 * @param string          $argument
	 * @return SessionCommandFlag
	 */
	public function parseArgument(Session $session, string $argument): CommandFlag
	{
		try {
			$this->setArgument(SessionManager::get($argument, false));
		} catch (BadMethodCallException) {
			throw new InvalidUsageException();
		}
		return $this;
	}
}