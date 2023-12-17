<?php

namespace Pecee\Http\Input\Attributes;

use Attribute;
use JetBrains\PhpStorm\ExpectedValues;
use Pecee\Http\Request;

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
     * @param array|null $settings
     * @param string|null $title
     * @param string|null $description
     * @param string $content_type
     */
    public function __construct(
        #[ExpectedValues([Route::GET, Route::POST, Route::PUT, Route::PATCH, Route::DELETE, Route::OPTIONS])] private string $method,
        private string $route,
        private ?array $settings = null,
        private ?string $title = null,
        private ?string $description = null,
        #[ExpectedValues([Request::CONTENT_TYPE_JSON, Request::CONTENT_TYPE_FORM_DATA, Request::CONTENT_TYPE_X_FORM_ENCODED, 'text/plain'])] private string $content_type = Request::CONTENT_TYPE_JSON
    ){}

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

    /**
     * @return array|null
     */
    public function getSettings(): ?array
    {
        return $this->settings;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->content_type;
    }
}