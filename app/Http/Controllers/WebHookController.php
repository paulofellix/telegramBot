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

      switch ($text) {
        case '/start':
            $msg = 'Oi, eu sou o bot Paulo Félix ;)';
          break;

        default:
            $msg = sprintf('Oi %s, você disse:

<b>%s</b>',$fromName,$text);
          break;
      }

      $data = array('chat_id'=>$chatId,'text'=> $msg,'parse_mode'=>'HTML');
      Requests::post('https://api.telegram.org/bot'.$_ENV['BOT_TOKEN'].'/sendMessage',array(),$data);
      return 'ok';
    }
}
