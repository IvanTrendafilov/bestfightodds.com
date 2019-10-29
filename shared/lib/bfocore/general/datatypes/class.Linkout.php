<?php

class Linkout
{

    private $m_iBookieID;
    private $m_sBookieName;
    private $m_iEventID;
    private $m_iEventName;
    private $m_sDate;
    private $m_sVisitorIP;

    public function __construct($a_iBookieID, $a_sBookieName, $a_iEventID, $a_sEventName, $a_sDate, $a_sVisitorIP)
    {
        $this->m_iBookieID = $a_iBookieID;
        $this->m_sBookieName = $a_sBookieName;
        $this->m_iEventID = $a_iEventID;
        $this->m_iEventName = $a_sEventName;
        $this->m_sDate = $a_sDate;
        $this->m_sVisitorIP = $a_sVisitorIP;
    }

    public function getBookieID()
    {
        return $this->m_iBookieID;
    }

    public function getBookieName()
    {
        return $this->m_sBookieName;
    }

    public function getEventID()
    {
        return $this->m_iEventID;
    }

    public function getEventName()
    {
        return $this->m_iEventName;
    }

    public function getDate()
    {
        return $this->m_sDate;
    }

    public function getVisitorIP()
    {
        return $this->m_sVisitorIP;
    }

}

?>