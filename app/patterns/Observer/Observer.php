<?php
namespace patterns\Observer;

/**
 * Observer Pattern - Behavioral
 * Observer interface
 */
interface Observer
{
    public function update(string $event, array $data): void;
}

