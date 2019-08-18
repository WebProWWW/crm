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
use yii\web\UploadedFile;

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
    public function actionParseXml()
    {
        $model = new XmlParser();
        $req = Yii::$app->request;
        if ($req->isPost and $model->load($req->post(), '')) {
            $data = null;
            if ($model->convertXmlToXlsx()) {
                return [
                    'xlsxFiles' => $model->xlsxFiles,
                    'errors' => $model->errors,
                ];
            }
            return [
                'errors' => $model->errors,
            ];
        }
        throw new BadRequestHttpException('Неверный запрос');
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
            if ($model->removeXlsx()) {
                return ['status' => true];
            }
            return ['errors' => $model->errors];
        }
        throw new BadRequestHttpException('Неверный запрос');
    }

    /**
     * @return array
     * @throws BadRequestHttpException
     */
    public function actionParseXlsx()
    {
        $model = new XmlParser();
        $req = Yii::$app->request;
        if ($req->isPost) {
            $model->yandexXlsxFile = UploadedFile::getInstanceByName('yandexXlsxFile');
            $model->avitoXlsxFile = UploadedFile::getInstanceByName('avitoXlsxFile');
            $model->cianXlsxFile = UploadedFile::getInstanceByName('cianXlsxFile');
            if ($model->convertXlsxToXml()) {
                return [
                    'xmlFiles' => $model->xmlFiles,
                    'processErrors' => $model->errors,
                ];
            }
            return ['errors' => $model->errors];
        }
        throw new BadRequestHttpException('Неверный запрос');
    }

    /**
     * @return array
     * @throws BadRequestHttpException
     */
    public function actionRemoveXml()
    {
        $model = new XmlParser();
        $req = Yii::$app->request;
        if ($req->isPost and $model->load($req->post(), '')) {
            if ($model->removeXml()) {
                return ['status' => true];
            }
            return ['errors' => $model->errors];
        }
        throw new BadRequestHttpException('Неверный запрос');
    }

}

/**/