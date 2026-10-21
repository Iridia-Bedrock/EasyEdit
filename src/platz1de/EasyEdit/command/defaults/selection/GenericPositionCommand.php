<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\FlagArgumentParser;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\StringCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\world\Position;
use pocketmine\block\BlockTypeIds;
use Throwable;

class GenericPositionCommand extends EasyEditCommand
{
	use FlagArgumentParser;

	public function __construct(private int $number, private bool $target = false)
	{
		parent::__construct($target ? "/hpos$number" : "/pos$number", [KnownPermissions::PERMISSION_SELECT], $target ? [] : ["/$number"]);
		$this->flagOrder = $target ? [] : ["x" => false, "y" => false, "z" => false];
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		if ($this->target) {
			try {
				$block = $session->asPlayer()->getTargetBlock(256, [BlockTypeIds::WATER => true, BlockTypeIds::LAVA => true, BlockTypeIds::AIR => true]);
			} catch (Throwable) {
				return;
			}
			if ($block !== null) $session->selectPos($block->getPosition(), $this->number);
			return;
		}
		if ($flags->hasFlag("x") && $flags->hasFlag("y") && $flags->hasFlag("z")) {
			$session->selectPos(Position::fromObject(ArgumentParser::parseCoordinates($session, $flags->getStringFlag("x"), $flags->getStringFlag("y"), $flags->getStringFlag("z")), $session->asPlayer()->getPosition()->getWorld()), $this->number);
		} else {
			$session->selectPos($session->asPlayer()->getPosition(), $this->number);
		}
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		if ($this->target) return [];
		//TODO: Turn these into integers (handle special cases like ~ and ^ while parsing flags)
		return [
			"x" => new StringCommandFlag("x"),
			"y" => new StringCommandFlag("y"),
			"z" => new StringCommandFlag("z")
		];
	}
}
