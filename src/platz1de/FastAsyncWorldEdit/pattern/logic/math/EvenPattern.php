<?php

namespace platz1de\FastAsyncWorldEdit\pattern\logic\math;

use platz1de\FastAsyncWorldEdit\pattern\Pattern;
use platz1de\FastAsyncWorldEdit\pattern\type\AxisPatternData;
use platz1de\FastAsyncWorldEdit\selection\Selection;
use platz1de\FastAsyncWorldEdit\world\ChunkController;

class EvenPattern extends Pattern
{
	use AxisPatternData;

	/**
	 * @param int             $x
	 * @param int             $y
	 * @param int             $z
	 * @param ChunkController $iterator
	 * @param Selection       $selection
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, ChunkController $iterator, Selection $selection): bool
	{
		if ($this->xAxis && abs($x) % 2 !== 0) {
			return false;
		}
		if ($this->yAxis && abs($y) % 2 !== 0) {
			return false;
		}
		if ($this->zAxis && abs($z) % 2 !== 0) {
			return false;
		}
		return true;
	}
}