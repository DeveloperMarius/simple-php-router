<?php

namespace Dummy\InputValidatorRules;

use Somnambulist\Components\Validation\Rule;

class ValidatorRuleCustomTest extends Rule{

    protected string $message = 'rule.custom_test';

    public function check($value): bool
    {
        return $value === 'customValue';
    }

}