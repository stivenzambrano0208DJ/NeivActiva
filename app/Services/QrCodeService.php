<?php

namespace App\Services;

class QrCodeService {
    private $tcpdfQrPath;

    public function __construct($tcpdfQrPath = null) {
        $this->tcpdfQrPath = $tcpdfQrPath ?: 'C:/xampp/phpMyAdmin/vendor/tecnickcom/tcpdf/include/barcodes/qrcode.php';
    }

    public function generarPng($contenido, $rutaDestino, $escala = 8, $margen = 4) {
        $matriz = $this->crearMatriz($contenido);
        $directorio = dirname($rutaDestino);

        if (!is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        $png = $this->matrizAPng($matriz, $escala, $margen);

        if (file_put_contents($rutaDestino, $png) === false) {
            throw new RuntimeException('No se pudo guardar el codigo QR.');
        }

        return $rutaDestino;
    }

    private function crearMatriz($contenido) {
        if (!class_exists('QRcode')) {
            if (!file_exists($this->tcpdfQrPath)) {
                throw new RuntimeException('No se encontro el generador QR de TCPDF.');
            }
            require_once $this->tcpdfQrPath;
        }

        $qr = new QRcode($contenido, 'M');
        $barcode = $qr->getBarcodeArray();

        if (empty($barcode['bcode'])) {
            throw new RuntimeException('No se pudo construir la matriz QR.');
        }

        return $barcode['bcode'];
    }

    private function matrizAPng($matriz, $escala, $margen) {
        $modulos = count($matriz);
        $tamano = ($modulos + ($margen * 2)) * $escala;
        $raw = '';

        for ($y = 0; $y < $tamano; $y++) {
            $raw .= chr(0);
            $moduloY = intdiv($y, $escala) - $margen;

            for ($x = 0; $x < $tamano; $x++) {
                $moduloX = intdiv($x, $escala) - $margen;
                $oscuro = $moduloX >= 0
                    && $moduloY >= 0
                    && $moduloX < $modulos
                    && $moduloY < $modulos
                    && (int) $matriz[$moduloY][$moduloX] === 1;

                $raw .= $oscuro ? "\x11\x18\x27" : "\xff\xff\xff";
            }
        }

        return "\x89PNG\r\n\x1a\n"
            . $this->chunk('IHDR', pack('NNC5', $tamano, $tamano, 8, 2, 0, 0, 0))
            . $this->chunk('IDAT', gzcompress($raw, 9))
            . $this->chunk('IEND', '');
    }

    private function chunk($tipo, $datos) {
        return pack('N', strlen($datos)) . $tipo . $datos . pack('N', crc32($tipo . $datos));
    }
}
