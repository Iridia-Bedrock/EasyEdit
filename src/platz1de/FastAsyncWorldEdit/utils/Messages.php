<?php

namespace platz1de\FastAsyncWorldEdit\utils;

use platz1de\FastAsyncWorldEdit\FastAsyncWorldEdit;
use platz1de\FastAsyncWorldEdit\thread\input\MessageInputData;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\ConfigLoadException;
use pocketmine\utils\TextFormat;
use UnexpectedValueException;

class Messages
{
	private const MESSAGE_VERSION = "3.0.5";

	/**
	 * @var string[]
	 */
	private static array $messages = [];

	public static function load(): void
	{
        FastAsyncWorldEdit::getInstance()->saveResource("messages.yml");
        $path = FastAsyncWorldEdit::getInstance()->getDataFolder() . "messages.yml";
        try {
            $custom = new Config($path, Config::YAML);
        } catch (ConfigLoadException $exception) {
            copy($path, $path . ".old");
            FastAsyncWorldEdit::getInstance()->saveResource("messages.yml", true);
            $custom = new Config($path, Config::YAML);
            FastAsyncWorldEdit::getInstance()->getLogger()->warning("Your invalid messages.yml was backed up as messages.yml.old and replaced with the default messages");
        }

        $current = $custom->get("message-version", "1.0");
        if (!is_string($current)) {
            throw new UnexpectedValueException("message-version is not a string");
        }
        if ($current !== self::MESSAGE_VERSION) {
            copy($custom->getPath(), $custom->getPath() . ".old");
            FastAsyncWorldEdit::getInstance()->saveResource("messages.yml", true);

            FastAsyncWorldEdit::getInstance()->getLogger()->warning("Your messages were replaced with a newer Version");
            $custom->reload();
        }

        $messages = $custom->getAll();
		foreach ($messages as $key => $value) {
			/** @var string|string[] $value */
			if (is_array($value)) {
				$messages[$key] = implode(PHP_EOL, $value);
			}
		}
		/** @var string[] $messages */
		self::$messages = $messages;

		MessageInputData::from(self::$messages);
	}

	/**
	 * @param Player|string    $player
	 * @param MessageComponent $message
	 */
	public static function send(mixed $player, MessageComponent $message): void
	{
		if ($player instanceof Player || ($player = Server::getInstance()->getPlayerExact($player)) instanceof Player) {
			$player->sendMessage(self::translate("prefix") . $message->toString());
		}
	}

	/**
	 * @param string   $id
	 * @param string[] $replace
	 * @param bool     $isId
	 * @return string
	 */
	public static function replace(string $id, array $replace = [], bool $isId = true): string
	{
		return str_replace(array_keys($replace), array_values($replace), $isId ? self::translate($id) : $id);
	}

	/**
	 * @param string $id
	 * @return string
	 */
	public static function translate(string $id): string
	{
		return self::$messages[$id] ?? TextFormat::RED . "The message " . $id . " was not found, please try deleting your messages.yml";
	}

	/**
	 * @param string[] $messages
	 */
	public static function setMessageData(array $messages): void
	{
		self::$messages = $messages;
	}
}
