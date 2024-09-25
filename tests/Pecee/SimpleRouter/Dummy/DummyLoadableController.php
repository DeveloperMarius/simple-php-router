<?php

namespace Dummy;

use Pecee\Http\Input\Attributes\Route;
use Pecee\Http\Input\Attributes\RouteGroup;
use Pecee\Http\Input\Attributes\ValidatorAttribute;

#[RouteGroup('/group')]
class DummyLoadableController{

    #[Route(Route::GET, '/test2/endpoint')]
    public function method1(){
        return 'method1';
    }

    #[Route(Route::POST, '/test/url'), ValidatorAttribute('fullname', 'string', 'min:5|max:20'), ValidatorAttribute('company', 'string')]
    public function method2(){
        return 'method2';
    }
}