<?php
$dir = 'c:/xampp/htdocs/NeivActiva/resources/views';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$results = [];

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (preg_match('/<style>(.*?)<\/style>/s', $content, $matches)) {
            $cssContent = trim($matches[1]);
            $lines = count(explode("\n", $cssContent));
            if ($lines > 5) { // Only care about substantial CSS
                $results[] = [
                    'file' => str_replace('c:/xampp/htdocs/NeivActiva/', '', str_replace('\\', '/', $file->getPathname())),
                    'lines' => $lines
                ];
            }
        }
    }
}

echo json_encode($results, JSON_PRETTY_PRINT);
