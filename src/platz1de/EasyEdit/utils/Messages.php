<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\thread\input\MessageInputData;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use UnexpectedValueException;

class Messages
{
	private const MESSAGE_VERSION = "3.0.1";

	/**
	 * @var string[]
	 */
	private static array $messages = [];

	public static function load(): void
	{
        EasyEdit::getInstance()->saveResource("messages.yml");
        $custom = new Config(EasyEdit::getInstance()->getDataFolder() . "messages.yml", Config::YAML);

        $current = $custom->get("message-version", "1.0");
        if (!is_string($current)) {
            throw new UnexpectedValueException("message-version is not a string");
        }
        if ($current !== self::MESSAGE_VERSION) {
            copy($custom->getPath(), $custom->getPath() . ".old");
            EasyEdit::getInstance()->saveResource("messages.yml", true);

            EasyEdit::getInstance()->getLogger()->warning("Your messages were replaced with a newer Version");
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