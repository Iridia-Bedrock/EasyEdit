<?php

namespace platz1de\FastAsyncWorldEdit\schematic\nbt;

use pocketmine\utils\Binary;

class BigEndianAbstractNBTSerializer extends BaseAbstractNBTSerializer
{
	public function readShort(): int
	{
		return Binary::readShort($this->buffer->get(2));
	}

	public function readSignedShort(): int
	{
		return Binary::readSignedShort($this->buffer->get(2));
	}

	public function readInt(): int
	{
		return Binary::readInt($this->buffer->get(4));
	}

	public function readLong(): int
	{
		return Binary::readLong($this->buffer->get(8));
	}

	public function readFloat(): float
	{
		return Binary::readFloat($this->buffer->get(4));
	}

	public function readDouble(): float
	{
		return Binary::readDouble($this->buffer->get(8));
	}

	protected function getIntArrayFormat(): string
	{
		return "N*";
	}
}
