<?php

namespace modules\site\frontend;

use yii\base\Module as BaseModule;

/**
 * Class Module
 * @package modules\site\frontend
 */
class Module extends BaseModule
{

    public $controllerNamespace = 'modules\site\frontend\controllers';
    public $layout = 'main';


    public function init()
    {
        parent::init();
        // custom initialization code goes here
    }
}
