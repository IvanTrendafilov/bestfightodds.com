<?php
/**
 * 
 * Description of ParsedTotal
 *
 * @author Christian
 */
class ParsedTotal
{
    private $fTotalPoints;
    private $sOverMoneyline;
    private $sUnderMoneyline;

    function  __construct($a_fTotalPoints, $a_sOverML, $a_sUnderML)
    {
        $this->fTotalPoints = $a_fTotalPoints;
        $this->sOverMoneyline = $a_sOverML;
        $this->sUnderMoneyline = $a_sUnderML;

        if (strtoupper($this->sOverMoneyline) == 'EV' || strtoupper($this->sOverMoneyline) == 'EVEN')
		{
			$this->sOverMoneyline = '100';
		}

		if (strtoupper($this->sUnderMoneyline) == 'EV' || strtoupper($this->sUnderMoneyline) == 'EVEN')
		{
			$this->sUnderMoneyline = '100';
		}
    }

    public function getTotalPoints()
    {
        return $this->fTotalPoints;
    }

    public function getOverML()
    {
        return $this->sOverMoneyline;
    }

    public function getUnderML()
    {
        return $this->sUnderMoneyline;
    }


}
?>
