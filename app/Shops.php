<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Shops extends Model
{
    protected $table = "shops";

    protected $fillable = [
        "num",
        "logo",
        "shop_name",
        "shop_url",
        "nick",
        "credit",
        'shop_type',
        "feedback",
        "dsr",
        "place",
        "goods_num",
        "goods_sales_month",
        "content",
        "shop_created_at",
        "tel",
        "collection_num",
        "keyword",
        "shop_categroy",

    ];

    protected $guarded = [];

//    public $timestamps =false;

}
