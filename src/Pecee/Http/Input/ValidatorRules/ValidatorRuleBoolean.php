<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleBoolean extends InputValidatorRule
{

    protected $tag = 'boolean';
    protected $requires = array('required');

    /**
     * "On" and "Off" are allowed by purpose
     *
     * @param IInputItem $inputItem
     * @return bool
     */
    public function validate(IInputItem $inputItem): bool
    {
        return filter_var($inputItem->getValue(), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null;
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is not of type boolean';
    }

}