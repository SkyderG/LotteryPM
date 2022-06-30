<?php

namespace lottery\util;

use lottery\Loader;

class MessageTranslator
{

    public static function translate(string $key, array $data = [])
    {
        $config = Loader::getInstance()->getConfig();

        if (!is_null($config->get($key, null))) {
            $message = $config->get($key);
            $message = str_replace("{n}", PHP_EOL, $message);

            if (count($data) > 0) {
                for ($i = 0; $i < count($data); $i++) {
                    $message = str_replace("{" . $i . "}", $data[$i], $message);
                }
            }
            return $message;
        }
        return "";
    }

    public static function translateNested(string $key, array $data = [])
    {
        $config = Loader::getInstance()->getConfig();

        if (!is_null($config->getNested($key))) {
            $message = $config->getNested($key);
            $message = str_replace("{n}", PHP_EOL, $message);

            if (count($data) > 0) {
                for ($i = 0; $i < count($data); $i++) {
                    $message = str_replace("{" . $i . "}", $data[$i], $message);
                }
            }
            return $message;
        }
        return "";
    }

    public static function translateArrayMessage(string $key, array $data = []): string
    {
        $config = Loader::getInstance()->getConfig();

        if (!is_null($config->getNested($key))) {
            $message = $config->getNested($key);
            if (is_array($message)) {
                for ($line = 0; $line < count($message); $line++) {
                    $message[$line] = str_replace("{n}", PHP_EOL, $message[$line]);
                    if (count($data) > 0) {
                        for ($i = 0; $i < count($data); $i++) {
                            $message[$line] = str_replace("{" . $i . "}", $data[$i], $message[$line]);
                        }
                    }
                }
                return implode("\n", $message);
            } else {
                return self::translateNested($key, $data);
            }
        }
        return "";
    }
}