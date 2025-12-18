<?php
namespace patterns\Decorator;

/**
 * Decorator Pattern - Structural
 * Base decorator interface
 */
interface ModelDecoratorInterface
{
    public function getData(): array;
    public function format(): string;
}

/**
 * Base model decorator
 */
abstract class ModelDecorator implements ModelDecoratorInterface
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    abstract public function format(): string;
}

