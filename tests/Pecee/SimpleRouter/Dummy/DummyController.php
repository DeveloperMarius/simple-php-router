<?php

use Pecee\Http\Input\Attributes\Route;
use Pecee\Http\Input\Attributes\RouteAttribute;

class DummyController
{
    public function index()
    {

    }


	public function method1()
	{

	}

    public function method2()
    {

    }

    public function method3()
    {
        return 'method3';
    }

    #[
        Route(Route::POST, '/my/test/url'),
        RouteAttribute('fullname', 'string', 'min:5|max:20'),
        RouteAttribute('company', 'string')
    ]
    public function method4()
    {
        return 'method4';
    }

    public function param($params = null)
    {
        echo join(', ', func_get_args());
    }

	public function getTest()
    {
        echo 'getTest';
    }

    public function postTest()
    {
        echo 'postTest';
    }

    public function putTest()
    {
        echo 'putTest';
    }

}