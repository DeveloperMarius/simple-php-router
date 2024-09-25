<?php

class TestRouter extends \Pecee\SimpleRouter\SimpleRouter
{

    public function __construct()
    {
        static::request()->setHost('testhost.com');
    }

    public static function debugNoReset(string $testUrl, string $testMethod = 'get'): void
    {
        $request = static::request();

        $request->setUrl((new \Pecee\Http\Url($testUrl, false))->setHost('local.unitTest'));
        $request->setMethod($testMethod);

        static::start();
    }

    public static function debug(string $testUrl, string $testMethod = 'get', bool $reset = true): void
    {
        try {
            static::debugNoReset($testUrl, $testMethod);
        } catch (\Exception $e) {
            static::$defaultNamespace = null;
            static::router()->reset();
            throw $e;
        }

        if ($reset === true) {
            static::$defaultNamespace = null;
            static::router()->reset();
        }

    }

    public static function debugOutput(string $testUrl, string $testMethod = 'get', bool $reset = true): string
    {
        $response = null;

        // Route request
        ob_start();
        static::debug($testUrl, $testMethod, $reset);
        $response = ob_get_clean();

        // Return response
        return $response;
    }

    public static function resetRouter(){
        global $_SERVER;
        unset($_SERVER['content_type']);
        unset($_SERVER['remote_addr']);
        unset($_SERVER['remote-addr']);
        unset($_SERVER['http-cf-connecting-ip']);
        unset($_SERVER['http-client-ip']);
        unset($_SERVER['http-x-forwarded-for']);
        unset($_SERVER['emote-addr']);
        global $_GET;
        $_GET = [];
        global $_POST;
        $_POST = [];
        TestRouter::router()->reset();
    }

}