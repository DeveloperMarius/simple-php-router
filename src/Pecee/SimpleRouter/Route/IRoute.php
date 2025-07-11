<?php

namespace Pecee\SimpleRouter\Route;

use Closure;
use Pecee\Http\Input\InputValidator;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\Router;

interface IRoute
{
    /**
     * Method called to check if a domain matches
     *
     * @param string $url
     * @param Request $request
     * @return bool
     */
    public function matchRoute(string $url, Request $request): bool;

    /**
     * Called when route is matched.
     * Returns class to be rendered.
     *
     * @param Request $request
     * @param Router $router
     * @return string
     * @throws NotFoundHttpException
     */
    public function renderRoute(Request $request, Router $router): ?string;

    /**
     * Returns callback name/identifier for the current route based on the callback.
     * Useful if you need to get a unique identifier for the loaded route, for instance
     * when using translations etc.
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Set allowed request methods
     *
     * @param array $methods
     * @return static
     */
    public function setRequestMethods(array $methods): self;

    /**
     * Get allowed request methods
     *
     * @return array
     */
    public function getRequestMethods(): array;

    /**
     * @return IRoute|null
     */
    public function getParent(): ?IRoute;

    /**
     * Get the group for the route.
     *
     * @return IGroupRoute|null
     */
    public function getGroup(): ?IGroupRoute;

    /**
     * Set group
     *
     * @param IGroupRoute $group
     * @return static
     */
    public function setGroup(IGroupRoute $group): self;

    /**
     * Set parent route
     *
     * @param IRoute $parent
     * @return static
     */
    public function setParent(IRoute $parent): self;

    /**
     * Set callback
     *
     * @param array|Closure|string $callback
     * @return static
     */
    public function setCallback(array|Closure|string $callback): self;

    /**
     * @return string|array|callable|null
     */
    public function getCallback(): callable|array|string|null;

    /**
     * Return active method
     *
     * @return string|null
     */
    public function getMethod(): ?string;

    /**
     * Set active method
     *
     * @param string $method
     * @return static
     */
    public function setMethod(string $method): self;

    /**
     * Get class
     *
     * @return string|null
     */
    public function getClass(): ?string;

    /**
     * @param string $namespace
     * @return static
     */
    public function setNamespace(string $namespace): self;

    /**
     * @return string|null
     */
    public function getNamespace(): ?string;

    /**
     * @param string $namespace
     * @return static
     */
    public function setDefaultNamespace(string $namespace): IRoute;

    /**
     * Get default namespace
     * @return string|null
     */
    public function getDefaultNamespace(): ?string;

    /**
     * Get parameter names.
     *
     * @return array
     */
    public function getWhere(): array;

    /**
     * Set parameter names.
     *
     * @param array $options
     * @return static
     */
    public function setWhere(array $options): self;

    /**
     * Add regular expression parameter match.
     * Alias for LoadableRoute::where()
     *
     * @param array $options
     * @return static
     */
    public function where(array $options): self;

    /**
     * Sets the router name, which makes it easier to obtain the url or router at a later point.
     * Alias for LoadableRoute::setName().
     *
     * @param string|array $name
     * @return static
     * @see LoadableRoute::setName()
     */
    public function name(array|string $name): self;

    /**
     * Get parameters
     *
     * @return array
     */
    public function getParameters(): array;

    /**
     * Set parameters
     *
     * @param array $parameters
     * @return static
     */
    public function setParameters(array $parameters): self;

    /**
     * Set original parameters
     *
     * @param array $originalParameters
     * @return static
     */
    public function setOriginalParameters(array $originalParameters): self;

    /**
     * Merge with information from another route.
     *
     * @param array $settings
     * @param bool $merge
     * @return static
     */
    public function setSettings(array $settings, bool $merge = false): self;

    /**
     * Export route settings to array so they can be merged with another route.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Get middlewares array
     *
     * @return array
     */
    public function getMiddlewares(): array;

    /**
     * Set middleware class-name
     *
     * @param string $middleware
     * @return static
     */
    public function addMiddleware(string $middleware): self;

    /**
     * Set middlewares array
     *
     * @param array $middlewares
     * @return static
     */
    public function setMiddlewares(array $middlewares): self;

    /**
     * If enabled parameters containing null-value will not be passed along to the callback.
     *
     * @param bool $enabled
     * @return static $this
     */
    public function setFilterEmptyParams(bool $enabled): self;

    /**
     * Status if filtering of empty params is enabled or disabled
     * @return bool
     */
    public function getFilterEmptyParams(): bool;

    /**
     * Get InputValidator if one is present
     *
     * @return InputValidator|array|null
     */
    public function getInputValidator(): InputValidator|array|null;
}
