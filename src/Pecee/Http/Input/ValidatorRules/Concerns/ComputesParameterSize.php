<?php

namespace Pecee\Http\Input\ValidatorRules\Concerns;

use Pecee\Http\Input\Exceptions\InputsNotValidatedException;
use Pecee\Http\Input\Exceptions\InputValidationException;
use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputFile;

trait ComputesParameterSize
{
    /**
     * @param IInputItem $input
     * @return float|int|null
     */
    public function computeSize(IInputItem $input): float|int|null
    {
        if (is_a($input, InputFile::class))
            return intval($input->getSize()) / 1024; // Size in Kb
        $input_value = $input->getValue();
        if (is_array($input_value))
            return count($input_value);
        if (is_numeric($input_value))
            return is_int($input_value) ? $input_value : floatval($input_value);
        if(is_string($input_value))
            return strlen($input_value);
        return null;
    }

    /**
     * @throws InputsNotValidatedException
     */
    public function parseSizeAttribute(): float|int
    {
        if (count($this->getAttributes()) <= 0 || !is_numeric($this->getAttributes()[0]))
            throw new InputsNotValidatedException('Size attribute invalid.');
        $size = $this->getAttributes()[0];
        return is_int($size) ? intval($size) : floatval($size);
    }
}