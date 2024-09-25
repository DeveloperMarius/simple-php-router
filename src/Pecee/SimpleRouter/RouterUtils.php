<?php

namespace Pecee\SimpleRouter;

use InvalidArgumentException;
use Pecee\Http\Input\InputHandler;
use Pecee\SimpleRouter\SimpleRouter as Router;
use Pecee\Http\Url;
use Pecee\Http\Response;
use Pecee\Http\Request;

trait RouterUtils
{

    /**
     * Get url for a route by using either name/alias, class or method name.
     *
     * The name parameter supports the following values:
     * - Route name
     * - Controller/resource name (with or without method)
     * - Controller class name
     *
     * When searching for controller/resource by name, you can use this syntax "route.name@method".
     * You can also use the same syntax when searching for a specific controller-class "MyController@home".
     * If no arguments is specified, it will return the url for the current loaded route.
     *
     * @param string|null $name
     * @param string|array|null $parameters
     * @param array|null $getParams
     * @return Url
     * @throws InvalidArgumentException
     */
    public function url(?string $name = null, string|array|null $parameters = null, ?array $getParams = null): Url
    {
        return Router::getUrl($name, $parameters, $getParams);
    }

    /**
     * @return Response
     */
    public function response(): Response
    {
        return Router::response();
    }

    /**
     * @return Request
     */
    public function request(): Request
    {
        return Router::request();
    }

    /**
     * Get input class
     * @param string|null $index Parameter index name
     * @param string|mixed|null $defaultValue Default return value
     * @param array ...$methods Default methods
     * @return InputHandler|array|string|null
     */
    public function input(?string $index = null, mixed $defaultValue = null, ...$methods): mixed
    {
        if($index !== null){
            return $this->request()->getInputHandler()->value($index, $defaultValue, ...$methods);
        }

        return $this->request()->getInputHandler();
    }

    /**
     * @param string $url
     * @param int|null $code
     */
    public function redirect(string $url, ?int $code = null): void
    {
        if($code !== null){
            $this->response()->httpCode($code);
        }

        $this->response()->redirect($url);
    }

    /**
     * Get current csrf-token
     * @return string|null
     */
    public function csrf_token(): ?string
    {
        $baseVerifier = Router::router()->getCsrfVerifier();

        return $baseVerifier?->getTokenProvider()->getToken();
    }
}