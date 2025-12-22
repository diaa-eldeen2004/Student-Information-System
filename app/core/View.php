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
            // Extract path from base_url if it's a full URL
            $basePath = '';
            if (!empty($baseUrl)) {
                $parsed = parse_url($baseUrl);
                $basePath = $parsed['path'] ?? '';
            }
            $basePath = rtrim($basePath, '/');
            $assetPath = $basePath . '/' . ltrim($path, '/');
            // Ensure path starts with / for absolute path
            if (!empty($assetPath) && $assetPath[0] !== '/') {
                $assetPath = '/' . $assetPath;
            }
            // Remove double slashes
            $assetPath = preg_replace('#/+#', '/', $assetPath);
            return $assetPath;
        };

        $shared = [
            'url' => $urlHelper,
            'asset' => $assetHelper,
            'baseUrl' => $baseUrl,
        ];

        extract(array_merge($shared, $data), EXTR_SKIP);

        // Pass helpers into the view as well
        $content = $this->buffer($viewPath, array_merge($data, $shared));
        
        // If layout is false or empty string, skip layout
        if ($layout === false || $layout === '') {
            echo $content;
            return;
        }
        
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
        // Ensure url and asset helpers are always available
        if (!isset($data['url']) || !is_callable($data['url'])) {
            $baseUrl = rtrim($this->config['base_url'] ?? '', '/');
            $data['url'] = function($path) use ($baseUrl) {
                $base = $baseUrl === '' ? '' : $baseUrl;
                return $base . '/' . ltrim($path, '/');
            };
        }
        if (!isset($data['asset']) || !is_callable($data['asset'])) {
            $baseUrl = rtrim($this->config['base_url'] ?? '', '/');
            $data['asset'] = function($path) use ($baseUrl) {
                $basePath = '';
                if (!empty($baseUrl)) {
                    $parsed = parse_url($baseUrl);
                    $basePath = $parsed['path'] ?? '';
                }
                $basePath = rtrim($basePath, '/');
                $assetPath = $basePath . '/' . ltrim($path, '/');
                if (!empty($assetPath) && $assetPath[0] !== '/') {
                    $assetPath = '/' . $assetPath;
                }
                $assetPath = preg_replace('#/+#', '/', $assetPath);
                return $assetPath;
            };
        }
        
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

