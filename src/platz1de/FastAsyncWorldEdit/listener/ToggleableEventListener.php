<?php

namespace platz1de\FastAsyncWorldEdit\listener;

use platz1de\FastAsyncWorldEdit\FastAsyncWorldEdit;
use pocketmine\Server;

trait ToggleableEventListener
{
	public static function init(): void
	{
		Server::getInstance()->getPluginManager()->registerEvents(new self(), FastAsyncWorldEdit::getInstance());
	}
}