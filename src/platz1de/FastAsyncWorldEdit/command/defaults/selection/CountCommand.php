<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\selection;

use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\flags\SingularCommandFlag;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\result\CountingTaskResult;
use platz1de\FastAsyncWorldEdit\selection\Selection;
use platz1de\FastAsyncWorldEdit\session\Session;
use platz1de\FastAsyncWorldEdit\task\editing\CountTask;
use platz1de\FastAsyncWorldEdit\utils\BlockParser;
use platz1de\FastAsyncWorldEdit\utils\MixedUtils;
use pocketmine\block\Block;
use pocketmine\block\RuntimeBlockStateRegistry;

class CountCommand extends SphericalSelectionCommand
{
	public function __construct()
	{
		parent::__construct("/count", [KnownPermissions::PERMISSION_SELECT]);
	}

	/**
	 * @param Session               $session
	 * @param Selection             $selection
	 * @param CommandFlagCollection $flags
	 */
	public function processSelection(Session $session, Selection $selection, CommandFlagCollection $flags): void
	{
		$session->runTask(new CountTask($selection))->then(function (CountingTaskResult $result) use ($flags, $session): void {
			$blocks = [];
			if ($flags->hasFlag("detailed")) {
				foreach ($result->getBlocks() as $block => $count) {
					$blocks[] = BlockParser::runtimeToStateString($block) . ": " . MixedUtils::humanReadable($count);
				}
			} else {
				$normalized = [];
				$certainNames = [];
				foreach ($result->getBlocks() as $block => $count) {
					$normalized[$block >> Block::INTERNAL_STATE_DATA_BITS] = ($normalized[$block >> Block::INTERNAL_STATE_DATA_BITS] ?? 0) + $count;
					$name = RuntimeBlockStateRegistry::getInstance()->fromStateId($block)->getName();
					if ($name !== "Unknown") {
						$certainNames[$block >> Block::INTERNAL_STATE_DATA_BITS] = $name;
					}
				}
				foreach ($normalized as $block => $count) {
					$blocks[] = ($certainNames[$block] ?? "Unknown") . ": " . MixedUtils::humanReadable($count);
				}
			}
			$session->sendMessage("blocks-counted", ["{time}" => $result->getFormattedTime(), "{changed}" => MixedUtils::humanReadable(array_sum($result->getBlocks())), "{blocks}" => implode("\n", $blocks)]);
		});
	}

	public function getKnownFlags(Session $session): array
	{
		$base = parent::getKnownFlags($session);
		$base["detailed"] = new SingularCommandFlag("detailed", ["detail", "depth"], "d");
		return $base;
	}
}