<?php

namespace App\Core;

class View {
    public static function render($view, $data = []) {
        extract($data);
        
        $viewFile = dirname(__DIR__, 2) . "/resources/views/{$view}.php";
        $layoutFile = dirname(__DIR__, 2) . "/resources/views/layouts/main.php";

        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$view}");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if (file_exists($layoutFile) && $view !== 'errors/404') {
            ob_start();
            require $layoutFile;
            return ob_get_clean();
        }

        return $content;
    }
}
