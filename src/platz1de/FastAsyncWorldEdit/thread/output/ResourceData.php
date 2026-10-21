<?php

namespace platz1de\FastAsyncWorldEdit\thread\output;

use platz1de\FastAsyncWorldEdit\convert\BedrockStatePreprocessor;
use platz1de\FastAsyncWorldEdit\convert\BlockStateConvertor;
use platz1de\FastAsyncWorldEdit\convert\BlockTagManager;
use platz1de\FastAsyncWorldEdit\convert\TileConvertor;
use platz1de\FastAsyncWorldEdit\utils\ConfigManager;
use platz1de\FastAsyncWorldEdit\utils\ExtendedBinaryStream;
use platz1de\FastAsyncWorldEdit\utils\RepoManager;
use platz1de\FastAsyncWorldEdit\world\HeightMapCache;

class ResourceData extends OutputData
{
	public function __construct(private string $rawJTB, private string $rawBTJ) { }

	public function handle(): void { }

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->rawJTB);
		$stream->putString($this->rawBTJ);
		$stream->putString(BedrockStatePreprocessor::$rawData);
		$stream->putString(BlockTagManager::$rawData);
		$stream->putLong(RepoManager::getVersion());
		BedrockStatePreprocessor::$rawData = "";
		BlockTagManager::$rawData = "";
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		BlockStateConvertor::loadResourceData($stream->getString(), $stream->getString());
		BedrockStatePreprocessor::loadResourceData($stream->getString());
		BlockTagManager::loadResourceData($stream->getString());
		TileConvertor::load($stream->getLong());
		HeightMapCache::loadIgnore(ConfigManager::getTerrainIgnored());
	}
}