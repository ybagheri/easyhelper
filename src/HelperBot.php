<?php

namespace Ybagheri;


use Ybagheri\EasyDatabase;
use Ybagheri\EasyHelper;
use Ybagheri\GeneralHelper;

trait HelperBot
{

    use GeneralHelper;
    public function getInfo($captionLists, $tagLists = [])
    {
        try {
            $caption = "";
            if (isset($captionLists)) {
                foreach ($captionLists as $key => $value) {
                    if ($value['state'] == 1) {
                        $caption = $value['info']['caption'];
                    }
                }
            }
            if ($tagLists !== []) {
                foreach ($tagLists as $key => $value) {
                    if ($value['state'] == 1) {
                        return array('title' => $value['info']['title'], 'performer' => $value['info']['performer'], 'caption' => $caption);
                    }
                }
            }
            return array('caption' => $caption);

        } catch (Exception $e) {
            return false;
        }
    }

    public function sendBatchFile($path, $withDel = false, $sleep = 3, $caption = null, $AudioTitle = null, $blnAppendTitle = false, $AudioPerformer = null, $blnAppendPerformer = false,$title=null,$performer=null,$titleStartChar=null,$titleLengthChar=null,$performerStartChar=null,$performerLengthChar=null, $fileNameAsTitle=false, $fileNameAsPerformer=false)
    {
        $files = GeneralHelper::allFileInDir($path);

        $counter = 0;
        if (!empty($files)) {
            foreach ($files as $file) {
                $fileType = self::fileType($file);
                if ($fileType == 'audio') {

                    if(isset($titleStartChar) || isset($performerStartCharc) || $blnAppendTitle || $blnAppendPerformer ) {
                        try {
                            $getID3 = new \getID3;
                            $ThisFileInfo = $getID3->analyze($file);
                            \getid3_lib::CopyTagsToComments($ThisFileInfo);

                        } catch (\getid3_exception $e) {
                        }


                        if ($blnAppendTitle && isset($ThisFileInfo)) {
                            $AudioTitle = $ThisFileInfo['comments_html']['title'][0] . ' ' . $AudioTitle;
                        }
                        if ($blnAppendPerformer && isset($ThisFileInfo)) {
                            $AudioPerformer = $ThisFileInfo['comments_html']['artist'][0] . ' ' . $AudioPerformer;
                        }




                        if (isset($titleStartChar) && isset($titleLengthChar)&& isset($ThisFileInfo)) {

                            //should be in this format:
                            //start-length ex: ---> 12-7
                            //0 is first charactr.
                            if($fileNameAsTitle==false){
                                $AudioTitle = $ThisFileInfo['comments_html']['title'][0];
                                $AudioTitle = substr($AudioTitle, $titleStartChar, $titleLengthChar);
                            }else{

                                $AudioTitle = pathinfo($ThisFileInfo['filename'], PATHINFO_FILENAME);
                                $AudioTitle = substr($AudioTitle, $titleStartChar, $titleLengthChar);
                            }


                        }

                        if (isset($performerStartCharc) && isset($performerLengthChar)&& isset($ThisFileInfo)) {

                            //should be in this format:
                            //start-length ex: ---> 12-7
                            //0 is first charactr.
                            if($fileNameAsPerformer==false){
                                $AudioPerformer = $ThisFileInfo['comments_html']['artist'][0];

                                $AudioPerformer = substr($AudioPerformer, $performerStartChar, $performerLengthChar);

                            }else{
                                $AudioPerformer = pathinfo($ThisFileInfo['filename'], PATHINFO_FILENAME);
                                $AudioPerformer = substr($AudioPerformer, $performerStartChar, $performerLengthChar);

                            }

                        }

                        if(! is_null($title)){
                            $AudioTitle=$title;

                        }

                        if(! is_null($performer)){
                            $AudioPerformer=$performer;
                        }
                    }


                    $playTime = self::getPlayTimeAudio($file);
                    $playTime = ($playTime) ? $playTime['playtime_seconds'] : null;
                    $fields = ['caption' => $caption, 'performer' => $AudioPerformer, 'title' => $AudioTitle,'duration' => $playTime];

                } else {
                    $fields = ['caption' => $caption];
                }


                $res = $this->upload($fileType, $file, $fields);

                if (!empty($res) && $res->ok) {
                    $counter++;
                }
                if ($withDel && file_exists($file)) {
                    unlink($file);
                }
                sleep($sleep);
            }
            return ['count' => count($files), 'sent' => $counter];
        }

        return ['count' => 0, 'sent' => 0];
    }


