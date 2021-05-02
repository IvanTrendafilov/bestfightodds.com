<?php

require_once('lib/bfocore/utils/class.LinkTools.php');

class Fight
{
    private $iID;
    private $sFighter1;
    private $sFighter2;
    private $iEventID;
    private $sComment;
    private $iFighter1ID;
    private $iFighter2ID;
    private $bOrderChanged;
    private $bIsMainEvent;
    private $bIsFuture;
    private $aMetadata;

    /**
     * Constructor
     *
     * @param int $a_iID ID
     * @param string $a_sFighter1 Fighter 1's name
     * @param string $a_sFighter2 Fighter 2's name
     * @param int $a_iEventID Event ID
     * @param string $a_sComment Comment
     */
    public function __construct($a_iID, $a_sFighter1, $a_sFighter2, $a_iEventID, $a_sComment = '')
    {
        $this->iID = $a_iID;
        if (trim($a_sFighter1) > trim($a_sFighter2)) {
            $this->sFighter1 = trim(strtoupper($a_sFighter2));
            $this->sFighter2 = trim(strtoupper($a_sFighter1));
            $this->bOrderChanged = true;
        } else {
            $this->sFighter1 = trim(strtoupper($a_sFighter1));
            $this->sFighter2 = trim(strtoupper($a_sFighter2));
            $this->bOrderChanged = false;
        }

        if ($a_iEventID == '') {
            $this->iEventID = -1;
        } else {
            $this->iEventID = $a_iEventID;
        }
        $this->sComment = $a_sComment;
        $this->aMetadata = [];
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
     * Get fighter 1's name
     *
     * @deprecated Use getFighter(fighterNo) instead
     * @return <type>
     */
    public function getFighter1()
    {
        return $this->sFighter1;
    }

    /**
     * Get fighter 2's name
     *
     * @deprecated
     * @return <type> Use getFighter(fighterNo) instead
     */
    public function getFighter2()
    {
        return $this->sFighter2;
    }

    /**
     * Get fighter name
     *
     * @deprecated Use getTeam instead
     * @param int $a_iFighter Fighter (1 or 2)
     * @return string Fighter name
     */
    public function getFighter($a_iFighter)
    {
        return $this->getTeam($a_iFighter);
    }

    /**
     * See getFighterAsStringFromString below
     *
     * @deprecated Use getTeamAsString instead
     */
    public function getFighterAsString($a_iFighter)
    {
        return $this->getTeamAsString($a_iFighter);
    }

    public function getTeamAsString($a_iTeam)
    {
        return $this->getFighterAsStringFromString($this->getFighter($a_iTeam));
    }
    
    public function getTeamLastNameAsString($a_iTeam)
    {
        //Gets everything after first name: $aParts = explode(' ', $a_sName, 2); return $aParts[1];
        $aParts = explode(' ', $this->getFighterAsStringFromString($this->getFighter($a_iTeam)));
        return $aParts[count($aParts) - 1];
    }

    public function getFighterAsLinkString($a_iFighter)
    {
        $sName = $this->getFighterAsString($a_iFighter);
        $sName = LinkTools::slugString($sName);

        /*$sName = str_replace('.', '', $sName);
        $sName = str_replace(' ', '-', $sName);
        $sName = str_replace("'", "", $sName);*/
        return $sName . '-' . ($a_iFighter == 1 ? $this->iFighter1ID : $this->iFighter2ID);
    }

    public function getFightAsLinkString()
    {
        $sName = LinkTools::slugString($this->getFighterAsString(1)) . '-vs-' . LinkTools::slugString($this->getFighterAsString(2));
        return $sName . '-' . $this->iID;
    }

    /**
     * Returns a more nicer looking representation of the fighter
     * Warning: Use only when displaying fighter name directly to user and NOT when working with the name.
     *
     * TODO: Currently fixes if a fighter has a name like B.J. Penn.
     *		 However does not fix if a name has 3 or more letter like
     *		 'R.K.B Whatever' which would look like 'R.k.B Whatever'
     *
     */
    public function getFighterAsStringFromString($a_sFighter)
    {
        $string = $a_sFighter;

        $word_splitters = array(' ', '.', '-', "O'", "L'", "D'", 'St.', 'Mc');
        $lowercase_exceptions = array('the', 'van', 'den', 'von', 'und', 'der', 'de', 'da', 'of', 'and', "l'", "d'");
        $uppercase_exceptions = array('III', 'IV', 'VI', 'VII', 'VIII', 'IX');
    
        $string = strtolower($string);
        foreach ($word_splitters as $delimiter) {
            $words = explode($delimiter, $string);
            $newwords = array();
            foreach ($words as $word) {
                if (in_array(strtoupper($word), $uppercase_exceptions)) {
                    $word = strtoupper($word);
                } elseif (!in_array($word, $lowercase_exceptions)) {
                    $word = ucfirst($word);
                }
    
                $newwords[] = $word;
            }
    
            if (in_array(strtolower($delimiter), $lowercase_exceptions)) {
                $delimiter = strtolower($delimiter);
            }
    
            $string = join($delimiter, $newwords);
        }
        return trim($string);
    }

    /**
     * Get fighter ID
     *
     * @param int $a_iFighter Fighter (1 or 2)
     * @return int Fighter ID
     */
    public function getFighterID($a_iFighter)
    {
        if (!isset($this->iFighter1ID) || !isset($this->iFighter2ID)) {
            return -1;
        }
        switch ($a_iFighter) {
            case 1: return $this->iFighter1ID;
                break;
            case 2: return $this->iFighter2ID;
                break;
            default: return -1;
                break;
        }
    }

    public function getEventID()
    {
        return $this->iEventID;
    }

    public function getComment()
    {
        return $this->sComment;
    }

    public function hasOrderChanged()
    {
        return $this->bOrderChanged;
    }

    public function setComment($a_sNewComment)
    {
        $this->sComment = $a_sNewComment;
    }

    public function setFighterID($a_iFighter, $a_iFighterID)
    {
        if ($this->bOrderChanged == true) {
            if ($a_iFighter == 1) {
                $this->iFighter2ID = $a_iFighterID;
            } elseif ($a_iFighter == 2) {
                $this->iFighter1ID = $a_iFighterID;
            }
        } else {
            if ($a_iFighter == 1) {
                $this->iFighter1ID = $a_iFighterID;
            } elseif ($a_iFighter == 2) {
                $this->iFighter2ID = $a_iFighterID;
            }
        }
    }

    public function setMainEvent($a_bIsMainEvent)
    {
        switch ($a_bIsMainEvent) {
            case 1: $a_bIsMainEvent = true;
                break;
            case 0: $a_bIsMainEvent = false;
                break;
        }

        $this->bIsMainEvent = $a_bIsMainEvent;
    }

    public function isMainEvent()
    {
        if (isset($this->bIsMainEvent)) {
            return $this->bIsMainEvent;
        }
        return false;
    }

    public function setIsFuture($a_bIsFuture)
    {
        switch ($a_bIsFuture) {
            case 1: $a_bIsFuture = true;
                break;
            case 0: $a_bIsFuture = false;
                break;
        }

        $this->bIsFuture = $a_bIsFuture;
    }

    public function isFuture()
    {
        if (isset($this->bIsFuture)) {
            return $this->bIsFuture;
        }
        return false;
    }

    public function setEventID($a_iEventID)
    {
        $this->iEventID = $a_iEventID;
    }


    public function getTeam($a_iTeam)
    {
        switch ($a_iTeam) {
            case 1: return $this->sFighter1;
                break;
            case 2: return $this->sFighter2;
                break;
            default:
                return 0;
                break;
        }
    }

    public function setMetadata($a_sAttribute, $a_sValue)
    {
        $this->aMetadata[(string) $a_sAttribute] = (string) $a_sValue;
    }

    public function getMetadata($a_sAttribute)
    {
        return isset($this->aMetadata[(string) $a_sAttribute]) ? $this->aMetadata[(string) $a_sAttribute] : false;
    }
}
