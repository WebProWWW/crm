<?php
/**
 * Author: Timur Valiev
 * Site: https://webprowww.github.io
 * 2019-08-06 05:38
 */

namespace assets;


use yii\web\AssetBundle;

class Axios extends AssetBundle
{
    public $sourcePath = '@vendor/axios/axios/dist';
    public $js = ['axios.min.js'];
}