<?php

namespace App\Console\Commands;

use App\ConfigModel;
use App\Keywords;
use function foo\func;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Storage;
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
    private $keyword_num = 0;
    //每个关键词抓取的数据条数　从配置取
    private $keyword_catch_num = 600; //默认每个关键词抓取600条数据
    private $keywords = null;
    private $cookieJar=null;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->upper_limit = self::getConfig('kandianbao', 'upper_limit')->config_value ?: 600;
        $this->designArea = self::getConfig('kandianbao', 'area')->config_value;
        list($this->keywords, $this->keyword_num) = self::get_keyword_num();
        $this->keyword_catch_num = self::getConfig('kandianbao','keyword_catch_num')->config_value;
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo "看店宝爬虫开始。。。\n";

        #请求参数
        $client = new Client(['cookies' => true,'http_errors' => true,
            'headers' => ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',]]
        );
        echo "登录看店宝。。。\n";
        $client = $this->login($client);  //登录看店宝 登录成功后讲client对象返回　否则请求数据时会是未登录状态

        //抓取类型　STAPP 手淘APP　
        $grasp_type  = self::getConfig('kandianbao', 'grasp_type')->config_value ;
        $grasp = explode(',',$grasp_type);

        if (in_array('STAPP',$grasp)) {
            echo "开始爬取手淘APP数据\n";
            $this->start_stapp($client); //爬取手淘APP数据
        }

    }

    //登录看店宝
    private function login(Client $client)
    {
        #请求登录地址
        $login_url = 'https://my.dianshangyi.com/user/oauth/authorize?response_type=code&client_id=60c8f95b218af67f1eaf7664ef85a533&state=sFkh5R&redirect_uri=https://www.kandianbao.com/oauth/dsy/callback/&scope=me&next=https://www.kandianbao.com/';

        #请求页面
        $response = $client->get($login_url);
        #如果请求成功
        if (200 == $response->getStatusCode()) {
            $strBody = (string)($response->getBody());  //获取页面内容
            $inputs = self::strSimpleSingleHtml($strBody, 'input', 'value');  //获取所有input元素的内容
            $authenticity_token = $inputs[1]; //获取到登录页面提交时隐藏的token

        } else {
            return $this->error('打开登录页面失败');
        }

        // get cookie
//        $this->cookieJar = self::setCookie($client);
        $user = [
            "account" => "llc@jupin.net.cn",
            "password" => "jupin123",
            "csrf_token" => $authenticity_token,
            "next"=>'https://www.kandianbao.com/',
        ];  #登录信息

        $url = 'https://my.dianshangyi.com/user/login/';
        #登录账号
        try{
            $response = $client->request('POST', $url ,
                array(
//                    'cookies' => $this->cookieJar,
                    'form_params' => $user,
                    'headers' => [
                        'referer' => $login_url,
                    ],
                )
            );
            if(strstr($response->getBody()->getContents(),'验证码' )){  //再次请求　手动输入验证码
                //人工验证码
            $cap_url = 'https://my.kandianbao.com/validcode/captcha.gif';
            try {
                $data = $client->request('get',$cap_url)->getBody()->getContents();
                Storage::disk('/home/hujiao/kandianbao/public/')->put('captcha.jpg', $data);
            } catch (ClientException $e) {
                return $this->error('fetch fail');
            }
//            $handle=fopen("php://stdin", "r");
//            $s=fgets($handle);
//            $str = str_replace(array("\r\n", "\r", "\n"), "", $s)
            }

            echo "看店宝账号登录成功\n";
//            $cookie = explode(';',$response->getHeader('Set-Cookie')[0]);
//            list($session,$session_value) = explode('=',$cookie[0]);
//            $domain = explode('=',$cookie[1])[1];
//            $coookie = CookieJar::fromArray([
//                $session => $session_value,
//            ], $domain);
            dd();

            $main_url = "https://so.kandianbao.com/app/%E6%99%AE%E6%B4%B1%E8%8C%B6/1/";
            $res = $client->request('GET',$main_url,
                    [
//                        'cookies'=>$this->cookieJar,
                    ]
            );

            dd($res->getBody()->getContents());



            return $client;
        }catch (RequestException $e){
            echo $e->getMessage();
        }
        exit();


;
    }

    #开始爬取手淘APP数据
    public function start_stapp(Client $client)
    {
        $num_end =(int) ((int)$this->keyword_catch_num / 10) ;#批量采集 采集多少页

        foreach ($this->keywords as $keywords) {
            echo "抓取关键词:{$keywords->keyword}";
            $keyword = urlencode($keywords->keyword);
            $main_url = "https://so.kandianbao.com/app/{$keyword}/{$num_end}/";
            $res= $client->get($main_url);
            dd($res->getBody()->getContents());
            #请求关键词搜索结果
            $res = $client->request('GET',$main_url,
                array(
                    'cookies' => $this->cookieJar,
                )
                );
            dd($res->getBody()->getContents());
            $url ="https://api.kandianbao.com/item-over/{$keyword}/5c344e8e347fea2c7eb6f9df/0/?callback=over&_=1546931855313";
            DB::transaction(function () use ($keywords) {

                # 每用一个关键词，把这个词的状态改成1，视为已经用过
                Keywords::whereId($keywords->id)->update(['state' => 1]);

            });

        }

    }
}