<?php

use Pecee\Http\Input\Attributes\ValidatorAttribute;
use Pecee\Http\Input\Exceptions\InputValidationException;
use Pecee\Http\Input\InputValidator;
use Pecee\Http\Input\InputValidatorItem;
use Pecee\Http\Input\ValidatorRules\ValidatorRuleCustom;
use Pecee\Http\Request;

require_once 'Dummy/InputValidatorRules/ValidatorRuleCustomTest.php';
require_once 'Dummy/DummyController.php';

class InputValidationTest extends \PHPUnit\Framework\TestCase
{

    public function testInputValidator2()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'false',
            'email' => 'user@provider.com',
            'ip' => '192.168.105.22',
            'type' => 'user'
        ];

        $request = new Request(false);
        $request->setMethod('get');
        TestRouter::setRequest($request);

        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs([
                'fullname' => 'string|max:14',
                'isAdmin' => 'boolean',
                'email' => 'email',
                'ip' => 'ip',
                'nullable' => 'nullable',
                'type' => \Somnambulist\Components\Validation\Rules\In::make(array('user', 'admin'))
            ]);

        $output = TestRouter::debugOutput('/my/test/url', 'get');

        $this->assertEquals('method3', $output);
    }

    public function testInputValidatorFailed2()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'not true',
            'email' => 'user#provider.com',
            'ip' => '192.168.1s05.22',
            'type' => 'manager'
        ];

        $request = new Request(false);
        $request->setMethod('get');
        TestRouter::setRequest($request);

        $this->expectException(InputValidationException::class);
        TestRouter::get('/my/test/url', 'DummyController@method1')
            ->validateInputs([
                'fullname' => 'string|max:10',
                'isAdmin' => 'boolean',
                'email' => 'email',
                'ip' => 'ip',
                'nullable' => 'required',
                'type' => \Somnambulist\Components\Validation\Rules\In::make(array('user', 'admin'))
            ]);

        TestRouter::debug('/my/test/url', 'get');
    }

    public function testInputValidatorFailed3()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'not true',
            'email' => 'user#provider.com',
            'ip' => '192.168.1s05.22',
        ];

        $request = new Request(false);
        $request->setMethod('get');
        TestRouter::setRequest($request);

        $this->expectException(InputValidationException::class);
        TestRouter::get('/my/test/url', 'DummyController@method1')
            ->validateInputs([
                'fullname' => 'string|max:10',
                'isAdmin' => 'boolean',
                'email' => 'email',
                'ip' => 'ip',
                'nullable' => 'required'
            ]);

        TestRouter::debug('/my/test/url', 'get');
    }

    public function testCustomInputValidatorRule()
    {
        InputValidator::getFactory()->addRule('customTest', new \Dummy\InputValidatorRules\ValidatorRuleCustomTest());

        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'customParam' => 'customValue'
        ];

        $request = new Request(false);
        $request->setMethod('get');
        TestRouter::setRequest($request);

        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs([
                'customParam' => 'customTest'
            ]);

        $output = TestRouter::debugOutput('/my/test/url', 'get');

        $this->assertEquals('method3', $output);
    }

    public function testCustomInputValidatorRuleFailed()
    {
        InputValidator::getFactory()->addRule('customTest', new \Dummy\InputValidatorRules\ValidatorRuleCustomTest());

        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'customParam' => 'notCustomValue'
        ];

        $request = new Request(false);
        $request->setMethod('get');
        TestRouter::setRequest($request);

        $this->expectException(\Somnambulist\Components\Validation\Exceptions\RuleException::class);
        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs([
                'customParam' => 'custom'
            ]);

        TestRouter::debug('/my/test/url', 'get');
    }

    public function testCustomInputValidatorRule3()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'customParam' => 'notCustomValue'
        ];

        $request = new Request(false);
        $request->setMethod('get');
        TestRouter::setRequest($request);

        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs([
                'customParam' => [
                    function(mixed $value){
                        return $value == 'notCustomValue';
                    }
                ]
            ]);

        $output = TestRouter::debugOutput('/my/test/url', 'get');

        $this->assertEquals('method3', $output);
    }

    public function testCustomInputValidatorRuleFailed3()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'customParam' => 'notCustomValue'
        ];

        $request = new Request(false);
        $request->setMethod('get');
        TestRouter::setRequest($request);

        $this->expectException(InputValidationException::class);
        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs([
                'customParam' => [
                    function(mixed $value){
                        return $value !== 'notCustomValue';
                    }
                ]
            ]);

        TestRouter::debug('/my/test/url', 'get');
    }

    public function testCustomInputValidatorRuleRequireFailed()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'emailAddress' => 1
        ];

        $request = new Request(false);
        $request->setMethod('get');
        TestRouter::setRequest($request);

        $this->expectException(InputValidationException::class);
        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs([
                'emailAddress' => 'email'
            ]);

        TestRouter::debug('/my/test/url', 'get');
    }

    public function testInputValidatorData()
    {
        $data = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'false',
            'email' => 'user@provider.com',
            'ip' => '192.168.105.22',
        ];

        $valid = InputValidator::make()->setRules([
            'fullname' => 'string|max:14',
            'isAdmin' => 'boolean',
            'email' => 'email',
            'ip' => 'ip',
            'nullable' => 'nullable'
        ])->validateData($data)->passes();

        $this->assertTrue($valid);
    }

    public function testInputValidatorDataFailed()
    {
        $data = [
            'fullname' => 'Max',
            'isAdmin' => 'false',
            'email' => 'user@provider.com',
            'ip' => '192.168.105.22',
        ];

        $this->expectException(InputValidationException::class);

        $valid = InputValidator::make()->setRules([
            'fullname' => 'string|min:4|max:14',
            'isAdmin' => 'boolean',
            'email' => 'email',
            'ip' => 'ip',
            'nullable' => 'required'
        ])->validateData($data)->passes();

        $this->assertFalse($valid);
    }

    public function testAttributeValidatorRules(){
        InputValidator::$parseAttributes = true;
        TestRouter::resetRouter();
        global $_POST;

        $_POST = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'false',
            'email' => 'user@provider.com',
            'ip' => '192.168.105.22',
        ];

        $request = new Request(false);
        $request->setMethod('post');
        TestRouter::setRequest($request);

        TestRouter::post('/my/test/url', #[ValidatorAttribute('fullname', 'string', 'min:5|max:50')] function (){
            return 'success';
        });

        $output = TestRouter::debugOutput('/my/test/url', 'post');

        $this->assertEquals('success', $output);

    }

    public function testAttributeValidatorRulesFailed(){
        InputValidator::$parseAttributes = true;
        TestRouter::resetRouter();
        global $_POST;

        $_POST = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'false',
            'email' => 'user@provider.com',
            'ip' => '192.168.105.22',
        ];

        $request = new Request(false);
        $request->setMethod('post');
        TestRouter::setRequest($request);

        $this->expectException(InputValidationException::class);
        TestRouter::post('/my/test/url', #[ValidatorAttribute('fullname', 'string', 'min:5|max:6')] function (){

        });

        TestRouter::debug('/my/test/url', 'post');
    }

    public function testAttributeValidatorRules2(){
        InputValidator::$parseAttributes = true;
        TestRouter::resetRouter();
        global $_POST;

        $_POST = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'false',
            'email' => 'user@provider.com',
            'ip' => '192.168.105.22',
            'company' => 'Intel'
        ];

        $request = new Request(false);
        $request->setMethod('post');
        TestRouter::setRequest($request);

        TestRouter::post('/my/test/url', 'DummyController@method4');

        $output = TestRouter::debugOutput('/my/test/url', 'post');

        $this->assertEquals('method4', $output);

    }

    public function testAttributeValidatorRulesFailed2(){
        InputValidator::$parseAttributes = true;
        TestRouter::resetRouter();
        global $_POST;

        $_POST = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'false',
            'email' => 'user@provider.com',
            'ip' => '192.168.105.22',
        ];

        $request = new Request(false);
        $request->setMethod('post');
        TestRouter::setRequest($request);

        $this->expectException(InputValidationException::class);
        TestRouter::post('/my/test/url', 'DummyController@method4');

        TestRouter::debug('/my/test/url', 'post');
    }

    public function testAttributeValidatorRulesFailed3(){
        InputValidator::$parseAttributes = true;
        TestRouter::resetRouter();
        global $_POST;

        $_POST = [
            'fullname' => 'Max',
            'isAdmin' => 'false',
            'email' => 'user@provider.com',
            'ip' => '192.168.105.22'
        ];

        $request = new Request(false);
        $request->setMethod('post');
        TestRouter::setRequest($request);

        $this->expectException(InputValidationException::class);
        TestRouter::post('/my/test/url', 'DummyController@method4');

        TestRouter::debug('/my/test/url', 'post');
    }

    public function testAttributeValidatorRulesFailed4(){
        InputValidator::$parseAttributes = true;
        TestRouter::resetRouter();
        global $_POST;

        $_POST = [
            'fullname' => 'Max',
            'isAdmin' => 'false',
            'email' => 'user@provider.com',
            'ip' => '192.168.105.22'
        ];

        $request = new Request(false);
        $request->setMethod('post');
        TestRouter::setRequest($request);

        $this->expectException(InputValidationException::class);
        TestRouter::post('/my/test/url', ['DummyController', 'method4']);

        TestRouter::debug('/my/test/url', 'post');
    }

    public function testAttributeValidatorRules3(){
        InputValidator::$parseAttributes = true;
        TestRouter::resetRouter();
        global $_POST;

        $_POST = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'false',
            'email' => 'user@provider.com',
            'ip' => '192.168.105.22',
            'company' => null
        ];

        $request = new Request(false);
        $request->setMethod('post');
        TestRouter::setRequest($request);

        TestRouter::post('/my/test/url', #[ValidatorAttribute('fullname', 'string', 'min:5|max:20'),ValidatorAttribute('company', 'string', 'nullable')] function (){
            return 'success';
        });

        $output = TestRouter::debugOutput('/my/test/url', 'post');

        $this->assertEquals('success', $output);

    }

}