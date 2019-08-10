<?php
/**
 * Author: Timur Valiev
 * Site: https://webprowww.github.io
 * 2019-08-06 13:10
 */

namespace modules\fidParser\models;

use Yii;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;
use yii\validators\UrlValidator;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;

/**
 * Class FormFeed
 * @package modules\fidParser\models
 * @property array $xlsxFiles
 */
class XmlParser extends Model
{
    public $avito;
    public $yandex;
    public $cian;
    public $xlsxFileName;

    private $_modulePath;
    private $_pathOut;

    public function init()
    {
        parent::init();
        libxml_use_internal_errors(true);
        $this->_modulePath = Yii::getAlias('@modules') . DS . 'fidParser';
        $this->_pathOut = Yii::getAlias('@webroot') . DS . 'fid-parser' . DS . 'out-xlsx';
    }

    public function rules()
    {
        return [
            ['xlsxFileName', 'string', 'message' => 'Значение должно быть строкой.'],
            ['xlsxFileName', 'match', 'pattern' => '/[a-zA-Z0-9\s_\\.\-\(\):]+.xlsx$/i', 'message' => 'Неверный формат файла.'],
            [['avito', 'yandex', 'cian'], 'validateFeeds', 'skipOnEmpty' => true],
        ];
    }

    public function validateFeeds($attr)
    {
        $feeds = ArrayHelper::getValue($this, $attr);
        $validator = new UrlValidator();
        if (is_array($feeds)) {
            foreach ($feeds as $id => $feed) {
                if (!$validator->validate($feed)) {
                    $this->addError($id, 'Введённое значение не является правильным URL.');
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function removeXlsx()
    {
        $file = $this->_pathOut . DS . $this->xlsxFileName;
        try {
            FileHelper::unlink($file);
        } catch (ErrorException $e) {
            $this->addError('xlsxFileName', 'Файл не найден');
            return false;
        }
        return true;
    }

    public function parse()
    {
        if (is_array($this->yandex)) {
            foreach ($this->yandex as $yandexUrl) {
                $dataYandex = $this->parseYandex($yandexUrl);
                $this->writeXlsx($dataYandex, 'tpl-yandex.xlsx', 3, 'yandex');
            }
        }
        return true;
    }

    /**
     * @return array
     */
    public function getXlsxFiles()
    {
        $data = [];
        try {
            $files = FileHelper::findFiles($this->_pathOut, [
                'recursive' => false,
                'only' => ['*.xlsx']
            ]);
        } catch (InvalidArgumentException $e) {
            return [];
        }
        foreach ($files as $id => $file) {
            $basename = StringHelper::basename($file);
            $data[] = [
                'id' => $id + 1,
                'name' => $basename,
                'url' => Yii::getAlias('@web') . "/fid-parser/out-xlsx/{$basename}",
            ];
        }
        return $data;
    }

    /**
     * @param array $data
     * @param string $tplXlsx
     * @param int $offset
     * @param string $prefix
     * @return void
     */
    private function writeXlsx(&$data, $tplXlsx, $offset, $prefix='')
    {
        $tpl = $this->_modulePath . DS . 'tplXlsx' . DS . $tplXlsx;

        if (!file_exists($tpl)) {
            $this->addError('processErrors', "Файл шаблона {$tplXlsx} не найден");
            return;
        }
        try {
            $spreadSheet = (new ReaderXlsx())->load($tpl);
        } catch (ReaderException $e) {
            $this->addError('processErrors', $e->getMessage());
            return;
        }
        try {
            $workSheet = $spreadSheet->getSheet(0);
        } catch (SpreadsheetException $e) {
            $this->addError('processErrors', $e->getMessage());
            return;
        }
        foreach ($data as $key => $cols) {
            $row = $key + $offset + 1;
            foreach ($cols as $col => $val) {
                $workSheet->setCellValueByColumnAndRow($col, $row, $val);
            }
        }
        try {
            FileHelper::createDirectory($this->_pathOut);
        } catch (Exception $e) {
            $this->addError('processErrors', $e->getMessage());
            return;
        }
        $file = $this->_pathOut . DS . $prefix . '-' . date('d.m.Y-H:i:s') . '.xlsx';
        try {
            (new WriterXlsx($spreadSheet))->save($file);
        } catch (WriterException $e) {
            $this->addError('processErrors', $e->getMessage());
            return;
        }
        return;
    }

    /**
     * @param string $url
     * @return array
     */
    private function parseYandex($url)
    {
        try {
            $xml = simplexml_load_file($url);
        } catch (ErrorException $e) {
            $this->addError('processErrors', 'Источник ссылки не найден');
            return [];
        }

        if ($xml === false) return [];
        $data = [];
        foreach ($xml->offer as $object) {
            $col = [];
            $col[1] = (string) $object['internal-id'];
            $col[2] = (string) $object->type;
            $col[3] = (string) $object->{'property-type'};
            $col[4] = (string) $object->category;
            $col[5] = (string) $object->url;
            $col[6] = (string) $object->{'creation-date'};
            $col[7] = (string) $object->{'deal-status'};
            $location = $object->location;
            $col[8] = (string) $location->country;
            $col[9] = (string) $location->region;
            $col[10] = (string) $location->{'locality-name'};
            $col[11] = (string) $location->address;
            $col[12] = (string) $location->latitude;
            $col[13] = (string) $location->longitude;
            $price = $object->price;
            $col[14] = (string) $price->value;
            $col[15] = (string) $price->currency;
            $salesAgent = $object->{'sales-agent'};
            $phoneArr = (array) $salesAgent->phone;
            $phoneStr = '';
            foreach ($phoneArr as $phone) {
                $phoneStr .= (string) $phone . PHP_EOL;
            }
            $col[16] = $phoneStr;
            $col[17] = (string) $salesAgent->organization;
            $col[18] = (string) $salesAgent->url;
            $col[19] = (string) $salesAgent->category;
            $col[20] = (string) $salesAgent->photo;
            $col[21] = (string) $object->rooms;
            $col[22] = (string) $object->{'new-flat'};
            $col[23] = (string) $object->{'building-name'};
            $col[24] = (string) $object->{'yandex-building-id'};
            $col[25] = (string) $object->{'yandex-house-id'};
            $col[26] = (string) $object->{'floor'};
            $col[27] = (string) $object->{'floors-total'};
            // $col[28] images, ...
            $col[29] = (string) $object->{'building-state'};
            $col[30] = (string) $object->{'ready-quarter'};
            $col[31] = (string) $object->{'built-year'};
            $col[32] = (string) $object->description;
            $col[33] = (string) $object->area->value;
            $col[34] = (string) $object->area->unit;
            $PhotoArr = (array) $object->image;
            $photoCount = count($PhotoArr);
            $photoStr = '';
            for ($i = 0; $i <= $photoCount; $i++) {
                $url = (string) $PhotoArr[$i];
                $col[$i + 35] = $url;
                $photoStr .= (string) $url . ', ';
            }
            $col[28] = $photoStr;
            array_push($data, $col);
        }
        return $data;
    }

}