<?php

use Ybagheri\EasyDatabase;

class EasyDatabaseTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        /*
        form .env file.
        DB_HOST
        DB_USERNAME
        DB_PASSWORD
        DB_DATABASE
        should set.
        */
        $con = new EasyDatabase();
        $query = "SELECT * from test";
        $result = $con->query($query);
        $this->assertEquals($result['ok'], true);

        //from array.
        $con = new EasyDatabase(['host' => 'localhost', 'username' => 'root1', 'password' => 'root1', 'database' => 'root1']);
        $query = "SELECT * from test";
        $result = $con->query($query);
        $this->assertEquals($result['ok'], true);

    }

    public function testQuery()
    {
        $con = new EasyDatabase();
        $query = "SELECT * from test";
        $result = $con->query($query);
        $this->assertEquals($result['ok'], true);
    }
}