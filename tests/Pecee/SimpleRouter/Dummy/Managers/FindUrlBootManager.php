<?php

class FindUrlBootManager implements \Pecee\SimpleRouter\IRouterBootManager
{

    /**
     * @var mixed $result
     */
    protected mixed $result;

    /**
     * @param mixed $result
     */
    public function __construct(mixed &$result)
    {
        $this->result = &$result;
    }

    /**
     * Called when router loads it's routes
     *
     * @param \Pecee\SimpleRouter\Router $router
     * @param \Pecee\Http\Request $request
     */
    public function boot(\Pecee\SimpleRouter\Router $router, \Pecee\Http\Request $request): void
    {
        $contact = $router->findRoute('contact');

        if($contact !== null) {
            $this->result = true;
        }
    }
}