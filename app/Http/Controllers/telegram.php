<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Telegram\Bot\Api;

class telegram extends Controller
{
    /**
     * @var Api()
     */
    private $telegram;

    private $channel;

    function getFileUrl($file){
        $file  = $this->telegram->getFile(['file_id' => $file]);
        return 'https://api.telegram.org/file/bot'.config('telegram.bot_token').'/'.$file->getFilePath();
    }

    function clrStr($str){

        $words = [
            '@Campe85',
            '@Tamasha_channel'
        ];

        foreach ($words as $word) {
            $str = str_ireplace($word, '', $str);
        }

        return $str;
    }

    function getCaptionFromMessage($message){
        $caption = $message->has('caption') ? $message->getCaption() : '';
        return $this->clrStr($caption);
    }

    function getTextFromMessage($message){
        $text = $message->has('text') ? $message->getText() : '';
        return $this->clrStr($text);
    }

    function addSignature($message){
        return implode("\n\n", [trim($message), '💟 @telegfa']);
    }

    public function handle()
    {
        $this->channel = env('TELEGRAM_CHANNEL');

        $this->telegram = new Api();
        $response = $this->telegram->getWebhookUpdates();
        $message = $response->getMessage();

        if($message->has('photo')){

            $caption = $this->getCaptionFromMessage($message);

            $photo = $message->getPhoto();
            $photo = $photo[count($photo)-1]['file_id'];

            $this->telegram->sendPhoto([
                'chat_id' => $this->channel,
                'photo' => $photo,
                'caption' => $this->addSignature($caption),
                'disable_notification' => true
            ]);

        } elseif($message->has('video')) {

            $caption = $this->getCaptionFromMessage($message);

            $this->telegram->sendVideo([
                'chat_id' => $this->channel,
                'video' => $message->getVideo()->getFileId(),
                'caption' => $this->addSignature($caption),
                'disable_notification' => true
            ]);

        } elseif($message->has('document')) {

            $document = $message->getDocument();
            switch ($document->getMimeType()){
                case 'video/mp4':

                    $caption = $this->getCaptionFromMessage($message);

                    $this->telegram->sendVideo([
                        'chat_id' => $this->channel,
                        'video' => $this->getFileUrl($document->getFileId()),
                        'caption' => $this->addSignature($caption),
                        'disable_notification' => true
                    ]);

                    break;
            }

        } elseif($message->has('text')) {

            $caption = $this->getTextFromMessage($message);

            $this->telegram->sendMessage([
                'chat_id' => $this->channel,
                'text' => $caption,
                'disable_notification' => true
            ]);

        }
    }
}
