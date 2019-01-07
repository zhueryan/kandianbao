<?php
/**
 * Created by PhpStorm.
 * User: deepin
 * Date: 19-1-7
 * Time: 下午5:58
 */

namespace App\Console\Commands;
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

}