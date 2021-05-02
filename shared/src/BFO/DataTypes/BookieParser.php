<?php

namespace BFO\DataTypes;

/**
 * Bookie parser class
 */
class BookieParser
{
    private $iID;
    private $iBookieID;
    private $sName;
    private $sParseURL;
    private $sMockFile;
    private $bCNInUse; //Changenum in use
    private $sCNURLSuffix; //Changenum URL suffix
    
    /**
     * Constructor
     */
    public function __construct($a_iID, $a_iBookieID, $a_sName, $a_sParseURL, $a_sMockFile, $a_bCNInUse, $a_sCNURLSuffix)
    {
        $this->iID = $a_iID;
        $this->iBookieID = $a_iBookieID;
        $this->sName = $a_sName;
        $this->sParseURL = $a_sParseURL;
        $this->sMockFile = $a_sMockFile;
        $this->bCNInUse = $a_bCNInUse;
        $this->sCNURLSuffix = $a_sCNURLSuffix;
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

    public function getBookieID()
    {
        return $this->iBookieID;
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
     * Get Parse URL
     *
     * @return string URL
     */
    public function getParseURL()
    {
        return $this->sParseURL;
    }

    public function getMockFile()
    {
        return $this->sMockFile;
    }

    public function hasChangenumInUse()
    {
        return $this->bCNInUse;
    }

    public function getChangenumSuffix()
    {
        return $this->sCNURLSuffix;
    }
}
