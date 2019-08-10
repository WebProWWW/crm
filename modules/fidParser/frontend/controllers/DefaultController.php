<?php

namespace modules\fidParser\frontend\controllers;

use Yii;
use yii\web\Controller;
use modules\fidParser\models\XmlParser;

/**
 * Class DefaultController
 * @package modules\fidParser\frontend\controllers
 */
class DefaultController extends Controller
{

    public function actionIndex()
    {
        $model = new XmlParser();
        return $this->render('index', ['model' => $model]);
    }

}
