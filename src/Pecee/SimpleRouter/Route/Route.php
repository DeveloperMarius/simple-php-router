<?php

namespace Pecee\SimpleRouter\Route;

use Closure;
use Pecee\Http\Input\InputValidator;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\ClassNotFoundHttpException;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\Router;

abstract class Route implements IRoute
{

    public static bool $parameterReverseOrder = true;

    protected const PARAMETERS_REGEX_FORMAT = '%s([\w]+)(\%s?)%s';
    protected const PARAMETERS_DEFAULT_REGEX = '[\w-]+';

    /**
     * If enabled parameters containing null-value
     * will not be passed along to the callback.
     *
     * @var bool
     */
    protected bool $filterEmptyParams = true;

    /**
     * Default regular expression used for parsing parameters.
     * @var string|null
     */
    protected ?string $defaultParameterRegex = null;
    protected string $paramModifiers = '{}';
    protected string $paramOptionalSymbol = '?';
    protected string $urlRegex = '/^%s\/?$/u';
    protected ?IGroupRoute $group = null;
    protected ?IRoute $parent = null;
    /**
     * @var string|callable|null
     */
    protected $callback;
    protected ?string $defaultNamespace = null;

    /* Default options */
    protected ?string $namespace = null;
    protected array $requestMethods = [];
    protected array $where = [];
    protected array $parameters = [];
    protected array $originalParameters = [];
    protected array $middlewares = [];
    /**
     * @var InputValidator|array|null
     */
    protected InputValidator|array|null $inputValidator = null;

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
        $router->debug('Starting rendering route "%s"', get_class($this));

        $callback = $this->getCallback();

        if ($callback === null) {
            return null;
        }

        $router->debug('Parsing parameters');

        $parameters = $this->getParameters();

        $router->debug('Finished parsing parameters');

        /* Filter parameters with null-value */
        if ($this->filterEmptyParams === true) {
            $parameters = array_filter($parameters, static function ($var): bool {
                return ($var !== null);
            });
        }

        /* Render callback function */
        if (is_callable($callback) === true) {
            $router->debug('Executing callback');

            /* Load class from type hinting */
            if (is_array($callback) === true && isset($callback[0], $callback[1]) === true) {
                $callback[0] = $router->getClassLoader()->loadClass($callback[0]);
            }

            /* When the callback is a function */

            return $router->getClassLoader()->loadClosure($callback, $parameters);
        }

        $controller = $this->getClass();
        $method = $this->getMethod();

        $namespace = $this->getNamespace();
        $className = ($namespace !== null && $controller[0] !== '\\') ? $namespace . '\\' . $controller : $controller;

        $router->debug('Loading class %s', $className);
        $class = $router->getClassLoader()->loadClass($className);

        if ($method === null) {
            $method = '__invoke';
        }

        if (method_exists($class, $method) === false) {
            throw new ClassNotFoundHttpException($className, $method, sprintf('Method "%s" does not exist in class "%s"', $method, $className), 404, null);
        }

        $router->debug('Executing callback %s -> %s', $className, $method);

        return $router->getClassLoader()->loadClassMethod($class, $method, $parameters);
    }

    protected function parseParameters(string $route, string $url, string $parameterRegex = null): ?array
    {
        $regex = (!str_contains($route, $this->paramModifiers[0])) ? null :
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

                if ($key < count($parameters[1])) {

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

            $groupParameters = $this->getGroup() !== null ? $this->getGroup()->getParameters() : [];

            $lastParams = [];

            /* Only take matched parameters with name */
            foreach ((array)$parameters[1] as $name) {

                // Ignore parent parameters
                if (isset($groupParameters[$name]) === true) {
                    $lastParams[$name] = $matches[$name];
                    continue;
                }

                $values[$name] = (isset($matches[$name]) === true && $matches[$name] !== '') ? $matches[$name] : null;
            }

            $values = self::$parameterReverseOrder ? array_merge($values, $lastParams) : array_merge($lastParams, $values);
        }

        $this->originalParameters = $values;

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
        if (is_string($this->callback) === true && str_contains($this->callback, '@')) {
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
     * @param array|Closure|string $callback
     * @return static
     */
    public function setCallback(array|Closure|string $callback): IRoute
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @return string|callable|array|null
     */
    public function getCallback(): callable|array|string|null
    {
        return $this->callback;
    }

    public function getMethod(): ?string
    {
        if (is_array($this->callback) === true && count($this->callback) > 1) {
            return $this->callback[1];
        }

        if (is_string($this->callback) === true && str_contains($this->callback, '@')) {
            $tmp = explode('@', $this->callback);

            return $tmp[1];
        }

        return null;
    }

    public function getClass(): ?string
    {
        if (is_array($this->callback) === true && count($this->callback) > 0) {
            return $this->callback[0];
        }

        if (is_string($this->callback) === true && str_contains($this->callback, '@')) {
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
        // Do not set namespace when class-hinting is used
        if (is_array($this->callback) === true) {
            return $this;
        }

        $ns = $this->getNamespace();

        if ($ns !== null) {
            // Don't overwrite namespaces that starts with \
            if ($ns[0] !== '\\') {
                $namespace .= '\\' . $ns;
            } else {
                $namespace = $ns;
            }
        }

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

        if (count($this->requestMethods) !== 0) {
            $values['method'] = $this->requestMethods;
        }

        if (count($this->where) !== 0) {
            $values['where'] = $this->where;
        }

        if (count($this->middlewares) !== 0) {
            $values['middleware'] = $this->middlewares;
        }

        if ($this->defaultParameterRegex !== null) {
            $values['defaultParameterRegex'] = $this->defaultParameterRegex;
        }

        if ($this->getInputValidator() !== null) {
            $values['validator'] = $this->getInputValidator();
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
        if (isset($settings['namespace']) === true) {
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

        if (isset($settings['validator']) === true) {
            $this->validateInputs($settings['validator']);
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
    public function where(array $options): static
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

        if (count($this->originalParameters) !== 0) {
            $parameters = $this->originalParameters;
        }

        return self::$parameterReverseOrder ? array_merge($parameters, $this->parameters) : array_merge($this->parameters, $parameters);
    }

    /**
     * Get parameters
     *
     * @param array $parameters
     * @return static
     */
    public function setParameters(array $parameters): IRoute
    {
        $this->parameters = array_merge($this->parameters, $parameters);

        return $this;
    }

    /**
     * Set original parameters
     *
     * @param array $originalParameters
     * @return static
     */
    public function setOriginalParameters(array $originalParameters): IRoute
    {
        $this->originalParameters = $originalParameters;

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

    /**
     * Get InputValidator if one is present
     * @return InputValidator|array|null
     */
    public function getInputValidator(): InputValidator|array|null
    {
        return $this->inputValidator;
    }

    /**
     * @param array|InputValidator $validator
     * @return void
     */
    public function validateInputs(array|InputValidator $validator): void
    {
        $this->inputValidator = $validator;
    }

}