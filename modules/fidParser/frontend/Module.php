<?php

namespace modules\fidParser\frontend;

use Yii;


/**
 * Class Module
 * @package modules\fidParser\frontend
 */
class Module extends \yii\base\Module
{

    public $controllerNamespace = 'modules\fidParser\frontend\controllers';
    public function init()
    {
        parent::init();
        Asset::register(Yii::$app->view);
    }
}
/**/