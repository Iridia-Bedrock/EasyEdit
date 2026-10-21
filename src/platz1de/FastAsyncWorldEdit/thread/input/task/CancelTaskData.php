<?php

namespace platz1de\FastAsyncWorldEdit\thread\input\task;

use platz1de\FastAsyncWorldEdit\session\SessionIdentifier;
use platz1de\FastAsyncWorldEdit\thread\input\InputData;
use platz1de\FastAsyncWorldEdit\thread\ThreadData;
use platz1de\FastAsyncWorldEdit\utils\ExtendedBinaryStream;

class CancelTaskData extends InputData
{
	private SessionIdentifier $identifier;

	public static function from(SessionIdentifier $identifier): void
	{
		$data = new self();
		$data->identifier = $identifier;
		$data->send();
	}

	public function handle(): void
	{
		ThreadData::requirePause($this->identifier);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->identifier->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->identifier = SessionIdentifier::fastDeserialize($stream->getString());
	}
}