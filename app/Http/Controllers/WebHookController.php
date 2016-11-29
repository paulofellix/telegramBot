<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

use Illuminate\Http\Request;
use Requests;

class WebHookController extends BaseController
{
    public function index(Request $request)
    {
      $update = $request->all()['message'];
      $fromName = $update['from']['first_name'];
      $chatId = $update['chat']['id'];
      $text = $update['text'];
      $data = array('chat_id'=>$chatId,'text'=>'Replay: '.$text);
      Requests::post('https://api.telegram.org/bot'.$_ENV['BOT_TOKEN'].'/sendMessage',array(),$data);
      abort(404);
    }
}
