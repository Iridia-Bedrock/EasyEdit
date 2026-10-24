<?php

namespace platz1de\FastAsyncWorldEdit\utils;

use platz1de\FastAsyncWorldEdit\listener\ChunkRefreshListener;
use platz1de\FastAsyncWorldEdit\world\blockupdate\UpdateSubChunkBlocksInjector;
use platz1de\FastAsyncWorldEdit\world\ChunkInformation;
use pocketmine\block\tile\TileFactory;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use UnexpectedValueException;

class LoaderManager
{
	/**
	 * @param World $world
	 * @param int   $chunkX
	 * @param int   $chunkZ
	 * @return Chunk
	 */
	public static function getChunk(World $world, int $chunkX, int $chunkZ): Chunk
	{
		$chunk = $world->getChunk($chunkX, $chunkZ);

		if (!$chunk instanceof Chunk) {
			throw new UnexpectedValueException("Could not load chunk " . $chunkX . " " . $chunkZ . ", was it generated first?");
		}

		return $chunk;
	}

	/**
	 * @param World              $world
	 * @param ChunkInformation[] $chunks
	 * @param string[]           $injections
	 */
	public static function setChunks(World $world, array $chunks, array $injections): void
	{
		$preparedInjections = [];
		foreach ($injections as $hash => $injection) {
			World::getBlockXYZ($hash, $x, $y, $z);
			$preparedInjections[$x][$z][$y] = $injection;
		}

		foreach ($chunks as $hash => $chunk) {
			World::getXZ($hash, $x, $z);
			self::injectChunk($world, $x, $z, $chunk, $preparedInjections[$x][$z] ?? []);
			$world->unloadChunk($x, $z);
		}
	}

	/**
	 * Installs edited chunk data, using subchunk injections when available to avoid full chunk resends
	 * @param World            $world
	 * @param int              $x
	 * @param int              $z
	 * @param ChunkInformation $chunkInformation
	 * @param string[]         $preparedInjections
	 * @see          World::setChunk()
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public static function injectChunk(World $world, int $x, int $z, ChunkInformation $chunkInformation, array $preparedInjections): void
	{
		$chunk = $chunkInformation->getChunk();
		$chunkHash = World::chunkHash($x, $z);

		//TODO: this deletes entities in unloaded chunks (load entities to EditThread)
		if ($world->isChunkLoaded($x, $z)) {
			$old = $world->getChunk($x, $z);
			if ($old !== null) {
				foreach ($old->getTiles() as $tile) {
					$tile->close();
				}
			}
		}

		$chunk->setTerrainDirty();

		if ($preparedInjections === []) {
			// Async edits return complete chunks. Use PocketMine's normal replacement path so
			// chunk state, listeners and persistence bookkeeping stay in sync.
			$world->setChunk($x, $z, $chunk);
			// setChunk() can transfer compatible tile instances from the old chunk. The
			// ChunkInformation tile data below is authoritative, so recreate those instead.
			foreach ($chunk->getTiles() as $tile) {
				$tile->close();
			}
			foreach ($world->getChunkPlayers($x, $z) as $player) {
				ChunkRefreshListener::getInstance()->addAffectedPlayer($player->getName());
			}
		} else {
			// Small main-thread edits use subchunk packets to avoid resending full chunks.
			(function () use ($preparedInjections, $z, $x, $chunkHash, $chunk): void {
				$this->chunks[$chunkHash] = $chunk;

				unset($this->blockCache[$chunkHash], $this->changedBlocks[$chunkHash]);

				foreach ($this->getChunkListeners($x, $z) as $loader) {
					if ($loader instanceof Player) {
						foreach ($preparedInjections as $injection) {
							//Hack to allow instant, flicker-free block setting, costly network wise
							$loader->getNetworkSession()->sendDataPacket(UpdateSubChunkBlocksInjector::create($injection));
						}
					} else {
						$loader->onChunkChanged($x, $z, $chunk);
					}
				}
			})->call($world);
		}

		foreach ($chunkInformation->getTiles() as $rawTile) {
			$tile = TileFactory::getInstance()->createFromData($world, $rawTile);
			if ($tile !== null) {
				$chunk->addTile($tile);
				foreach ($world->getChunkPlayers($tile->getPosition()->getX() >> 4, $tile->getPosition()->getZ() >> 4) as $player) {
					if ($preparedInjections === []) {
						PacketUtils::sendFakeBlockAt($player, $tile->getPosition(), VanillaBlocks::AIR());
					}
					PacketUtils::resendBlock($tile->getPosition(), $world, $player);
				}
			}
		}
	}
}
