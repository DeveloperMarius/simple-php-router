<?php

require_once 'Dummy/DummyController.php';
require_once 'Dummy/DummyLoadableController.php';

class RouterControllerTest extends \PHPUnit\Framework\TestCase
{

    public function testGet()
    {
        // Match normal route on alias
        TestRouter::controller('/url', 'DummyController');

        $response = TestRouter::debugOutput('/url/test', 'get');

        $this->assertEquals('getTest', $response);

    }

    public function testPost()
    {
        // Match normal route on alias
        TestRouter::controller('/url', 'DummyController');

        $response = TestRouter::debugOutput('/url/test', 'post');

        $this->assertEquals('postTest', $response);

    }

    public function testPut()
    {
        // Match normal route on alias
        TestRouter::controller('/url', 'DummyController');

        $response = TestRouter::debugOutput('/url/test', 'put');

        $this->assertEquals('putTest', $response);

    }

    public function testAutoload(){
        TestRouter::resetRouter();

        TestRouter::loadRoutes(\Dummy\DummyLoadableController::class);

        $response = TestRouter::debugOutput('/group/test2/endpoint');
        $this->assertEquals('method1', $response);
    }

    public function testAutoload2(){
        TestRouter::resetRouter();
        global $_POST;

        $_POST = [
            'fullname' => 'Max Mustermann',
            'company' => 'Company name'
        ];

        $request = new \Pecee\Http\Request(false);
        $request->setMethod('post');
        TestRouter::setRequest($request);

        TestRouter::loadRoutes(\Dummy\DummyLoadableController::class);

        $response = TestRouter::debugOutput('/group/test/url', 'post');
        $this->assertEquals('method2', $response);
    }

    public function testAutoloadWithoutGroup(){
        TestRouter::resetRouter();
        global $_POST;

        $_POST = [
            'fullname' => 'Max Mustermann',
            'company' => 'Company name'
        ];

        $request = new \Pecee\Http\Request(false);
        $request->setMethod('post');
        TestRouter::setRequest($request);

        TestRouter::loadRoutes(DummyController::class);

        $response = TestRouter::debugOutput('/my/test/url', 'post');
        $this->assertEquals('method4', $response);
    }

}