    public function getFileId($type, $message)
    {
        if ($type != 'photo') {
            $file_id = $message->$type->file_id;
        } else {
            $photo = $message->photo;
            $photos = array_reverse($photo);
            $file_id = $photos[0]->file_id;
        }
        return $file_id;
    }

    public function upload($type, $path = null, $fields = [], $file_id = null)
    {

        $allFields = [
            'chat_id' => $this->fromId,
            'photo' => null,
            'caption' => null,
            'parse_mode' => null,
            'disable_notification' => null,
            'reply_to_message_id' => null,
            'reply_markup' => null,
            'audio' => null,
            'duration' => null,
            'performer' => null,
            'title' => null,
            'document' => null,
            'video' => null,
            'width' => null,
            'height' => null,
            'supports_streaming' => null,
            'voice' => null,
        ];

        if(! empty($fields)){
            foreach ($fields as $key => $value) {
                $allFields[$key] = $fields[$key];
            }
        }
        if (is_null($file_id)) {
            //Upload to Telegram
            if (function_exists('curl_file_create')) { // php 5.6+
                $cFile = curl_file_create($path);
            } else {
                $cFile = '@' . realpath($path);
            }
            $allFields[$type] = $cFile;
        } else {
            $allFields[$type] = $file_id;
        }

//        if (isset($message->caption)) {
//            $allFields['caption'] = $message->caption;
//        }

//        if (! is_null($ReplyMarkp)) {
//            $allFields['reply_markup'] = json_encode($ReplyMarkp);
//        }

        if ($type != 'video_note') {
            $method = 'send' . ucfirst($type);
        } else {
            $method = 'sendVideoNote';
        }
//        \Ybagheri\EasyHelper::telegramHTTPRequest($this->environment["BOT_TOKEN"),'sendMessage',['chat_id' => $this->fromId,'text' =>var_export($allFields,true)]);
        //var_dump($allFields);
        $allFields = $this->removeNullValue($allFields);
        //var_dump($allFields);
        return $this->$method($allFields);
    }

    public function createInlineQueryResult($inline_query)
    {
        $toset = ['inline_query_id' => $inline_query->id, 'results' => json_encode([]), 'cache_time' => 0, 'is_personal' => true];
        $results = [];
        $i = 0;
        $inlineQuery = $inline_query->query;

        if (substr($inlineQuery, 0, 3) == 'To:') {
            if (is_array($this->destinationList) && !empty($this->destinationList)) {
                foreach ($this->destinationList as $dl) {
                    $i++;
                    $message_text = 'To: ' . $dl['type'] . ' ' . $dl['name'];//json_encode(['inlineMode' => true, 'sendTo' => $dl['chat_id'], 'file_id' => $fileId, 'type' => $type, 'caption' => $caption]);
                    $jsonData = json_encode(['inline' => 'remove_sendto', 'sendto' => ['chat_id' => $dl['chat_id']]]);
                    $strId = '/addto/' . $dl['chat_id'] . '/' . $this->fromId . '/' . $dl['type'];
                    $results[] = [
                        'id' => $strId,
                        'type' => 'article',
                        'title' => (string)$dl['name'],
                        'description' => 'type: ' . $dl['type'],
//                                    'thumb_url' => $this->urlProfile($dl['chat_id']),
                        'input_message_content' => [
                            'message_text' => $message_text,
                            'parse_mode' => 'HTML'
                        ],
                        "reply_markup" => ['inline_keyboard' => [
                            [ // Row 1
                                ['text' => "✕ " . $dl['type'] . ' ' . $dl['name'], 'callback_data' => $jsonData]
                            ],
                        ],
                        ],
                    ];


                }

                $toset['results'] = json_encode($results);


            } else {
                $toset['switch_pm_text'] = 'empty list';
                $toset['switch_pm_parameter'] = '_';
            }

            return self::answerInlineQuery($toset);
        } elseif (substr($inlineQuery, 0, 7) == 'Delete:') {
            if (is_array($this->destinationList) && !empty($this->destinationList)) {
                foreach ($this->destinationList as $dl) {
                    $i++;

                    $message_text = 'Deleted.';
                    $strId = '/deleteId/' . $dl['chat_id'] . '/' . $this->fromId;
                    $results[] = [
                        'id' => $strId,
                        'type' => 'article',
                        'title' => (string)$dl['name'],
                        'description' => 'type: ' . $dl['type'] . '   touch to delete from list',
                        'thumb_url' => self::urlProfile($dl['chat_id']),
                        'input_message_content' => [
                            'message_text' => $message_text,
                            'parse_mode' => 'HTML'
                        ],
                    ];


                }

                $toset['results'] = json_encode($results);
            } else {
                $toset['switch_pm_text'] = 'empty list';
                $toset['switch_pm_parameter'] = '_';
            }

            return self::answerInlineQuery($toset);
        } elseif (substr($inlineQuery, 0, 6) == 'Title:') {
            self::searchTitleOrPerformerInline($inline_query, substr($inlineQuery, 6), 'title');
        } elseif (substr($inlineQuery, 0, 10) == 'Performer:') {
            self::searchTitleOrPerformerInline($inline_query, substr($inlineQuery, 10), 'performer');

        } elseif (substr($inlineQuery, 0, 8) == 'Caption:') {
            self::searchCaptionInline($inline_query, substr($inlineQuery, 8));
        }


        return false;
    }

