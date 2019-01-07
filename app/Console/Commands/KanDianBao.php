<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException;
use Yangqi\Htmldom\Htmldom;

class KanDianBao extends Command
{
    use KanDianBaoTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:kandianbao';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '看店宝爬虫';

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
        echo "开始抓取看店宝。。。\n";

        //请求登录地址
        $login_url ='https://my.dianshangyi.com/user/oauth/authorize?response_type=code&client_id=60c8f95b218af67f1eaf7664ef85a533&state=N4FUgJ&redirect_uri=https://www.kandianbao.com/oauth/dsy/callback/&scope=me&next=https://www.kandianbao.com/';
        //请求参数
        $client = new Client(['cookies'=>true]);
        //请求页面
        $response = $client->request('GET', $login_url, [
//            'query' => $query,
        ]);
        //如果请求成功
        if (200 == $response->getStatusCode()) {
            $strBody = (string)($response->getBody());  //获取页面内容
            $inputs = self::strSimpleSingleHtml($strBody,'input','value');  //获取所有input元素的内容
            $authenticity_token = $inputs[1]; //获取到登录页面提交时隐藏的token
            $payload =[
                "account"=>"llc@jupin.net.cn",
                "password"=>"jupin123",
                "csrf_token"=> $authenticity_token,
//                'headers'=>['referer'=>$login_url],
            ];  //登录信息

        $login = $client->request('POST',
            'https://my.dianshangyi.com/user/login/',
            ['form_params' => $payload]
            );
            dd($login);

        } else {
            return $this->error('打开登录页面失败');
        }


    }
}
