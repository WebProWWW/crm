<?php
/**
 * Author: Timur Valiev
 * Site: https://webprowww.github.io
 * 2019-08-03 03:43
 */

namespace assets;


use yii\web\AssetBundle;

class Bootstrap extends AssetBundle
{
    public $sourcePath =  '@vendor/twbs/bootstrap/dist';
    public $css = ['css/bootstrap.min.css'];
    public $js = ['js/bootstrap.bundle.min.js'];
    public $depends = [JQuery::class];
}

/**/