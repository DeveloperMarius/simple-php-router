<?php

use Pecee\Http\Request;

require_once 'Dummy/CsrfVerifier/DummyCsrfVerifier.php';
require_once 'Dummy/Security/SilentTokenProvider.php';

class CsrfVerifierTest extends \PHPUnit\Framework\TestCase
{

    public function testTokenPass()
    {
        TestRouter::resetRouter();

        global $_POST;

        $tokenProvider = new SilentTokenProvider();
        $_POST[DummyCsrfVerifier::POST_KEY] = $tokenProvider->getToken();
        $csrf = new DummyCsrfVerifier();
        $csrf->setTokenProvider($tokenProvider);

        $request = new Request(false);
        $request->setMethod(\Pecee\Http\Request::REQUEST_TYPE_POST);
        $request->setUrl(new \Pecee\Http\Url('/page'));
        $request->fetch();

        $csrf->handle($request);

        // If handle doesn't throw exception, the test has passed
        $this->assertTrue(true);
    }

    public function testTokenFail()
    {
        $this->expectException(\Pecee\Http\Middleware\Exceptions\TokenMismatchException::class);

        global $_POST;

        $tokenProvider = new SilentTokenProvider();
        $csrf = new DummyCsrfVerifier();
        $csrf->setTokenProvider($tokenProvider);

        $request = new Request(false);
        $request->setMethod(\Pecee\Http\Request::REQUEST_TYPE_POST);
        $request->setUrl(new \Pecee\Http\Url('/page'));
        $request->fetch();

        $csrf->handle($request);
    }

    public function testExcludeInclude()
    {
        $router = TestRouter::router();
        $csrf = new DummyCsrfVerifier();
        $request = $router->getRequest();

        $request->setUrl(new \Pecee\Http\Url('/exclude-page'));
        $this->assertTrue($csrf->testSkip($router->getRequest()));

        $request->setUrl(new \Pecee\Http\Url('/exclude-all/page'));
        $this->assertTrue($csrf->testSkip($router->getRequest()));

        $request->setUrl(new \Pecee\Http\Url('/exclude-all/include-page'));
        $this->assertFalse($csrf->testSkip($router->getRequest()));

        $request->setUrl(new \Pecee\Http\Url('/include-page'));
        $this->assertFalse($csrf->testSkip($router->getRequest()));
    }

}