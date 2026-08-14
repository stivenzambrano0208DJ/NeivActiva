<?php
/**
 * Optimiza imagenes (redimensiona + recomprime) con GD, sobrescribiendo en sitio.
 * No cambia nombres ni extensiones, asi que no rompe referencias.
 *
 * Uso:  php -d extension=gd scratch/optimizar_imagenes.php
 */
if (!function_exists('imagecreatefromjpeg')) {
    fwrite(STDERR, "GD no disponible. Ejecuta con: php -d extension=gd scratch/optimizar_imagenes.php\n");
    exit(1);
}

$dirs = [
    __DIR__ . '/../public/assets/img',
    __DIR__ . '/../public/uploads/eventos',
];
$maxW    = 1600; // ancho maximo (px)
$calidad = 82;   // calidad JPEG

$totalAntes = 0;
$totalDespues = 0;

foreach ($dirs as $dir) {
    foreach (glob($dir . '/*') as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            continue;
        }

        $antes = filesize($file);
        $info = @getimagesize($file);
        if (!$info) { continue; }
        [$w, $h] = $info;

        $img = ($ext === 'png') ? @imagecreatefrompng($file) : @imagecreatefromjpeg($file);
        if (!$img) { continue; }

        if ($w > $maxW) {
            $nh = (int) round($h * $maxW / $w);
            $dst = imagecreatetruecolor($maxW, $nh);
            if ($ext === 'png') {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            }
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $maxW, $nh, $w, $h);
            imagedestroy($img);
            $img = $dst;
        }

        if ($ext === 'png') {
            imagepng($img, $file, 8);
        } else {
            imagejpeg($img, $file, $calidad);
        }
        imagedestroy($img);
        clearstatcache(true, $file);

        $despues = filesize($file);
        $totalAntes += $antes;
        $totalDespues += $despues;
        printf("%-58s %7.0f KB -> %7.0f KB  (-%.0f%%)\n",
            basename($file), $antes / 1024, $despues / 1024,
            $antes > 0 ? (1 - $despues / $antes) * 100 : 0);
    }
}

printf("\nTOTAL: %.0f KB -> %.0f KB  (ahorro %.0f KB)\n",
    $totalAntes / 1024, $totalDespues / 1024, ($totalAntes - $totalDespues) / 1024);
