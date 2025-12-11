<?php
namespace core;

class Controller
{
    protected View $view;

    public function __construct()
    {
        $this->view = new View();
    }

    protected function model(string $name)
    {
        $class = '\\models\\' . $name;
        if (class_exists($class)) {
            return new $class();
        }

        throw new \RuntimeException("Model {$name} not found");
    }
}

