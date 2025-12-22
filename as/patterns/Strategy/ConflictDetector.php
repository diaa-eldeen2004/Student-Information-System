<?php
namespace patterns\Strategy;

/**
 * Strategy Pattern - Behavioral
 * Context class that uses different conflict detection strategies
 */
class ConflictDetector
{
    private ConflictDetectionStrategy $strategy;

    public function __construct(ConflictDetectionStrategy $strategy)
    {
        $this->strategy = $strategy;
    }

    public function setStrategy(ConflictDetectionStrategy $strategy): void
    {
        $this->strategy = $strategy;
    }

    public function detectConflict(array $data): bool
    {
        return $this->strategy->checkConflict($data);
    }

    public function getErrorMessage(): string
    {
        return $this->strategy->getErrorMessage();
    }
}

