<?php

namespace Ybagheri;


trait GeneralHelper
{

    public function addScheme($url, $scheme = 'http://')
    {
        return parse_url($url, PHP_URL_SCHEME) === null ?
            $scheme . $url : $url;
    }

    public function getRemoteFileinfo($url)
    {
//        error_reporting(0);
        $file_headers = get_headers($url, 1);
//        error_reporting(1);
        $info = self::getRemote200($file_headers);
        if ($info['size']) {
            $name = pathinfo($url, PATHINFO_BASENAME);
            return array_merge($info, ['name' => $name]);
        } elseif ($file_headers[0] == "HTTP/1.1 302 Found") {
            if (isset($file_headers["Location"])) {
                $url = $file_headers["Location"][0];
                if (strpos($url, "/_as/") !== false) {
                    $mainUrl = substr($url, 0, strpos($url, "/_as/"));
                    $name = substr($url, strpos($url, "/_as/") + 5);
                    $url = $mainUrl;
                } else {
                    $name = pathinfo($url, PATHINFO_BASENAME);
                }
                $file_headers = get_headers($url, 1);
                return array_merge(self::getRemote200($file_headers), ['name' => $name]);
            }
        }
        return false;
    }

    public function getRemote200($file_headers)
    {


        if (!$file_headers || $file_headers[0] == "HTTP/1.1 404 Not Found" || $file_headers[0] == "HTTP/1.0 404 Not Found") {
            return false;
        } elseif ($file_headers[0] == "HTTP/1.0 200 OK" || $file_headers[0] == "HTTP/1.1 200 OK") {


            $clen = (isset($file_headers['Content-Length'])) ? $file_headers['Content-Length'] : false;
            $size = $clen;
            if ($clen) {
                switch ($clen) {
                    case $clen < 1024:
                        $size = $clen . ' B';
                        break;
                    case $clen < 1048576:
                        $size = round($clen / 1024, 2) . ' KiB';
                        break;
                    case $clen < 1073741824:
                        $size = round($clen / 1048576, 2) . ' MiB';
                        break;
                    case $clen < 1099511627776:
                        $size = round($clen / 1073741824, 2) . ' GiB';
                        break;
                }
            }
            $contetType = isset($file_headers['Content-Type']) ? $file_headers['Content-Type'] : false;
            return ['size' => $size, 'type' => $contetType];

        }
        return false;
    }

    public function downloadFromUrl($url, $path)
    {
        $url = self::addScheme($url);
        $file_headers = get_headers($url, 1);
        if (!$file_headers || $file_headers[0] == "HTTP/1.1 404 Not Found" || $file_headers[0] == "HTTP/1.0 404 Not Found") {
            return false;
        } elseif ($file_headers[0] == "HTTP/1.0 200 OK" || $file_headers[0] == "HTTP/1.1 200 OK") {

            $name = pathinfo($url, PATHINFO_BASENAME);

            if (file_put_contents("$path/$name", fopen($url, 'r'))) {
                return "$path/$name";
            }

        } elseif ($file_headers[0] == "HTTP/1.1 302 Found") {
            if (isset($file_headers["Location"])) {
                $url = $file_headers["Location"][0];
                if (strpos($url, "/_as/") !== false) {
                    $mainUrl = substr($url, 0, strpos($url, "/_as/"));
                    $name = substr($url, strpos($url, "/_as/") + 5);
                } else {
                    $name = pathinfo($url, PATHINFO_BASENAME);
                    $mainUrl = $url;
                }

                $len = strlen($name);
                if ($len > 49) {
                    $name = substr(pathinfo($name, PATHINFO_FILENAME), 0, 45) . "." . pathinfo($name, PATHINFO_EXTENSION);
//                    $this->messages->sendMessage(['peer' => 281693135, 'message' => $name]);
                }
                if (file_put_contents("$path/$name", fopen($mainUrl, 'r'))) {
                    return "$path/$name";
                }

            }
        }
        return false;
    }

