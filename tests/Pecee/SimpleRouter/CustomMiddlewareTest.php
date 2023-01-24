<?php

use Pecee\Http\Request;

require_once 'Dummy/DummyController.php';
require_once 'Dummy/Middleware/IpRestrictMiddleware.php';
require_once 'Dummy/Middleware/AuthMiddleware.php';

class CustomMiddlewareTest extends \PHPUnit\Framework\TestCase
{

    public function testAuthMiddlewareWithEchoCallback(){
        TestRouter::resetRouter();

        AuthMiddleware::$auth = true;
        TestRouter::group(['prefix' => '/','middleware' => AuthMiddleware::class], function () {
            TestRouter::get('/home', 'DummyController@method3');
        });

        TestRouter::get('/login', 'DummyController@login');


        $out = TestRouter::debugOutput('/home');
        $this->assertEquals('login', $out);

        TestRouter::resetRouter();

        AuthMiddleware::$auth = false;
        TestRouter::group(['prefix' => '/','middleware' => AuthMiddleware::class], function () {
            TestRouter::get('/home', 'DummyController@method3');
        });

        TestRouter::get('/login', 'DummyController@login');
        $out = TestRouter::debugOutput('/home');
        $this->assertEquals('method3', $out);
    }

    public function testIpBlock() {

        $this->expectException(\Pecee\SimpleRouter\Exceptions\HttpException::class);

        global $_SERVER;

        // Test exact ip
        TestRouter::resetRouter();

        $_SERVER['remote-addr'] = '5.5.5.5';

        $request = new Request(false);
        $request->setMethod('get');
        TestRouter::setRequest($request);

        TestRouter::group(['middleware' => IpRestrictMiddleware::class], function() {
            TestRouter::get('/fail', 'DummyController@method1');
        });

        TestRouter::debug('/fail');

        // Test ip-range


        TestRouter::resetRouter();

        $_SERVER['remote-addr'] = '8.8.4.4';

        $request = new Request(false);
        $request->setMethod('get');
        TestRouter::setRequest($request);

        TestRouter::group(['middleware' => IpRestrictMiddleware::class], function() {
            TestRouter::get('/fail', 'DummyController@method1');
        });

        TestRouter::debug('/fail');

    }

    public function testIpSuccess() {

        global $_SERVER;

        TestRouter::resetRouter();
        // Test ip that is not blocked

        $_SERVER['remote-addr'] = '6.6.6.6';

        TestRouter::group(['middleware' => IpRestrictMiddleware::class], function() {
            TestRouter::get('/success', 'DummyController@method1');
        });

        TestRouter::debug('/success');

        // Test ip in whitelist
        TestRouter::resetRouter();

        $_SERVER['remote-addr'] = '8.8.2.2';

        TestRouter::group(['middleware' => IpRestrictMiddleware::class], function() {
            TestRouter::get('/success', 'DummyController@method1');
        });

        TestRouter::debug('/success');

        $this->assertTrue(true);

    }

}