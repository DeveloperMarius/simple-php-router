<?php

namespace Pecee\Http\Input;

//use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

class InputItem implements /*ArrayAccess,*/ IInputItem, IteratorAggregate
{

    /**
     * @var string $index
     */
    public string $index;
    /**
     * @var string $name
     */
    public string $name;
    /**
     * @var mixed|null $value
     */
    public $value;

    /**
     * @param string $index
     * @param mixed|null $value
     */
    public function __construct(string $index, $value = null)
    {
        $this->index = $index;
        $this->value = $value;

        // Make the name human friendly, by replace _ with space
        $this->name = ucfirst(str_replace('_', ' ', strtolower($this->index)));
    }

    /**
     * @return string
     */
    public function getIndex(): string
    {
        return $this->index;
    }

    /**
     * @param string $index
     * @return IInputItem
     */
    public function setIndex(string $index): IInputItem
    {
        $this->index = $index;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set input name
     * @param string $name
     * @return static
     */
    public function setName(string $name): IInputItem
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        if(is_array($this->value)){
            return $this->parseValueFromArray($this->value);
        }
        return $this->value;
    }

    /**
     * @return bool
     */
    public function hasInputItems(): bool
    {
        return is_array($this->value);
    }

    /**
     * @return InputItem[]
     */
    public function getInputItems(): array
    {
        if(is_array($this->value)){
            return $this->value;
        }
        return array();
    }

    /**
     * @param array $array
     * @return array
     */
    protected function parseValueFromArray(array $array): array
    {
        $output = [];
        /* @var $item InputItem */
        foreach ($array as $key => $item) {

            if ($item instanceof IInputItem) {
                $item = $item->getValue();
            }

            $output[$key] = is_array($item) ? $this->parseValueFromArray($item) : $item;
        }

        return $output;
    }

    /**
     * Set input value
     * @param mixed $value
     * @return static
     */
    public function setValue($value): IInputItem
    {
        $this->value = $value;

        return $this;
    }

    //TODO integrate into php 8 update
    /*public function offsetExists($offset): bool
    {
        return isset($this->value[$offset]);
    }

    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset) === true) {
            return $this->value[$offset];
        }

        return null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->value[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->value[$offset]);
    }*/

    public function __toString(): string
    {
        return $this->getIndex() . ':' . json_encode($this->getValue());
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->getValue());
    }
}