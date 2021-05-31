<?php

namespace BFO\DataTypes;

class Bookie
{
    private $id;
    private $name;
    private $affiliate_url;

    public function __construct(int $id, string $name, string $affiliate_url)
    {
        $this->id = $id;
        $this->name = $name;
        $this->affiliate_url = $affiliate_url;
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
}
