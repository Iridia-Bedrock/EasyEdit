<?php

namespace platz1de\FastAsyncWorldEdit\pattern\logic\relation;

use platz1de\FastAsyncWorldEdit\pattern\Pattern;
use platz1de\FastAsyncWorldEdit\pattern\type\BlockPatternData;
use platz1de\FastAsyncWorldEdit\selection\Selection;
use platz1de\FastAsyncWorldEdit\world\ChunkController;

class AbovePattern extends Pattern
{
	use BlockPatternData;

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
		$y--;
		if ($y >= 0) {
			return $this->block->equals($iterator->getBlock($x, $y, $z));
		}
		return false;
	}
}