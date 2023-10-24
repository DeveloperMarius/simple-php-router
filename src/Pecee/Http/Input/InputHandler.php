<?php

namespace Pecee\Http\Input;

use Pecee\Exceptions\InvalidArgumentException;
use Pecee\Http\Input\Attributes\ValidatorAttribute;
use Pecee\Http\Request;
use Pecee\SimpleRouter\SimpleRouter;
use Somnambulist\Components\Validation\Validation;

class InputHandler implements IInputHandler
{

    /**
     * @var bool $handleEmptyStringAsNull
     */
    public static bool $handleEmptyStringAsNull = true;

    /**
     * @var InputItem[]
     */
    protected array $get = [];

    /**
     * Original get/params variables
     * @var array
     */
    protected array $originalParams = [];

    /**
     * @var InputItem[]
     */
    protected array $data = [];

    /**
     * Original post variables
     * @var array
     */
    protected array $originalPost = [];

    /**
     * @var array
     */
    protected array $originalBody = [];

    /**
     * @var string
     */
    protected string $originalBodyPlain = '';

    /**
     * @var InputFile[]
     */
    protected array $file = [];

    /**
     * Get original file variables
     * @var array
     */
    protected array $originalFile = [];

    /**
     * @var Request
     */
    protected Request $request;

    /**
     * Input constructor.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->parseInputs();
    }

    /**
     * Parse input values
     *
     */
    public function parseInputs(): void
    {

        $this->originalParams = [];
        $this->get = [];
        $this->originalPost = [];
        $this->data = [];
        $this->originalBody = [];
        $this->originalBodyPlain = '';
        $this->originalFile = [];
        $this->file = [];

        /* Parse get requests */
        if (count($_GET) !== 0) {
            $this->originalParams = $_GET;
            $this->get = $this->parseInputItem($this->originalParams);
        }

        /* Get body */
        $this->originalBodyPlain = file_get_contents('php://input');

        /* Parse body */
        if (in_array($this->request->getMethod(), Request::$requestTypesPost, false)) {
            switch($this->request->getContentType()){
                case Request::CONTENT_TYPE_JSON:
                    $body = json_decode($this->originalBodyPlain, true);
                    if ($body !== false && $body !== null) {
                        $this->originalBody = $body;
                        $this->data = $this->parseInputItem($body);
                    }
                    break;
                case Request::CONTENT_TYPE_X_FORM_ENCODED:
                    $body = null;
                    parse_str($this->originalBodyPlain, $body);
                    if ($body !== null) {
                        $this->originalBody = $body;
                        $this->data = $this->parseInputItem($body);
                    }
                    break;
                //case Request::CONTENT_TYPE_X_FORM_ENCODED|Request::CONTENT_TYPE_FORM_DATA:
                default:
                    if (count($_POST) !== 0) {
                        $this->originalPost = $_POST;
                        $this->data = $this->parseInputItem($this->originalPost);
                    }
                    break;
            }
        }

        /* Parse get requests */
        if (count($_FILES) !== 0) {
            $this->originalFile = $_FILES;
            $this->file = $this->parseFiles($this->originalFile);
        }
    }

    /**
     * @param array $files Array with files to parse
     * @param string|null $parentKey Key from parent (used when parsing nested array).
     * @return array
     */
    public function parseFiles(array $files, ?string $parentKey = null): array
    {
        $list = [];

        foreach ($files as $key => $value) {

            // Parse multi dept file array
            if(isset($value['name']) === false && is_array($value)) {
                $list[$key] = (new InputFile($key))->addInputFile($this->parseFiles($value, $key));
                continue;
            }

            // Handle array input
            if (is_array($value['name']) === false) {
                $values['index'] = $parentKey ?? $key;

                try {
                    $list[$key] = InputFile::createFromArray($values + $value);
                } catch (InvalidArgumentException $e) {

                }
                continue;
            }

            $keys = [$key];
            $files = $this->rearrangeFile($value['name'], $keys, $value);

            /** @var InputFile[] $list */
            if (isset($list[$key]) === true) {
                $list[$key]->addInputFile(array_values($files));
            } else {
                $list[$key] = (new InputFile($key))->addInputFile(array_values($files));
            }

        }

        return $list;
    }