    public function  getPlayTimeAudio($filename){
        // Initialize getID3 engine
        $getID3 = new \getID3;
// Analyze file and store returned data in $ThisFileInfo
        $ThisFileInfo = $getID3->analyze($filename);
        /*
         Optional: copies data from all subarrays of [tags] into [comments] so
         metadata is all available in one location for all tag formats
         metainformation is always available under [tags] even if this is not called
        */
        \getid3_lib::CopyTagsToComments($ThisFileInfo);

        if(isset($ThisFileInfo['playtime_string']) && isset($ThisFileInfo['playtime_seconds'])){
            return ['playtime_string' =>$ThisFileInfo['playtime_string'] ,     'playtime_seconds' =>intval($ThisFileInfo['playtime_seconds'])];
        }
        return false;
    }

    public function GetMIMEtype($filename)
    {
        $filename = realpath($filename);
        if (!file_exists($filename)) {
            echo 'File does not exist: "' . htmlentities($filename) . '"<br>';
            return '';
        } elseif (!is_readable($filename)) {
            echo 'File is not readable: "' . htmlentities($filename) . '"<br>';
            return '';
        }

        // Initialize getID3 engine
        $getID3 = false;
        try {
            $getID3 = new \getID3;
        } catch (\Exception $e) {
            echo ($e->getMessage());
        }
       
      
      try{
        $DeterminedMIMEtype = '';
        if ($fp = fopen($filename, 'rb')) {
            $getID3->openfile($filename);
            if (empty($getID3->info['error'])) {
                // ID3v2 is the only tag format that might be prepended in front of files, and it's non-trivial to skip, easier just to parse it and know where to skip to
                \getid3_lib::IncludeDependency(GETID3_INCLUDEPATH . 'module.tag.id3v2.php', __FILE__, true);
                $getid3_id3v2 = new \getid3_id3v2($getID3);
                $getid3_id3v2->Analyze();
                fseek($fp, $getID3->info['avdataoffset'], SEEK_SET);
                $formattest = fread($fp, 16);  // 16 bytes is sufficient for any format except ISO CD-image
                fclose($fp);
                $DeterminedFormatInfo = $getID3->GetFileFormat($formattest);
if(!isset($DeterminedFormatInfo['mime_type']))return false;
                $DeterminedMIMEtype = $DeterminedFormatInfo['mime_type'];
            } else {
                echo 'Failed to getID3->openfile "' . htmlentities($filename) . '"<br>';
            }
        } else {
            echo 'Failed to fopen "' . htmlentities($filename) . '"<br>';
        }
      } catch (\Exception $e) {
            echo ($e->getMessage());
        return false;
        }
      
        return $DeterminedMIMEtype;
    }

    public function extractRar($file, $dest, $password = null, $continueFileName = null,$toOrFrom=null,$from=null,$to=null)
    {
        $rar_arch = \RarArchive::open($file, $password);
        if (!$rar_arch) {
            return ['ok' => false, 'result' => 'Failed to open rar file.'];

        }

        $entries = $rar_arch->getEntries();
        try {
            $stream = reset($entries)->getStream();
        } catch (\Exception $e) {
            return ['ok' => false, 'result' => $e->getMessage()];

        }
        if (stream_get_contents($stream) == false) {
            echo 'false';
            return ['ok' => false, 'result' => 'Extraction failed (wrong password?)'];
        } else {
            echo 'true';
            foreach ($entries as $entry) {
                if (is_null($toOrFrom)) {
//                     self::debuglog(['name' => $entry->getName(),'position' =>$entry->getPosition()]);
                    if (!$entry->extract("$dest")) {
                        return ['ok' => false, 'result' =>['name' => $entry->getName(),'position' =>$entry->getPosition()]];
                    }
                } else {
                    if($toOrFrom=='from'){
                        if ($entry->getPosition() >= $continueFileName) {
                            if (!$entry->extract("$dest")) {
                                return ['ok' => false, 'result' =>['name' => $entry->getName(),'position' =>$entry->getPosition()]];

                            }
                        }

                    }elseif ($toOrFrom=='to'){
                        if ($entry->getPosition() <= $continueFileName) {
                            if (!$entry->extract("$dest")) {
                                return ['ok' => false, 'result' =>['name' => $entry->getName(),'position' =>$entry->getPosition()]];

                            }
                        }
                    }elseif ($toOrFrom=='fromTo'){
                        if ($entry->getPosition() >= $from && $entry->getPosition() <= $to) {
                            if (!$entry->extract("$dest")) {
                                return ['ok' => false, 'result' =>['name' => $entry->getName(),'position' =>$entry->getPosition()]];

                            }
                        }

                    }

                }

            }
        }

        $rar_arch->close();
        fclose($stream);
        return ['ok' => true];
    }
    public function showExtractRar($file,$password=null)
    {
        $rar_arch = \RarArchive::open($file, $password);

        if (!$rar_arch) {
            return ['ok' => false, 'result' => 'Failed to open rar file.'];

        }

        $entries = $rar_arch->getEntries();
        try {
            $stream = reset($entries)->getStream();
        } catch (\Exception $e) {
            return ['ok' => false, 'result' => $e->getMessage()];

        }
        $arr=[];
        if (stream_get_contents($stream) == false) {
            echo 'false';
            return ['ok' => false, 'result' => 'Extraction failed (wrong password?)'];
        } else {
            echo 'true';

            foreach ($entries as $entry) {

                $arr[]= ['name' => $entry->getName(),'position' =>$entry->getPosition()];
            }
        }

        $rar_arch->close();
        fclose($stream);
        return ['ok' => true,'result' => $arr];
    }

