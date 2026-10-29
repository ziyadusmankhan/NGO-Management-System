<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InitializeLookupKey extends Command
{
    protected $signature = 'security:lookup-key';

    protected $description = 'Create the separate exact-search secret in the local .env, without changing APP_KEY or printing secrets';

    public function handle(): int
    {
        $path = base_path('.env');
        if (! is_file($path)) {
            $this->error('Create the environment file first.');

            return 1;
        }
        $handle = fopen($path, 'r+');
        if (! $handle || ! flock($handle, LOCK_EX)) {
            $this->error('Cannot lock environment file.');

            return 1;
        }
        try {
            $text = stream_get_contents($handle);
            if (preg_match('/^SECURITY_LOOKUP_KEY=([^\r\n]*)/m', $text, $matches) && trim($matches[1], " \t\"'") !== '') {
                $this->info('Lookup key already configured; it was not changed.');

                return 0;
            }
            $text = preg_replace('/^SECURITY_LOOKUP_KEY=.*\r?\n?/m', '', $text);
            $text = rtrim($text)."\nSECURITY_LOOKUP_KEY=".base64_encode(random_bytes(32))."\n";
            rewind($handle);
            if (fwrite($handle, $text) !== strlen($text)) {
                throw new \RuntimeException('Environment write failed.');
            }ftruncate($handle, strlen($text));
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
        $this->info('Lookup key stored privately in .env. Back it up securely with the existing APP_KEY. Clear cached configuration before conversion.');

        return 0;
    }
}
