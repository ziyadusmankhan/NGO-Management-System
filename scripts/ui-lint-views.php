<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$failed = 0; $count = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS)) as $file) {
    if (! str_ends_with($file, '.blade.php')) { continue; }
    $count++;
    try { token_get_all(app('blade.compiler')->compileString(file_get_contents($file)), TOKEN_PARSE); }
    catch (Throwable $e) { $failed++; echo substr($file, strlen(base_path())+1).': '.$e->getMessage()."\n"; }
}
echo "{$count} templates parsed; {$failed} failures.\n";
exit($failed ? 1 : 0);
