<?php

namespace platz1de\FastAsyncWorldEdit\task\editing\cubic;

use platz1de\FastAsyncWorldEdit\selection\BlockListSelection;
use platz1de\FastAsyncWorldEdit\selection\Selection;
use platz1de\FastAsyncWorldEdit\selection\StaticBlockListSelection;

trait CubicStaticUndo
{
	abstract public function getTargetWorld(): string;

	abstract public function getSelection(): Selection;

	/**
	 * @return StaticBlockListSelection
	 */
	public function createUndoBlockList(): BlockListSelection
	{
		return new StaticBlockListSelection($this->getTargetWorld(), $this->getSelection()->getPos1(), $this->getSelection()->getPos2());
	}
}