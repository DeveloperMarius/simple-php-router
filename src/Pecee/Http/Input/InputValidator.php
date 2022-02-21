<?php

namespace Pecee\Http\Input;

use Closure;
use Pecee\Http\Input\Attributes\ValidatorAttribute;
use Pecee\Http\Input\Exceptions\InputsNotValidatedException;
use Pecee\Http\Input\Exceptions\InputValidationException;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Route\IRoute;
use Pecee\SimpleRouter\Router;
use Pecee\SimpleRouter\SimpleRouter;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

class InputValidator
{

    /**
     * @var bool $parseAttributes
     */
    public static bool $parseAttributes = false;

    /* Static config settings */

    /**
     * Allow throwing exceptions
     * @var bool
     */
    private static $throwExceptions = true;

    /**
     * @param bool $throwExceptions
     */
    public static function setThrowExceptions(bool $throwExceptions): void
    {
        self::$throwExceptions = $throwExceptions;
    }

    /**
     * @return bool
     */
    public static function isThrowExceptions(): bool
    {
        return self::$throwExceptions;
    }

    /**
     * @var string|null $customValidatorRuleNamespace
     */
    private static $customValidatorRuleNamespace = null;

    /**
     * @param string $customValidatorRuleNamespace
     */
    public static function setCustomValidatorRuleNamespace(string $customValidatorRuleNamespace): void
    {
        self::$customValidatorRuleNamespace = $customValidatorRuleNamespace;
    }

    /**
     * @return string|null
     */
    public static function getCustomValidatorRuleNamespace(): ?string
    {
        return self::$customValidatorRuleNamespace;
    }

    /* InputValidator Object */

    /**
     * @var string|Closure|null
     */
    protected $rewriteCallbackOnFailure = null;
    /**
     * @var InputValidatorItem[]
     */
    protected $items = array();
    /**
     * @var bool|null
     */
    protected $valid = null;
    /**
     * @var InputValidatorItem[]|null
     */
    protected $errors = null;

    /**
     * Creates a new InputValidator
     * @return InputValidator
     */
    public static function make(): InputValidator
    {
        return new InputValidator();
    }

    public function __construct()
    {
    }

    /**
     * @param $settings
     * @return self
     */
    public function parseSettings($settings): self
    {
        if (is_array($settings)) {
            foreach ($settings as $key => $item) {
                if ($item instanceof InputValidatorItem)
                    $this->add($item);
                else if ((is_string($item) || is_array($item) || $item instanceof InputValidatorRule) && is_string($key)) {
                    $itemObject = InputValidatorItem::make($key);
                    $itemObject->parseSettings($item);
                    $this->add($itemObject);
                }
            }
        }
        return $this;
    }

    /**
     * @param string|Closure $callback
     * @return self
     */
    protected function rewriteCallbackOnFailure(string $callback): self
    {
        $this->rewriteCallbackOnFailure = $callback;
        return $this;
    }

    /**
     * @return InputValidatorItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param InputValidatorItem $validator
     * @return self
     */
    public function add(InputValidatorItem $validator): self
    {
        $this->items[] = $validator;
        return $this;
    }

    /**
     * Set all Input Items that should be validated
     * @param InputValidatorItem[] $items
     * @return self
     */
    public function items(array $items): self
    {
        $this->items = $items;
        return $this;
    }

    /**
     * Validate all Items
     * @param Request $request
     * @return bool
     */
    public function validate(Request $request): bool
    {
        $this->errors = array();
        $inputHandler = $request->getInputHandler();
        foreach ($this->getItems() as $item) {
            $inputItem = $inputHandler->find($item->getKey());
            $callback = $item->validate($inputItem);
            if (!$callback)
                $this->errors[] = $item;
        }
        $this->valid = empty($this->errors);
        if ($this->fails()) {
            if ($this->rewriteCallbackOnFailure !== null)
                $request->setRewriteCallback($this->rewriteCallbackOnFailure);
            if(self::isThrowExceptions()){
                throw new InputValidationException('Failed to validate inputs', $this->getErrors());
            }
        }
        return $this->passes();
    }

