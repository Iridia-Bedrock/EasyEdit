<?php

namespace platz1de\FastAsyncWorldEdit\pattern\functional;

use platz1de\FastAsyncWorldEdit\pattern\Pattern;
use platz1de\FastAsyncWorldEdit\pattern\type\EmptyPatternData;
use platz1de\FastAsyncWorldEdit\selection\Selection;
use platz1de\FastAsyncWorldEdit\world\ChunkController;
use platz1de\FastAsyncWorldEdit\world\HeightMapCache;

class GravityPattern extends Pattern
{
	use EmptyPatternData;

	/**
	 * @param int             $x
	 * @param int             $y
	 * @param int             $z
	 * @param ChunkController $iterator
	 * @param Selection       $current
	 * @return int
	 */
	public function getFor(int $x, int &$y, int $z, ChunkController $iterator, Selection $current): int
	{
		HeightMapCache::load($iterator, $x >> 4, $z >> 4);

		$originalY = $y;
		if (!HeightMapCache::isSolid($x, $y, $z)) {
			$y -= HeightMapCache::searchSolidDownwards($x, $y, $z) - 1;
		}
		return parent::getFor($x, $originalY, $z, $iterator, $current);
	}
}