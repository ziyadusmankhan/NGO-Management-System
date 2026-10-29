<?php
// Blade directive names need a boundary before the next directive.
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__).'/resources/views', FilesystemIterator::SKIP_DOTS)) as $file) {
    if (! str_ends_with($file, '.blade.php')) { continue; }
    $source = file_get_contents($file);
    $updated = preg_replace('/(@[a-zA-Z]+)(?=@)/', "$1\n", $source);
    if ($updated !== $source) { file_put_contents($file, $updated); }
}
