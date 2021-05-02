<?php

class PropTemplate
{
    private $iID;
    private $iBookieID;
    private $sTemplate;
    private $sTemplateNeg;
    private $iPropTypeID;
    private $aTemplatePropValues;
    private $aTemplateNegPropValues;
    private $iFieldsTypeID;
    private $bIsEventProp = false;
    private $sLastUsed;

    private $bNegIsPrimary = false;

    public function __construct($a_iID, $a_iBookieID, $a_sTemplate, $a_sTemplateNeg, $a_iPropTypeID, $a_iFieldsTypeID, $a_sLastUsed)
    {
        $this->iID = $a_iID;
        $this->iBookieID = $a_iBookieID;
        $this->sTemplate = strtoupper($a_sTemplate);
        $this->iPropTypeID = $a_iPropTypeID;
        $this->sTemplateNeg = strtoupper($a_sTemplateNeg);
        $this->iFieldsTypeID = $a_iFieldsTypeID;
        $this->sLastUsed = $a_sLastUsed;

        //Match all prop variables (<A-Z>) and store these as array
        if (preg_match_all('/<[A-Z]+?>/', $this->sTemplate, $this->aTemplatePropValues)) {
            $this->aTemplatePropValues = $this->aTemplatePropValues[0];
        }
        if (preg_match_all('/<[A-Z]+?>/', $this->sTemplateNeg, $this->aTemplateNegPropValues)) {
            $this->aTemplateNegPropValues = $this->aTemplateNegPropValues[0];
        }

        if ($a_sTemplate == '') {
            $this->bNegIsPrimary = true;
        }
    }

    public function getID()
    {
        return $this->iID;
    }

    public function getBookieID()
    {
        return $this->iBookieID;
    }

    public function getTemplate()
    {
        return $this->sTemplate;
    }

    public function getTemplateNeg()
    {
        return $this->sTemplateNeg;
    }

    public function getPropTypeID()
    {
        return $this->iPropTypeID;
    }

    public function getFieldsTypeID()
    {
        return $this->iFieldsTypeID;
    }

    public function setFieldsTypeID($a_iFieldsTypeID)
    {
        $this->iFieldsTypeID = $a_iFieldsTypeID;
    }

    public function toString()
    {
        $aRetStr = $this->getTemplate() . ' / ' . $this->getTemplateNeg();
        $aRetStr = str_replace('<', '&#60;', $aRetStr);
        $aRetStr = str_replace('>', '&#62;', $aRetStr);
        return $aRetStr;
    }

    public function getPropVariables()
    {
        return $this->aTemplatePropValues;
    }

    public function getNegPropVariables()
    {
        return $this->aTemplateNegPropValues;
    }

    public function isNegPrimary()
    {
        return $this->bNegIsPrimary;
    }

    public function getFieldsTypeAsExample()
    {
        switch ($this->iFieldsTypeID) {
            case 1: return 'koscheck vs miller';
            break;
            case 2: return 'josh koscheck vs dan miller';
            break;
            case 3: return 'koscheck';
            break;
            case 4: return 'josh koscheck';
            break;
            case 5: return 'j.koscheck';
            break;
            case 6: return 'j.koscheck vs d.miller';
            break;
            case 7: return 'j koscheck vs d miller';
            break;
            case 8: return 'j koscheck';
            break;
            default:
        }
    }

    public function setEventProp($a_bState)
    {
        $this->bIsEventProp = $a_bState;
    }

    public function isEventProp()
    {
        return $this->bIsEventProp;
    }

    public function getLastUsedDate()
    {
        return $this->sLastUsed;
    }
}
