<?php

use Ybagheri\EasyHelper;

class EasyHelperTest extends PHPUnit_Framework_TestCase
{
    private $token = '521988982:AAGOA0NLTfSxjv3YfnqmMl4aUuKdRI5c-3k'; //

    public function testTelegramHTTPRequest()
    {
        $result = EasyHelper::telegramHTTPRequest($this->token, "getMe", []);
        $this->assertEquals($result->ok, true);


    }


    public function checkProxyAndCertificateAuthorityHTTPRequest()
    {
        $result = EasyHelper::telegramHTTPRequest($this->token, "getMe", [ 'proxy_url' => '118.70.144.77','proxy_port' => '3128',],"cacert-2019-11-27.pem");
        $this->assertEquals($result->ok, true);


    }


    public function testMethodGetArgs()
    {
        $myclass = new MyClass;
        $result = $myclass->myMethod('oneParam', 'secondParam');
        $this->assertEquals($result[0]['parameter'], 'testFirstParam');
        $this->assertEquals($result[0]['isOptional'], false);
        $this->assertEquals($result[1]['parameter'], 'testSecodondOptionalParam');
        $this->assertEquals($result[1]['isOptional'], true);

        $result2 = doSomething('oneParam', 'secondParam');
        $this->assertEquals($result2[0]['parameter'], 'testFirstParam');
        $this->assertEquals($result2[0]['isOptional'], false);
        $this->assertEquals($result2[1]['parameter'], 'testSecodondOptionalParam');
        $this->assertEquals($result2[1]['isOptional'], true);
    }

}

function doSomething($testFirstParam, $testSecodondOptionalParam = null)
{
    return EasyHelper::methodGetArgs( __FUNCTION__);
}


class MyClass
{
    function myMethod($testFirstParam, $testSecodondOptionalParam = null)
    {
        return EasyHelper::methodGetArgs(__FUNCTION__,__CLASS__);
    }
}
