<?php

namespace platz1de\FastAsyncWorldEdit\command\flags;

use platz1de\FastAsyncWorldEdit\command\exception\PatternParseException;
use platz1de\FastAsyncWorldEdit\pattern\parser\ParseError;
use platz1de\FastAsyncWorldEdit\pattern\parser\PatternParser;
use platz1de\FastAsyncWorldEdit\pattern\Pattern;
use platz1de\FastAsyncWorldEdit\session\Session;

/**
 * @extends ValuedCommandFlag<Pattern>
 */
class PatternCommandFlag extends ValuedCommandFlag
{
	/**
	 * @param Session         $session
	 * @param string          $argument
	 * @return PatternCommandFlag
	 */
	public function parseArgument(Session $session, string $argument): CommandFlag
	{
		try {
			$this->setArgument(PatternParser::parseInput($argument, $session->asPlayer()));
			return $this;
		} catch (ParseError $exception) {
			throw new PatternParseException($exception);
		}
	}
}