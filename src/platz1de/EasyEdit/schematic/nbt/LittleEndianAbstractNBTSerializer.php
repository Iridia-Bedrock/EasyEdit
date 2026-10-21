<?php

namespace platz1de\EasyEdit\schematic\nbt;

use pocketmine\utils\Binary;

class LittleEndianAbstractNBTSerializer extends BaseAbstractNBTSerializer
{
	public function readShort(): int
	{
		return Binary::readLShort($this->buffer->get(2));
	}

	public function readSignedShort(): int
	{
		return Binary::readSignedLShort($this->buffer->get(2));
	}

	public function readInt(): int
	{
		return Binary::readLInt($this->buffer->get(4));
	}

	public function readLong(): int
	{
		return Binary::readLLong($this->buffer->get(8));
	}

	public function readFloat(): float
	{
		return Binary::readLFloat($this->buffer->get(4));
	}

	public function readDouble(): float
	{
		return Binary::readLDouble($this->buffer->get(8));
	}

	protected function getIntArrayFormat(): string
	{
		return "V*";
	}
}
