<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\clipboard;

use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\command\FlagArgumentParser;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\flags\FacingCommandFlag;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\result\SelectionManipulationResult;
use platz1de\FastAsyncWorldEdit\session\Session;
use platz1de\FastAsyncWorldEdit\task\DynamicStoredFlipTask;
use platz1de\FastAsyncWorldEdit\utils\MixedUtils;
use platz1de\FastAsyncWorldEdit\utils\VectorUtils;
use pocketmine\math\Facing;

class FlipCommand extends FAWECommand
{
	use FlagArgumentParser;

	public function __construct()
	{
		parent::__construct("/flip", [KnownPermissions::PERMISSION_CLIPBOARD]);
		$this->flagOrder = ["direction" => false];
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$session->runTask(new DynamicStoredFlipTask($session->getClipboard(), Facing::axis($flags->getIntFlag("direction"))))->then(function (SelectionManipulationResult $result) use ($session) {
			$session->sendMessage("blocks-rotated", ["{time}" => $result->getFormattedTime(), "{changed}" => MixedUtils::humanReadable($result->getChanged())]);
		});
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"direction" => FacingCommandFlag::default(VectorUtils::getFacing($session->asPlayer()->getLocation()), "direction", ["dir"], "d")
		];
	}
}