<?php

namespace Woaap\Deploy\Controllers;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Linfo\Linfo;
use Illuminate\Http\Request;


class SinfoController extends Controller
{
    public function check(Request $request)
    {
        //校验签名
        $timestamp = $request->input('timestamp', 0);
        if (abs($timestamp - time()) > 20)
            abort(403, 'timestamp is fail');

        if (!Hash::check(implode('', $request->only('timestamp')) . env('APP_SIGN_SALT'), $request->input('sign')))
            abort(403, 'sign check fail');

        $services = explode(',', env('APP_HA'));
        $data = [];

        if (count($services) <= 1 or request('isOutRawdata')) {
            $linfo = new Linfo();
            $linfo->getParser();

            $linfo->determineCPUPercentage();
            $sInfo['cpu'] = $linfo->getCPU();
            $sInfo ['ram'] = $linfo->getRam();

            $data[env('APP_NAME')] = $sInfo;
        } else {
            foreach ($services as $service) {
                // 处理data数据
                $url = 'http://' . $service . ':' . $request->server('SERVER_PORT') . $request->getPathInfo();
                $response = $this->httpGet($url, ($request->all() + ['isOutRawdata' => 1]));

                $data[env('APP_NAME') . '-' . $service] = $response;
            }
        }

        return $data;
    }

    public function httpGet($url, $params)
    {
        $client = new Client(['timeout' => 3]);

        $response = $client->request('get', $url, [
            'query' => $params
        ]);

        if ($response->getStatusCode() != 200)
            return false;

        $envContent = $response->getBody()->getContents();
        return json_decode($envContent, true);
    }
}
