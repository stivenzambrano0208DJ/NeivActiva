<?php

namespace App\Services;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use RuntimeException;

class QrCodeService {

    public function __construct($tcpdfQrPath = null) {
        // Parámetro conservado por compatibilidad; ya no se usa (antes apuntaba
        // a un archivo de TCPDF en XAMPP que no existe en producción/Linux).
    }

    /**
     * Genera un PNG del código QR y lo guarda en $rutaDestino.
     * Firma conservada para no tocar los llamadores existentes.
     */
    public function generarPng($contenido, $rutaDestino, $escala = 8, $margen = 4) {
        $directorio = dirname($rutaDestino);
        if (!is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        // Mapear la antigua escala/margen (en módulos) a tamaño/margen en píxeles.
        $size = max(200, (int) $escala * 37);
        $margin = max(0, (int) $margen * 3);

        try {
            $qrCode = QrCode::create($contenido)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::Medium)
                ->setSize($size)
                ->setMargin($margin);

            $result = (new PngWriter())->write($qrCode);
            $result->saveToFile($rutaDestino);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo generar el código QR: ' . $e->getMessage(), 0, $e);
        }

        return $rutaDestino;
    }
}