    public function typeFromTelegramMessage($message)
    {
        if (isset($message->photo)) {
            return 'photo';
        } elseif (isset($message->video)) {
            return 'video';
        } elseif (isset($message->audio)) {
            return 'audio';
        } elseif (isset($message->document)) {
            return 'document';
        } elseif (isset($message->voice)) {
            return 'voice';
        } elseif (isset($message->video_note)) {
            return 'video_note';
        } elseif (isset($message->animation)) {
            return 'animation';
        } elseif (isset($message->game)) {
            return 'game';
        } elseif (isset($message->sticker)) {
            return 'sticker';
       } elseif (isset($message->poll)) {
        return 'poll';
       } elseif (isset($message->dice)) {
        return 'dice';
        }
        return false;
    }



    public function add2ChannelOrGroupList($cht_id, $title, $type)
    {
        $blnExist = false;

        foreach ($this->destinationList as $key => $value) {

            if (isset($value['chat_id']) && $value['chat_id'] == $cht_id) {

                return ['ok' => false, 'result' => 'duplicate'];
            }
        }


        $this->destinationList[count($this->destinationList)] = ['name' => $title, 'chat_id' => $cht_id, 'type' => $type];
        return ['ok' => file_put_contents($this->storage . DIRECTORY_SEPARATOR . $this->fromId . DIRECTORY_SEPARATOR . 'destinationList.json', json_encode($this->destinationList)), 'result' => 'sucess'];


    }

    public function sendOriginalAndWithoutCaption($type, $message)
    {
        $fileId = self::getFileId($type, $message);
        $caption = isset($message->caption)?$message->caption:null;
        if ($type == 'audio') {
            $res = self::upload($type, null, ['caption' => $caption, 'reply_markup' => json_encode($this->sendToAudioReply)], $fileId);
        }elseif ($type == 'photo') {

            $res = self::upload($type, null, ['caption' => $caption, 'reply_markup' => json_encode($this->sendToPhotoReply)], $fileId);

        } else {
            $res = self::upload($type, null, ['caption' => $caption, 'reply_markup' => json_encode($this->sendToReply)], $fileId);
        }

//        $this->forwardMessage(['chat_id' => $this->spyChannelid, 'from_chat_id' => $this->fromId, 'message_id' => $message->message_id]);

        if (isset($this->setting[$this->fromId]['force_reply']) && $this->setting[$this->fromId]['force_reply']) {
            $res = self::upload($type, null, ['reply_markup' => json_encode($this->forceReply)], $fileId);

        }
    }

    public function urlProfile($groupid)
    {
        $url = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $url .= $_SERVER['HTTP_HOST'] . htmlspecialchars($_SERVER['REQUEST_URI']);
        $url = dirname($url) . '/' . $this->publicPath . '/' . $this->fromId . '/profile/' . $groupid . '.jpg';
        return $url;
    }

    public function downloadProfilePhoto($getChat, $groupid)
    {
        if (isset($getChat->result->photo) && isset($getChat->result->photo->small_file_id)) {
            $getFile = $this->getFile(['file_id' => $getChat->result->photo->small_file_id]);
            $fileurl = 'https://api.telegram.org/file/bot' . $this->botToken . '/' . $getFile->result->file_path;
            $path = $this->storage . DIRECTORY_SEPARATOR . $this->fromId . DIRECTORY_SEPARATOR . 'profile' . DIRECTORY_SEPARATOR . $groupid . '.' . pathinfo($getFile->result->file_path, PATHINFO_EXTENSION);
            if (file_put_contents($path, fopen($fileurl, 'r')) !== false) {
                return $path;
            }
        }
        return false;

    }

