<?php

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;

class AuthMiddleware implements IMiddleware{

    public static bool $auth = true;

    public function handle(Request $request): void{
        if(self::$auth){
            $request->setRewriteCallback('DummyController@login');
        }

    }

}