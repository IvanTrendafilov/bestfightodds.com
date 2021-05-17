<?php

namespace BFO\DataTypes;

class PropType
{
    private $id;
    private $prop_desc;
    private $negprop_desc;
    private $is_team_specific = false;
    private $team_num;
    private $is_event_prop = false;

    public function __construct($id, $prop_desc, $negprop_desc, $team_num = 0)
    {
        $this->id = $id;
        $this->prop_desc = $prop_desc;
        $this->negprop_desc = $negprop_desc;

        if (preg_match('/<T>/', $prop_desc) > 0 || preg_match('/<T>/', $negprop_desc) > 0) {
            $this->is_team_specific = true;
        }

        $this->team_num = $team_num;
    }

    public function getID()
    {
        return $this->id;
    }

    public function setID($id)
    {
        $this->id = $id;
    }

    public function getPropDesc()
    {
        return $this->prop_desc;
    }

    public function setPropDesc($prop_desc)
    {
        $this->prop_desc = $prop_desc;
    }

    public function getPropNegDesc()
    {
        return $this->negprop_desc;
    }

    public function setPropNegDesc($negprop_desc)
    {
        $this->negprop_desc = $negprop_desc;
    }

    public function isTeamSpecific()
    {
        return $this->is_team_specific;
    }

    public function getTeamNum()
    {
        return $this->team_num;
    }

    public function invertTeamNum()
    {
        if ($this->team_num == 1) {
            $this->team_num = 2;
            return true;
        } elseif ($this->team_num == 2) {
            $this->team_num = 1;
            return true;
        }
        return false;
    }

    public function setEventProp($is_event_prop)
    {
        $this->is_event_prop = $is_event_prop;
    }

    public function isEventProp()
    {
        return $this->is_event_prop;
    }
}
