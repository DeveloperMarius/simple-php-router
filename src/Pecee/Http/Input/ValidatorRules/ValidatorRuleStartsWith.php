<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleStartsWith extends InputValidatorRule
{

    protected ?string $tag = 'starts_with';
    protected array $requires = array('string', 'numeric', 'array');

    /**
     * @param array $value
     * @return bool
     */
    private function isAssociativeArray(array $value): bool
    {
        if (array() === $value) return false;
        return array_keys($value) !== range(0, count($value) - 1);
    }

    public function validate(IInputItem $inputItem): bool
    {
        if (is_string($inputItem->getValue()) || is_numeric($inputItem->getValue())) {
            $value = strval($inputItem->getValue());
            foreach ($this->getAttributes() as $attribute) {
                if (str_starts_with($attribute, $value))
                    return true;
            }
            return false;
        }

        if (is_array($inputItem->getValue())) {
            if ($this->isAssociativeArray($inputItem->getValue())) {
                //Support for PHP 7.1, array_key_first since PHP 7.3 (Removed since this version aims at PHP 8)
                $key = array_key_first($inputItem->getValue());
                $first_value = $inputItem->getValue()[$key];
            } else {
                $first_value = $inputItem->getValue()[0];
            }
            foreach ($this->getAttributes() as $attribute) {
                if ($first_value === $attribute)
                    return true;
            }
            return false;
        }

        return false;
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s must start with %s';
    }

}