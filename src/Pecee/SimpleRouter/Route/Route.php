<?php

namespace Pecee\SimpleRouter\Route;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\ClassNotFoundHttpException;
use Pecee\SimpleRouter\Exceptions\MissingParametersHttpException;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\Router;
use Pecee\SimpleRouter\SimpleRouter;

abstract class Route implements IRoute
{
    protected const PARAMETERS_REGEX_FORMAT = '%s([\w]+)(\%s?)%s';
    protected const PARAMETERS_DEFAULT_REGEX = '[\w-]+';

    /**
     * If enabled parameters containing null-value
     * will not be passed along to the callback.
     *
     * @var bool
     */
    protected $filterEmptyParams = true;

    /**
     * Default regular expression used for parsing parameters.
     * @var string|null
     */
    protected $defaultParameterRegex;
    protected $paramModifiers = '{}';
    protected $paramOptionalSymbol = '?';
    protected $urlRegex = '/^%s\/?$/u';
    protected $group;
    protected $parent;
    protected $callback;
    protected $defaultNamespace;

    /* Default options */
    protected $namespace;
    protected $requestMethods = [];
    protected $where = [];
    protected $parameters = [];
    protected $originalParameters = [];
    protected $middlewares = [];

    /**
     * Render route
     *
     * @param Request $request
     * @param Router $router
     * @return string|null
     * @throws NotFoundHttpException
     */
    public function renderRoute(Request $request, Router $router): ?string
    {
        $router->debug('Starting rendering route "%s"', \get_class($this));

        $callback = $this->getCallback();

        if ($callback === null) {
            return null;
        }

        $router->debug('Parsing parameters');

        $parameters = $this->getParameters();

        $router->debug('Finished parsing parameters');

        /* Filter parameters with null-value */
        if ($this->filterEmptyParams === true) {
            $parameters = array_filter($parameters, static function ($var) {
                return ($var !== null);
            });
        }

        /* Render callback function */
        if (\is_callable($callback) === true) {
            $router->debug('Executing callback');

            /* When the callback is a function */
            $parameters = $this->checkParameters($callback, null, $parameters);
            return $router->getClassLoader()->loadClosure($callback, $parameters);
        }

        $controller = $this->getClass();
        $method = $this->getMethod();

        $namespace = $this->getNamespace();
        $className = ($namespace !== null && $controller[0] !== '\\') ? $namespace . '\\' . $controller : $controller;

        $router->debug('Loading class %s', $className);
        $class = $router->getClassLoader()->loadClass($className);

        if ($method === null) {
            $controller[1] = '__invoke';
        }

        if (method_exists($class, $method) === false) {
            throw new ClassNotFoundHttpException($className, $method, sprintf('Method "%s" does not exist in class "%s"', $method, $className), 404, null);
        }

        $router->debug('Executing callback');

        $parameters = $this->checkParameters($class, $method, $parameters);
        return \call_user_func_array([$class, $method], $parameters);
    }

    /**
     * @param $classOrCallable
     * @param null $method
     * @param array $parameters
     * @return array
     * @throws MissingParametersHttpException
     */
    protected function checkParameters($classOrCallable, $method = null, $parameters = array()): array{
        try{
            if($method === null)
                $reflection = new \ReflectionFunction($classOrCallable);
            else
                $reflection = new \ReflectionMethod($classOrCallable, $method);
            //if($reflection->getNumberOfRequiredParameters() > count($parameters)){
            $missing = array();
            $functionParameters = array();
            $missingParameters = array();
            $removeParameters = array();
            foreach($reflection->getParameters() as $parameter){
                $functionParameters[$parameter->getName()] = $parameter->isOptional();
                if(!$parameter->isOptional() && !in_array($parameter->getName(), array_keys($parameters))){
                    $missingParameters[] = $parameter->getName();
                }
            }
            foreach(array_keys($parameters) as $parameter){
                if(!in_array($parameter, array_keys($functionParameters))){
                    $removeParameters[] = $parameter;
                }
            }
            if(empty($missingParameters) && empty($removeParameters)){
                return $parameters;
            }
            if(!SimpleRouter::router()->isProcessParametersSafe()){
                if($method === null){
                    throw new MissingParametersHttpException(null, null, $missing);
                }else{
                    throw new MissingParametersHttpException($classOrCallable, $method, $missing);
                }
            }
            foreach($missingParameters as $parameter){
                $parameters[$parameter] = null;//TODO match type?
            }
            foreach($removeParameters as $parameter){
                unset($parameters[$parameter]);
            }
        } catch(\ReflectionException $e) {
        }
        return $parameters;
    }

