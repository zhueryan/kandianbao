<?php
/**
 * Created by PhpStorm.
 * User: deepin
 * Date: 19-1-7
 * Time: 下午5:58
 */

namespace App\Console\Commands;
use App\ConfigModel;
use Carbon\Carbon;
use Yangqi\Htmldom\Htmldom;

trait KanDianBaoTrait{


    /**
     * 获取页面全部标签,
     * str = 传递过来的为html代码
     * dom = 标签:比如a.img等,
     * sort = 详情：比如src,href,plaintext,class等
     */
    public static function strSimpleSingleHtml($str,$dom,$sort)
    {
        $htmldom = new Htmldom();
        $html = $htmldom->load($str);
        $result=[];
        foreach($html->find($dom) as $element)
            $result[]=$element->$sort;
        return $result;
    }
    //获取配置表数据
    public static function getConfig($config_type,$config_key){
        return ConfigModel::whereConfigType($config_type)
            ->whereConfigKey($config_key)->first();
    }
    //获取当前时间
    public static function getNowTime(){
        return Carbon::now()->toDateTimeString();
    }
    //获取当前日期
    public static function getNowDate(){
        return Carbon::now()->toDateString();
    }





}