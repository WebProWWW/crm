<?php
/**
 * Author: Timur Valiev
 * Site: https://webprowww.github.io
 * 2019-08-03 15:47
 */

namespace modules\fidParser\frontend;


use assets\AnimateCSS;
use assets\Axios;
use assets\VueJS;
use yii\web\AssetBundle;

class Asset extends AssetBundle
{
    public $js = ['js/feed-parser.js'];
    public $depends = [
        AnimateCSS::class,
        Axios::class,
        VueJS::class,
    ];
}