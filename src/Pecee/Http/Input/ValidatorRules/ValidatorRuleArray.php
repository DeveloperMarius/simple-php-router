<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleArray extends InputValidatorRule
{

    protected ?string $tag = 'array';
    protected array $requires = array('required');

    public function validate(IInputItem $inputItem): bool
    {
        return is_array($inputItem->getValue());
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is not of type array';
    }

}