<?php

namespace Pecee\Http\Input;

interface IInputValidatorRule
{

    /**
     * @param ...$attributes
     * @return InputValidatorRule
     */
    public static function make(...$attributes): InputValidatorRule;

    /**
     * @param ...$attributes
     */
    public function __construct(...$attributes);

    /**
     * @return string
     */
    public function getTag(): string;

    /**
     * @return array
     */
    public function getAttributes(): array;

    /**
     * @param IInputItem $inputItem
     * @return mixed
     */
    public function validate(IInputItem $inputItem);
}