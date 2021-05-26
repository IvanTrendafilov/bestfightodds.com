<?php

namespace BFO\DataTypes;

use BFO\Utils\LinkTools;

class Fighter
{
    private $name;
    private $id;
    
    public function __construct($name, $id)
    {
        $this->name = $name;
        $this->id = $id;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getID()
    {
        return $this->id;
    }

    public function getNameAsString()
    {
        $string = $this->name;

        $word_splitters = [' ', '.', '-', "O'", "L'", "D'", 'St.', 'Mc'];
        $lowercase_exceptions = ['the', 'van', 'den', 'von', 'und', 'der', 'de', 'da', 'of', 'and', "l'", "d'"];
        $uppercase_exceptions = ['III', 'IV', 'VI', 'VII', 'VIII', 'IX'];
    
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
    
    public function getFighterAsLinkString()
    {
        $name = LinkTools::slugString($this->getNameAsString());
        return $name . '-' . $this->id;
    }
}
