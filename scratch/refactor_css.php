<?php
$viewsDir = 'c:/xampp/htdocs/NeivActiva/resources/views/';
$cssDir = 'c:/xampp/htdocs/NeivActiva/public/assets/css/views/';

// Create directory if it doesn't exist
if (!is_dir($cssDir)) {
    mkdir($cssDir, 0777, true);
}

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));

$processed = 0;

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filePath = $file->getPathname();
        $basename = $file->getBasename('.php');
        $content = file_get_contents($filePath);
        
        // Match the first <style>...</style> block
        // Assuming there is only one major style block per file
        if (preg_match('/<style>([\s\S]*?)<\/style>/i', $content, $matches)) {
            $cssContent = trim($matches[1]);
            
            // Only extract if there is actual CSS content
            if (!empty($cssContent)) {
                // Save to .css file
                $cssFilePath = $cssDir . $basename . '.css';
                file_put_contents($cssFilePath, $cssContent);
                
                // Replace in .php file
                $linkTag = '<link rel="stylesheet" href="/NeivActiva/public/assets/css/views/' . $basename . '.css">';
                // We use preg_replace to replace just the block we matched, limit 1
                $newContent = preg_replace('/<style>[\s\S]*?<\/style>/i', $linkTag, $content, 1);
                
                file_put_contents($filePath, $newContent);
                $processed++;
                echo "Processed: {$basename}.php -> {$basename}.css\n";
            }
        }
    }
}

echo "Total processed: $processed\n";
