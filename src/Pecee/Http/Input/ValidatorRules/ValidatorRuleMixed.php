<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleMixed extends InputValidatorRule
{

    protected $tag = 'mixed';
    protected $requires = array();

    public function validate(IInputItem $inputItem): bool
    {
        return true;
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is not of type mixed';
    }

}