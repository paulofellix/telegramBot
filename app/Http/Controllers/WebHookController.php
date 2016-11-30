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

      switch (strtolower($text)) {
        case '/start':
          $msg = 'Oi, eu sou o bot Paulo Félix ;)';
          break;
        case '/getchamados':
          $sessionId = $this->openGLPISession();
          $msg = $this->getMeusChamados($sessionId, 'paulo.felix');
          $this->killGLPISession($sessionId);
          break;
        default:
            $msg = sprintf('Oi %s, você disse:

<b>%s</b>',$fromName,$text);
          break;
      }

      $data = array('chat_id'=>$chatId,'text'=> $msg,'parse_mode'=>'HTML');
      // return $msg;
      return Requests::post('https://api.telegram.org/bot'.$_ENV['BOT_TOKEN'].'/sendMessage',array(),$data);
    }

    function openGLPISession()
    {
      $headers = array('Authorization'=>$_ENV['GLPI_USER_TOKEN'],'App-Token'=>$_ENV['GLPI_APP_TOKEN']);
      $session = Requests::get($_ENV['GLPI_API_URL'].'initSession',$headers);
      return json_decode($session->body)->session_token;
    }

    function killGLPISession($sessionId)
    {
      $this->makeGLPIRequest('killSession',$sessionId);
    }

    public function getMeusChamados($sessionId,$userName)
    {
      $response = $this->makeGLPIRequest('search/User?criteria[0][field]=1&criteria[0][searchtype]=contains&criteria[0][value]='.$userName.'&uid_cols&forcedisplay[0]=2&forcedisplay[1]=9', $sessionId);
      $user = json_decode($response->body,true)['data'][0];
      $response = $this->makeGLPIRequest('search/Ticket?criteria[0][field]=12&criteria[0][searchtype]=contains&criteria[0][value]=1&criteria[1][link]=OR&criteria[1][field]=12&criteria[1][searchtype]=contains&criteria[1][value]=2&criteria[2][link]=OR&criteria[2][field]=12&criteria[2][searchtype]=contains&criteria[2][value]=3&criteria[3][link]=OR&criteria[3][field]=12&criteria[3][searchtype]=contains&criteria[3][value]=4&forcedisplay[0]=2&forcedisplay[1]=15&forcedisplay[2]=21&forcedisplay[3]=4&forcedisplay[4]=5&uid_cols=true&order=DESC', $sessionId);
      $data = json_decode($response->body,true)['data'];
      $tickets = array();
      foreach ($data as $ticket) {
        if ($ticket['Ticket.Ticket_User.User.name'] == $user['User.id'])
          $tickets[] = $ticket;
      }

      $msg = sprintf('Ola %s você tem um total de %d chamados em aberto, segue abaixo:

',$user['User.firstname'],count($tickets));
      foreach ($tickets as $ticket) {
        $msg .= '#Chamado '.$ticket['Ticket.id'].'
';
        $msg .= 'Titulo: '.$ticket['Ticket.name'].'
';
        $msg .= 'Status: ';
        switch ($ticket['Ticket.status']) {
          case 1:
             $msg .= 'Novo';
            break;
          case 2:
            $msg .= 'Processando (Atribuído)';
          case 3:
            $msg .= 'Processando (Planejado)';
          case 4:
            $msg .= 'Pendente';
          case 5:
            $msg .= 'Solucionado';
          case 6:
            $msg .= 'Fechado';
          default:
            break;
        }
      }
      dd($msg);

    }

    public function getGLPIHeaders($sessionId)
    {
      return array('Session-Token'=>$sessionId,'App-Token'=>$_ENV['GLPI_APP_TOKEN']);
    }

    public function makeGLPIRequest($endPoint,$sessionId)
    {
      $headers = $this->getGLPIHeaders($sessionId);
      return Requests::get($_ENV['GLPI_API_URL'].$endPoint,$headers);
    }
}
