<?php

// One-time mechanical extraction. Run from the repository root after reviewing the diff.
$root = dirname(__DIR__);
$views = $root.'/resources/views';
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($views, FilesystemIterator::SKIP_DOTS)) as $file) {
    if (! str_ends_with($file, '.blade.php')) { continue; }
    $source = file_get_contents($file);
    $relative = str_replace('\\', '/', substr($file, strlen($views)+1));
    $key = str_replace(['/', '.blade.php'], ['-', ''], $relative);
    $index = 0;
    $source = preg_replace_callback('/<style>(.*?)<\/style>/s', function ($match) use ($root, $relative, $key, &$index) {
        if ($relative === 'layouts/app.blade.php') { return ''; }
        if (str_contains($match[1], '{{') || str_contains($match[1], '@if')) { return $match[0]; }
        $path = 'css/views/'.$key.'-'.++$index.'.css';
        if (! is_dir(dirname($root.'/public/'.$path))) { mkdir(dirname($root.'/public/'.$path), 0777, true); }
        file_put_contents($root.'/public/'.$path, trim($match[1])."\n");
        return '<link rel="stylesheet" href="{{ asset(\''.$path.'\') }}">';
    }, $source);
    if (str_contains(strtolower($source), '<html') && $relative !== 'layouts/app.blade.php') {
        $theme = str_starts_with($relative, 'auth/') ? 'foundation-auth.css' : 'foundation-print.css';
        if (! str_contains($source, $theme)) {
            $source = str_replace('</head>', '<link rel="stylesheet" href="{{ asset(\'css/'.$theme.'\') }}"></head>', $source);
        }
    }
    file_put_contents($file, $source);
}
echo "Extracted static styles; retained Blade and workflow logic.\n";
