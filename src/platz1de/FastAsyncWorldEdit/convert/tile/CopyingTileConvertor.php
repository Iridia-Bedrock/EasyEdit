<?php

namespace platz1de\FastAsyncWorldEdit\convert\tile;

use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\CompoundTag;

class CopyingTileConvertor extends TileConvertorPiece
{
	public function preprocessTileState(BlockStateData $state): ?CompoundTag
	{
		return null;
	}
}