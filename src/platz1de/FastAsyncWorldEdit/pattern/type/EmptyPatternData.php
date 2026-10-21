<?php

namespace platz1de\FastAsyncWorldEdit\pattern\type;

use platz1de\FastAsyncWorldEdit\utils\ExtendedBinaryStream;

trait EmptyPatternData
{
	public function putData(ExtendedBinaryStream $stream): void { }

	public function parseData(ExtendedBinaryStream $stream): void { }
}