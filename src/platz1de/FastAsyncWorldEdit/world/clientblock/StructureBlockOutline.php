<?php

namespace platz1de\FastAsyncWorldEdit\world\clientblock;

use platz1de\FastAsyncWorldEdit\math\BlockVector;
use platz1de\FastAsyncWorldEdit\utils\PacketUtils;
use platz1de\FastAsyncWorldEdit\world\ReferencedWorldHolder;
use pocketmine\player\Player;
use pocketmine\world\World;

class StructureBlockOutline extends ClientSideBlock
{
	use ReferencedWorldHolder;

	public CompoundBlock $block;

	/**
	 * @param string      $world
	 * @param BlockVector $startPosition
	 * @param BlockVector $endPosition
	 */
	public function __construct(string $world, private BlockVector $startPosition, private BlockVector $endPosition, private int $anchorIndex = 0)
	{
		$this->world = $world;
		parent::__construct();
	}

	public function send(Player $player): void
	{
		$this->recalculatePosition($player);
		PacketUtils::sendFakeBlock($player, $this->block);
	}

	public function remove(Player $player): void
	{
		//Minecraft doesn't delete BlockData if the original Block shouldn't have some or whole chunks get sent
		PacketUtils::sendFakeBlock($player, $this->block, true);
		PacketUtils::resendBlock($this->block->getPosition(), $player->getWorld(), $player);
	}

	public function checkResend(Player $player): void
	{
		if ($this->getWorldName() === $player->getWorld()->getFolderName()) {
			$this->send($player);
		}
	}

	public function resendChunk(Player $player, int $chunkX, int $chunkZ): void
	{
		if ($this->getWorldName() === $player->getWorld()->getFolderName() && ($this->block->getPosition()->getFloorX() >> 4) === $chunkX && ($this->block->getPosition()->getFloorZ() >> 4) === $chunkZ) {
			$this->send($player);
		}
	}

	public function update(Player $player): void
	{
		if ($this->getWorldName() === $player->getWorld()->getFolderName() && !$player->isUsingChunk($this->block->getPosition()->getX() >> 4, $this->block->getPosition()->getZ() >> 4)) {
			$this->remove($player);
			$this->send($player);
		}
	}

	public function recalculatePosition(Player $player): void
	{
		$pos1 = $this->startPosition;
		$pos2 = $this->endPosition;
		$anchorX = (($player->getPosition()->getFloorX() >> 4) << 4) + ($this->anchorIndex % 16);
		$anchorZ = (($player->getPosition()->getFloorZ() >> 4) << 4) + (intdiv($this->anchorIndex, 16) % 16);
		$anchorY = min(World::Y_MAX - 1, World::Y_MIN + intdiv($this->anchorIndex, 256));
		$this->block = Registry::OUTLINE_BLOCK();
		$this->block->position($player->getWorld(), $anchorX, $anchorY, $anchorZ);
		$this->block->getData()
			->setInt("xStructureOffset", $pos1->x - $anchorX)
			->setInt("yStructureOffset", $pos1->y - $anchorY)
			->setInt("zStructureOffset", $pos1->z - $anchorZ)
			->setInt("xStructureSize", $pos2->x - $pos1->x + 1)
			->setInt("yStructureSize", $pos2->y - $pos1->y + 1)
			->setInt("zStructureSize", $pos2->z - $pos1->z + 1);
	}
}
