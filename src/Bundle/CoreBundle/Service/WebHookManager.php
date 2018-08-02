<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 02.08.18
 * Time: 15:43
 */

namespace UniteCMS\CoreBundle\Service;

use GuzzleHttp\Client;

class WebHookManager
{
    public function fire() {

        $client = new Client();

        $res = $client->request('GET', 'https://www.orf.at');
        #echo $res->getStatusCode();
        #echo $res->getBody();

        #exit;

    }

}