<?php

namespace Pecee\Http\Input\Attributes;

use Attribute;
use JetBrains\PhpStorm\ExpectedValues;

/**
 *
 *
 * @since 8.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Route
{

    public const
        GET = 'get',
        POST = 'post',
        PUT = 'put',
        PATCH = 'patch',
        DELETE = 'delete',
        OPTIONS = 'options';

    /**
     * @param string $method
     * @param string $route
     */
    public function __construct(
        #[ExpectedValues([Route::GET, Route::POST, Route::PUT, Route::PATCH, Route::DELETE, Route::OPTIONS])] private string $method,
        private string $route
    ) {}

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getRoute(): string
    {
        return $this->route;
    }
}