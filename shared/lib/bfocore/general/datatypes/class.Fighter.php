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

	function getNameAsString() 
	{
		$string = $this->sName;

		$word_splitters = array(' ', '.', '-', "O'", "L'", "D'", 'St.', 'Mc');
		$lowercase_exceptions = array('the', 'van', 'den', 'von', 'und', 'der', 'de', 'da', 'of', 'and', "l'", "d'");
		$uppercase_exceptions = array('III', 'IV', 'VI', 'VII', 'VIII', 'IX');
	
		$string = strtolower($string);
		foreach ($word_splitters as $delimiter)
		{ 
			$words = explode($delimiter, $string); 
			$newwords = array(); 
			foreach ($words as $word)
			{ 
				if (in_array(strtoupper($word), $uppercase_exceptions))
					$word = strtoupper($word);
				else
				if (!in_array($word, $lowercase_exceptions))
					$word = ucfirst($word); 
	
				$newwords[] = $word;
			}
	
			if (in_array(strtolower($delimiter), $lowercase_exceptions))
				$delimiter = strtolower($delimiter);
	
			$string = join($delimiter, $newwords); 
		} 
		return trim($string); 
	}
	
	public function getFighterAsLinkString()
	{
		$sName = LinkTools::slugString($this->getNameAsString());
		return $sName . '-' . $this->iID;
	}
}

?>