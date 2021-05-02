<?php

namespace BFO\DataTypes;

/**
 * Bookie class
 */
class Bookie
{
    private $iID;
    private $sName;
    private $sURL;
    private $sRefURL;

    /**
     * Constructor
     *
     * @param int $a_iID ID
     * @param string $a_sName Name
     * @param string $a_sURL URL
     * @param string $a_sRefURL Affiliate URL
     */
    public function __construct($a_iID, $a_sName, $a_sURL, $a_sRefURL)
    {
        $this->iID = $a_iID;
        $this->sName = $a_sName;
        $this->sURL = $a_sURL;
        $this->sRefURL = $a_sRefURL;
    }

    /**
     * Get ID
     *
     * @return int ID
     */
    public function getID()
    {
        return $this->iID;
    }

    /**
     * Get name
     *
     * @return string Name
     */
    public function getName()
    {
        return $this->sName;
    }

    /**
     * Get URL
     *
     * @return string URL
     */
    public function getURL()
    {
        return $this->sURL;
    }

    /**
     * Get Affiliate URL
     *
     * @return string Affiliate URL
     */
    public function getRefURL()
    {
        return $this->sRefURL;
    }
}
