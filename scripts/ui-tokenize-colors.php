<?php
// Mechanical migration of fixed legacy colors to theme variables, preserving light values.
$root = dirname(__DIR__);
$tokens = [];
$transform = function ($source) use (&$tokens) {
    $source = preg_replace('/var\(--legacy-[a-z0-9-]+,\s*(#[a-f0-9]+)\)/i', '$1', $source);
    return preg_replace_callback('/(?<=[;{\s"\'])(color|background(?:-color)?|border(?:-(?:top|bottom|left|right|color))?|box-shadow)\s*:\s*([^;{}<>]+)/i', function ($declaration) use (&$tokens) {
        $property = strtolower($declaration[1]);
        $kind = $property === 'color' ? 'text' : (str_starts_with($property, 'background') ? 'surface' : 'border');
        return $declaration[1].':'.preg_replace_callback('/#([a-f0-9]{6}|[a-f0-9]{3})(?![a-f0-9])/i', function ($match) use (&$tokens, $kind) {
            $hex = strtolower($match[1]);
            $expanded = strlen($hex) === 3 ? implode('', array_map(fn($c)=>$c.$c, str_split($hex))) : $hex;
            $rgb = array_map(fn($i)=>hexdec(substr($expanded,$i,2))/255,[0,2,4]);
            [$r,$g,$b]=$rgb; $max=max($rgb);$min=min($rgb);$delta=$max-$min;$light=($max+$min)/2;
            $sat=$delta<0.00001?0:$delta/(1-abs(2*$light-1));
            $hue=0;
            if($delta>0){$hue=60*($max===$r?fmod(($g-$b)/$delta,6):($max===$g?($b-$r)/$delta+2:($r-$g)/$delta+4));if($hue<0)$hue+=360;}
            $semantic = $sat>.18 && $delta>.035;
            $family=$hue<18||$hue>=345?'danger':($hue<70?'warning':($hue<170?'success':($hue<260?'info':'accent')));
            $palette=[
                'danger'=>['#ffb7bf','#48252c','#73414a'], 'warning'=>['#f3d18d','#44371e','#6c5730'],
                'success'=>['#a0dfb6','#203f2e','#39634a'], 'info'=>['#aaccf0','#20394e','#3b5d7e'],
                'accent'=>['#d6c5f1','#362c48','#59466e'],
            ];
            if($kind==='text') $dark=$semantic?$palette[$family][0]:($light>.85?'#ffffff':($light<.3?'#e6efe9':'#adbbb2'));
            elseif($kind==='border') $dark=$semantic?$palette[$family][2]:'#34473c';
            else $dark=$semantic?($light<.5?'#31765b':$palette[$family][1]):($light>.97?'#18251f':'#203128');
            $key='--legacy-'.$kind.'-'.$hex;
            $tokens[$key]=['light'=>'#'.$hex,'dark'=>$dark];
            return 'var('.$key.', #'.$hex.')';
        }, $declaration[2]);
    }, $source);
};
$files = glob($root.'/public/css/views/*.css');
$files = array_merge($files,[$root.'/public/css/foundation-ui.css',$root.'/public/css/foundation-auth.css']);
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/resources/views',FilesystemIterator::SKIP_DOTS)) as $file) {
    if(str_ends_with($file,'.blade.php'))$files[]=(string)$file;
}
foreach($files as $file){$source=file_get_contents($file);$updated=$transform($source);if($updated!==$source)file_put_contents($file,$updated);}
ksort($tokens);
$css="/* Generated theme tokens for legacy component colors. Light values remain fallbacks. */\n:root[data-theme=dark]{\n";
foreach($tokens as $key=>$value)$css.=' '.$key.':'.$value['dark'].";\n";
$css.="}\n@media print{:root[data-theme=dark]{\n";
foreach($tokens as $key=>$value)$css.=' '.$key.':'.$value['light'].";\n";
$css.="}}\n";
file_put_contents($root.'/public/css/foundation-legacy-tokens.css',$css);
echo count($tokens)." legacy color tokens migrated.\n";
