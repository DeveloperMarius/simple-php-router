<?php

namespace Pecee\Http\Input\Attributes;

use Attribute;

/**
 *
 *
 * @since 8.0
 */
#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_FUNCTION|Attribute::TARGET_PROPERTY|Attribute::IS_REPEATABLE|Attribute::TARGET_PARAMETER)]
class ValidatorAttribute
{

    private ?string $type;
    private array $validator;

    /**
     * @param string|null $name
     * @param string|null $type
     * @param string|array $validator
     */
    public function __construct(
        private ?string $name = null,
        ?string $type = null,
        string|array $validator = array()
    ){
        if(is_string($validator))
            $validator = explode('|', $validator);
        $this->validator = $validator;
        $this->setType($type);
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getValidatorType(): ?string
    {
        return match ($this->type){
            'bool' => 'boolean',
            'int' => 'integer',
            default => $this->type
        };
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        if($type === null){
            $this->type = null;
            return;
        }
        if(str_starts_with($type, '?')){
            $type = substr($type, 1);
            array_unshift($this->validator, 'nullable');
        }
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function getValidator(): array
    {
        return $this->validator;
    }

    /**
     * @param string $validator
     * @return void
     */
    public function addValidator(string $validator): void
    {
        if(!in_array($validator, $this->validator))
            $this->validator[] = $validator;
    }

    /**
     * @return array
     */
    public function getFullValidator(): array
    {
        $validator = $this->getValidator();
        if($this->getValidatorType() !== null && !in_array($this->getValidatorType(), $validator))
            array_unshift($validator, $this->getValidatorType());
        if(!in_array('nullable', $validator))
            array_unshift($validator, 'required');
        return $validator;
    }

}