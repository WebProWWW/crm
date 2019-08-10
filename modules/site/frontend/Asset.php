<?php
/**
 * Author: Timur Valiev
 * Site: https://webprowww.github.io
 * 2019-08-03 03:49
 */

namespace modules\site\frontend;

use assets\AnimateCSS;
use assets\BootstrapGrid;
use assets\BootstrapHelper;
use assets\BootstrapReboot;
use assets\FontAwesome;
use assets\JQuery;
use assets\VueJS;
use yii\web\AssetBundle;

/**
 * Class Asset
 * @package modules\site\frontend
 */
class Asset extends AssetBundle
{
    public $css = ['css/main.css'];
    public $js = ['js/main.js'];
    public $depends = [
        BootstrapReboot::class,
        BootstrapGrid::class,
        BootstrapHelper::class,
        FontAwesome::class,
        AnimateCSS::class,
        JQuery::class,
        VueJS::class,
    ];
}

/**/