    protected function parseParameters($route, $url, $parameterRegex = null): ?array
    {
        $regex = (strpos($route, $this->paramModifiers[0]) === false) ? null :
            sprintf
            (
                static::PARAMETERS_REGEX_FORMAT,
                $this->paramModifiers[0],
                $this->paramOptionalSymbol,
                $this->paramModifiers[1]
            );

        // Ensures that host names/domains will work with parameters
        $url = '/' . ltrim($url, '/');
        $urlRegex = '';
        $parameters = [];

        if ($regex === null || (bool)preg_match_all('/' . $regex . '/u', $route, $parameters) === false) {
            $urlRegex = preg_quote($route, '/');
        } else {

            foreach (preg_split('/((-?\/?){[^}]+})/', $route) as $key => $t) {

                $regex = '';

                if ($key < \count($parameters[1])) {

                    $name = $parameters[1][$key];

                    /* If custom regex is defined, use that */
                    if (isset($this->where[$name]) === true) {
                        $regex = $this->where[$name];
                    } else {
                        $regex = $parameterRegex ?? $this->defaultParameterRegex ?? static::PARAMETERS_DEFAULT_REGEX;
                    }

                    $regex = sprintf('((\/|-)(?P<%2$s>%3$s))%1$s', $parameters[2][$key], $name, $regex);
                }

                $urlRegex .= preg_quote($t, '/') . $regex;
            }
        }

        if (trim($urlRegex) === '' || (bool)preg_match(sprintf($this->urlRegex, $urlRegex), $url, $matches) === false) {
            return null;
        }

        $values = [];

        if (isset($parameters[1]) === true) {

            /* Only take matched parameters with name */
            foreach ((array)$parameters[1] as $name) {
                $values[$name] = (isset($matches[$name]) === true && $matches[$name] !== '') ? $matches[$name] : null;
            }
        }

        return $values;
    }

    /**
     * Returns callback name/identifier for the current route based on the callback.
     * Useful if you need to get a unique identifier for the loaded route, for instance
     * when using translations etc.
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        if (\is_string($this->callback) === true && strpos($this->callback, '@') !== false) {
            return $this->callback;
        }

        return 'function:' . md5($this->callback);
    }

    /**
     * Set allowed request methods
     *
     * @param array $methods
     * @return static
     */
    public function setRequestMethods(array $methods): IRoute
    {
        $this->requestMethods = $methods;

        return $this;
    }

    /**
     * Get allowed request methods
     *
     * @return array
     */
    public function getRequestMethods(): array
    {
        return $this->requestMethods;
    }

    /**
     * @return IRoute|null
     */
    public function getParent(): ?IRoute
    {
        return $this->parent;
    }

    /**
     * Get the group for the route.
     *
     * @return IGroupRoute|null
     */
    public function getGroup(): ?IGroupRoute
    {
        return $this->group;
    }

    /**
     * Set group
     *
     * @param IGroupRoute $group
     * @return static
     */
    public function setGroup(IGroupRoute $group): IRoute
    {
        $this->group = $group;

        /* Add/merge parent settings with child */

        return $this->setSettings($group->toArray(), true);
    }

    /**
     * Set parent route
     *
     * @param IRoute $parent
     * @return static
     */
    public function setParent(IRoute $parent): IRoute
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Set callback
     *
     * @param string|array|\Closure $callback
     * @return static
     */
    public function setCallback($callback): IRoute
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @return string|callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    public function getMethod(): ?string
    {
        if (\is_array($this->callback) === true && \count($this->callback) > 1) {
            return $this->callback[1];
        }

        if (\is_string($this->callback) === true && strpos($this->callback, '@') !== false) {
            $tmp = explode('@', $this->callback);

            return $tmp[1];
        }

        return null;
    }

    public function getClass(): ?string
    {
        if (\is_array($this->callback) === true && \count($this->callback) > 0) {
            return $this->callback[0];
        }

        if (\is_string($this->callback) === true && strpos($this->callback, '@') !== false) {
            $tmp = explode('@', $this->callback);

            return $tmp[0];
        }

        return null;
    }

    public function setMethod(string $method): IRoute
    {
        $this->callback = [$this->getClass(), $method];

        return $this;
    }

    public function setClass(string $class): IRoute
    {
        $this->callback = [$class, $this->getMethod()];

        return $this;
    }

    /**
     * @param string $namespace
     * @return static
     */
    public function setNamespace(string $namespace): IRoute
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @param string $namespace
     * @return static
     */
    public function setDefaultNamespace(string $namespace): IRoute
    {
        $this->defaultNamespace = $namespace;

        return $this;
    }

    public function getDefaultNamespace(): ?string
    {
        return $this->defaultNamespace;
    }

