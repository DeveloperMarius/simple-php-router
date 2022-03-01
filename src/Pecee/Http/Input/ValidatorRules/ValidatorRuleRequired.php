<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputFile;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleRequired extends InputValidatorRule
{

    protected $tag = 'required';

    public function validate(IInputItem $inputItem): bool
    {
        if (is_a($inputItem, InputFile::class)) {
            return $inputItem->getFilename() !== null && trim($inputItem->getFilename()) !== '';
        }
        if(is_array($inputItem->getValue())){
            return sizeof($inputItem->getValue()) > 0;
        }

        return $inputItem->getValue() !== null && trim($inputItem->getValue()) !== '';
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is required';
    }

}