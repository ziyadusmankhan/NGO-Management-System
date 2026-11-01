<?php

namespace Tests\Support;

class PhpWorker
{
    public static function command(): array
    {
        // Ignore machine-specific INI/debugger settings, but explicitly load
        // shared dependencies. PDO is built in on Windows and shared on Ubuntu.
        $command = [PHP_BINARY, '-n'];
        foreach (['pdo', 'mbstring', 'pdo_sqlite', 'sqlite3', 'fileinfo', 'openssl'] as $extension) {
            $path = ini_get('extension_dir').DIRECTORY_SEPARATOR
                .(PHP_OS_FAMILY === 'Windows' ? 'php_' : '').$extension.'.'.PHP_SHLIB_SUFFIX;
            if (! is_file($path)) {
                $path = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.$path;
            }
            if (is_file($path)) {
                array_push($command, '-d', 'extension='.$path);
            }
        }

        return $command;
    }
}
