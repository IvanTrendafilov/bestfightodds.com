<?php

require_once('lib/bfocore/general/datatypes/class.PropBet.php');

class EventPropBet extends PropBet
{
    private $iEventID; //Event the prop is linked to

    public function __construct($a_iEventID, $a_iBookieID, $a_sPropName, $a_sPropOdds, $a_sNegPropName, $a_sNegPropOdds, $a_iPropTypeID, $a_sDate)
    {
        parent::__construct(-1, $a_iBookieID, $a_sPropName, $a_sPropOdds, $a_sNegPropName, $a_sNegPropOdds, $a_iPropTypeID, $a_sDate, 0);
        $this->setEventID($a_iEventID);
    }

    public function setEventID($a_iEventID)
    {
        $this->iEventID = $a_iEventID;
    }

    public function getEventID()
    {
        return $this->iEventID;
    }

    public function equals($a_oPropBet)
    {
        $bEquals = ($this->iEventID == $a_oPropBet->getEventID() &&
                $this->getBookieID() == $a_oPropBet->getBookieID() &&
                $this->getPropOdds() == $a_oPropBet->getPropOdds() &&
                $this->getNegPropOdds() == $a_oPropBet->getNegPropOdds() &&
                $this->getPropTypeID() == $a_oPropBet->getPropTypeID()
                );
        return $bEquals;
    }

}

?>
