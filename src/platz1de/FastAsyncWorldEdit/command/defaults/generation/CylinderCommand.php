<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\generation;

use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\command\FlagArgumentParser;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\flags\FloatCommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\IntegerCommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\PatternCommandFlag;
use platz1de\FastAsyncWorldEdit\command\flags\SingularCommandFlag;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\math\OffGridBlockVector;
use platz1de\FastAsyncWorldEdit\pattern\logic\selection\SidesPattern;
use platz1de\FastAsyncWorldEdit\selection\Cylinder;
use platz1de\FastAsyncWorldEdit\session\Session;
use platz1de\FastAsyncWorldEdit\task\editing\SetTask;

class CylinderCommand extends FAWECommand
{
	use FlagArgumentParser;

	public function __construct()
	{
		parent::__construct("/cylinder", [KnownPermissions::PERMISSION_GENERATE, KnownPermissions::PERMISSION_EDIT], ["/cy"]);
		$this->flagOrder = ["radius" => true, "height" => true, "pattern" => true];
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$pattern = $flags->hasFlag("hollow") ? new SidesPattern($flags->getFloatFlag("thickness"), [$flags->getPatternFlag("pattern")]) : $flags->getPatternFlag("pattern");
		$session->runSettingTask(new SetTask(new Cylinder($session->asPlayer()->getWorld()->getFolderName(), OffGridBlockVector::fromVector($session->asPlayer()->getPosition()), $flags->getFloatFlag("radius"), $flags->getIntFlag("height")), $pattern));
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"radius" => new FloatCommandFlag("radius", ["rad"], "r"),
			"height" => new IntegerCommandFlag("height", [], "h"),
			"pattern" => new PatternCommandFlag("pattern", [], "p"),
			"hollow" => new SingularCommandFlag("hollow", [], "h"),
			"thickness" => FloatCommandFlag::default(1.0, "thickness", ["thick"], "t")
		];
	}
}