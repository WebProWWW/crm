<?php
/**
 * Author: Timur Valiev
 * Site: https://webprowww.github.io
 * 2019-08-05 00:17
 */

namespace assets;


use yii\web\AssetBundle;

class AnimateCSS extends AssetBundle
{
    public $sourcePath = '@vendor/daneden/animate.css';
    public $css = ['animate.min.css'];
}