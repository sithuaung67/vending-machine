<?php
declare(strict_types=1);
namespace App\Core;

final class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layout'): string
    {
        $file = dirname(__DIR__, 2) . '/views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$template}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        $content = ob_get_clean();

        if ($layout !== null) {
            $layoutFile = dirname(__DIR__, 2) . '/views/' . $layout . '.php';
            ob_start();
            require $layoutFile;
            $content = ob_get_clean();
        }
        return $content;
    }
}
