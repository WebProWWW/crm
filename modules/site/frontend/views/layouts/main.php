<?php
/**
 * Author: Timur Valiev
 * Site: https://webprowww.github.io
 * 2019-08-03 01:06
 */

/* @var $this yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use modules\site\frontend\Asset;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use widgets\Breadcrumbs;

Asset::register($this);

$breadcrumbs = ArrayHelper::getValue($this->params, 'breadcrumbs', false);

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <title><?= Html::encode($this->title) ?></title>
    <meta name="format-detection" content="telephone=no">
    <meta name="format-detection" content="date=no">
    <meta name="format-detection" content="address=no">
    <meta name="format-detection" content="email=no">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $this->registerCsrfMetaTags() ?>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-auto">
                <a class="header-ln" href="<?= Url::home() ?>">
                    <?= Html::img('@web/img/logo-color.svg', [
                        'height' => 13,
                    ]) ?>
                    <span class="ml-1">CRM</span>
                </a>
            </div>
            <div class="col-auto ml-auto">
                <div class="dropdown">
                    <a class="header-ln js-prevent" href="#">
                    <span class="mr-1">User</span>
                        <i class="fas fa-user-circle"></i>
                    </a>
                    <div class="dropdown-content">
                        <a class="dropdown-ln" href="">
                            <i class="fas fa-cog fa-fw"></i>
                            <span class="ml-1">Профиль</span>
                        </a>
                        <a class="dropdown-ln" href="">
                            <i class="fas fa-sign-out-alt fa-fw"></i>
                            <span class="ml-1">Выйти</span>
                        </a>
                    </div><!-- /.dropdown-content -->
                </div><!-- /.dropdown -->
            </div><!-- /.col -->
        </div>
    </div>
</div>

<div class="wrapper">
    <?php if ($breadcrumbs = ArrayHelper::getValue($this->params, 'breadcrumbs')): ?>
        <div class="container">
            <?= Breadcrumbs::widget(['links'=>$breadcrumbs]) ?>
        </div>
    <?php endif ?>

    <?= $content ?>

    <div class="footer">
        <div class="container mt-20px">
            <div class="d-flex justify-content-center align-items-center em-8">
                <div>
                    <a href="https://webprowww.github.io" target="_blank">WebPRO</a>
                </div>
                <div>&nbsp;&nbsp;|&nbsp;&nbsp;</div>
                <div>
                    <a href="https://www.yiiframework.com" target="_blank">
                        <?= Html::img('@web/img/yii_logo_dark.svg', [
                            'class' => 'd-block',
                            'height' => 14,
                        ]) ?>
                    </a>
                </div>
                <?php if (YII_ENV_DEV): ?>
                    <div>&nbsp;&nbsp;|&nbsp;&nbsp;</div>
                    <div>
                        <a href="<?= Url::to(['/gii']) ?>" target="_blank">Gii</a>
                    </div>
                <?php endif ?>
            </div><!-- /.d-flex -->
        </div><!-- /.container -->
    </div><!-- /.footer -->

</div><!-- /.wrapper -->

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>