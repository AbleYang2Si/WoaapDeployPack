<?php

namespace Woaap\Deploy\Controllers;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class LogController extends Controller
{

    public function search(Request $request)
    {
        //校验签名
        $timestamp = $request->input('timestamp', 0);
        if (abs($timestamp - time()) > 20)
            abort(403, 'timestamp is fail');

        if (!Hash::check(implode('', $request->only('keywords', 'date', 'timestamp')) . env('APP_SIGN_SALT'), $request->input('sign')))
            abort(403, 'sign check fail');

        $keywords = $request->input('keywords');
        $date = $request->input('date', Carbon::now()->toDateString());
        if (empty($date)) {
            $date = Carbon::now()->toDateString();
        }
        $isOutRawdata = $request->input('isOutRawdata', false);

        $services = explode(',', env('APP_HA'));
//        $local = $request->server('SERVER_ADDR');

        // 获取本地日志
        $command = 'cat ' . storage_path('logs/laravel-' . $date . '.log');
        foreach (explode('|', $keywords) as $keyword) {
            if ($keyword === '' || is_null($keyword))
                continue;

            $command .= " | grep '" . $keyword . "'";
        }

        //通过ha节点数只能判断当前获取条数
        $limit = count($services) ? ceil(200 / count($services)) : 200;

        $command .= ' | tail -' . $limit;

        if ($services <= 1) {
            $logs = self::commandRun($command);
        } else {
            if ($isOutRawdata) {
                $logs = self::commandRun($command);

                return $logs;
            }

            $logs = [];
            // 获取其它节点数据
            foreach ($services as $service) {
                // 处理data数据
                $url = 'http://' . $service . ':' . $request->server('SERVER_PORT') . $request->getPathInfo();
                $data = $this->httpGet($url, ($request->all() + ['isOutRawdata' => 1]));

                if (empty($data))
                    continue;

                $logs = array_merge($logs, $data);
            }
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

    public static function commandRun($command)
    {
        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $resultContent = $process->getOutput();

        return explode(PHP_EOL, $resultContent);
    }
}
