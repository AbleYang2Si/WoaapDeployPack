<?php

namespace Woaap\Deploy\Controllers;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LogController extends Controller
{

    public function search(Request $request)
    {
        $keywords = $request->input('keywords');
        $date = $request->input('date', Carbon::now()->toDateString());
        if (empty($date)) {
            $date = Carbon::now()->toDateString();
        }
        $isOutRawdata = $request->input('isOutRawdata', false);

        $services = explode(',', env('APP_HA'));
        $local = $request->server('SERVER_ADDR');

        // 获取本地日志
        $command = 'cat ' . storage_path('logs/laravel-' . $date . '.log');
        foreach (explode('|', $keywords) as $keyword) {
            if ($keyword === '' || is_null($keyword))
                continue;

            $command .= " | grep '" . $keyword . "'";
        }

        $command .= ' | tail -200';

        exec($command, $logs);

        if ($isOutRawdata) {
            return $logs;
        }
        // 获取其它节点数据
        foreach (array_diff($services, [$local]) as $service) {
            // 处理data数据
            $url = 'http://' . $service . $request->getPathInfo();
            $data = $this->httpGet($url, ($request->all() + ['isOutRawdata' => 1]));

            if (empty($data))
                continue;

            $logs = array_merge($logs, $data);
        }

        $resultLogs = collect();
        foreach ($logs as $log) {
            $dateStr = substr($log, 1, 19);

            if (empty(strtotime($dateStr)))
                continue;

            $resultLogs->push($log);
        }

        return $resultLogs->sortBy(function ($log) {
            $dateStr = substr($log, 1, 19);

            return strtotime($dateStr);
        })->toJson();
    }

    public function searchLocal(Request $request) {
        if (strpos($request->server('HTTP_HOST'), '10.') === 0) {
            return $this->search($request);
        }
    }

    public function httpGet($url, $params)
    {
        $client = new Client(['timeout' => 10]);

        $response = $client->request('get', $url, [
            'query' => $params
        ]);

        if ($response->getStatusCode() != 200)
            return [];

        $envContent = $response->getBody()->getContents();
        return json_decode($envContent, true);
    }
}
