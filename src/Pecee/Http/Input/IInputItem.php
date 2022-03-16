<?php

namespace Pecee\Http\Input;

interface IInputItem
{

    /**
     * @return string
     */
    public function getIndex(): string;

    /**
     * @param string $index
     * @return $this
     */
    public function setIndex(string $index): self;

    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * @return mixed
     */
    public function getValue(): mixed;

    /**
     * @param mixed $value
     */
    public function setValue(mixed $value): self;

    /**
     * @return bool
     */
    public function hasInputItems(): bool;

    /**
     * @return array
     */
    public function getInputItems(): array;

    /**
     * @return string
     */
    public function __toString(): string;

}