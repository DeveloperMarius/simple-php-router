<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleNumeric extends InputValidatorRule
{

    protected ?string $tag = 'numeric';
    protected array $requires = array('required');

    public function validate(IInputItem $inputItem): bool
    {
        return is_numeric($inputItem->getValue());
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is not numeric';
    }

}