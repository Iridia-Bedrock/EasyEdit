<?php

namespace platz1de\FastAsyncWorldEdit\selection\identifier;

interface BlockListSelectionIdentifier extends SelectionIdentifier
{
	public function toIdentifier(): StoredSelectionIdentifier;
}