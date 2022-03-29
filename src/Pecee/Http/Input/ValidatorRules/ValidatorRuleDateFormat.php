<?php

namespace Pecee\Http\Input\ValidatorRules;

use DateTime;
use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleDateFormat extends InputValidatorRule
{

    protected $tag = 'date_format';
    protected $requires = array('string');

    public function validate(IInputItem $inputItem): bool
    {
        return sizeof($this->getAttributes()) > 0 && DateTime::createFromFormat($this->getAttributes()[0], $inputItem->getValue()) !== false;
    }

    public function getErrorMessage(): string
    {
        return 'Failed to create date from format %2$s for Input %1$s';
    }

}