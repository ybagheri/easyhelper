<?php

use Ybagheri\EasyHelper;
class EasyDatabaseTest extends PHPUnit_Framework_TestCase
{
private $token='521988982:AAGOA0NLTfSxjv3YfnqmMl4aUuKdRI5c-3k'; //
private $chatId = 105841687;
    public function testEasyHelper()
    {
        $result=EasyHelper::makeHTTPRequest($this->token, "getMe", []);           
        $this->assertEquals($result->ok,true );

    }
}