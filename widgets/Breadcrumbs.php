<?php
/**
 * Author: Timur Valiev
 * Site: https://webprowww.github.io
 * 2019-02-07 14:54
 */

namespace widgets;

use yii\widgets\Breadcrumbs as BaseWidget;

use yii\helpers\Url;

/**
 * Class Breadcrumbs
 * @package widgets
 *
 * @property array $formatLinks
 */
class Breadcrumbs extends BaseWidget
{
    public $tag = 'div';
    public $itemTemplate = '{link}';
    public $activeItemTemplate = '<span class="breadcrumb-ln active">{link}</span>';

    public function init()
    {
        parent::init();
        $this->homeLink = [
            'encode' => false,
            'label' => '<i class="fas fa-home"></i>',
            'url' => Url::home(),
            'class' => 'breadcrumb-ln',
        ];
        $this->links = $this->formatLinks;

    }

    public function getFormatLinks()
    {
        $out = [];
        foreach ($this->links as $link) {
            if (is_array($link) and !isset($link['class'])) {
                $link['class'] = 'breadcrumb-ln';
            }
            $out[] = $link;
        }
        return $out;
    }
}