<?php

namespace platz1de\FastAsyncWorldEdit\task\editing;

use Generator;
use platz1de\FastAsyncWorldEdit\pattern\functional\GravityPattern;
use platz1de\FastAsyncWorldEdit\pattern\Pattern;
use platz1de\FastAsyncWorldEdit\pattern\PatternWrapper;
use platz1de\FastAsyncWorldEdit\selection\BinaryBlockListStream;
use platz1de\FastAsyncWorldEdit\selection\BlockListSelection;
use platz1de\FastAsyncWorldEdit\selection\constructor\ShapeConstructor;
use platz1de\FastAsyncWorldEdit\selection\LinearSelection;
use platz1de\FastAsyncWorldEdit\selection\Selection;
use platz1de\FastAsyncWorldEdit\selection\SelectionContext;
use platz1de\FastAsyncWorldEdit\selection\VerticalStaticBlockListSelection;
use platz1de\FastAsyncWorldEdit\task\editing\cubic\CubicStaticUndo;
use platz1de\FastAsyncWorldEdit\utils\ExtendedBinaryStream;
use platz1de\FastAsyncWorldEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;

class SetTask extends SelectionEditTask
{
	use CubicStaticUndo {
		CubicStaticUndo::createUndoBlockList as private getDefaultBlockList;
	}

	protected Pattern $pattern;

	/**
	 * @param Selection             $selection
	 * @param Pattern               $pattern
	 * @param SelectionContext|null $context
	 */
	public function __construct(Selection $selection, Pattern $pattern, ?SelectionContext $context = null)
	{
		$pattern = PatternWrapper::wrap([$pattern]);
		$this->pattern = $pattern;
		if ($context === null) {
			$context = $pattern->getSelectionContext();
		}
		parent::__construct($selection, $context);
	}

	public function calculateEffectiveComplexity(): int
	{
		//TODO: Add complexity of patterns
		return $this->getSelection()->getVolume() * 2;
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "set";
	}

	/**
	 * @param EditTaskHandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator
	{
		$selection = $this->getSelection();
		$pattern = $this->pattern;
		$updateHeightMap = $pattern->contains(GravityPattern::class);
		yield from $selection->asShapeConstructors(function (int $x, int $y, int $z) use ($updateHeightMap, $pattern, $selection, $handler): void {
			$block = $pattern->getFor($x, $y, $z, $handler->getOrigin(), $selection);
			if ($block !== -1) {
				$handler->changeBlock($x, $y, $z, $block);
				if ($updateHeightMap) {
					HeightMapCache::setBlockAt($x, $y, $z, $block >> Block::INTERNAL_STATE_DATA_BITS === BlockTypeIds::AIR);
				}
			}
		}, $this->context);
	}

	/**
	 * @return BlockListSelection
	 */
	public function createUndoBlockList(): BlockListSelection
	{
		if ($this->getSelection() instanceof LinearSelection) {
			return new BinaryBlockListStream($this->getTargetWorld());
		}
		if ($this->pattern->contains(GravityPattern::class)) {
			return new VerticalStaticBlockListSelection($this->getTargetWorld(), $this->getSelection()->getPos1(), $this->getSelection()->getPos2());
		}
		return $this->getDefaultBlockList();
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putString($this->pattern->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->pattern = Pattern::fastDeserialize($stream->getString());
	}
}