    public function download($type, $message, $file_id = null, $pathDir = null,$newName=null)
    {

        if (is_null($file_id)) {
            $file_id = self::getFileId($type, $message);
        }
        $getFile = $this->getFile(['file_id' => $file_id]);
        if($getFile->ok !== false) {
            $fileurl = 'https://api.telegram.org/file/bot' . $this->token . '/' . $getFile->result->file_path;

            if (!isset($pathDir)) {
                if (is_null($newName)) {
                    $path = $this->storage . DIRECTORY_SEPARATOR . $this->fromId . DIRECTORY_SEPARATOR . 'download' . DIRECTORY_SEPARATOR . pathinfo($getFile->result->file_path, PATHINFO_BASENAME);

                } else {
                    //$this->log($getFile->result->file_path);

                    $path = $this->storage . DIRECTORY_SEPARATOR . $this->fromId . DIRECTORY_SEPARATOR . 'download' . DIRECTORY_SEPARATOR . $newName . '.' . (is_null(pathinfo($getFile->result->file_path, PATHINFO_EXTENSION)) ? 'jpg' : pathinfo($getFile->result->file_path, PATHINFO_EXTENSION));

                }
            } else {
                if (is_null($newName)) {
                    $path = $this->storage . DIRECTORY_SEPARATOR . $this->fromId . DIRECTORY_SEPARATOR . $pathDir . DIRECTORY_SEPARATOR . pathinfo($getFile->result->file_path, PATHINFO_BASENAME);

                } else {
                    $path = $this->storage . DIRECTORY_SEPARATOR . $this->fromId . DIRECTORY_SEPARATOR . $pathDir . DIRECTORY_SEPARATOR . $newName . '.' . (is_null(pathinfo($getFile->result->file_path, PATHINFO_EXTENSION)) ? 'jpg' : pathinfo($getFile->result->file_path, PATHINFO_EXTENSION));

                }
                $dir = $this->storage .DIRECTORY_SEPARATOR. $this->fromId;
                if (!is_dir(realpath($dir . "/$pathDir"))) {
                    mkdir($dir . "/$pathDir");
                }
            }

//            if (file_put_contents($path, fopen($fileurl, 'r')) !== false) {
//                return $path;
//            }

            if (self::downloadWithCurlProgress($type, $this->botToken, $this->fromId, $path, $fileurl) !== false) {
                return array($getFile, $path);
            }
        }



        return false;

    }
    public function downloadUrlContent($url, $message_id)
    {
        $path = $this->storage .DIRECTORY_SEPARATOR. $this->fromId . DIRECTORY_SEPARATOR.'download';

        $file = GeneralHelper::downloadFromUrl($url, $path);

        if ($file !== false) {


            return self::uploadOrExtractAndUploadToTel($message_id, $file);

        } else {
            $this->sendMessage(['chat_id' => $this->fromId, 'text' => $this->lang['download_failed']]);
            return 'download_failed';

        }
    }

    public function fileType($file = null, $mimetype = null)
    {

        if (is_null($mimetype)) {
            $mimetype = GeneralHelper::GetMIMEtype($file);
        }

        $type = explode("/", $mimetype)[0];
        switch ($type) {
            case 'audio':
                return 'audio';
                break;
            case 'video':
                return 'video';
                break;
            case 'image':
                return 'photo';
                break;
            case 'application':
                return 'document';
                break;
            default:
                return 'document';
        }
    }


