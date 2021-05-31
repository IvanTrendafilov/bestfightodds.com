<?php

namespace BFO\DataTypes;

class PropTemplate
{
    private $id;
    private $bookie_id;
    private $template_pos;
    private $template_neg;
    private $proptype_id;
    private $prop_pos_values;
    private $prop_neg_values;
    private $fieldstype_id;
    private $is_event_prop = false;
    private $last_used_date;

    private $bNegIsPrimary = false;

    public function __construct(int $id, int $bookie_id, string $template_pos, string $template_neg, int $proptype_id, int $fieldstype_id, string $last_used_date)
    {
        $this->id = (int) $id;
        $this->bookie_id = $bookie_id;
        $this->template_pos = strtoupper($template_pos);
        $this->proptype_id = $proptype_id;
        $this->template_neg = strtoupper($template_neg);
        $this->fieldstype_id = $fieldstype_id;
        $this->last_used_date = $last_used_date;

        //Match all prop variables (<A-Z>) and store these as array
        if (preg_match_all('/<[A-Z]+?>/', $this->template_pos, $this->prop_pos_values)) {
            $this->prop_pos_values = $this->prop_pos_values[0];
        }
        if (preg_match_all('/<[A-Z]+?>/', $this->template_neg, $this->prop_neg_values)) {
            $this->prop_neg_values = $this->prop_neg_values[0];
        }

        if ($template_pos == '') {
            $this->bNegIsPrimary = true;
        }
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getBookieID(): int
    {
        return $this->bookie_id;
    }

    public function getTemplate(): string
    {
        return $this->template_pos;
    }

    public function getTemplateNeg(): string
    {
        return $this->template_neg;
    }

    public function getPropTypeID(): int
    {
        return $this->proptype_id;
    }

    public function getFieldsTypeID(): int
    {
        return $this->fieldstype_id;
    }

    public function setFieldsTypeID(int $fieldstype_id): void
    {
        $this->fieldstype_id = $fieldstype_id;
    }

    public function toString(): string
    {
        $return_string = $this->getTemplate() . ' / ' . $this->getTemplateNeg();
        $return_string = str_replace('<', '&#60;', $return_string);
        $return_string = str_replace('>', '&#62;', $return_string);
        return $return_string;
    }

    public function getPropVariables(): array
    {
        return $this->prop_pos_values;
    }

    public function getNegPropVariables(): array
    {
        return $this->prop_neg_values;
    }

    public function isNegPrimary()
    {
        return $this->bNegIsPrimary;
    }

    public function getFieldsTypeAsExample(): string
    {
        switch ($this->fieldstype_id) {
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

    public function setEventProp(bool $is_event_prop): void
    {
        $this->is_event_prop = $is_event_prop;
    }

    public function isEventProp(): bool
    {
        return $this->is_event_prop;
    }

    public function getLastUsedDate(): string
    {
        return $this->last_used_date;
    }

    public function equals(PropTemplate $other): bool
    {
        //Only compare ID if specified by both objects
        if ($this->getID() > 0 && $other->getID() > 0
            && $this->getID() != $other->getID()) {
                return false;
        }

        if ($this->getBookieID() == $other->getBookieID()
            && $this->getTemplate() == $other->getTemplate()
            && $this->getTemplateNeg() == $other->getTemplateNeg()
            && $this->getPropTypeID() == $other->getPropTypeID()
            && $this->getFieldsTypeID() == $other->getFieldsTypeID()
            && $this->isEventProp() == $other->isEventProp()) {
                return true;
        }

        return false;
    }
}
