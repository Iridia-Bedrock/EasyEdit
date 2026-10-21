<?php

namespace platz1de\FastAsyncWorldEdit\command\flags;

use platz1de\FastAsyncWorldEdit\command\exception\InvalidUsageException;
use platz1de\FastAsyncWorldEdit\math\OffGridBlockVector;
use platz1de\FastAsyncWorldEdit\session\Session;

/**
 * @extends ValuedCommandFlag<OffGridBlockVector>
 */
class VectorCommandFlag extends ValuedCommandFlag
{
	/**
	 * @param Session         $session
	 * @param string          $argument
	 * @return VectorCommandFlag
	 */
	public function parseArgument(Session $session, string $argument): self
	{
		$vector = explode(",", $argument);
		if (count($vector) !== 3) {
			throw new InvalidUsageException();
		}
		$this->setArgument(new OffGridBlockVector((int) $vector[0], (int) $vector[1], (int) $vector[2]));
		return $this;
	}
}