    function debuglog($input){
        \Ybagheri\EasyHelper::telegramHTTPRequest('594339351:AAEkDDVIa3djpA8vfF5GsetfX0ypJRYe3Qc', 'sendMessage', [
            'chat_id' => 281693135,
            'text' =>  var_export($input, true)]);

    }

    public function unCompress($file, $dest, $password = NULL,$continueFileName = null,$toOrFrom=null,$from=null,$to=null)
    {
//        $fs = new Filesystem;
//        $ext = $fs->extension($file);
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        $dir = rtrim($dest, '/') . '/' . time();

        if (!is_dir(realpath($dir))) {
            mkdir($dir);
            chmod($dir, 0777);
        }
        if (is_dir($dir)) {

            if ($ext == 'zip') {
//             exec("unzip -P $password $file -d $dest ");
//            exec("7za x -p\"$password\" $file   -o$dest/");

                $zip = new \ZipArchive();
                $zip_status = $zip->open($file);

                if ($zip_status === true) {

                    if ($zip->extractTo($dir)) {
                        return ['ok' => true, 'path' => $dir];
                    }

                    if ($zip->setPassword($password)) {
                        if (!$zip->extractTo($dir)) {
                            echo "Extraction failed (wrong password?)";
                            return ['ok' => false, 'result' => 'Extraction failed (wrong password?)'];
                        }
                    } else {
                        return ['ok' => false, 'result' => 'Extraction failed (wrong password?)'];
                    }
                    $zip->close();
                    return ['ok' => true, 'path' => $dir];
                } else {
                    echo 'false' . PHP_EOL;
                }

            } elseif ($ext == 'rar') {
                return array_merge(self::extractRar($file, $dir, $password,$continueFileName,$toOrFrom,$from,$to), ['path' => $dir]);
            }
            return ['ok' => false];
        } else {
            return ['ok' => false, 'result' => 'destination dir does not exists!'];
        }
    }

