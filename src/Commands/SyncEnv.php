<?php

namespace Woaap\Deploy\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SyncEnv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:env';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sync env';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $url = 'http://127.0.0.1:8000/api/fetchConfContent';

        $params['app_name'] = config('app.name');
        $params['app_env'] = config('app.env');
        $params['timestamp'] = time();
        $params['sign'] = Hash::make(implode('', $params) . env('APP_SIGN_SALT'));

        $client = new Client();
        $response = $client->request('get', $url, [
            'query' => $params
        ]);

        $envContent = $response->getBody()->getContents();
        file_put_contents(base_path('.env'), $envContent);
        $this->info('sync env success.....................................');
    }
}
