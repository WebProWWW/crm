<?php
/**
 * Author: Timur Valiev
 * Site: https://webprowww.github.io
 * 2019-08-03 00:32
 */

return [
    'name'     => 'CMS',
    'version'  => '2.0',
    'modules'  => [
        'site'   => [
            'class'    => modules\site\frontend\Module::class,
            'modules'  => [
                'fid-parser' => ['class' => 'modules\fidParser\frontend\Module'],
            ],
        ],
    ],
    'components' => [
        'urlManager' => [
            'enablePrettyUrl'      => true,
            'enableStrictParsing'  => false,
            'showScriptName'       => false,
            'suffix'               => '.html',
            'rules' => [
                [
                    'class' =>  yii\web\GroupUrlRule::class,
                    'routePrefix' => 'site',
                    'rules' => [
                        '<action:[\w\-]+>' => 'default/<action>',
                        'fid-parser/<action:[\w\-]+>' => 'fid-parser/default/<action>',
                        '<controller:[\w\-]+>/<action:[\w\-]+>' => '<controller>/<action>',
                        '<module:[\w\-]+>/<controller:[\w\-]+>/<action:[\w\-]+>' => '<module>/<controller>/<action>',
                        [
                            'pattern' => 'fid-parser/api/<action:[\w\-]+>',
                            'route' => 'fid-parser/api/<action>',
                            'suffix' => '.json',
                        ],
                    ],
                ],
                '' => '/site/default/index',
            ],
        ],
    ],
];

/**/
