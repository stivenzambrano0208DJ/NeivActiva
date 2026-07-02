<?php
function searchDir($dir) {
    $files = scandir($dir);
    foreach($files as $file) {
        if($file === '.' || $file === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if(is_dir($path)) {
            searchDir($path);
        } else {
            if(pathinfo($path, PATHINFO_EXTENSION) === 'php' || pathinfo($path, PATHINFO_EXTENSION) === 'js') {
                $c = file_get_contents($path);
                $lines = explode("\n", $c);
                foreach($lines as $i => $l) {
                    if(stripos($l, 'fallaron') !== false || stripos($l, 'encontraron') !== false) {
                        echo $path . ' (' . ($i+1) . '): ' . trim($l) . "\n";
                    }
                }
            }
        }
    }
}
searchDir('app');
searchDir('resources');
searchDir('public');
