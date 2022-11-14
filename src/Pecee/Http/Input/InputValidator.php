<?php

namespace Pecee\Http\Input;

use Closure;
use Pecee\Http\Input\Attributes\ValidatorAttribute;
use Pecee\Http\Input\Exceptions\InputValidationException;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Route\IRoute;
use Pecee\SimpleRouter\Router;
use Pecee\SimpleRouter\SimpleRouter;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use Somnambulist\Components\Validation\Factory;
use Somnambulist\Components\Validation\Validation;

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
     * @var Factory|null $factory
     */
    private static ?Factory $factory = null;

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
     * @return Factory
     */
    public static function getFactory(): Factory
    {
        if(self::$factory === null){
            self::$factory = new Factory();

        }
        return self::$factory;
    }

    /* InputValidator Object */

    /**
     * @var string|Closure|null
     */
    protected string|Closure|null $rewriteCallbackOnFailure = null;
    /**
     * @var array $rules
     */
    protected array $rules;

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
        $this->rules = array();
    }

    /**
     * @param string $key
     * @param string $validation
     * @return self
     */
    public function addRule(string $key, string $validation): self
    {
        $this->rules[$key] = $validation;
        return $this;
    }

    /**
     * @param array $rules
     * @return self
     */
    public function setRules(array $rules): self
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @param string|Closure $callback
     * @return self
     */
    protected function rewriteCallbackOnFailure(string|Closure $callback): self
    {
        $this->rewriteCallbackOnFailure = $callback;
        return $this;
    }

    /**
     * @param InputHandler $inputHandler
     * @return Validation
     * @throws InputValidationException
     */
    public function validateInputs(InputHandler $inputHandler): Validation
    {
        return $this->validateItems($inputHandler);
    }

    /**
     * @param Request $request
     * @return Validation
     * @throws InputValidationException
     */
    public function validateRequest(Request $request): Validation
    {
        return $this->validateItems($request->getInputHandler());
    }

    /**
     * @param InputHandler $inputHandler
     * @return Validation
     * @throws InputValidationException
     */
    private function validateItems(InputHandler $inputHandler): Validation
    {
        $validation = self::getFactory()->validate($inputHandler->values(array_keys($this->getRules())), $this->getRules());
        if ($validation->fails() && self::isThrowExceptions()) {
            throw new InputValidationException('Failed to validate inputs', $validation);
        }
        return $validation;
    }

    /**
     * @param InputItem|InputFile $inputItem
     * @param string|array $rules
     * @return Validation
     * @throws InputValidationException
     */
    public function validateItem(InputItem|InputFile $inputItem, string|array $rules): Validation
    {
        $validation = self::getFactory()->validate(array(
            $inputItem->getName() => $inputItem->getValue()
        ), array(
            $inputItem->getName() => $rules
        ));
        if ($validation->fails() && self::isThrowExceptions()) {
            throw new InputValidationException('Failed to validate input', $validation);
        }
        return $validation;
    }

    /**
     * @param array $data
     * @return Validation
     * @throws InputValidationException
     */
    public function validateData(array $data): Validation
    {
        $validation = self::getFactory()->validate($data, $this->getRules());
        if ($validation->fails() && self::isThrowExceptions()) {
            throw new InputValidationException('Failed to validate inputs', $validation);
        }
        return $validation;
    }

    /**
     * @param Router $router
     * @param IRoute $route
     * @return InputValidator|null
     * @since 8.0
     */
    public static function parseValidatorFromRoute(Router $router, IRoute $route): ?InputValidator
    {
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
                        if($routeAttribute->getName() !== null)
                            $settings[$routeAttribute->getName()] = $routeAttribute->getFullValidator();
                    }
                    $routeAttributeValidator = InputValidator::make()->setRules($settings);
                }
            }
        }
        return $routeAttributeValidator;
    }

    /**
     * @param Router $router
     * @param IRoute $route
     * @return InputValidator|null
     * @since 8.0
     */
    public static function parseValidatorFromRouteParameters(Router $router, IRoute $route): ?InputValidator
    {
        $parsedData = $route->getParameters();
        $routeAttributeValidator = null;
        if(InputValidator::$parseAttributes){
            $reflectionMethod = self::getReflection($router, $route);
            if($reflectionMethod !== null){
                $parameters = $reflectionMethod->getParameters();
                if(sizeof($parameters) > 0){
                    $settings = array();
                    foreach($parameters as $parameter){
                        $attributes = $parameter->getAttributes(ValidatorAttribute::class);
                        if(sizeof($attributes) > 0){
                            /* @var ValidatorAttribute $routeAttribute */
                            $routeAttribute = $attributes[0]->newInstance();
                            if($routeAttribute->getName() === null)
                                $routeAttribute->setName($parameter->getName());
                            if($routeAttribute->getType() === null && $parameter->getType() !== null){
                                $routeAttribute->setType($parameter->getType()->getName());
                                if($parameter->getType()->allowsNull())
                                    $routeAttribute->addValidator('nullable');
                            }
                            $settings[$routeAttribute->getName()] = $routeAttribute->getFullValidator();
                            $parsedData[$routeAttribute->getName()] = (new InputParser(new InputItem($routeAttribute->getName(), $parsedData[$routeAttribute->getName()])))->parseFromSetting($routeAttribute->getType())->getValue();
                        }
                    }
                    $routeAttributeValidator = InputValidator::make()->setRules($settings);
                }
            }
        }
        $route->setOriginalParameters($parsedData);
        return $routeAttributeValidator;
    }

    /**
     * @param Router $router
     * @param IRoute|null $route
     * @return ReflectionFunction|ReflectionMethod|null
     */
    public static function getReflection(Router $router, ?IRoute $route = null)
    {
        $reflectionMethod = null;
        if($route === null){
            $route = SimpleRouter::router()->getCurrentProcessingRoute();
        }
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