    /**
     * @param array $data
     * @return bool
     * @throws InputValidationException
     * @throws InputsNotValidatedException
     */
    public function validateData(array $data): bool
    {
        $this->errors = array();
        foreach ($this->getItems() as $item) {
            $inputItem = new InputItem($item->getKey(), $data[$item->getKey()] ?? null);
            $callback = $item->validate($inputItem);
            if (!$callback)
                $this->errors[] = $item;
        }
        $this->valid = empty($this->errors);
        if ($this->fails()) {
            if(self::isThrowExceptions()){
                throw new InputValidationException('Failed to validate inputs', $this->getErrors());
            }
        }
        return $this->passes();
    }

    /**
     * Check if inputs passed validation
     * @return bool
     */
    public function passes(): bool
    {
        if ($this->valid === null)
            throw new InputsNotValidatedException();
        return $this->valid;
    }

    /**
     * Check if inputs failed valida
     * @return bool
     */
    public function fails(): bool
    {
        if ($this->valid === null)
            throw new InputsNotValidatedException();
        return !$this->valid;
    }

    /**
     * @return InputValidatorItem[]|null
     */
    public function getErrors(): ?array
    {
        if ($this->valid === null)
            throw new InputsNotValidatedException();
        return $this->errors;
    }

    /**
     * @param Router $router
     * @param IRoute $route
     * @return InputValidator|null
     * @since 8.0
     */
    public static function parseValidatorFromRoute(Router $router, IRoute $route): ?InputValidator{
        $routeAttributeValidator = null;
        if(InputValidator::$parseAttributes){
            $reflectionMethod = self::getReflection($router, $route);
            if($reflectionMethod !== null){
                $attributes = $reflectionMethod->getAttributes(ValidatorAttribute::class);
                if(sizeof($attributes) > 0){
                    $settings = array();
                    foreach($attributes as $attribute){
                        /* @var ValidatorAttribute $routeAttribute */
                        $routeAttribute = $attribute->newInstance();
                        $settings[$routeAttribute->getName()] = $routeAttribute->getFullValidator();
                    }
                    $routeAttributeValidator = InputValidator::make()->parseSettings($settings);
                }
            }
        }
        return $routeAttributeValidator;
    }

    /**
     * @param Router $router
     * @param IRoute|null $route
     * @return ReflectionFunction|ReflectionMethod|null
     */
    public static function getReflection(Router $router, ?IRoute $route = null){
        $reflectionMethod = null;
        if($route === null){
            $route = SimpleRouter::request()->getLoadedRoute();
        }
        $callback = $route->getCallback();
        try{
            if($callback !== null){
                if(is_callable($callback) === true){
                    /* Load class from type hinting */
                    if(is_array($callback) === true && isset($callback[0], $callback[1]) === true){
                        $callback[0] = $router->getClassLoader()->loadClass($callback[0]);
                    }

                    /* When the callback is a function */
                    $reflectionMethod = is_array($callback) ? new ReflectionMethod($callback[0], $callback[1]) : ($callback instanceof Closure ? new ReflectionFunction($callback) : new ReflectionMethod($callback));
                } else {

                    $controller = $route->getClass();
                    $method = $route->getMethod();

                    $namespace = $route->getNamespace();
                    $className = ($namespace !== null && $controller[0] !== '\\') ? $namespace . '\\' . $controller : $controller;
                    $class = $router->getClassLoader()->loadClass($className);

                    if($method === null){
                        $method = '__invoke';
                    }

                    if(method_exists($class, $method) !== false){
                        $reflectionMethod = new ReflectionMethod($class, $method);
                    }
                }
            }
        }catch(ReflectionException $e){}
        return $reflectionMethod;
    }
}