<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleMimeTypes extends InputValidatorRule
{

    protected ?string $tag = 'mime_types';
    protected array $requires = array('file');

    public function validate(IInputItem $inputItem): bool
    {
        if (sizeof($this->getAttributes()) > 0) {
            foreach ($this->getAttributes() as $attribute) {
                if ($attribute === $inputItem->getMime())
                    return true;
            }
        }
        return false;
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is not of type string';
    }

}