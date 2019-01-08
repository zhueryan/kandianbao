<?php
/**
 * Created by PhpStorm.
 * User: deepin
 * Date: 19-1-7
 * Time: 下午5:58
 */

namespace App\Console\Commands;

use App\ConfigModel;
use App\Keywords;
use Carbon\Carbon;
use GuzzleHttp\Cookie\CookieJar;
use Yangqi\Htmldom\Htmldom;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;

trait KanDianBaoTrait
{


    /**
     * 获取页面全部标签,
     * str = 传递过来的为html代码
     * dom = 标签:比如a.img等,
     * sort = 详情：比如src,href,plaintext,class等
     */
    public static function strSimpleSingleHtml($str, $dom, $sort)
    {
        $htmldom = new Htmldom();
        $html = $htmldom->load($str);
        $result = [];
        foreach ($html->find($dom) as $element)
            $result[] = $element->$sort;
        return $result;
    }

    //获取配置表数据
    public static function getConfig($config_type, $config_key)
    {
        return ConfigModel::whereConfigType($config_type)
            ->whereConfigKey($config_key)->first();
    }

    //获取当前时间
    public static function getNowTime()
    {
        return Carbon::now()->toDateTimeString();
    }

    //获取当前日期
    public static function getNowDate()
    {
        return Carbon::now()->toDateString();
    }

    //获取每天爬取的关键词数量 获取要爬取的关键词
    public function get_keyword_num()
    {
        $limit = $this->getConfig('kandianbao', 'keyword_num');
        $limit = $limit->config_value ?: 1;
        return [Keywords::whereIn('state', [0, 2])
            ->orderBy('state', 'desc')
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get(),$limit];
    }


    public static function setCookie($client){
        $config = $client->getConfig();
        $cookie = $config['cookies']->toArray()[0];
        return CookieJar::fromArray([
            $cookie['Name'] => $cookie['Value'],
        ], $cookie['Domain']);
    }
    /**
     * 发送请求
     * https://guzzle-cn.readthedocs.io/zh_CN/latest/quickstart.html
     *
     * @param string $url
     * @param array $params
     * @param string $method
     * @param array $configs
     * @param string $contentType
     * @return array
     */
    public static function request($url, $params = [], $method = 'POST', array $configs = [],
                                   $contentType = 'form_params')
    {
        $configs['timeout'] = array_get($configs, 'timeout', 5);
        $client = new Client($configs);
        $params = strtoupper($method) == 'GET' ? ['query' => $params] : [$contentType => $params];
        Log::info("httpRequest send", ['url' => $url, 'params' => $params, 'method' => $method, 'configs' => $configs]);

        try {
            $request = $client->request($method, $url, $params);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();

            Log::info("httpRequest response error:", ['url' => $url, 'params' => $params, 'method' => $method,
                'configs' => $configs, 'errorCode' => $errorCode, 'errorMessage' => $errorMessage]);

            return [
                'success' => false,
                'errorCode' => $errorCode,
                'errorMessage' => $errorMessage,
            ];
        }

        $httpCode = $request->getStatusCode();
        $return = $request->getBody()->getContents();
        $response = json_decode($return, true);
        $success = $httpCode == 200 ? 'success' : 'error';

        Log::info("httpRequest response $success:", ['url' => $url, 'params' => $params, 'method' => $method,
            'configs' => $configs, 'httpCode' => $httpCode, 'response' => $response]);

        if ($httpCode != 200) {
            return [
                'success' => false,
                'errorCode' => $httpCode,
                'errorMessage' => '',
            ];
        }

        return [
            'success' => true,
            'data' => $response
        ];
    }


}