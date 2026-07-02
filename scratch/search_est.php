<?php
$c = file_get_contents('app/Controllers/MainController.php');
$lines = explode("\n", $c);
foreach($lines as $i => $l) {
    if(stripos($l, 'function estadisticas') !== false) {
        echo ($i+1) . ': ' . trim($l) . "\n";
    }
}