    /**
     * Rearrange multi-dimensional file object created by PHP.
     *
     * @param array $values
     * @param array $index
     * @param array|null $original
     * @return array
     */
    protected function rearrangeFile(array $values, array &$index, ?array $original): array
    {
        $originalIndex = $index[0];
        array_shift($index);

        $output = [];

        foreach ($values as $key => $value) {

            if (is_array($original['name'][$key]) === false) {

                try {

                    $file = InputFile::createFromArray([
                        'index'    => (empty($key) === true && empty($originalIndex) === false) ? $originalIndex : $key,
                        'name'     => $original['name'][$key],
                        'error'    => $original['error'][$key],
                        'tmp_name' => $original['tmp_name'][$key],
                        'type'     => $original['type'][$key],
                        'size'     => $original['size'][$key],
                    ]);

                    if (isset($output[$key]) === true) {
                        $output[$key][] = $file;
                        continue;
                    }

                    $output[$key] = $file;
                    continue;

                } catch (InvalidArgumentException $e) {

                }
            }

            $index[] = $key;

            $files = $this->rearrangeFile($value, $index, $original);

            if (isset($output[$key]) === true) {
                $output[$key][] = $files;
            } else {
                $output[$key] = $files;
            }

        }

        return $output;
    }

    /**
     * Parse input item from array
     *
     * @param array $array
     * @return array
     */
    protected function parseInputItem(array $array): array
    {
        $list = [];

        foreach ($array as $key => $value) {

            // Handle array input
            if (is_array($value) === true && count($value) > 0) {
                $value = $this->parseInputItem($value);
                /*if(array_keys($value) !== range(0, count($value) - 1)){
                    //Parse again if associative array
                    $value = $this->parseInputItem($value);
                }else if(count($value) > 0 && is_array($value[0])){
                    $value = array_map(fn($item) => $this->parseInputItem($item), $value);
                }*/
            }

            if($value === '' && self::$handleEmptyStringAsNull)
                $value = null;

            $list[$key] = new InputItem($key, $value);
        }

        return $list;
    }

    /**
     * Find input object
     *
     * @param string $index
     * @param mixed $defaultValue
     * @param string|array ...$methods - Strings or one array of methods
     * @return InputItem|InputFile
     */
    public function find(string $index, mixed $defaultValue = null, ...$methods): InputFile|InputItem
    {
        $element = new InputItem($index, $defaultValue);

        if(count($methods) == 1) {
            $methods = is_array($methods[0]) ? array_values($methods[0]) : $methods;
        }

        if (count($methods) === 0 || in_array(Request::REQUEST_TYPE_GET, $methods, true) === true) {
            $element = $this->get($index);
        }

        if (($element->getValue() === null && count($methods) === 0) || (count($methods) !== 0 && count(array_intersect(Request::$requestTypesPost, $methods)) !== 0)) {
            $element = $this->data($index);
        }

        if (($element->getValue() === null && count($methods) === 0) || (count($methods) !== 0 && in_array('file', $methods, true) === true)) {
            $element = $this->file($index);
            if($element->getValue() === null){
                $element = new InputItem($index, $defaultValue);
            }
        }

        return $element;
    }

    /**
     * Get input element value matching index
     *
     * @param string $index
     * @param mixed|null $defaultValue
     * @param string|array ...$methods
     * @return mixed
     */
    public function value(string $index, mixed $defaultValue = null, ...$methods): mixed
    {
        $input = $this->find($index, $defaultValue, ...$methods);

        if ($input instanceof IInputItem) {
            $input = $input->getValue();
        }

        /* Handle collection */
        if (is_array($input) && count($input) === 0) {
            return $defaultValue;
        }

        return ($input === null || (is_string($input) && trim($input) === '')) ? $defaultValue : $input;
    }

