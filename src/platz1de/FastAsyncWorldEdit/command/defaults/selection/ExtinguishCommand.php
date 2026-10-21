<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\selection;

use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\convert\BlockTagManager;
use platz1de\FastAsyncWorldEdit\pattern\block\StaticBlock;
use platz1de\FastAsyncWorldEdit\pattern\logic\relation\BlockPattern;
use platz1de\FastAsyncWorldEdit\selection\Selection;
use platz1de\FastAsyncWorldEdit\session\Session;
use platz1de\FastAsyncWorldEdit\task\editing\SetTask;
use pocketmine\block\VanillaBlocks;

class ExtinguishCommand extends SphericalSelectionCommand
{
	public function __construct()
	{
		parent::__construct("/extinguish", [KnownPermissions::PERMISSION_EDIT], ["/ext"]);
	}

	/**
	 * @param Session               $session
	 * @param Selection             $selection
	 * @param CommandFlagCollection $flags
	 */
	public function processSelection(Session $session, Selection $selection, CommandFlagCollection $flags): void
	{
		$session->runSettingTask(new SetTask($selection, new BlockPattern(BlockTagManager::getTag("fire", true), [StaticBlock::from(VanillaBlocks::AIR())])));
	}
}