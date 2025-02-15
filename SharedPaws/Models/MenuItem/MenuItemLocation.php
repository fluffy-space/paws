<?php

namespace SharedPaws\Models\MenuItem;

use Exception;

class MenuItemLocation // TODO: static class transpile
{
    public array $locations = [
        'header' => 1,
        'footer' => 2,
        'email' => 3,
    ];

    public function hasArea(string $area): bool
    {
        return isset($this->locations[$area]);
    }

    public function getLocationId(string $area): int
    {
        if (!$this->hasArea($area)) {
            throw new Exception("Area '$area' not found.");
        }
        return $this->locations[$area];
    }

    public function getLocations(): array
    {
        return array_flip($this->locations);
    }
}