    /**
     * Get input element value matching index
     *
     * @param array<string>|array<string, callable> $filter
     * @param string|array ...$methods
     * @return array
     */
    public function values(array $filter = [], ...$methods): array
    {
        $inputs = $this->all($filter, ...$methods);

        $values = array();
        foreach($inputs as $key => $input){
            if ($input instanceof IInputItem) {
                $values[$key] = $input->getValue();
            }else{
                $values[$key] = $input;
            }
        }

        return $values;
    }

    /**
     * Check if a input-item exist
     *
     * @param string|array $index
     * @param string|array ...$methods
     * @return bool
     */
    public function exists(string|array $index, ...$methods): bool
    {
        if(is_array($index) === true) {
            foreach($index as $key) {
                if($this->value($key, null, ...$methods) === null) {
                    return false;
                }
            }

            return true;
        }
        return $this->value($index, null, ...$methods) !== null;
    }

    /**
     * Find post-value by index or return default value.
     *
     * @param string $index
     * @param mixed $defaultValue
     * @return InputItem
     * @deprecated Use $item->data() instead
     */
    public function post(string $index, mixed $defaultValue = null): InputItem
    {
        return $this->data($index, $defaultValue);
    }

    /**
     * Find body-value by index or return default value.
     *
     * @param string $index
     * @param mixed $defaultValue
     * @return InputItem
     */
    public function data(string $index, mixed $defaultValue = null): InputItem
    {
        if(!isset($this->data[$index]))
            return new InputItem($index, $defaultValue);
        return $this->data[$index];
    }

    /**
     * Find file by index or return default value.
     *
     * @param string $index
     * @param mixed $defaultValue
     * @return InputFile
     */
    public function file(string $index, mixed $defaultValue = null): InputFile
    {
        if(!isset($this->file[$index]))
            return (new InputFile($index))->setValue($defaultValue);
        return $this->file[$index];
    }

    /**
     * Find parameter/query-string by index or return default value.
     *
     * @param string $index
     * @param mixed $defaultValue
     * @return InputItem
     */
    public function get(string $index, mixed $defaultValue = null): InputItem
    {
        if(!isset($this->get[$index]))
            return new InputItem($index, $defaultValue);
        return $this->get[$index];
    }

    /**
     * Get all get/post/file items
     * @param array<string>|array<string, callable> $filter Only take items in filter
     * @param string|array ...$methods
     * @return array<string, IInputItem>
     */
    public function all(array $filter = [], ...$methods): array
    {
        $output = array();
        if(empty($methods)){
            $output = $this->data + $this->get + $this->file;
        }else{
            if(is_array($methods[0]))
                $methods = $methods[0];
            if(in_array(Request::REQUEST_TYPE_GET, $methods, true) === true){
                $output += $this->get;
            }
            if(count(array_intersect(Request::$requestTypesPost, $methods)) !== 0){
                $output += $this->data;
            }
            if(in_array('file', $methods, true) === true){
                $output += $this->file;
            }
        }

        $keys = count($filter) > 0 && !is_numeric(array_key_first($filter)) ? array_keys($filter) : $filter;
        $keys = array_map(fn($key) => explode('.', $key)[0], $keys);

        $output = (count($keys) > 0) ? array_intersect_key($output, array_flip($keys)) : $output;

        $this->all_recursive($filter, $output);

        return $output;
    }

    private function all_recursive(array $filter, array &$output){
        $filter_sub = array();
        foreach ($filter as $filterKey => $parser) {
            if(is_int($filterKey)){
                $filterKey = $parser;
                $parser = null;
            }
            $filterKey = str_replace('.*', '', $filterKey);
            $filterKeyParts = explode('.', $filterKey, 2);
            if (array_key_exists($filterKeyParts[0], $output) === false) {
                $output[$filterKeyParts[0]] = new InputItem($filterKeyParts[0]);
            }
            if(sizeof($filterKeyParts) === 2 && $output[$filterKeyParts[0]]->hasInputItems()){
                if(!isset($filter_sub[$filterKeyParts[0]]))
                    $filter_sub[$filterKeyParts[0]] = array();
                $filter_sub[$filterKeyParts[0]][$filterKeyParts[1]] = $parser;
            }else {
                if($parser !== null)
                    $output[explode('.', $filterKey)[0]]->parser()->parseFromSetting($parser)->getValue();
            }
        }
        foreach($filter_sub as $key => $filter){
            $items = $output[$key]->getInputItems();
            if(array_keys($items) !== range(0, count($items) - 1)){
                $this->all_recursive($filter, $items);
            }else{
                foreach($items as &$item){
                    $this->all_recursive($filter, $item);
                }
            }
            $output[$key]->setValue($items);
        }
    }