    /**
     * @return string|null
     */
    public function getNamespace(): ?string
    {
        return $this->namespace ?? $this->defaultNamespace;
    }

    /**
     * Export route settings to array so they can be merged with another route.
     *
     * @return array
     */
    public function toArray(): array
    {
        $values = [];

        if ($this->namespace !== null) {
            $values['namespace'] = $this->namespace;
        }

        if (\count($this->requestMethods) !== 0) {
            $values['method'] = $this->requestMethods;
        }

        if (\count($this->where) !== 0) {
            $values['where'] = $this->where;
        }

        if (\count($this->middlewares) !== 0) {
            $values['middleware'] = $this->middlewares;
        }

        if ($this->defaultParameterRegex !== null) {
            $values['defaultParameterRegex'] = $this->defaultParameterRegex;
        }

        return $values;
    }

    /**
     * Merge with information from another route.
     *
     * @param array $settings
     * @param bool $merge
     * @return static
     */
    public function setSettings(array $settings, bool $merge = false): IRoute
    {
        if ($this->namespace === null && isset($settings['namespace']) === true) {
            $this->setNamespace($settings['namespace']);
        }

        if (isset($settings['method']) === true) {
            $this->setRequestMethods(array_merge($this->requestMethods, (array)$settings['method']));
        }

        if (isset($settings['where']) === true) {
            $this->setWhere(array_merge($this->where, (array)$settings['where']));
        }

        if (isset($settings['parameters']) === true) {
            $this->setParameters(array_merge($this->parameters, (array)$settings['parameters']));
        }

        // Push middleware if multiple
        if (isset($settings['middleware']) === true) {
            $this->setMiddlewares(array_merge((array)$settings['middleware'], $this->middlewares));
        }

        if (isset($settings['defaultParameterRegex']) === true) {
            $this->setDefaultParameterRegex($settings['defaultParameterRegex']);
        }

        return $this;
    }

    /**
     * Get parameter names.
     *
     * @return array
     */
    public function getWhere(): array
    {
        return $this->where;
    }

    /**
     * Set parameter names.
     *
     * @param array $options
     * @return static
     */
    public function setWhere(array $options): IRoute
    {
        $this->where = $options;

        return $this;
    }

    /**
     * Add regular expression parameter match.
     * Alias for LoadableRoute::where()
     *
     * @param array $options
     * @return static
     * @see LoadableRoute::where()
     */
    public function where(array $options)
    {
        return $this->setWhere($options);
    }

    /**
     * Get parameters
     *
     * @return array
     */
    public function getParameters(): array
    {
        /* Sort the parameters after the user-defined param order, if any */
        $parameters = [];

        if (\count($this->originalParameters) !== 0) {
            $parameters = $this->originalParameters;
        }

        return array_merge($parameters, $this->parameters);
    }

    /**
     * Get parameters
     *
     * @param array $parameters
     * @return static
     */
    public function setParameters(array $parameters): IRoute
    {
        /*
         * If this is the first time setting parameters we store them so we
         * later can organize the array, in case somebody tried to sort the array.
         */
        if (\count($parameters) !== 0 && \count($this->originalParameters) === 0) {
            $this->originalParameters = $parameters;
        }

        $this->parameters = array_merge($this->parameters, $parameters);

        return $this;
    }

    /**
     * Add middleware class-name
     *
     * @param string $middleware
     * @return static
     * @deprecated This method is deprecated and will be removed in the near future.
     */
    public function setMiddleware(string $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * Add middleware class-name
     *
     * @param string $middleware
     * @return static
     */
    public function addMiddleware(string $middleware): IRoute
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * Set middlewares array
     *
     * @param array $middlewares
     * @return static
     */
    public function setMiddlewares(array $middlewares): IRoute
    {
        $this->middlewares = $middlewares;

        return $this;
    }

    /**
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Set default regular expression used when matching parameters.
     * This is used when no custom parameter regex is found.
     *
     * @param string $regex
     * @return static
     */
    public function setDefaultParameterRegex(string $regex): self
    {
        $this->defaultParameterRegex = $regex;

        return $this;
    }

    /**
     * Get default regular expression used when matching parameters.
     *
     * @return string
     */
    public function getDefaultParameterRegex(): string
    {
        return $this->defaultParameterRegex;
    }

    /**
     * If enabled parameters containing null-value will not be passed along to the callback.
     *
     * @param bool $enabled
     * @return static $this
     */
    public function setFilterEmptyParams(bool $enabled): IRoute
    {
        $this->filterEmptyParams = $enabled;
        return $this;
    }

    /**
     * Status if filtering of empty params is enabled or disabled
     * @return bool
     */
    public function getFilterEmptyParams(): bool
    {
        return $this->filterEmptyParams;
    }

}