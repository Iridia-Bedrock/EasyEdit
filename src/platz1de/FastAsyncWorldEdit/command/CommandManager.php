<?php

namespace platz1de\FastAsyncWorldEdit\command;

use platz1de\FastAsyncWorldEdit\command\exception\CommandException;
use platz1de\FastAsyncWorldEdit\command\flags\CommandFlagParser;
use platz1de\FastAsyncWorldEdit\session\SessionManager;
use pocketmine\player\Player;
use pocketmine\Server;

class CommandManager
{
	/**
	 * @var FAWECommand[]
	 */
	private static array $commands = [];

	/**
	 * @param FAWECommand $command
	 */
	public static function registerCommand(FAWECommand $command): void
	{
		self::$commands[strtolower($command->getName())] = $command;
		Server::getInstance()->getCommandMap()->register("fawe", $command);
	}

	/**
	 * @param FAWECommand[] $commands
	 */
	public static function registerCommands(array $commands): void
	{
		foreach ($commands as $command) {
			self::registerCommand($command);
		}
	}

	/**
	 * @param FAWECommand $command
	 * @param string[]        $args
	 * @param Player          $player
	 */
	public static function processCommand(FAWECommand $command, array $args, Player $player): void
	{
		$executor = $command->getExecutor($args);
		try {
			$session = SessionManager::get($player);
			$executor->process($session, CommandFlagParser::parseFlags($executor, $args, $session));
		} catch (CommandException $e) {
			$e->sendWarning(SessionManager::get($player), $command);
		}
	}

	/**
	 * @return FAWECommand[]
	 */
	public static function getCommands(): array
	{
		return self::$commands;
	}

	/**
	 * @param string $command
	 * @return FAWECommand|null
	 */
	public static function getKnownCommand(string $command): ?FAWECommand
	{
		foreach (self::$commands as $cmd) {
			if (strtolower($cmd->getName()) === strtolower($command)) {
				return $cmd;
			}
			foreach ($cmd->getAliases() as $alias) {
				if (strtolower($alias) === strtolower($command)) {
					return $cmd;
				}
			}
		}
		return null;
	}
}
