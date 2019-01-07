<?php

namespace App\Console\Commands;

use App\ConfigModel;
use App\Keywords;
use function foo\func;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException;
use Yangqi\Htmldom\Htmldom;
use Illuminate\Support\Facades\DB;

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
# 数据上限（最大为600，超过可能会报‘超过’查询上限的提示）
    private $upper_limit = null;
# 获取需要采集的地域
    private $designArea = null;
    //重试次数
    private $retries_count = 0;
    //每天爬取的关键词个数
    private $keyword_num = 1;
    private $keywords =null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->upper_limit = self::getConfig('kandianbao','upper_limit')->config_value?:600;
        $this->designArea =self::getConfig('kandianbao','area')->config_value;
        $this->keywords = Keywords::whereIn('state',[0,2])
            ->orderBy('state','desc')
            ->orderBy('id','asc')
            ->limit(10)
            ->get();
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo "开始抓取看店宝。。。\n";

        $user_agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36';
        //请求登录地址
        $login_url ='https://my.dianshangyi.com/user/oauth/authorize?response_type=code&client_id=60c8f95b218af67f1eaf7664ef85a533&state=N4FUgJ&redirect_uri=https://www.kandianbao.com/oauth/dsy/callback/&scope=me&next=https://www.kandianbao.com/';
        //请求参数
        $client = new Client(['cookies'=>true,'headers'=>['User-Agent' => $user_agent,]]);

        //请求页面
        $response = $client->get($login_url);

        //如果请求成功
        if (200 == $response->getStatusCode()) {
            $strBody = (string)($response->getBody());  //获取页面内容
            $inputs = self::strSimpleSingleHtml($strBody,'input','value');  //获取所有input元素的内容
            $authenticity_token = $inputs[1]; //获取到登录页面提交时隐藏的token
            //人工验证码
//            $captcha  = $client->get("https://my.kandianbao.com/validcode/captcha.gif");
            $captcha = self::request('https://my.kandianbao.com/validcode/captcha.gif');

//            $handle=fopen("php://stdin", "r");
//            $s=fgets($handle);
//            $str = str_replace(array("\r\n", "\r", "\n"), "", $s);

        } else {
            return $this->error('打开登录页面失败');
        }

        $payload =[
            "account"=>"llc@jupin.net.cn",
            "password"=>"jupin123",
            "csrf_token"=> $authenticity_token,
            'next'=>'/',
        ];  //登录信息

        $url = 'https://my.dianshangyi.com/user/login/';
        //登录账号
        $login = $client->post(
            $url,
            [
                'form_params' => $payload,  //表达数据
                'headers'=>[
                    'User-Agent' => $user_agent,
                    'referer'=>$login_url,
                ],  //header
            ]
        );
        $this->start();
    }
    //开始爬取数据
    public function start(){
        foreach ($this->keywords as $keyword){
            DB::transaction(function () use ($keyword){
                if($keyword->state == 2){
                    //分地域采集
                    $num_end = 3;

                }else{

                }

            });
        }

    }
}
