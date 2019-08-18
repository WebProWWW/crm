<?php
/**
 * Author: Timur Valiev
 * Site: https://webprowww.github.io
 * 2019-08-03 12:47
 */

namespace assets;


use yii\web\AssetBundle;

class VueJS extends AssetBundle
{
    public $sourcePath = '@vendor/vuejs/vue/dist';
    public $js = ['vue.min.js'];
}