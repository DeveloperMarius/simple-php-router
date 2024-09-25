<?php

namespace Pecee\Http\Input\Attributes;

use Attribute;

/**
 *
 *
 * @since 8.0
 */
#[Attribute(Attribute::TARGET_CLASS)]
class RouteGroup
{

    /**
     * @param string $route
     * @param array|null $settings
     */
    public function __construct(
        private string $route,
        private ?array $settings = null
    ){}

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
    public function getSettings(): ?array{
        return $this->settings;
    }
}