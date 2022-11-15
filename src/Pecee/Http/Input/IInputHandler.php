<?php

namespace Pecee\Http\Input;

interface IInputHandler
{

    public function parseInputs(): void;

    /**
     * @param string $index
     * @param mixed|null $defaultValue
     * @param string|array ...$methods
     * @return InputItem|InputFile
     */
    public function find(string $index, mixed $defaultValue = null, ...$methods): InputFile|InputItem;

    /**
     * @param string $index
     * @param mixed $defaultValue
     * @param string|array ...$methods
     * @return mixed
     */
    public function value(string $index, mixed $defaultValue = null, ...$methods): mixed;

    /**
     * @param string $index
     * @param string|array ...$methods
     * @return bool
     */
    public function exists(string $index, ...$methods): bool;

    /**
     * @param string $index
     * @param mixed $defaultValue
     * @return InputItem
     */
    public function post(string $index, mixed $defaultValue = null): InputItem;

    /**
     * @param string $index
     * @param mixed $defaultValue
     * @return InputItem
     */
    public function data(string $index, mixed $defaultValue = null): InputItem;

    /**
     * @param string $index
     * @param mixed $defaultValue
     * @return InputFile
     */
    public function file(string $index, mixed $defaultValue = null): InputFile;

    /**
     * @param string $index
     * @param mixed $defaultValue
     * @return InputItem
     */
    public function get(string $index, mixed $defaultValue = null): InputItem;

    /**
     * @param array $filter
     * @return array<string, IInputItem>
     */
    public function all(array $filter = []): array;

    /**
     * @param array $filter
     * @return array
     */
    public function values(array $filter = []): array;
}