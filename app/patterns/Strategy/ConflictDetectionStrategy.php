<?php
namespace patterns\Strategy;

/**
 * Strategy Pattern - Behavioral
 * Interface for conflict detection strategies
 */
interface ConflictDetectionStrategy
{
    public function checkConflict(array $data): bool;
    public function getErrorMessage(): string;
}

