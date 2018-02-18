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
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datas));
        $res = curl_exec($ch);
        if (curl_error($ch)) {
            var_dump(curl_error($ch));
        } else {
            return json_decode($res);
        }
    }
    static function telegramHTTPRequest($token, $method, $datas = [])
    {
        $url = "https://api.telegram.org/bot" . $token ;
        self::makeHTTPRequest($url, $method, $datas);
    }


}