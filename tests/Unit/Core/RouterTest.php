<?php
namespace Tests\Unit\Core;

use Tests\TestCase;
use core\Router;

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        $routes = [
            ['GET', '/', 'Home@index'],
            ['GET', '/auth/login', 'Auth@login'],
            ['POST', '/auth/login', 'Auth@login'],
            ['GET', '/student/dashboard', 'Student@dashboard'],
        ];
        $this->router = new Router($routes, '');
    }

    public function testResolveRootPath(): void
    {
        [$controller, $method, $params] = $this->router->resolve('/', 'GET');
        
        $this->assertEquals('Home', $controller);
        $this->assertEquals('index', $method);
        $this->assertIsArray($params);
    }

    public function testResolveLoginPath(): void
    {
        [$controller, $method, $params] = $this->router->resolve('/auth/login', 'GET');
        
        $this->assertEquals('Auth', $controller);
        $this->assertEquals('login', $method);
    }

    public function testResolvePostMethod(): void
    {
        [$controller, $method, $params] = $this->router->resolve('/auth/login', 'POST');
        
        $this->assertEquals('Auth', $controller);
        $this->assertEquals('login', $method);
    }

    public function testResolveNotFound(): void
    {
        [$controller, $method, $params] = $this->router->resolve('/nonexistent', 'GET');
        
        $this->assertNull($controller);
        $this->assertNull($method);
    }

    public function testResolveWithBasePath(): void
    {
        $routes = [
            ['GET', '/', 'Home@index'],
        ];
        $router = new Router($routes, '/Student-Information-System/public');
        
        [$controller, $method, $params] = $router->resolve('/Student-Information-System/public/', 'GET');
        
        $this->assertEquals('Home', $controller);
        $this->assertEquals('index', $method);
    }

    public function testNormalizePath(): void
    {
        $routes = [
            ['GET', '/test', 'Test@index'],
        ];
        $router = new Router($routes, '');
        
        [$controller, $method, $params] = $router->resolve('/test/', 'GET');
        
        $this->assertEquals('Test', $controller);
    }
}
