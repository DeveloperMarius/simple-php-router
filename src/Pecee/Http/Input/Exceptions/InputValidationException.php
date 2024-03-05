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
        $errors = array();
        foreach ($this->getValidation()->errors()->toArray() as $key => $rule_errors){
            foreach ($rule_errors as $rule => $message){
                if(!isset($errors[$key]))
                    $errors[$key] = array();
                $errors[$key][$rule] = (string) $message;
            }
        }
        return $errors;
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
        $messages = array();
        foreach ($this->getErrorMessages() as $key => $rules){
            foreach ($rules as $rule => $message){
                $messages[] = $key . ': ' . $rule . ' - ' . $message;
            }
        }
        return 'Failed to validate inputs: ' . (sizeof($messages) === 0 ? 'keine' : join(';', $messages));
    }

}