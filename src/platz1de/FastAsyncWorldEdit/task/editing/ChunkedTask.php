<?php

namespace platz1de\FastAsyncWorldEdit\task\editing;

use Generator;
use platz1de\FastAsyncWorldEdit\selection\constructor\ShapeConstructor;

interface ChunkedTask
{
	/**
	 * @param EditTaskHandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator;
}