    /**
     * @return ValidatorAttribute[]
     */
    private function getValidatorAttributes(): array{
        $reflection = InputValidator::getReflection(SimpleRouter::router());
        $attributes = $reflection->getAttributes(ValidatorAttribute::class);
        $validator_attributes = array();
        foreach($attributes as $attribute){
            /* @var ValidatorAttribute $routeAttribute */
            $routeAttribute = $attribute->newInstance();
            if($routeAttribute->getName() !== null)
                $validator_attributes[] = $routeAttribute;
        }
        return $validator_attributes;
    }

    /**
     * @return IInputItem[]
     */
    public function requireAttributes(?array $filter = null): array{
        $value_filter = array();
        foreach($this->getValidatorAttributes() as $attribute){
            if($filter === null || in_array($attribute->getName(), $filter))
                $value_filter[$attribute->getName()] = $attribute->getType();
        }
        return $this->all($value_filter);
    }

    /**
     * @param array|null $filter
     * @return array
     */
    public function requireAttributeValues(?array $filter = null): array{
        $value_filter = array();
        foreach($this->getValidatorAttributes() as $attribute){
            if($filter === null || in_array($attribute->getName(), $filter))
                $value_filter[$attribute->getName()] = $attribute->getType();
        }
        return $this->values($value_filter);
    }

    /**
     * @param InputValidator|array|null $validator
     * @return Validation
     */
    public function validateAttributes(InputValidator|array|null $validator = null): Validation{
        if($validator === null){
            $validator = array();
            foreach($this->getValidatorAttributes() as $attribute){
                $validator[$attribute->getName()] = $attribute->getFullValidator();
            }
        }
        if(!$validator instanceof InputValidator){
            $tmp = InputValidator::make();
            $tmp->setRules($validator);
            $validator = $tmp;
        }
        return $validator->validateInputs($this);
    }

    /**
     * Add GET parameter
     *
     * @param string $key
     * @param InputItem $item
     */
    public function addGet(string $key, InputItem $item): void
    {
        $this->get[$key] = $item;
    }

    /**
     * Add data parameter
     *
     * @param string $key
     * @param InputItem $item
     */
    public function addData(string $key, InputItem $item): void
    {
        $this->data[$key] = $item;
    }

    /**
     * Add FILE parameter
     *
     * @param string $key
     * @param InputFile $item
     */
    public function addFile(string $key, InputFile $item): void
    {
        $this->file[$key] = $item;
    }

    /**
     * Get original post variables
     * @return array
     */
    public function getOriginalPost(): array
    {
        return $this->originalPost;
    }

    /**
     * Set original post variables
     * @param array $post
     * @return static $this
     */
    public function setOriginalPost(array $post): self
    {
        $this->originalPost = $post;

        return $this;
    }

    /**
     * @return array
     */
    public function getOriginalBody(): array
    {
        return $this->originalBody;
    }

    /**
     * @return string
     */
    public function getOriginalBodyPlain(): string
    {
        return $this->originalBodyPlain;
    }

    /**
     * Get original get variables
     * @return array
     */
    public function getOriginalParams(): array
    {
        return $this->originalParams;
    }

    /**
     * Set original get-variables
     * @param array $params
     * @return static $this
     */
    public function setOriginalParams(array $params): self
    {
        $this->originalParams = $params;

        return $this;
    }

    /**
     * Get original file variables
     * @return array
     */
    public function getOriginalFile(): array
    {
        return $this->originalFile;
    }

    /**
     * Set original file posts variables
     * @param array $file
     * @return static $this
     */
    public function setOriginalFile(array $file): self
    {
        $this->originalFile = $file;

        return $this;
    }

}