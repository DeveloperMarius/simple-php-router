<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

class ValidatorRuleEndsWith extends InputValidatorRule
{

    protected ?string $tag = 'ends_with';
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
                if (str_ends_with($value, $attribute))
                    return true;
            }
            return false;
        }
        if (is_array($inputItem->getValue())) {
            if ($this->isAssociativeArray($inputItem->getValue())) {
                //Support for PHP 7.1, array_key_last since PHP 7.3 (Removed since this version aims at PHP 8)
                $key = array_key_last($inputItem->getValue());
                $last_value = $inputItem->getValue()[$key];
            } else {
                $last_value = $inputItem->getValue()[sizeof($inputItem->getValue()) - 1];
            }
            foreach ($this->getAttributes() as $attribute) {
                if ($last_value === $attribute)
                    return true;
            }
            return false;
        }

        return false;
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s must end with %s';
    }

}