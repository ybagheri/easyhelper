<?php

use Ybagheri\EasyHelper;

class EasyDatabaseTest extends PHPUnit_Framework_TestCase
{
    private $token = '521988982:AAGOA0NLTfSxjv3YfnqmMl4aUuKdRI5c-3k'; //
    private $chatId = 105841687;

    public function testEasyHelper()
    {
        $result=EasyHelper::telegramHTTPRequest($this->token, "getMe", []);
        $this->assertEquals($result->ok,true );

        $myclass = new MyClass;
        $result = $myclass->myFun('oneParam', 'secondParam');
        $this->assertEquals($result[0]['parameter'], 'testFirstParam');
        $this->assertEquals($result[0]['isOptional'], false);
        $this->assertEquals($result[1]['parameter'], 'testSecodondOptionalParam');
        $this->assertEquals($result[1]['isOptional'], true);

    }
}

class MyClass
{
    function myFun($testFirstParam, $testSecodondOptionalParam = null)
    {
        return EasyHelper::methodGetArgs(__CLASS__, __FUNCTION__);
    }
}