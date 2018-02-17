<?php
/**
 * Created by PhpStorm.
 * User: bagheri
 * Date: 02/17/2018
 * Time: 03:20 PM
 */

namespace Ybagheri;


class EasyHelper
{

    static function makeHTTPRequest($token, $method, $datas = [])
    {
        $url = "https://api.telegram.org/bot" . $token . "/" . $method;
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


}