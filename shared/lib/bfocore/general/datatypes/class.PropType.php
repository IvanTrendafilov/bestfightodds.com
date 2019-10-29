<?php

class PropType
{
    private $iID;
    private $sPropDesc;
    private $sPropNegDesc;
    private $bTeamSpecific = false;
    private $iTeamNum;
    private $bIsEventProp = false;

    public function __construct($a_iID, $a_sPropDesc, $a_sPropNegDesc, $a_iTeamNum = 0)
    {
        $this->iID = $a_iID;
        $this->sPropDesc = $a_sPropDesc;
        $this->sPropNegDesc = $a_sPropNegDesc;

        if (preg_match('/<T>/', $a_sPropDesc) > 0 || preg_match('/<T>/', $a_sPropNegDesc) > 0)
        {
            $this->bTeamSpecific = true;
        }

        $this->iTeamNum = $a_iTeamNum;
    }

    public function getID()
    {
        return $this->iID;
    }

    public function setID($a_iID)
    {
        $this->iID = $a_iID;
    }

    public function getPropDesc()
    {
        return $this->sPropDesc;
    }

    public function setPropDesc($a_sPropDesc)
    {
        $this->sPropDesc = $a_sPropDesc;
    }

    public function getPropNegDesc()
    {
        return $this->sPropNegDesc;
    }

    public function setPropNegDesc($a_sPropNegDesc)
    {
        $this->sPropNegDesc = $a_sPropNegDesc;
    }

    public function isTeamSpecific()
    {
        return $this->bTeamSpecific;
    }

    public function getTeamNum()
    {
        return $this->iTeamNum;
    }

    public function invertTeamNum()
    {
        if ($this->iTeamNum == 1)
        {
            $this->iTeamNum = 2;
            return true;
        }
        else if ($this->iTeamNum == 2)
        {
            $this->iTeamNum = 1;
            return true;
        }
        return false;
    }

    public function setEventProp($a_bState)
    {
        $this->bIsEventProp = $a_bState;
    }

    public function isEventProp()
    {
        return $this->bIsEventProp;
    }


}

?>
