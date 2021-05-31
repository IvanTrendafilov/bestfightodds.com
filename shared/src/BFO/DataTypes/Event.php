<?php

namespace BFO\DataTypes;

use BFO\Utils\LinkTools;

class Event
{
    private $id;
    private $date;
    private $name;
    private $display;

    public function __construct(int $id, string $date, string $name, bool $display = true)
    {
        $this->id = $id;
        $this->date = substr($date, 0, 10);
        $this->name = $name;
        $this->display = $display;
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate($new_date): void
    {
        $this->date = $new_date;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /*
     * Short name converts a longer name to a shorter one by omitting everything after the first. Example: UFC 201: Lawler vs. Woodley => UFC 201
     */
    public function getShortName(): string
    {
        $mark_pos = strpos($this->getName(), ':');
        if ($mark_pos != null) {
            return substr($this->getName(), 0, $mark_pos);
        }
        return $this->getName();
    }

    public function setName(string $new_name): void
    {
        $this->name = $new_name;
    }

    public function isDisplayed(): bool
    {
        return $this->display;
    }

    public function setDisplay($display): void
    {
        $this->display = $display;
    }

    /**
     * Gets the event as a link-friendly string in the format <name>-<id>
     * Example: ufc-98-evans-vs-machida-132
     */
    public function getEventAsLinkString(): string
    {
        $name = LinkTools::slugString($this->name);
        return strtolower($name . '-' . $this->id);
    }
}
