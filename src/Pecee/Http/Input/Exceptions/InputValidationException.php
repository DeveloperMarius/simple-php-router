<?php

namespace Pecee\Http\Input\Exceptions;

use Exception;
use Somnambulist\Components\Validation\Validation;
use Throwable;

class InputValidationException extends Exception
{

    /**
     * @var Validation|null $validation
     */
    private ?Validation $validation;

    public function __construct(string $message, ?Validation $validation = null, int $code = 0, Throwable $previous = null)
    {
        $this->validation = $validation;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return Validation|null
     */
    public function getValidation(): ?Validation
    {
        return $this->validation;
    }

    /**
     * @return array
     */
    public function getErrorMessages(): array
    {
        if($this->getValidation() === null)
            return array();
        return $this->getValidation()->errors()->all();
    }

    /**
     * @param string $key
     * @return array|null
     */
    public function getErrorsForItem(string $key): ?array
    {
        if($this->getValidation() === null)
            return null;
        $errors = $this->getValidation()->errors()->get($key);
        if(empty($errors))
            return null;
        return $errors;
    }

    /**
     * @return string
     */
    public function getDetailedMessage(): string
    {
        return 'Failed to validate inputs: ' . (empty($this->getErrorMessages()) ? 'keine' : join('; ', $this->getErrorMessages()));
    }

}