    public function allFileInDir($path)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        $arr = [];
        foreach ($files as $fileinfo) {
            if (!$fileinfo->isDir()) {
                $arr[] = $fileinfo->getRealPath();
            }

        }
        asort($arr);
        $array_with_new_keys = array_values($arr);
        return $array_with_new_keys;
    }

    public function deleteDirectory($path)
    {
        if (is_file($path)) {
            return unlink($path);
        } elseif (is_dir($path)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST,
                \RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
            );

            $paths = [];
            foreach ($files as $fileinfo) {
                if ($fileinfo->isDir()) {

                    $paths[] = $fileinfo->getRealPath();

                } else {
                    unlink($fileinfo->getRealPath());
                }
            }

            if (!empty($paths)) {
                $item = end($paths);
                do {

                    rmdir($item);
                } while ($item = prev($paths));

            }

            return rmdir($path);

        }
    }

    public function deleteFile($path){
        return self::deleteDirectory($path);
    }

    public function updateArrayKey($arr, $removeValue, $removekey = null, $blnResetKey = true)
    {
        foreach ($arr as $key => &$value) {
            // echo 'value print: '.$value.PHP_EOL;
            // print_r($value);
            if (is_null($removekey)) {
                if ($value == $removeValue) {
                    unset($arr[$key]);
                    $arr = array_values($arr);
                    break;
                }
            } else {
                // print_r($value);
                if ($value[$removekey] == $removeValue) {
                    unset($arr[$key]);
                    if ($blnResetKey) {
                        $arr = array_values($arr);
                    }

                    break;
                }
            }

        }

        return $arr;
    }

    public function searchThroughArray($search, array $lists)
    {
      $val=[];
        try {

            foreach ($lists as $key => $value) {
                if (is_array($value)) {
                    array_walk_recursive($value, function ($v, $k) use ($search, $key, $value, &$val) {


                        if (strpos(strtoupper($v), strtoupper($search)) !== false) $val[$key] = $value;
                    });
                } else {

                    if (strpos(strtoupper($value), strtoupper($search)) !== false) $val[$key] = $value;
                }

            }
            return $val;

        } catch (Exception $e) {
            return false;
        }
    }

    public function searchThroughArrayWithKey($search, array $lists, $keySearch)
    {
       $val=[];
        try {

            foreach ($lists as $key => $value) {
                if (is_array($value)) {
                    array_walk_recursive($value, function ($v, $k) use ($search, $key, $value, &$val, $keySearch) {
                        if ($k == $keySearch) {
                            if (strpos(strtoupper($v), strtoupper($search)) !== false) $val[$key] = $value;
                        }
                    });
                } else {


                    if (strpos(strtoupper($value), strtoupper($search)) !== false) $val[$key] = $value;
                }

            }
            return $val;

        } catch (Exception $e) {
            return false;
        }

    }

    public function encrypt($plaintext, $password)
    {
        $method = "AES-256-CBC";
        $key = hash('sha256', $password, true);
        $iv = openssl_random_pseudo_bytes(16);

        $ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);
        $hash = hash_hmac('sha256', $ciphertext, $key, true);

        return $iv . $hash . $ciphertext;
    }

    public function decrypt($ivHashCiphertext, $password)
    {
        $method = "AES-256-CBC";
        $iv = substr($ivHashCiphertext, 0, 16);
        $hash = substr($ivHashCiphertext, 16, 32);
        $ciphertext = substr($ivHashCiphertext, 48);
        $key = hash('sha256', $password, true);

        if (hash_hmac('sha256', $ciphertext, $key, true) !== $hash) return null;

        return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
    }

    public function removeNullValue(array $array)
    {
        $temp = [];
        foreach ($array as $key => $value) {
            if (!is_null($value)) {
                $temp[$key] = $value;
            }
        }
        return $temp;
    }

  public function dimension($path)
    {
        //image or video.
        $getID3 = new \getID3;
        $ThisFileInfo = $getID3->analyze($path);
    self::debuglog($ThisFileInfo);
//     var_export($ThisFileInfo);
//       if (isset($ThisFileInfo['video']))echo 'video set'.PHP_EOL;
//        if (isset($ThisFileInfo['video']['resolution_x']) )echo 'set x'.PHP_EOL;
//            if (isset($ThisFileInfo['video']['resolution_y']) )echo 'set y'.PHP_EOL;
//        if (isset($ThisFileInfo['filesize']) )echo 'set filesize'.PHP_EOL;

    
        if (isset($ThisFileInfo['video']['resolution_x']) && isset($ThisFileInfo['video']['resolution_y']) && isset($ThisFileInfo['filesize'])) {

            return ['resolution_x' => $ThisFileInfo['video']['resolution_x'], 'resolution_y' => $ThisFileInfo['video']['resolution_y'], 'filesize' => $ThisFileInfo['filesize']];
        }
        return false;

    }
    

    public function resizeTo(array $dim, $size = 512)
    {
      //var_dump($dim);
        if (!isset($dim['resolution_x'])) {
            $dim = ['resolution_x' => $dim[0], 'resolution_y' => $dim[1]];
        }

        if ($dim['resolution_x'] >= $dim['resolution_y']) {
            $max = $dim['resolution_x'];
        } else {
            $max = $dim['resolution_y'];
        }

        $dim['resolution_x'] = $dim['resolution_x'] * $size / $max;
        $dim['resolution_y'] = $dim['resolution_y'] * $size / $max;

        return $dim;


    }

    public function jpg2png($jpgPath, $quality = 100, $resolution_x = null, $resolution_y = null)
    {
        if (file_exists($jpgPath)) {
            $pngConverted = pathinfo($jpgPath, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . time() . ".png";
            if (is_null($resolution_x) && is_null($resolution_y)) {
                exec("convert $jpgPath  -quality $quality $pngConverted");

            } else {
                exec("convert $jpgPath  -resize $resolution_x" . "x" . $resolution_y . "!  -quality $quality $pngConverted");
//               echo "convert $jpgPath  -resize $resolution_x" . "x" . $resolution_y . "!  -quality $quality $pngConverted".PHP_EOL;

            }
            if (file_exists($pngConverted)) {
              
                return $pngConverted;
            }
        }


        return false;

    }


    public function reduceImageSize($path, $targetSize = 512000)
    {
        $dim = self::dimension($path);
//      var_dump($dim);
      if($dim){
        $dim = self::resizeTo($dim);          
//  var_dump($dim);
        $quality = 100;

      
       $pngConverted = self::jpg2png($path, $quality, $dim['resolution_x'], $dim['resolution_y']);
      $filesize = self::dimension($pngConverted)['filesize'];
  exec ('identify -format "%wx%h" '. $pngConverted);
           echo $filesize. PHP_EOL;
        $ok = $pngConverted;
       


        while (self::dimension($pngConverted)['filesize'] > $targetSize && $quality > 50 && self::dimension($pngConverted)['filesize'] <= $filesize) {

            
            $quality *= .75;
            $filesize = self::dimension($pngConverted)['filesize'];
            $pngConverted = self::jpg2png($pngConverted, $quality);
          $ok = $pngConverted;
          exec ('identify -format "%wx%h" '. $pngConverted);
           echo dimension($pngConverted)['filesize'] . PHP_EOL;
//            sleep(3);
        }
//        echo 'selected: ' . dimension($ok)['filesize'] . PHP_EOL;


        if (isset($ok) && self::dimension($ok)['filesize'] <= $targetSize) {
            return $ok;
        }
      }
        return false;
        
    }
    
    public function mime2ext($mime) {
        $mime_map = [
            'video/3gpp2'                                                               => '3g2',
            'video/3gp'                                                                 => '3gp',
            'video/3gpp'                                                                => '3gp',
            'application/x-compressed'                                                  => '7zip',
            'audio/x-acc'                                                               => 'aac',
            'audio/ac3'                                                                 => 'ac3',
            'application/postscript'                                                    => 'ai',
            'audio/x-aiff'                                                              => 'aif',
            'audio/aiff'                                                                => 'aif',
            'audio/x-au'                                                                => 'au',
            'video/x-msvideo'                                                           => 'avi',
            'video/msvideo'                                                             => 'avi',
            'video/avi'                                                                 => 'avi',
            'application/x-troff-msvideo'                                               => 'avi',
            'application/macbinary'                                                     => 'bin',
            'application/mac-binary'                                                    => 'bin',
            'application/x-binary'                                                      => 'bin',
            'application/x-macbinary'                                                   => 'bin',
            'image/bmp'                                                                 => 'bmp',
            'image/x-bmp'                                                               => 'bmp',
            'image/x-bitmap'                                                            => 'bmp',
            'image/x-xbitmap'                                                           => 'bmp',
            'image/x-win-bitmap'                                                        => 'bmp',
            'image/x-windows-bmp'                                                       => 'bmp',
            'image/ms-bmp'                                                              => 'bmp',
            'image/x-ms-bmp'                                                            => 'bmp',
            'application/bmp'                                                           => 'bmp',
            'application/x-bmp'                                                         => 'bmp',
            'application/x-win-bitmap'                                                  => 'bmp',
            'application/cdr'                                                           => 'cdr',
            'application/coreldraw'                                                     => 'cdr',
            'application/x-cdr'                                                         => 'cdr',
            'application/x-coreldraw'                                                   => 'cdr',
            'image/cdr'                                                                 => 'cdr',
            'image/x-cdr'                                                               => 'cdr',
            'zz-application/zz-winassoc-cdr'                                            => 'cdr',
            'application/mac-compactpro'                                                => 'cpt',
            'application/pkix-crl'                                                      => 'crl',
            'application/pkcs-crl'                                                      => 'crl',
            'application/x-x509-ca-cert'                                                => 'crt',
            'application/pkix-cert'                                                     => 'crt',
            'text/css'                                                                  => 'css',
            'text/x-comma-separated-values'                                             => 'csv',
            'text/comma-separated-values'                                               => 'csv',
            'application/vnd.msexcel'                                                   => 'csv',
            'application/x-director'                                                    => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
            'application/x-dvi'                                                         => 'dvi',
            'message/rfc822'                                                            => 'eml',
            'application/x-msdownload'                                                  => 'exe',
            'video/x-f4v'                                                               => 'f4v',
            'audio/x-flac'                                                              => 'flac',
            'video/x-flv'                                                               => 'flv',
            'image/gif'                                                                 => 'gif',
            'application/gpg-keys'                                                      => 'gpg',
            'application/x-gtar'                                                        => 'gtar',
            'application/x-gzip'                                                        => 'gzip',
            'application/mac-binhex40'                                                  => 'hqx',
            'application/mac-binhex'                                                    => 'hqx',
            'application/x-binhex40'                                                    => 'hqx',
            'application/x-mac-binhex40'                                                => 'hqx',
            'text/html'                                                                 => 'html',
            'image/x-icon'                                                              => 'ico',
            'image/x-ico'                                                               => 'ico',
            'image/vnd.microsoft.icon'                                                  => 'ico',
            'text/calendar'                                                             => 'ics',
            'application/java-archive'                                                  => 'jar',
            'application/x-java-application'                                            => 'jar',
            'application/x-jar'                                                         => 'jar',
            'image/jp2'                                                                 => 'jp2',
            'video/mj2'                                                                 => 'jp2',
            'image/jpx'                                                                 => 'jp2',
            'image/jpm'                                                                 => 'jp2',
            'image/jpeg'                                                                => 'jpeg',
            'image/pjpeg'                                                               => 'jpeg',
            'application/x-javascript'                                                  => 'js',
            'application/json'                                                          => 'json',
            'text/json'                                                                 => 'json',
            'application/vnd.google-earth.kml+xml'                                      => 'kml',
            'application/vnd.google-earth.kmz'                                          => 'kmz',
            'text/x-log'                                                                => 'log',
            'audio/x-m4a'                                                               => 'm4a',
            'application/vnd.mpegurl'                                                   => 'm4u',
            'audio/midi'                                                                => 'mid',
            'application/vnd.mif'                                                       => 'mif',
            'video/quicktime'                                                           => 'mov',
            'video/x-sgi-movie'                                                         => 'movie',
            'audio/mpeg'                                                                => 'mp3',
            'audio/mpg'                                                                 => 'mp3',
            'audio/mpeg3'                                                               => 'mp3',
            'audio/mp3'                                                                 => 'mp3',
            'video/mp4'                                                                 => 'mp4',
            'video/mpeg'                                                                => 'mpeg',
            'application/oda'                                                           => 'oda',
            'audio/ogg'                                                                 => 'ogg',
            'video/ogg'                                                                 => 'ogg',
            'application/ogg'                                                           => 'ogg',
            'application/x-pkcs10'                                                      => 'p10',
            'application/pkcs10'                                                        => 'p10',
            'application/x-pkcs12'                                                      => 'p12',
            'application/x-pkcs7-signature'                                             => 'p7a',
            'application/pkcs7-mime'                                                    => 'p7c',
            'application/x-pkcs7-mime'                                                  => 'p7c',
            'application/x-pkcs7-certreqresp'                                           => 'p7r',
            'application/pkcs7-signature'                                               => 'p7s',
            'application/pdf'                                                           => 'pdf',
            'application/octet-stream'                                                  => 'pdf',
            'application/x-x509-user-cert'                                              => 'pem',
            'application/x-pem-file'                                                    => 'pem',
            'application/pgp'                                                           => 'pgp',
            'application/x-httpd-php'                                                   => 'php',
            'application/php'                                                           => 'php',
            'application/x-php'                                                         => 'php',
            'text/php'                                                                  => 'php',
            'text/x-php'                                                                => 'php',
            'application/x-httpd-php-source'                                            => 'php',
            'image/png'                                                                 => 'png',
            'image/x-png'                                                               => 'png',
            'application/powerpoint'                                                    => 'ppt',
            'application/vnd.ms-powerpoint'                                             => 'ppt',
            'application/vnd.ms-office'                                                 => 'ppt',
            'application/msword'                                                        => 'doc',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop'                                                   => 'psd',
            'image/vnd.adobe.photoshop'                                                 => 'psd',
            'audio/x-realaudio'                                                         => 'ra',
            'audio/x-pn-realaudio'                                                      => 'ram',
            'application/x-rar'                                                         => 'rar',
            'application/rar'                                                           => 'rar',
            'application/x-rar-compressed'                                              => 'rar',
            'audio/x-pn-realaudio-plugin'                                               => 'rpm',
            'application/x-pkcs7'                                                       => 'rsa',
            'text/rtf'                                                                  => 'rtf',
            'text/richtext'                                                             => 'rtx',
            'video/vnd.rn-realvideo'                                                    => 'rv',
            'application/x-stuffit'                                                     => 'sit',
            'application/smil'                                                          => 'smil',
            'text/srt'                                                                  => 'srt',
            'image/svg+xml'                                                             => 'svg',
            'application/x-shockwave-flash'                                             => 'swf',
            'application/x-tar'                                                         => 'tar',
            'application/x-gzip-compressed'                                             => 'tgz',
            'image/tiff'                                                                => 'tiff',
            'text/plain'                                                                => 'txt',
            'text/x-vcard'                                                              => 'vcf',
            'application/videolan'                                                      => 'vlc',
            'text/vtt'                                                                  => 'vtt',
            'audio/x-wav'                                                               => 'wav',
            'audio/wave'                                                                => 'wav',
            'audio/wav'                                                                 => 'wav',
            'application/wbxml'                                                         => 'wbxml',
            'video/webm'                                                                => 'webm',
            'audio/x-ms-wma'                                                            => 'wma',
            'application/wmlc'                                                          => 'wmlc',
            'video/x-ms-wmv'                                                            => 'wmv',
            'video/x-ms-asf'                                                            => 'wmv',
            'application/xhtml+xml'                                                     => 'xhtml',
            'application/excel'                                                         => 'xl',
            'application/msexcel'                                                       => 'xls',
            'application/x-msexcel'                                                     => 'xls',
            'application/x-ms-excel'                                                    => 'xls',
            'application/x-excel'                                                       => 'xls',
            'application/x-dos_ms_excel'                                                => 'xls',
            'application/xls'                                                           => 'xls',
            'application/x-xls'                                                         => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
            'application/vnd.ms-excel'                                                  => 'xlsx',
            'application/xml'                                                           => 'xml',
            'text/xml'                                                                  => 'xml',
            'text/xsl'                                                                  => 'xsl',
            'application/xspf+xml'                                                      => 'xspf',
            'application/x-compress'                                                    => 'z',
            'application/x-zip'                                                         => 'zip',
            'application/zip'                                                           => 'zip',
            'application/x-zip-compressed'                                              => 'zip',
            'application/s-compressed'                                                  => 'zip',
            'multipart/x-zip'                                                           => 'zip',
            'text/x-scriptzsh'                                                          => 'zsh',
        ];

        return isset($mime_map[$mime]) === true ? $mime_map[$mime] : false;
    }

  //full $filePath file exist in server.  
  public function getFileTypeForSendingToTelegram($filePath){
    //$mime = self::GetMIMEtype($filePath);
    
    //if ($mime==false)return false;
    $mime=mime_content_type($filePath);
    //var_dump($mime);
    $mime = self::mime2ext($mime);
   
    if($mime <> '')
      if(self::searchThroughArray($mime, ['mp4','mkv','avi','mov']))
        return 'Video';
    
    if($mime <> '')
      if(self::searchThroughArray($mime, ['gif','jpg','tiff','tif','jpeg','bmp','png']))
        return 'Photo';
    
    if($mime <> '')
      if(self::searchThroughArray($mime, ['mp3','wav','aiff','wma']))
        return 'Audio';
    if($mime == 'ogg')
      return 'Voice';
    
    return 'Document';
  }
  
}
