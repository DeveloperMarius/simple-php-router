<?php

use Pecee\Http\Input\Attributes\ValidatorAttribute;
use Pecee\Http\Input\Exceptions\InputValidationException;
use Pecee\Http\Input\InputItem;
use Pecee\Http\Input\InputValidator;
use Pecee\Http\Request;

require_once 'Dummy/InputValidatorRules/ValidatorRuleCustomTest.php';
require_once 'Dummy/DummyController.php';

class InputParserTest extends \PHPUnit\Framework\TestCase
{
    public function testInputNormal()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'false',
            'age' => '100',
            'email' => 'user@provider.com',
            'ip' => '192.168.105.22',
        ];

        $request = new Request(false);
        $request->setMethod('get');
        TestRouter::setRequest($request);

        TestRouter::get('/my/test/url', function (){
            $inputHandler = TestRouter::request()->getInputHandler();
            $data = $inputHandler->all(array(
                'isAdmin',
                'age'
            ));
            $this->assertEquals("false", $data['isAdmin']->getValue());
            $this->assertIsString($data['isAdmin']->getValue());
            $this->assertEquals("100", $data['age']->getValue());
            $this->assertIsString($data['age']->getValue());
        });
        TestRouter::debug('/my/test/url', 'get');
    }

    public function testInputParser()
    {
        TestRouter::resetRouter();
        global $_GET;

        $_GET = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'false',
            'isUser' => 'he is',
            'age' => '100',
            'email' => ' user@Provider.Com ',
            'ip' => '192.168.105.22',
            'ip2' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'custom' => 'this is a text',
            'custom2' => 'this is a text with: company'
        ];

        $request = new Request(false);
        $request->setMethod('get');
        TestRouter::setRequest($request);

        TestRouter::get('/my/test/url', function (){
            $inputHandler = TestRouter::request()->getInputHandler();
            $data = $inputHandler->all(array(
                'isAdmin' => 'bool',
                'isUser' => 'bool',
                'age' => 'int',
                'email' => 'email',
                'ip' => 'ipv4',
                'ip2' => 'ipv6',
                'custom' => function(InputItem $inputItem){
                    $inputItem->parser()->toString();
                    return strpos($inputItem->getValue(), 'company') === false ? $inputItem->getValue() : 'illegal word';
                },
                'custom2' => function(InputItem $inputItem){
                    $inputItem->parser()->toString();
                    return strpos($inputItem->getValue(), 'company') === false ? $inputItem->getValue() : 'illegal word';
                }
            ));

            $this->assertEquals(false, $data['isAdmin']->getValue());
            $this->assertIsBool($data['isAdmin']->getValue());
            $this->assertNull($data['isUser']->getValue());
            $this->assertEquals(100, $data['age']->getValue());
            $this->assertIsInt($data['age']->getValue());
            $this->assertEquals('user@provider.com', $data['email']->getValue());
            $this->assertIsString($data['email']->getValue());
            $this->assertEquals('192.168.105.22', $data['ip']->getValue());
            $this->assertIsString($data['ip']->getValue());
            $this->assertEquals('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $data['ip2']->getValue());
            $this->assertIsString($data['ip2']->getValue());
            $this->assertNotEquals('illegal word', $data['custom']->getValue());
            $this->assertEquals('illegal word', $data['custom2']->getValue());
        });
        TestRouter::debug('/my/test/url', 'get');
    }

    public function testAttributeValidatorRules(){
        InputValidator::$parseAttributes = true;
        TestRouter::resetRouter();
        global $_POST;

        $_POST = [
            'fullname' => 'Max Mustermann',
            'isAdmin' => 'false',
            'isUser' => 'he is',
            'email' => 'user@provider.com',
            'ip' => '192.168.105.22',
        ];

        $request = new Request(false);
        $request->setMethod('post');
        TestRouter::setRequest($request);

        TestRouter::post('/my/test/url', #[ValidatorAttribute('isAdmin', 'bool')] function (){
            $data = TestRouter::request()->getInputHandler()->requireAttributes();
            $this->assertEquals(false, $data['isAdmin']->getValue());
            $this->assertIsBool($data['isAdmin']->getValue());
            return 'success';
        });

        $output = TestRouter::debugOutput('/my/test/url', 'post');

        $this->assertEquals('success', $output);

    }

}