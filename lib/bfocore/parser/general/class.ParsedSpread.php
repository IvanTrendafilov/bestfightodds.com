<?php

/**
 * Description of ParsedSpread
 *
 * @author Christian
 */
class ParsedSpread
{
    private $fTeam1Spread;
    private $fTeam2Spread;
    private $sTeam1Moneyline;
    private $sTeam2Moneyline;
    private $bSwitched;

    function  __construct($a_fTeam1Spread, $a_fTeam2Spread, $a_sTeam1Moneyline, $a_sTeam2Moneyline)
    {
        $this->bSwitched = false;
        $this->fTeam1Spread = (float) $a_fTeam1Spread;
        $this->fTeam2Spread = (float) $a_fTeam2Spread;
        $this->sTeam1Moneyline = $a_sTeam1Moneyline;
        $this->sTeam2Moneyline = $a_sTeam2Moneyline;

        if (strtoupper($this->sTeam1Moneyline) == 'EV' || strtoupper($this->sTeam1Moneyline) == 'EVEN')
		{
			$this->sTeam1Moneyline = '100';
		}

		if (strtoupper($this->sTeam2Moneyline) == 'EV' || strtoupper($this->sTeam2Moneyline) == 'EVEN')
		{
			$this->sTeam2Moneyline = '100';
		}
    }

    public function getSpread($a_iTeamNo)
    {
        switch ($a_iTeamNo)
        {
            case 1: return $this->fTeam1Spread;
                break;
            case 2: return $this->fTeam2Spread;
                break;
            default:
                return null;
        }
    }

    public function getMoneyline($a_iTeamNo)
    {
        switch ($a_iTeamNo)
        {
            case 1: return $this->sTeam1Moneyline;
                break;
            case 2: return $this->sTeam2Moneyline;
                break;
            default:
                return null;
        }
    }

    public function isSwitched()
    {
        return $this->bSwitched;
    }

    public function switchOdds()
	{
		$sTempOdds = $this->sTeam1Moneyline;
		$this->sTeam1Moneyline = $this->sTeam2Moneyline;
		$this->sTeam2Moneyline = $sTempOdds;

        $sTempOdds = $this->fTeam1Spread;
		$this->fTeam1Spread = $this->fTeam2Spread;
		$this->fTeam2Spread = $sTempOdds;

        $this->bSwitched = true;
	}

}
?>
