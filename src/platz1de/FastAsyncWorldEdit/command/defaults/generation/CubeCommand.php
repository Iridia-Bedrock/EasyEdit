<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\generation;

use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\command\FlagArgumentParser;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\flags\FloatCommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\PatternCommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\SingularCommandFlag;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\math\BlockOffsetVector;
use platz1de\FastAsyncWorldEdit\math\BlockVector;
use platz1de\FastAsyncWorldEdit\pattern\logic\selection\SidesPattern;
use platz1de\FastAsyncWorldEdit\selection\Cube;
use platz1de\FastAsyncWorldEdit\session\Session;
use platz1de\FastAsyncWorldEdit\task\editing\SetTask;

class CubeCommand extends FAWECommand
{
	use FlagArgumentParser;

	public function __construct()
	{
		parent::__construct("/cube", [KnownPermissions::PERMISSION_GENERATE, KnownPermissions::PERMISSION_EDIT], ["/cb"]);
		$this->flagOrder = ["size" => true, "pattern" => true];
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$pattern = $flags->hasFlag("hollow") ? new SidesPattern($flags->getFloatFlag("thickness"), [$flags->getPatternFlag("pattern")]) : $flags->getPatternFlag("pattern");
		$size = $flags->getFloatFlag("size") - 1;
		$offsetMax = new BlockOffsetVector((int) ceil($size / 2), (int) ceil($size / 2), (int) ceil($size / 2));
		$offsetMin = new BlockOffsetVector((int) floor($size / 2), (int) floor($size / 2), (int) floor($size / 2));
		$session->runSettingTask(new SetTask(new Cube($session->asPlayer()->getWorld()->getFolderName(), BlockVector::fromVector($session->asPlayer()->getPosition())->offset($offsetMax), BlockVector::fromVector($session->asPlayer()->getPosition())->offset($offsetMin->negate())), $pattern));
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"size" => new FloatCommandFlag("size", ["radius"], "s"),
			"pattern" => new PatternCommandFlag("pattern", [], "p"),
			"hollow" => new SingularCommandFlag("hollow", [], "h"),
			"thickness" => FloatCommandFlag::default(1.0, "thickness", ["thick"], "t")
		];
	}
}