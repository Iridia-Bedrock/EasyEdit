<?php

namespace platz1de\FastAsyncWorldEdit\schematic\nbt;

use pocketmine\nbt\NBT;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\ReaderTracker;
use pocketmine\nbt\TreeRoot;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryDataException;
use function array_values;
use function unpack;

/**
 * Reads nbt data directly from a compressed file instead of a fully loaded buffer.
 * We don't extend the pocketmine serializers here, their internal buffers changed multiple times already.
 */
abstract class BaseAbstractNBTSerializer implements AbstractNBTSerializer
{
	protected CompressedFileStream $buffer;

	/**
	 * @throws BinaryDataException
	 * @throws NbtDataException
	 */
	public function readFile(string $file): TreeRoot
	{
		$this->buffer = new CompressedFileStream($file);
		$type = $this->readByte();
		if ($type === NBT::TAG_End) {
			throw new NbtDataException("Found TAG_End at the start of buffer");
		}

		$rootName = $this->readString();
		return new TreeRoot(AbstractNBT::createTag($type, $this, new ReaderTracker(0)), $rootName);
	}

	public function readChunk(int $count): string
	{
		return $this->buffer->get($count);
	}

	public function skip(int $count): void
	{
		$this->buffer->setOffset($this->buffer->getOffset() + $count);
	}

	public function close(): void
	{
		$this->buffer->close();
	}

	public function __clone(): void
	{
		$this->buffer = clone $this->buffer;
	}

	public function optimizeHighFrequencyAccess(): void
	{
		$this->buffer->optimizeHighFrequencyAccess();
	}

	public function readByte(): int
	{
		return Binary::readByte($this->buffer->get(1));
	}

	public function readSignedByte(): int
	{
		return Binary::readSignedByte($this->buffer->get(1));
	}

	public function readByteArray(): string
	{
		$length = $this->readInt();
		if ($length < 0) {
			throw new NbtDataException("Array length cannot be less than zero ($length < 0)");
		}
		return $this->buffer->get($length);
	}

	public function readIntArray(): array
	{
		$length = $this->readInt();
		if ($length < 0) {
			throw new NbtDataException("Array length cannot be less than zero ($length < 0)");
		}
		$unpacked = unpack($this->getIntArrayFormat(), $this->buffer->get($length * 4));
		if ($unpacked === false) {
			throw new NbtDataException("Failed to read int array of length $length");
		}
		return array_values($unpacked);
	}

	abstract protected function getIntArrayFormat(): string;

	public function readString(): string
	{
		$length = $this->readShort();
		if ($length > NBT::TAG_NAME_MAX_LENGTH) {
			throw new NbtDataException("NBT string length too large ($length > " . NBT::TAG_NAME_MAX_LENGTH . ")");
		}
		return $this->buffer->get($length);
	}
}