    public function sendMessage2AllBotUsers($textMessage)
    {


        $cnn = new EasyDatabase();
        $sql = "SELECT `lastuser_tableid` FROM `sendmsghooks` WHERE `botuser_id`=$this->botUserId ";

        $lastuserResult = $cnn->query($sql);
        if (isset($lastuserResult['result'][0]['lastuser_tableid'])) {
//            if ($lastuserResult['result'][0]['lastuser_tableid'] ==0){
            //lastuser_tableid = 0 => it hasn't started.
            $sql = "SELECT `id`,`user_id` FROM `users` ORDER BY id ASC";
            $result = $cnn->query($sql);
            $startStart = time();
            $start = $startStart;
            $counter = 0;
            foreach ($result['result'] as $rst) {
                if ($lastuserResult['result'][0]['lastuser_tableid'] == 0 || $rst['id'] >= $lastuserResult['result'][0]['lastuser_tableid']) {
                    if ((time() - $start < 1 && $counter >= 25)) {
                        sleep(1);
                        $start = time();
                        $counter = 0;
                    } elseif (time() - $startStart >= 26) {
                        //save state.
                        $sql = "UPDATE `sendmsghooks` SET `lastuser_tableid`=" . $rst['id'] . ",`updated_at`=now() WHERE `botuser_id`=$this->botUserId ";
                        $result = $cnn->query($sql);
                        if (!$result['ok']) {
//                                $res = $this->sendMessage(['chat_id' => $this->superAdmins[0], 'text' => "somthing wrong in db. user". $rst['user_id']." \n should be *lastuser_tableid* in *sendmsghooks* table. \n note: message hasn't sent to all ", 'parse_mode' => 'Markdown']);
                            $res = $this->sendMessage(['chat_id' => $this->superAdmins[0], 'text' => "can't update db.[tag]" . $sql . "[/tag]"]);

                        }

                        $this->sendMessage(['chat_id' => $this->superAdmins[0], 'text' => 'reply with \\sendmsg command on your message agian. it hasn\'t sent to all.']);
                    }
                    $this->sendMessage(['chat_id' => $rst['user_id'], 'text' => $textMessage]);
                    $counter++;
                }

            }
            $this->sendMessage(['chat_id' => $this->superAdmins[0], 'text' => 'bot send messages to all']);
            $sql = "UPDATE `sendmsghooks` SET `lastuser_tableid`=0,`updated_at`=now() WHERE `botuser_id`=$this->botUserId ";
            $result = $cnn->query($sql);
            if (!$result['ok']) {
                $res = $this->sendMessage(['chat_id' => $this->superAdmins[0], 'text' => "can't update db.[tag]" . $sql . "[/tag]"]);
            } else {
                $this->sendMessage(['chat_id' => $this->superAdmins[0], 'text' => 'update *lastuser_tableid* successfully', 'parse_mode' => 'Markdown']);
            }

//            }
        } else {
            $res = $this->sendMessage(['chat_id' => $this->superAdmins[0], 'text' => "can't connet to db,*lastuser_tableid* could'nt retrieve. try again!!!", 'parse_mode' => 'Markdown']);
            return false;
        }


    }

    public function writeSettings(array $settings = [])
    {
        if (!empty($settings)) {
            foreach ($settings as $key => $value) {
                $this->setting[$this->fromId][$key] = $value;
            }
        }
        return file_put_contents($this->storage .DIRECTORY_SEPARATOR. $this->fromId . "/setting.json", json_encode($this->setting[$this->fromId], true));

    }

    public function addTag($title = "", $performer = "")
    {
        $data = time();
        $this->glTagLists[$this->fromId]["$data"]['info']['title'] = $title;
        $this->glTagLists[$this->fromId]["$data"]['info']['performer'] = $performer;
        $this->glTagLists[$this->fromId]["$data"]['state'] = 0;

        //$this->storage = __DIR__ . '/../' . $this->publicPath;
        $this->storage = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . $this->publicPath;
        $dir = $this->storage .DIRECTORY_SEPARATOR. $this->fromId;
        file_put_contents($dir . "/tagLists.json", json_encode($this->glTagLists[$this->fromId], true));


//        if ($title === "") {
//            $text = "Performer added to list.";
//        } elseif ($performer === "") {
//            $text = "Title added to list.";
//        } else {
//            $text = "Tag added to list.";
//        }
//        $this->sendMessage(['chat_id' => $this->fromId, 'text' => $text]);

    }

    public function addCaption($caption)
    {
        $data = time();
        $this->glCaptionLists[$this->fromId]["$data"]['info']['caption'] = $caption;
        //$this->storage = __DIR__ . '/../' . $this->publicPath;
        $this->storage = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . $this->publicPath;
        $dir = $this->storage .DIRECTORY_SEPARATOR. $this->fromId;
        file_put_contents($dir . "/captionLists.json", json_encode($this->glCaptionLists[$this->fromId], true));

    }

