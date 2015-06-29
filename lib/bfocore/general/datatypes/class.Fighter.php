<?php

require_once('lib/bfocore/utils/class.LinkTools.php');

/* 
 * A Fighter
 */
class Fighter
{
	private $sName;
	private $iID;
	
	public function __construct($a_sName, $a_iID)
	{
		$this->sName = $a_sName;
		$this->iID = $a_iID;
	}
	
	public function getName()
	{
		return $this->sName;
	}
	
	public function getID()
	{
		return $this->iID;
	}
	
	/**
	 * Use this for a nicer representation of the name. Only when displaying and when using though!
	 */
	public function getNameAsString()
	{
        $sTempFighter = strtolower($this->sName);
		$aNames = explode(' ', $sTempFighter);
		$sTempFighter = '';
		foreach ($aNames as $sName)
		{
			$sName[0] = strtoupper($sName[0]);
	
            // "McDonald", "O'Conner"..
            if (strncmp($sName,'Mc',2) == 0 || preg_match('/[OD]\'[a-zA-Z]/', $sName)) 
            {
                $sName[2] = strtoupper($sName[2]);
            }

            // "B.J. Penn"
            $iPos = strrpos($sName, '.');
            if ($iPos && $sName[$iPos - 2] == '.')
			{
				$sName[$iPos - 1] = strtoupper($sName[$iPos - 1]);
			}
			$iPos = strrpos($sName, '-');
			if ($iPos)
			{
				$sName[$iPos + 1] = strtoupper($sName[$iPos + 1]);
			}
			$sTempFighter .= $sName . ' ';
		}
		return trim($sTempFighter);
	}
	
	public function getFighterAsLinkString()
	{
		$sName = LinkTools::slugString($this->getNameAsString());
		return $sName . '-' . $this->iID;
	}
}

?>