<?php

namespace platz1de\FastAsyncWorldEdit\schematic\type;

use platz1de\FastAsyncWorldEdit\schematic\nbt\AbstractNBTSerializer;
use platz1de\FastAsyncWorldEdit\selection\DynamicBlockListSelection;
use pocketmine\nbt\tag\CompoundTag;

abstract class SchematicType
{
	public const TAG_WIDTH = "Width";
	public const TAG_HEIGHT = "Height";
	public const TAG_LENGTH = "Length";

	abstract public static function readIntoSelection(CompoundTag $nbt, DynamicBlockListSelection $target): void;

	abstract public static function getNbtSerializer(): AbstractNBTSerializer;
}