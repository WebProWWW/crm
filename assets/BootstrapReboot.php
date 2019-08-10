<?php
/**
 * Author: Timur Valiev
 * Site: https://webprowww.github.io
 * 2019-08-03 12:30
 */

namespace assets;


use yii\web\AssetBundle;

class BootstrapReboot extends AssetBundle
{
    public $sourcePath = '@vendor/twbs/bootstrap/dist/css';
    public $css = ['bootstrap-reboot.min.css'];
}