<?php

namespace Pecee\Http\Input\Attributes;

use Attribute;

/**
 *
 *
 * @since 8.0
 */
#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_FUNCTION|Attribute::TARGET_PROPERTY|Attribute::IS_REPEATABLE|Attribute::TARGET_PARAMETER)]
class ValidatorAttribute{

    /**
     * @param string|null $name
     * @param string|null $type
     * @param string|null $validator
     */
    public function __construct(
        private ?string $name = null,
        private ?string $type = null,
        private ?string $validator = null
    ){
        if($this->validator === '')
            $this->validator = null;
        if($this->type !== null && str_starts_with($this->type, '?')){
            $this->type = substr($this->type, 1);
            $this->addValidator('nullable');
        }
    }

    /**
     * @return string|null
     */
    public function getName(): ?string{
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void{
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string{
        return $this->type;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void{
        $this->type = $type;
        if($this->type !== null && str_starts_with($this->type, '?')){
            $this->type = substr($this->type, 1);
            $this->addValidator('nullable');
        }
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getValidator(): ?string{
        return $this->validator;
    }

    /**
     * @param string $validator
     * @return void
     */
    public function addValidator(string $validator){
        if($this->validator !== null){
            if(!str_contains($this->validator, $validator))
                $this->validator .= '|' . $validator;
        }else{
            $this->validator = $validator;
        }
    }

    /**
     * @return string
     */
    public function getFullValidator(): string{
        return $this->getType() !== null ? $this->getType() . ($this->getValidator() !== null ? '|' . $this->getValidator() : '') : ($this->getValidator() ?? '');
    }

}