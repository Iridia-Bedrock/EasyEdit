<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\selection;

use platz1de\FastAsyncWorldEdit\command\FAWECommand;
use platz1de\FastAsyncWorldEdit\command\FlagArgumentParser;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\flags\FloatCommandFlag;
use platz1de\FastAsyncWorldEdit\math\OffGridBlockVector;
use platz1de\FastAsyncWorldEdit\selection\Selection;
use platz1de\FastAsyncWorldEdit\selection\Sphere;
use platz1de\FastAsyncWorldEdit\session\Session;

abstract class SphericalSelectionCommand extends FAWECommand
{
	use FlagArgumentParser;

	public function __construct(string $name, array $permissions, array $aliases = [])
	{
		parent::__construct($name, $permissions, $aliases);
		$this->flagOrder = ["radius" => false];
	}

	public function getKnownFlags(Session $session): array
	{
		return [
			"radius" => new FloatCommandFlag("radius", [], "r")
		];
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		if ($flags->hasFlag("radius")) {
			$this->processSelection($session, new Sphere($session->asPlayer()->getWorld()->getFolderName(), OffGridBlockVector::fromVector($session->asPlayer()->getPosition()), $flags->getFloatFlag("radius")), $flags);
		} else {
			$this->processSelection($session, $session->getSelection(), $flags);
		}
	}

	abstract public function processSelection(Session $session, Selection $selection, CommandFlagCollection $flags): void;
}