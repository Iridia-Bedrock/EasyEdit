<?php

namespace platz1de\FastAsyncWorldEdit\thread;

use platz1de\FastAsyncWorldEdit\thread\block\BlockStateTranslationManager;
use pocketmine\scheduler\Task;

class EditAdapter extends Task
{
	public function onRun(): void
	{
		EditThread::getInstance()->parseOutput();
		BlockStateTranslationManager::tick();
		MainThreadTaskScheduler::getInstance()->tick();
	}
}