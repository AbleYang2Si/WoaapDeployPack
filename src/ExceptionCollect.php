<?php

namespace Woaap\Deploy;

use GuzzleHttp\Client;
class ExceptionCollect
{
    const DEPLOY_URL = 'http://deploy.woaap.com';

    /**
     * Notes: 异常收集
     * User: mark.gao
     * Date: 2021/3/15 13:43
     * @param $html
     * @return bool|mixed
     */
    public function collect($html)
    {
        $params = [
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'app_sign' => env('APP_SIGN_SALT'),
            'html_content' => $html
        ];
        return $this->httpPost($params);
    }

    public function httpPost($params)
    {
        $client = new Client(['timeout' => 10, 'verify' => false]);

        $response = $client->request('post', self::DEPLOY_URL . '/exception/collect', [
            'http_errors' => false,
            'json' => $params
        ]);
        if ($response->getStatusCode() != 200)
            return false;

        $envContent = $response->getBody()->getContents();
        return json_decode($envContent, true);
    }
}
