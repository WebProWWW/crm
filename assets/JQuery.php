<?php
/**
 * Author: Timur Valiev
 * Site: https://webprowww.github.io
 * 2019-08-03 09:39
 */

namespace assets;


use yii\web\AssetBundle;

class JQuery extends AssetBundle
{
    public $sourcePath = '@vendor/bower-asset/jquery/dist';
    public $js = ['jquery.min.js'];
}