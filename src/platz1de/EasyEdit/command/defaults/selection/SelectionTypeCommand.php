<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use Generator;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\InvalidUsageException;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\StringCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;

class SelectionTypeCommand extends EasyEditCommand
{
	private const TYPES = ["cuboid", "extend", "sphere", "ellipsoid", "cylinder", "poly"];

	public function __construct()
	{
		parent::__construct("/sel", [KnownPermissions::PERMISSION_SELECT], ["/selection"]);
	}

	public function process(Session $session, CommandFlagCollection $flags): void
	{
		if (!$flags->hasFlag("type")) {
			$session->deselect();
			return;
		}
		$type = strtolower($flags->getStringFlag("type"));
		$type = match ($type) {"cube" => "cuboid", "cyl" => "cylinder", default => $type};
		if (!in_array($type, self::TYPES, true)) throw new InvalidUsageException();
		$session->setSelectionType($type);
	}

	public function getKnownFlags(Session $session): array
	{
		return [];
	}

	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		if (count($args) > 1) throw new InvalidUsageException();
		if (isset($args[0])) yield StringCommandFlag::with($args[0], "type");
	}
}
