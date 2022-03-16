<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\Exceptions\InputsNotValidatedException;
use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;
use Pecee\Http\Input\ValidatorRules\Concerns\ComputesParameterSize;

class ValidatorRuleMax extends InputValidatorRule
{
    use ComputesParameterSize;

    protected ?string $tag = 'max';
    protected array $requires = array('string', 'file', 'array', 'numeric');

    /**
     * @throws InputsNotValidatedException
     */
    public function validate(IInputItem $inputItem): bool
    {
        return $this->computeSize($inputItem) <= $this->parseSizeAttribute();
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s is too big';
    }

}