    public function downloadWithCurlProgress($type, $botToken, $chat_id, $savefilepath, $urlpath, $sendeverysecond = 1)
    {
        if(is_null($sendeverysecond)){
            $sendeverysecond=1;
        }
        $sendmessage = false;
        $sendChatAction = false;
        $message_id = 0;
        $timelap = time();
        $timeChatAction = $timelap;
        $fp = fopen($savefilepath, 'w+');
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $urlpath);
        curl_setopt($curl, CURLOPT_TIMEOUT, 1000);
        curl_setopt($curl, CURLOPT_FILE, $fp);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_NOPROGRESS, 0);
        curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, function ($resource, $dltotal, $dlnow, $ultotal, $ulnow) use (&$timelap, $sendeverysecond, $botToken, $chat_id, &$sendmessage, &$message_id, $type, &$timeChatAction, &$sendChatAction) {

            //send every $sendeverysecond second progress time.
            if ((time() - $timelap) >= $sendeverysecond) {
                if($dltotal>0){
                    $emojiProgress = self::progressEmoji(100 * $dlnow / $dltotal);
                }else{
                    $emojiProgress=self::progressEmoji(0);
                }



                if ($sendmessage == false) {
                    $res = EasyHelper::telegramHTTPRequest($botToken, 'sendMessage', ['chat_id' => $chat_id, 'text' => $emojiProgress]);

                    if (isset($res->ok) && $res->ok) {
                        $message_id = $res->result->message_id;
                        $sendmessage = true;
                    }
                } else {
                    $res = EasyHelper::telegramHTTPRequest($botToken, 'editMessageText', ['chat_id' => $chat_id, 'message_id' => $message_id, 'text' => $emojiProgress]);
                }

                $timelap = time();
            }



        });
        $result = curl_exec($curl);
        fclose($fp);
        $res = EasyHelper::telegramHTTPRequest($botToken, 'editMessageText', ['chat_id' => $chat_id, 'message_id' => $message_id, 'text' => $this->progressEmoji(100)]);
        $res = EasyHelper::telegramHTTPRequest($botToken, 'deleteMessage', ['chat_id' => $chat_id, 'message_id' => $message_id,]);

        return $result;

    }

    public function progressEmoji($percentage)
    {

        $progress = "Sending ... \n" . "◽◽◽◽◽◽◽◽◽◽";
        if ($percentage < 10) {
            $progress = "Sending ... \n" . "◾◽◽◽◽◽◽◽◽◽";
        } elseif ($percentage < 20) {
            $progress = "Sending ... \n" . "◾◾◽◽◽◽◽◽◽◽";
        } elseif ($percentage < 30) {
            $progress = "Sending ... \n" . "◾◾◾◽◽◽◽◽◽◽";
        } elseif ($percentage < 40) {
            $progress = "Sending ... \n" . "◾◾◾◾◽◽◽◽◽◽";
        } elseif ($percentage < 50) {
            $progress = "Sending ... \n" . "◾◾◾◾◾◽◽◽◽◽";
        } elseif ($percentage < 60) {
            $progress = "Sending ... \n" . "◾◾◾◾◾◾◽◽◽◽";
        } elseif ($percentage < 70) {
            $progress = "Sending ... \n" . "◾◾◾◾◾◾◾◽◽◽";
        } elseif ($percentage < 80) {
            $progress = "Sending ... \n" . "◾◾◾◾◾◾◾◾◽◽";
        } elseif ($percentage < 90) {
            $progress = "Sending ... \n" . "◾◾◾◾◾◾◾◾◾◽";
        } elseif ($percentage <= 100) {
            $progress = "Sending ... \n" . "◾◾◾◾◾◾◾◾◾◾";
        }
        return $progress;
    }

    public function chatActionType($type)
    {
        if ($type == 'photo') {
            return 'upload_photo';
        } elseif ($type == 'video') {
            return 'upload_video';
        } elseif ($type == 'video note') {
            return 'upload_video_note';
        } elseif ($type == 'audio') {
            return 'upload_audio';
        } elseif ($type == 'document') {
            return 'upload_document';
        }
    }

    public function searchTitleOrPerformerInline($inline_query, $query = null, $titleOrPerformer = 'title')
    {
        $toset = ['inline_query_id' => $inline_query->id, 'results' => [], 'cache_time' => 0, 'is_personal' => true];
        $query = is_null($query) ? $inline_query->query : $query;
        if ($query == '') {
            $arr = [];
            foreach ($this->glTagLists[$this->fromId] as $key => $value) {
                if (isset($value['info'][$titleOrPerformer]) && $value['info'][$titleOrPerformer] != '') {

                    $arr[$key] = $value;
                }

            }
            $tglists = $arr;
        }
//elseif($query=='' && $titleOrPerformer == 'performer' ){
//    $arr=[];
//    foreach ($this->glTagLists[$this->fromId] as $key => $value) {
//        if(isset($value['info']['performer']) && $value['info']['performer'] != ''){
//
//            $arr[] =$value;
//        }
//
//    }
//    $tglists=$arr;
//}
        else {
            $tglists = GeneralHelper::searchThroughArrayWithKey($query, $this->glTagLists[$this->fromId], $titleOrPerformer);

        }


        $i = 0;
        $results = [];
        if ($tglists != false) {
            foreach ($tglists as $key => $value) {
                $i++;

//                $strId = '/'.strtoupper($titleOrPerformer[0]).'/' . substr($value['info'][$titleOrPerformer], 0, 63-strlen($this->fromId)-3) . '/' . $this->fromId;
                $strId = '/' . $titleOrPerformer . '/' . $key . '/' . $this->fromId;


//            $jsonData = json_encode(['ok' => 't', 'id'=>$this->fromId,'d'=>$value['info'][$titleOrPerformer]]);


//                $text = $titleOrPerformer.' selected.';//'Title: ' . $value['info']['title'] . "\x0A" . 'Performer: ' . $value['info']['performer'];
//                if($titleOrPerformer=='title'){
//                    $title='Title: ' . $value['info']['title'];
//                }elseif ($titleOrPerformer=='performer'){
//                    $title='Performer: ' . $value['info']['performer'];
//                }


//                $rows = ['text' => $text, 'callback_data' => "$key"];
                $results[] = [
                    'id' => $strId,
                    'type' => 'article',
                    'title' => $value['info'][$titleOrPerformer],
                    'input_message_content' => [
                        'message_text' => $titleOrPerformer . ' selected.',
                        'parse_mode' => 'HTML'
                    ],
//                    "reply_markup" => ['inline_keyboard' => [
//                        [ // Row 1
//                            $rows,
//                        ],
//                    ],
//                    ],
                ];


            }


            $toset['results'] = json_encode($results);
            $answer = self::answerInlineQuery($toset);
        }


    }

    public function searchCaptionInline($inline_query, $query = null)
    {
        $toset = ['inline_query_id' => $inline_query->id, 'results' => [], 'cache_time' => 0, 'is_personal' => true];
        $query = is_null($query) ? $inline_query->query : $query;

        if ($query == '') {
            $arr = [];
            foreach ($this->glCaptionLists[$this->fromId] as $key => $value) {
                if (isset($value['info']['caption']) && $value['info']['caption'] != '') {

                    $arr[$key] = $value;
                }

            }
            $cptionlists = $arr;
        } else {
            // search through caption
            $cptionlists = GeneralHelper::searchThroughArray($query, $this->glCaptionLists[$this->fromId]);

        }

        $i = 0;
        $results = [];

        if ($cptionlists != false) {

            foreach ($cptionlists as $key => $value) {
//                $i++;
//                $text = 'Caption selected.';// 'Caption name: ' . $value['info']['name'] . "\x0A" . 'Caption: ' . $value['info']['caption'];
//                $rows = ['text' => $text, 'callback_data' => "/"];
                $strId = '/cap/' . $key . '/' . $this->fromId;
                $results[] = [
                    'id' => $strId,
                    'type' => 'article',
                    'title' => $value['info']['caption'],
                    'input_message_content' => [
                        'message_text' => 'Caption selected.',
                        'parse_mode' => 'HTML'
                    ],

                ];

            }
        }
        if ($cptionlists != false) {
            $toset['results'] = json_encode($results);
            $answer = self::answerInlineQuery($toset);

        }
    }

    public function uploadOrExtractAndUploadToTel($message_id, $file)
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        if (($ext == 'rar' && $this->rarExtensionIsEnable) || $ext == 'zip') {
            if (isset($this->setting[$this->fromId]['compressed_password']) && is_array($this->setting[$this->fromId]['compressed_password'])) {
                $arr = GeneralHelper::searchThroughArrayWithKey($message_id, $this->setting[$this->fromId]['compressed_password'], 'message_id');
                $password = $arr[array_keys($arr)[0]]['password'];
            } else {
                $password = '';
            }
            $dest = $this->storage .DIRECTORY_SEPARATOR. $this->fromId . DIRECTORY_SEPARATOR.'uncompress';


            $res = GeneralHelper::unCompress($file, $dest, $password);
            if (isset($res['result']) && $res['result'] == 'Extraction failed (wrong password?)') {
                return $res['result'];
//                    $this->sendMessage(['chat_id' => $this->fromId, 'text' => '']);
            } elseif (isset($res['ok']) && $res['ok']) {
                // remove compressed file.
                GeneralHelper::deleteDirectory($file);

                $res = self::sendBatchFile($res['path'], true);
                $del = GeneralHelper::deleteDirectory($res['path']);
                if ($res['sent'] > 0 && $res['count'] == $res['sent']) {
                    if (isset($arr)) {
                        $this->setting[$this->fromId]['compressed_password'] = GeneralHelper::updateArrayKey($this->setting[$this->fromId]['compressed_password'], $message_id, 'message_id');
                        $this->writeSettings();
                    }

                    $msg = 'upload_successfully';
                } else {
                    $msg = 'upload_not_complete';
                }
                $this->sendMessage(['chat_id' => $this->fromId, 'text' => $this->lang[$msg]]);
                return $msg;


            }


        } else {

            $res = self::upload(self::fileType($file), $file);
            GeneralHelper::deleteDirectory($file);
            if (isset($res) && $res->ok) {
                $msg = 'upload_successfully';
            } else {
                $msg = 'upload_failed';
            }
            $this->sendMessage(['chat_id' => $this->fromId, 'text' => $this->lang[$msg]]);
            return $msg;
        }
    }

    public function uploadOrExtractAndUploadToTelFromLinkFileInServer($file, $password = '',$continueFileName=null,$title=null,$performer=null,$titleStartChar=null,$titleLengthChar=null,$performerStartChar=null,$performerLengthChar=null, $caption = null,$toOrFrom=null, $fileNameAsTitle=false, $fileNameAsPerformer=false,$from=null,$to=null)
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        if (($ext == 'rar' && $this->rarExtensionIsEnable) || $ext == 'zip') {

            $dest = $this->storage .DIRECTORY_SEPARATOR. $this->fromId . DIRECTORY_SEPARATOR.'uncompress';


            $res = GeneralHelper::unCompress($file, $dest, $password,$continueFileName,$toOrFrom,$from,$to);
            
var_dump($res);
            if (isset($res['result']) && $res['result'] == 'Extraction failed (wrong password?)') {

                return $res['result'];
//                    $this->sendMessage(['chat_id' => $this->fromId, 'text' => '']);
            } elseif (isset($res['ok']) && $res['ok']) {
                // remove compressed file.
                if(is_null($toOrFrom)){
                    GeneralHelper::deleteDirectory($file);
                }

                $desPath=$res['path'];


                $res = self::sendBatchFile($desPath, true, 3, $caption ,  null,  false,  null,  false,$title,$performer,$titleStartChar,$titleLengthChar,$performerStartChar,$performerLengthChar, $fileNameAsTitle, $fileNameAsPerformer);
                /*
                $del = GeneralHelper::deleteDirectory($desPath);
                if ($res['sent'] > 0 && $res['count'] == $res['sent']) {
                    $msg = 'upload_successfully';
                } else {
                    $msg = 'upload_not_complete';
                }
                $this->sendMessage(['chat_id' => $this->fromId, 'text' => $this->lang[$msg]]);
                return $msg;*/


            } else {
                $this->sendMessage(['chat_id' => $this->fromId, 'text' => 'ERROR: ' . json_encode($res)]);

            }


        } else {

//            $res = $this->upload($this->fileType($file), $file);
            GeneralHelper::deleteDirectory($file);
            if (isset($res) && $res->ok) {
                $msg = 'upload_successfully';
            } else {
                $msg = 'upload_failed';
            }
            $this->sendMessage(['chat_id' => $this->fromId, 'text' => $this->lang[$msg]]);
            return $msg;
        }
    }

    public function debuglog2($input){
        \Ybagheri\EasyHelper::telegramHTTPRequest('594339351:AAEkDDVIa3djpA8vfF5GsetfX0ypJRYe3Qc', 'sendMessage', [
            'chat_id' => 281693135,
            'text' =>  var_export($input, true)]);

    }

    public function getFileSize($type, $message)
    {
        if ($type != 'photo') {
            $file_size = $message->$type->file_size;
        } else {
            $photo = $message->photo;
            $photos = array_reverse($photo);
            $file_size = $photos[0]->file_size;
        }
        return round($file_size/1000000);
    }


}
