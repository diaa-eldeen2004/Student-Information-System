<?php
namespace middleware;

abstract class Middleware
{
    abstract public function handle(): bool;
}

