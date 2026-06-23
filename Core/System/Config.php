<?php

declare(strict_types=1);

namespace Core\System;

use Core\File;
use Core\Logger;
use Throwable;

class Config
{
    protected static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        [$file, $path] = self::splitKey($key);

        if (!isset(self::$cache[$file])) {
            self::loadFile($file);
        }

        return self::resolve(self::$cache[$file] ?? [], $path, $default);
    }

    public static function has(string $key): bool
    {
        return self::get($key, '__NOT_FOUND__') !== '__NOT_FOUND__';
    }

    public static function all(string $file): array
    {
        if (!isset(self::$cache[$file])) {
            self::loadFile($file);
        }

        return self::$cache[$file] ?? [];
    }

    protected static function splitKey(string $key): array
    {
        $parts = explode('.', $key, 2);
        return [$parts[0], $parts[1] ?? ''];
    }

    protected static function loadFile(string $file): void
    {
        $label = 'config';
        $filePath = File::getPath($label, $file) . ".php";

        if (!file_exists($filePath)) {
            Logger::warning("Config file not found: {$filePath}");
            return;
        }

        try {
            $data = require $filePath;

            if (!is_array($data)) {
                Logger::error("Config file does not return array: {$filePath}");
                return;
            }

            self::$cache[$file] = $data;
        } catch (Throwable $e) {
            Logger::error("Error loading config file '{$filePath}': " . $e->getMessage());
        }
    }

    protected static function resolve(array $array, string $path, mixed $default): mixed
    {
        if ($path === '') {
            return $array;
        }

        foreach (explode('.', $path) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }
}
