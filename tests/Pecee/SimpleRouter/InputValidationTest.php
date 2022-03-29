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
    public function testInputValidator()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'false',
            'email' => 'user@provider.com',
            'ip' => '192.168.105.22'
        ];

        $request = new Request(false);
        $request->setMethod('get');
        TestRouter::setRequest($request);

        TestRouter::get('/my/test/url', 'DummyController@method3')
            ->validateInputs(
                InputValidator::make()
                    ->add(
                        InputValidatorItem::make('fullname')->string()->max(14)->startsWith('Max')->endsWith('mann')
                    )
                    ->add(
                        InputValidatorItem::make('isAdmin')->boolean()
                    )
                    ->add(
                        InputValidatorItem::make('email')->email()
                    )
                    ->add(
                        InputValidatorItem::make('ip')->ip()
                    )
                    ->add(
                        InputValidatorItem::make('nullable')->nullable()
                    )
            );

        $output = TestRouter::debugOutput('/my/test/url', 'get');

        $this->assertEquals('method3', $output);
    }

    public function testInputValidatorFailed()
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
            ->validateInputs(
                InputValidator::make()
                    ->add(
                        InputValidatorItem::make('fullname')->string()->max(10)->startsWith('Maxx')->endsWith('x')
                    )
                    ->add(
                        InputValidatorItem::make('isAdmin')->boolean()
                    )
                    ->add(
                        InputValidatorItem::make('email')->email()
                    )
                    ->add(
                        InputValidatorItem::make('ip')->ip()
                    )
                    ->add(
                        InputValidatorItem::make('nullable')->required()
                    )
            );

        TestRouter::debug('/my/test/url', 'get');
    }

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
                'fullname' => 'string|max:14|starts_with:Max|ends_with:mann',
                'isAdmin' => 'boolean',
                'email' => 'email',
                'ip' => 'ip',
                'nullable' => 'nullable',
                'type' => 'in:admin:user'
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
                'fullname' => 'string|max:10|starts_with:Maxx|ends_with:x',
                'isAdmin' => 'boolean',
                'email' => 'email',
                'ip' => 'ip',
                'nullable' => 'required',
                'type' => 'in:admin:user'
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
                'fullname' => 'string|max:10|starts_with:Maxx|ends_with:x',
                'isAdmin' => 'boolean',
                'email' => 'email',
                'ip' => 'ip',
                'nullable' => 'required'
            ]);

        TestRouter::debug('/my/test/url', 'get');
    }

    public function testCustomInputValidatorRule()
    {
        InputValidator::setCustomValidatorRuleNamespace('Dummy\InputValidatorRules');

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
        InputValidator::setCustomValidatorRuleNamespace('Dummy\InputValidatorRules');

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
                'customParam' => 'custom'
            ]);

        TestRouter::debug('/my/test/url', 'get');
    }

    public function testCustomInputValidatorRule2()
    {
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
                'customParam' => 'Dummy\InputValidatorRules\ValidatorRuleCustom'
            ]);

        $output = TestRouter::debugOutput('/my/test/url', 'get');

        $this->assertEquals('method3', $output);
    }

    public function testCustomInputValidatorRuleFailed2()
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
                'customParam' => 'Dummy\InputValidatorRules\ValidatorRuleCustomTest'
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
                    ValidatorRuleCustom::make(function(\Pecee\Http\Input\IInputItem $item){
                        return $item->getValue() == 'notCustomValue';
                    })
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
                    ValidatorRuleCustom::make(function(\Pecee\Http\Input\IInputItem $item){
                        return $item->getValue() !== 'notCustomValue';
                    })
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

        $valid = InputValidator::make()->parseSettings([
            'fullname' => 'string|max:14|starts_with:Max|ends_with:mann',
            'isAdmin' => 'boolean',
            'email' => 'email',
            'ip' => 'ip',
            'nullable' => 'nullable'
        ])->validateData($data);

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

        $valid = InputValidator::make()->parseSettings([
            'fullname' => 'string|min:4|max:14|starts_with:Max|ends_with:mann',
            'isAdmin' => 'boolean',
            'email' => 'email',
            'ip' => 'ip',
            'nullable' => 'nullable'
        ])->validateData($data);

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