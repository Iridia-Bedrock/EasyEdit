<?php

namespace platz1de\FastAsyncWorldEdit\command\defaults\brush;

use Generator;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagCollection;
use platz1de\FastAsyncWorldEdit\command\KnownPermissions;
use platz1de\FastAsyncWorldEdit\command\OverloadedCommand;
use platz1de\FastAsyncWorldEdit\session\Session;
use RuntimeException;
use platz1de\FastAsyncWorldEdit\command\defaults\brush\SphericalBrushSubCommand;
use platz1de\FastAsyncWorldEdit\command\defaults\brush\SmoothingBrushSubCommand;
use platz1de\FastAsyncWorldEdit\command\defaults\brush\NaturalizingBrushSubCommand;
use platz1de\FastAsyncWorldEdit\command\defaults\brush\CylindricalBrushSubCommand;
use platz1de\FastAsyncWorldEdit\command\defaults\brush\PastingBrushSubCommand;
use platz1de\FastAsyncWorldEdit\command\defaults\brush\CubeBrushSubCommand;
use platz1de\FastAsyncWorldEdit\command\defaults\brush\PyramidBrushSubCommand;
use platz1de\FastAsyncWorldEdit\command\defaults\brush\ConeBrushSubCommand;
use platz1de\FastAsyncWorldEdit\command\defaults\brush\TorusBrushSubCommand;
use platz1de\FastAsyncWorldEdit\command\defaults\brush\EllipsoidBrushSubCommand;
use platz1de\FastAsyncWorldEdit\command\defaults\brush\FlattenBrushSubCommand;
use platz1de\FastAsyncWorldEdit\command\defaults\brush\ExtrudeBrushSubCommand;

class BrushCommand extends OverloadedCommand
{
	public function __construct()
	{
		parent::__construct("/brush", [
			new SphericalBrushSubCommand(),
			new SmoothingBrushSubCommand(),
			new NaturalizingBrushSubCommand(),
			new CylindricalBrushSubCommand(),
			new PastingBrushSubCommand(),
			new CubeBrushSubCommand(),
			new PyramidBrushSubCommand(),
			new ConeBrushSubCommand(),
			new TorusBrushSubCommand(),
			new EllipsoidBrushSubCommand()
		], true, [KnownPermissions::PERMISSION_BRUSH], ["/br"]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		throw new RuntimeException("This method should not be called");
	}

	public function getKnownFlags(Session $session): array
	{
		throw new RuntimeException("This method should not be called");
	}

	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		throw new RuntimeException("This method should not be called");
	}
}
