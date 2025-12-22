<?php
namespace patterns\Observer;

/**
 * Observer Pattern - Behavioral
 * Subject interface that observers can subscribe to
 */
interface Subject
{
    public function attach(Observer $observer): void;
    public function detach(Observer $observer): void;
    public function notify(string $event, array $data = []): void;
}

