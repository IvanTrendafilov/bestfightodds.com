<?php

namespace BFO\DataTypes;

use BFO\Utils\LinkTools;

class Event
{
    private $iID;
    private $sDate;
    private $sName;
    private $bDisplay;

    public function __construct($a_iID, $a_sDate, $a_sName, $a_bDisplay = true)
    {
        $this->iID = $a_iID;
        $this->sDate = substr($a_sDate, 0, 10);
        $this->sName = $a_sName;
        $this->bDisplay = $a_bDisplay;
    }

    public function getID()
    {
        return $this->iID;
    }

    public function getDate()
    {
        return $this->sDate;
    }

    public function setDate($a_sDate)
    {
        $this->sDate = $a_sDate;
    }

    public function getName()
    {
        return $this->sName;
    }

    //Short name converts a longer name to a shorter one by omitting everything after the first : .E.g. UFC 201: Lawler vs. Woodley => UFC 201
    public function getShortName()
    {
        $mark_pos = strpos($this->getName(), ':');
        if ($mark_pos != null) {
            return substr($this->getName(), 0, $mark_pos);
        }
        return $this->getName();
    }

    public function setName($new_name)
    {
        $this->sName = $new_name;
    }

    public function isDisplayed()
    {
        return $this->bDisplay;
    }

    public function setDisplay($display)
    {
        $this->bDisplay = $display;
    }

    /**
     * Gets the event as a link-friendly string in the format <name>-<id>
     *
     * Example: ufc-98-evans-vs-machida-132
     *
     * @return string Event as link string
     */
    public function getEventAsLinkString()
    {
        $sName = LinkTools::slugString($this->sName);
        return strtolower($sName . '-' . $this->iID);
    }
}
