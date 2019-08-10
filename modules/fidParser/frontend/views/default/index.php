<?php

/* @var $this yii\web\View */
/* @var $model modules\fidParser\models\XmlParser */

$this->title = 'XML Парсер';
$this->params['breadcrumbs'][] = $this->title;

$this->registerJsVar('xlsxFiles', $model->xlsxFiles);

?>
<div id="app-fid-parser-index"></div>