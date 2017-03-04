<?php

use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\Constant\HTTPHeader;
use \LINE\LINEBot\Event\MessageEvent;
use \LINE\LINEBot\Event\MessageEvent\TextMessage;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;

class HomeController extends BaseController {

    /*
    |--------------------------------------------------------------------------
    | Default Home Controller
    |--------------------------------------------------------------------------
    |
    | You may wish to use controllers instead of, or in addition to, Closure
    | based routes. That's great! Here is an example controller method to
    | get you started. To route to this controller, just add the route:
    |
    |   Route::get('/', 'HomeController@showWelcome');
    |
    */

    public function showWelcome()
    {
        return View::make('hello');
    }

    public function postLine()
    {
        $httpClient = new CurlHTTPClient('xwRQOgv+qz7hj7fUhOqsp44Lr6NSRAkReLr8IEm9pgpi44LJmXkXBsaH1VsuLywL0aa7uf85NGFJBRouESydj9FEjhQaYNqzRkkabjCxVUnmasf6AjB6Aee7E3jYw8GV9/DOMeC0xq+jJdJ036+V0gdB04t89/1O/w1cDnyilFU=');
        $bot = new LINEBot($httpClient, ['channelSecret' => 'fb909f7ddd902047b665a492f295476d']);

        $signature = Request::header(HTTPHeader::LINE_SIGNATURE);

        Log::info('[LINE] Info signature 1: ' . $signature);

        if (empty($signature)) {
            Log::error('[LINE] Invalid signature 2');
            $response = Response::make('Bad Request', 400);
            return $response;
        }

        // Check request with signature and parse request
        try {
            $request = Request::instance();
            $requestBody = $request->getContent();
            $events = $bot->parseEventRequest($requestBody, $signature);

            foreach ($events as $event) {
                if (!($event instanceof MessageEvent)) {
                    Log::info('[LINE] Text Message Come');
                    continue;
                }
                if (!($event instanceof TextMessage)) {
                    Log::info('[LINE] Non Text Message Come');
                    continue;
                }

                Log::info('[LINE] Reply from User: ' . $event->getText());

                $data = new stdclass();
                switch (strtolower($event->getText())) {
                    case 'random':
                        $data = $this->getRandomImage();
                        break;

                    default:
                        $data = $this->getImageByKeyword($event->getText());
                        break;
                }

                Log::info('[LINE] Image: ' . serialize($data));

                $MultiMessageBuilder = new MultiMessageBuilder();
                if (isset($data->author)) {
                    $textMessageBuilder = new TextMessageBuilder($data->author);
                    $ImageMessageBuilder = new ImageMessageBuilder($data->url, $data->thumb);
                    $MultiMessageBuilder->add($textMessageBuilder);
                    $MultiMessageBuilder->add($ImageMessageBuilder);
                } else {
                    $textMessageBuilder = new TextMessageBuilder('Cannot find the image you are looking for. Try another keyword. (moon wink)');
                    $MultiMessageBuilder->add($textMessageBuilder);
                }
                Log::info('[LINE] Reply Token: ' . $event->getReplyToken());
                Log::info('[LINE] Text Message: ' . serialize($textMessageBuilder));
                $botResponse = $bot->replyMessage($event->getReplyToken(), $MultiMessageBuilder);

                // $botResponse = str_replace("\0","[NULL]",$botResponse);
                Log::info('[LINE] LINEBot Response: ' . $botResponse->getRawBody());
            }

            $response = Response::make('Success', 200);
            return $response;
        } catch (\LINE\LINEBot\Exception\InvalidSignatureException $e) {
            Log::error('[LINE] Invalid signature');
            return Response::make('[LINE] Invalid signature', 400);
        } catch (\LINE\LINEBot\Exception\UnknownEventTypeException $e) {
            Log::error('[LINE] Unknown event type has come');
            return Response::make('[LINE] Unknown event type has come', 400);
        } catch (\LINE\LINEBot\Exception\UnknownMessageTypeException $e) {
            Log::error('[LINE] Unknown message type has come');
            return Response::make('[LINE] Unknown message type has come', 400);
        } catch (\LINE\LINEBot\Exception\InvalidEventRequestException $e) {
            Log::error('[LINE] Invalid event request');
            return Response::make('[LINE] Invalid event request', 400);
        } catch (Exception $e) {
            return Response::make('[LINE] General Error: ' . $e->getMessage(), 400);
        }
    }

    public function getRandomImage()
    {
        $url = 'https://api.unsplash.com/photos/random';
        $img = json_decode($this->getCurl($url));

        $data = new stdclass();

        if (is_object($img)) {
            $imgUrl = $img->urls->regular;
            $imgThumbUrl = $img->urls->thumb;
            $imgAuthor = $img->user->name;
            $data->url = $imgUrl;
            $data->author = $imgAuthor;
            $data->thumb = $imgThumbUrl;
        }

        return $data;
    }

    public function getImageByKeyword($keyword = '')
    {
        $keyword = empty($keyword) ? Input::get('keyword') : $keyword;
        $url = 'https://api.unsplash.com/search/photos?query=' . $keyword;
        $imgs = json_decode($this->getCurl($url));

        $data = new stdclass();
        if (count($imgs->results) > 0) {
            $randImgKey = array_rand($imgs->results);
            $img = $imgs->results[$randImgKey];
            $imgUrl = $img->urls->regular;
            $imgThumbUrl = $img->urls->thumb;
            $imgAuthor = $img->user->name;
            $data->url = $imgUrl;
            $data->author = $imgAuthor;
            $data->thumb = $imgThumbUrl;
        }

        return $data;
    }

    protected function getCurl($uri, $flag = false)
    {
        $output = "";
        try {
            $ch = curl_init($uri);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer 8045aedab8b74c5a7f0fae2b04fddc086484349d897682f86b5686a79dde58ef'
            ));
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $flag);
            $output = curl_exec($ch);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return $output;
    }

}
