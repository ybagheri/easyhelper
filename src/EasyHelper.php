<?php

namespace Ybagheri;


class EasyHelper
{

    static function makeHTTPRequest($url, $method, $datas = [])
    {
        $url=rtrim($url, '/');
        $url = $url . "/" .  trim($method, '/');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//         curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datas));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
        $res = curl_exec($ch);
        if (curl_error($ch)) {
            var_dump(curl_error($ch));
        } else {
            return json_decode($res);
        }
    }
    static function telegramHTTPRequest($token, $method, $datas = null)
    {
        $url = "https://api.telegram.org/bot" . $token ;
        return isset($datas)? self::makeHTTPRequest($url, $method, $datas):self::makeHTTPRequest($url, $method );

    }

    static  function methodGetArgs($methodName,$className=null ){
        if(is_null($className)){
            $r = new \ReflectionFunction($methodName);
        }else{
            $r = new \ReflectionMethod($className, $methodName);
        }

        $params = $r->getParameters();
        $parameters=[];
        $counter=0;
        foreach ($params as $param) {
            $parameters[$counter]['parameter']= $param->getName();
            $parameters[$counter]['isOptional']= $param->isOptional();
            $counter++;
        }
        return $parameters;
    }
//    static function methodParamArgArr

}