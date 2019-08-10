<?php
/**
 * Author: Timur Valiev
 * Site: https://webprowww.github.io
 * 2019-08-03 23:46
 */

namespace assets;


use yii\web\AssetBundle;

class FontAwesome extends AssetBundle
{
    public $sourcePath = '@vendor/fortawesome/font-awesome';
    public $css = ['css/all.min.css'];
}