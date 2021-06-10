<?php

namespace BFO\DataTypes;

/**
 * Represents a sportsbook (bookie) in the system
 */
class Bookie
{
    private $id;
    private $name;
    private $affiliate_url;
    private bool $is_active;

    public function __construct(int $id, string $name, string $affiliate_url, bool $is_active = true)
    {
        $this->id = $id;
        $this->name = $name;
        $this->affiliate_url = $affiliate_url;
        $this->is_active = $is_active;
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRefURL(): string
    {
        return $this->affiliate_url;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }
}
