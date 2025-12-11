<?php
namespace core;

class View
{
    private array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__) . '/config/config.php';
    }

    public function render(string $view, array $data = [], string $layout = 'layout'): void
    {
        $viewPath = dirname(__DIR__) . '/views/' . $view . '.php';
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View {$view} not found");
        }

        $baseUrl = rtrim($this->config['base_url'] ?? '', '/');

        $urlHelper = function($path) use ($baseUrl) {
            $base = $baseUrl === '' ? '' : $baseUrl;
            return $base . '/' . ltrim($path, '/');
        };

        $assetHelper = function($path) use ($baseUrl) {
            $base = $baseUrl === '' ? '' : $baseUrl;
            return $base . '/' . ltrim($path, '/');
        };

        $shared = [
            'url' => $urlHelper,
            'asset' => $assetHelper,
            'baseUrl' => $baseUrl,
        ];

        extract(array_merge($shared, $data), EXTR_SKIP);

        // Pass helpers into the view as well
        $content = $this->buffer($viewPath, array_merge($data, $shared));
        $layoutPath = dirname(__DIR__) . '/views/' . $layout . '.php';

        if (file_exists($layoutPath)) {
            $this->buffer(
                $layoutPath,
                array_merge(
                    $data,
                    [
                        'content' => $content,
                        'asset' => $assetHelper,
                        'url' => $urlHelper,
                        'baseUrl' => $baseUrl,
                    ]
                ),
                false
            );
        } else {
            echo $content;
        }
    }


    private function buffer(string $path, array $data, bool $return = true): ?string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        include $path;
        $output = ob_get_clean();

        if ($return) {
            return $output;
        }

        echo $output;
        return null;
    }
}

