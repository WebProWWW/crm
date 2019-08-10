<?php
/**
 * Author: Timur Valiev
 * Site: https://webprowww.github.io
 * 2019-08-06 05:49
 */

namespace modules\fidParser\frontend\controllers;

use modules\fidParser\models\XmlParser;
use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;

class ApiController extends Controller
{

    public function actionTest()
    {
        return Yii::$app->request->post();
    }

    /**
     * @return array
     * @throws BadRequestHttpException
     */
    public function actionParse()
    {
        $model = new XmlParser();
        $req = Yii::$app->request;
        if ($req->isPost and $model->load($req->post(), '')) {
            $data = null;
            if ($model->validate() && $model->parse()) {
                return [
                    'xlsxFiles' => $model->xlsxFiles,
                    'processErrors' => $model->errors,
                ];
            }
            return [
                'errors' => $model->errors,
            ];
        }
        throw new BadRequestHttpException();
    }

    /**
     * @return array
     * @throws BadRequestHttpException
     */
    public function actionRemoveXlsx()
    {
        $model = new XmlParser();
        $req = Yii::$app->request;
        if ($req->isPost and $model->load($req->post(), '')) {
            if ($model->validate() && $model->removeXlsx()) {
                return ['status' => true];
            }
            return ['errors' => $model->errors];
        }
        throw new BadRequestHttpException();
    }

}

/**/