<?php

namespace platz1de\FastAsyncWorldEdit\selection\identifier;

use platz1de\FastAsyncWorldEdit\selection\Selection;

interface SelectionIdentifier
{
	public function fastSerialize(): string;

	public static function fastDeserialize(string $data): static;

	public function asSelection(): Selection;
}