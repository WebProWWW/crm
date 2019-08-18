<?php

use yii\web\View;

/* @var $this View */
/* @var $model modules\fidParser\models\XmlParser */

$this->title = 'XML Парсер';
$this->params['breadcrumbs'][] = $this->title;

$this->registerJsVar('xlsxFiles', $model->xlsxFiles, View::POS_HEAD);
$this->registerJsVar('xmlFiles', $model->xmlFiles, View::POS_HEAD);

?>
<div id="app-fid-parser-index"></div>