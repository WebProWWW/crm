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
use yii\web\UploadedFile;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DOMDocument;
use SimpleXMLElement;
use Exception as NativeException;

/**
 * Class FormFeed
 * @package modules\fidParser\models
 *
 * @property array $xlsxFiles
 * @property array $xmlFiles
 *
 * @property string $avitoXmlUrl
 * @property string $yandexXmlUrl
 * @property string $cianXmlUrl
 * @property string $xlsxFileName
 *
 * @property UploadedFile $yandexXlsxFile
 * @property UploadedFile $avitoXlsxFile
 * @property UploadedFile $cianXlsxFile
 * @property string $xmlFileName
 *
 * @property string $randomName
 */
class XmlParser extends Model
{
    // XML -> XLSX
    public $avitoXmlUrl;
    public $yandexXmlUrl;
    public $cianXmlUrl;
    public $xlsxFileName;

    // XSLX -> XML
    public $yandexXlsxFile;
    public $avitoXlsxFile;
    public $cianXlsxFile;
    public $xmlFileName;

    private $_modulePath;
    private $_pathOutXlsx;
    private $_pathOutXml;
    private $_pathTmp;

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();
        libxml_use_internal_errors(true);
        $this->_modulePath = Yii::getAlias('@modules') . DS . 'fidParser';
        $this->_pathOutXlsx = Yii::getAlias('@webroot') . DS . 'fid-parser' . DS . 'out-xlsx';
        $this->_pathOutXml = Yii::getAlias('@webroot') . DS . 'fid-parser' . DS . 'out-xml';
        $this->_pathTmp = Yii::getAlias('@runtime') . DS . 'fid-parser';
    }

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            [['xlsxFileName', 'xmlFileName'], 'string', 'message' => 'Значение должно быть строкой.'],
            ['xlsxFileName', 'match', 'pattern' => '/[a-zA-Z0-9\s_\\.\-\(\):]+.xlsx$/i', 'message' => 'Неверный формат файла.'],
            ['xmlFileName', 'match', 'pattern' => '/[a-zA-Z0-9\s_\\.\-\(\):]+.xml$/i', 'message' => 'Неверный формат файла.'],
            [['avitoXmlUrl', 'yandexXmlUrl', 'cianXmlUrl'], 'validateFeedUrl'],
            [['yandexXlsxFile', 'avitoXlsxFile', 'cianXlsxFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'xlsx'],
        ];
    }

    /**
     * @param string $attr
     */
    public function validateFeedUrl($attr)
    {
        $validator = new UrlValidator();
        $url = $this->$attr;
        if (!$validator->validate($url) or $url === '') {
            $this->addError('processErrors', 'Значение не является правильным URL');
        }
    }

    /**
     * @return array
     */
    public function getXlsxFiles()
    {
        $data = [];
        try {
            $files = FileHelper::findFiles($this->_pathOutXlsx, [
                'recursive' => false,
                'only' => ['*.xlsx']
            ]);
        } catch (InvalidArgumentException $e) {
            return [];
        }
        foreach ($files as $id => $file) {
            $basename = StringHelper::basename($file);
            $data[] = [
                'name' => $basename,
                'url' => Yii::getAlias('@web') . "/fid-parser/out-xlsx/{$basename}",
            ];
        }
        ArrayHelper::multisort($data, 'name', SORT_DESC);
        return $data;
    }

    /**
     * @return array
     */
    public function getXmlFiles()
    {
        $data = [];
        try {
            $files = FileHelper::findFiles($this->_pathOutXml, [
                'recursive' => false,
                'only' => ['*.xml']
            ]);
        } catch (InvalidArgumentException $e) {
            return [];
        }
        foreach ($files as $id => $file) {
            $basename = StringHelper::basename($file);
            $data[] = [
                'name' => $basename,
                'url' => Yii::getAlias('@web') . "/fid-parser/out-xml/{$basename}",
            ];
        }
        ArrayHelper::multisort($data, 'name', SORT_DESC);
        return $data;
    }

    /**
     * @return bool
     */
    public function removeXlsx()
    {
        if (!$this->validate()) {
            return false;
        }
        $file = $this->_pathOutXlsx . DS . $this->xlsxFileName;
        try {
            FileHelper::unlink($file);
        } catch (NativeException $e) {
            $this->addError('xlsxFileName', 'Файл не найден');
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function removeXml()
    {
        if (!$this->validate()) {
            return false;
        }
        $file = $this->_pathOutXml . DS . $this->xmlFileName;
        try {
            FileHelper::unlink($file);
        } catch (NativeException $e) {
            $this->addError('xmlFileName', 'Файл не найден');
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function convertXmlToXlsx()
    {
        if ($this->validate()) {
            if ($this->yandexXmlUrl !== null) {
                if ($dataYandex = $this->parseYandex($this->yandexXmlUrl)) {
                    $this->writeXlsx($dataYandex, 'tpl-yandex.xlsx', 3, 'yandex');
                    return true;
                }
                return false;
            }
            if ($this->avitoXmlUrl !== null) {
                if ($dataAvito = $this->parseAvito($this->avitoXmlUrl)) {
                    $this->writeXlsx($dataAvito, 'tpl-avito.xlsx', 3, 'avito');
                    return true;
                }
                return false;
            }
            if ($this->cianXmlUrl !== null) {
                if ($dataCian = $this->parseCian($this->cianXmlUrl)) {
                    $this->writeXlsx($dataCian, 'tpl-cian.xlsx', 3, 'cian');
                    return true;
                }
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function convertXlsxToXml()
    {
        if ($this->validate()) {
            if ($this->yandexXlsxFile !== null and $yandex = $this->saveToTmp($this->yandexXlsxFile)) {
                $resYandex = $this->convertYandex($yandex);
                FileHelper::unlink($yandex);
                return $resYandex;
            }
            if ($this->avitoXlsxFile !== null and $avito = $this->saveToTmp($this->avitoXlsxFile)) {
                $resAvito = $this->convertAvito($avito);
                FileHelper::unlink($avito);
                return $resAvito;
            }
            if ($this->cianXlsxFile !== null and $cian = $this->saveToTmp($this->cianXlsxFile)) {
                $resCian = $this->convertCian($cian);
                FileHelper::unlink($cian);
                return $resCian;
            }
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getRandomName()
    {
        try {
            $random = date('d.m.Y-H:i:s') . '-' . Yii::$app->security->generateRandomString(5);
        } catch (Exception $e) {
            sleep(1);
            $random = date('d.m.Y-H:i:s');
        }
        return $random;
    }

    /**
     * @param string $url
     * @return bool|SimpleXMLElement
     */
    private function loadXml($url)
    {
        try {
            $xml = simplexml_load_file($url);
        } catch (NativeException $e) {
            $this->addError('processErrors', 'Источник ссылки не найден');
            return false;
        }
        return $xml;
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
            FileHelper::createDirectory($this->_pathOutXlsx);
        } catch (Exception $e) {
            $this->addError('processErrors', $e->getMessage());
            return;
        }
        $file = $this->_pathOutXlsx . DS . $prefix . '-' . $this->randomName . '.xlsx';
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
     * @return array|bool
     */
    private function parseYandex($url)
    {
        if (!$xml = $this->loadXml($url)) return false;
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
                $phoneStr .= (string) $phone . ', ';
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
            for ($i = 0; $i < $photoCount; $i++) {
                $url = (string) $PhotoArr[$i];
                $col[$i + 35] = $url;
                $photoStr .= (string) $url . ', ';
            }
            $col[28] = $photoStr;
            array_push($data, $col);
        }
        return $data;
    }

    /**
     * @param string $url
     * @return array|bool
     */
    private function parseAvito($url)
    {
        if (!$xml = $this->loadXml($url)) return false;
        $data = [];
        foreach ($xml->Ad as $object) {
            $col = [];
            $col[1] = (string) $object->Id;
            $col[2] = (string) $object->Category;
            $col[3] = (string) $object->OperationType;
            $col[4] = (string) $object->DateBegin;
            $col[5] = (string) $object->DateEnd;
            $col[6] = (string) $object->Region;
            $col[7] = (string) $object->City;
            $col[8] = (string) $object->Description;
            $col[9] = (string) $object->Price;
            $col[10] = (string) $object->CompanyName;
            $col[11] = (string) $object->ManagerName;
            $col[12] = (string) $object->EMail;
            $col[13] = (string) $object->ContactPhone;
            // $col[14] images, ...
            $col[15] = (string) $object->Rooms;
            $col[16] = (string) $object->Square;
            $col[17] = (string) $object->Floor;
            $col[18] = (string) $object->Floors;
            $col[19] = (string) $object->HouseType;
            $col[20] = (string) $object->MarketType;
            $col[21] = (string) $object->NewDevelopmentId;
            $col[22] = (string) $object->PropertyRights;
            $col[23] = (string) $object->CadastralNumber;
            $col[24] = (string) $object->DateBegin2;
            $PhotoArr = $object->Images->Image;
            $photoStr = '';
            $photoCount = count($PhotoArr);
            for ($i = 0; $i < $photoCount; $i++) {
                $url = (string) $PhotoArr[$i]['url'];
                $col[$i + 25] = $url;
                $photoStr .= $url . ', ';
            }
            $col[14] = $photoStr;
            array_push($data, $col);
        }
        return $data;
    }

    /**
     * @param string $url
     * @return array|bool
     */
    private function parseCian($url)
    {
        if (!$xml = $this->loadXml($url)) return false;
        $data = [];
        foreach ($xml->object as $object) {
            $col = [];
            $col[1] = (string) $object->Category;
            $col[2] = (string) $object->ExternalId;
            $col[3] = (string) $object->Description;
            $col[4] = (string) $object->Address;
            $col[5] = (string) $object->Coordinates->Lat;
            $col[6] = (string) $object->Coordinates->Lat;
            $phoneSchema = $object->Phones->PhoneSchema;
            $col[7] = (string) $phoneSchema->CountryCode;
            $col[8] = (string) $phoneSchema->Number;
            $udrgnd = $object->Underground;
            $col[9] = (string) $udrgnd->TransportType;
            $col[10] = (string) $udrgnd->Time;
            $col[11] = (string) $udrgnd->Id;
            $col[12] = (string) $object->TotalArea;
            $col[13] = (string) $object->MinArea;
            $col[14] = (string) $object->MaxArea;
            $col[15] = (string) $object->FloorNumber;
            $col[16] = (string) $object->ConditionType;
            $col[17] = (string) $object->IsOccupied;
            // $col[18] imagess, ...
            $col[19] = (string) $object->BusinessShoppingCenter->Id;
            $build = $object->Building;
            $col[20] = (string) $build->FloorsCount;
            $col[21] = (string) $build->BuildYear;
            $col[22] = (string) $build->TotalArea;
            $col[23] = (string) $build->Type;
            $col[24] = (string) $build->ClassType;
            $structArr = (array) $build->Infrastructure;
            $structStr = '';
            foreach ($structArr as $name => $yesNo) {
                $name = (string) $name;
                $yesNo = (string) $yesNo;
                if ($name !== '0' && $yesNo === 'true') {
                    $structStr .= $name . ', ';
                }
            }
            $col[25] = $structStr;
            $col[26] = (string) $object->PublishTerms->Terms->PublishTermSchema->Services->ServicesEnum;
            $barg = $object->BargainTerms;
            $col[27] = (string) $barg->Price;
            $col[28] = (string) $barg->PriceType;
            $col[29] = (string) $barg->Currency;
            $col[30] = (string) $barg->PaymentPeriod;
            $col[31] = (string) $barg->VatType;
            $col[32] = (string) $barg->LeaseType;
            $incOpt = (array) $barg->IncludedOptions->IncludedOptionsEnum;
            $incOptStr = '';
            foreach ($incOpt as $optName) {
                $optName = (string) $optName;
                $incOptStr .= $optName . ', ';
            }
            $col[33] = (string) $incOptStr;
            $col[34] = (string) $barg->ClientFee;
            $col[35] = (string) $barg->AgentFee;
            $PhotoArr = $object->Photos->PhotoSchema;
            $photoStr = '';
            $photoCount = count($PhotoArr);
            for ($i = 0; $i < $photoCount; $i++) {
                $url = (string) $PhotoArr[$i]->FullUrl;
                $col[$i + 35] = $url;
                $photoStr .= $url . ', ';
            }
            $col[18] = $photoStr;
            array_push($data, $col);
        }
        return $data;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @return bool
     */
    private function saveToTmp($uploadedFile)
    {
        try {
            FileHelper::createDirectory($this->_pathTmp);
            $file = Yii::$app->security->generateRandomString(10) . '.' . $uploadedFile->extension;
            $file = $this->_pathTmp . DS . $file;
        } catch (Exception $e) {
            $this->addError('processErrors', $e->getMessage());
            return false;
        }
        $uploadedFile->saveAs($file);
        return $file;
    }

    /**
     * @param string $file
     * @return bool|Worksheet
     */
    private function getWorkSheet($file)
    {
        try {
            $spreadSheet = (new ReaderXlsx())->load($file);
        } catch (ReaderException $e) {
            $this->addError('processErrors', $e->getMessage());
            return false;
        }
        try {
            $workSheet = $spreadSheet->getSheet(0);
        } catch (SpreadsheetException $e) {
            $this->addError('processErrors', $e->getMessage());
            return false;
        }
        return $workSheet;
    }

    /**
     * @param string $file
     * @return bool
     */
    private function convertYandex($file)
    {
        if (!$workSheet = $this->getWorkSheet($file)) return false;
        $xml = new DOMDocument('1.0', 'UTF-8');
        $root = $xml->createElement('realty-feed');
        foreach ($workSheet->getRowIterator(4) as $rn => $row) {
            $col1 = $workSheet->getCellByColumnAndRow(1, $rn)->getValue();
            $col2 = $workSheet->getCellByColumnAndRow(2, $rn)->getValue();
            $col3 = $workSheet->getCellByColumnAndRow(3, $rn)->getValue();
            $col4 = $workSheet->getCellByColumnAndRow(4, $rn)->getValue();
            $col5 = $workSheet->getCellByColumnAndRow(5, $rn)->getValue();
            $col6 = $workSheet->getCellByColumnAndRow(6, $rn)->getValue();
            $col7 = $workSheet->getCellByColumnAndRow(7, $rn)->getValue();
            $col8 = $workSheet->getCellByColumnAndRow(8, $rn)->getValue();
            $col9 = $workSheet->getCellByColumnAndRow(9, $rn)->getValue();
            $col10 = $workSheet->getCellByColumnAndRow(10, $rn)->getValue();
            $col11 = $workSheet->getCellByColumnAndRow(11, $rn)->getValue();
            $col12 = $workSheet->getCellByColumnAndRow(12, $rn)->getValue();
            $col13 = $workSheet->getCellByColumnAndRow(13, $rn)->getValue();
            $col14 = $workSheet->getCellByColumnAndRow(14, $rn)->getValue();
            $col15 = $workSheet->getCellByColumnAndRow(15, $rn)->getValue();
            $col16 = $workSheet->getCellByColumnAndRow(16, $rn)->getValue();
            $col17 = $workSheet->getCellByColumnAndRow(17, $rn)->getValue();
            $col18 = $workSheet->getCellByColumnAndRow(18, $rn)->getValue();
            $col19 = $workSheet->getCellByColumnAndRow(19, $rn)->getValue();
            $col20 = $workSheet->getCellByColumnAndRow(20, $rn)->getValue();
            $col21 = $workSheet->getCellByColumnAndRow(21, $rn)->getValue();
            $col22 = $workSheet->getCellByColumnAndRow(22, $rn)->getValue();
            $col23 = $workSheet->getCellByColumnAndRow(23, $rn)->getValue();
            $col24 = $workSheet->getCellByColumnAndRow(24, $rn)->getValue();
            $col25 = $workSheet->getCellByColumnAndRow(25, $rn)->getValue();
            $col26 = $workSheet->getCellByColumnAndRow(26, $rn)->getValue();
            $col27 = $workSheet->getCellByColumnAndRow(27, $rn)->getValue();
            $col28 = $workSheet->getCellByColumnAndRow(28, $rn)->getValue();
            $col29 = $workSheet->getCellByColumnAndRow(29, $rn)->getValue();
            $col30 = $workSheet->getCellByColumnAndRow(30, $rn)->getValue();
            $col31 = $workSheet->getCellByColumnAndRow(31, $rn)->getValue();
            $col32 = $workSheet->getCellByColumnAndRow(32, $rn)->getValue();
            $col33 = $workSheet->getCellByColumnAndRow(33, $rn)->getValue();
            $col34 = $workSheet->getCellByColumnAndRow(34, $rn)->getValue();
            $offer = $xml->createElement('offer');
            $offer->setAttribute('internal-id', $col1);
            $offer->appendChild($xml->createElement('type', $col2));
            $offer->appendChild($xml->createElement('property-type', $col3));
            $offer->appendChild($xml->createElement('category', $col4));
            $offer->appendChild($xml->createElement('url', $col5));
            $offer->appendChild($xml->createElement('creation-date', $col6));
            $offer->appendChild($xml->createElement('deal-status', $col7));
            $location = $xml->createElement('location');
            $location->appendChild($xml->createElement('country', $col8));
            $location->appendChild($xml->createElement('region', $col9));
            $location->appendChild($xml->createElement('locality-name', $col10));
            $location->appendChild($xml->createElement('address', $col11));
            $location->appendChild($xml->createElement('latitude', $col12));
            $location->appendChild($xml->createElement('longitude', $col13));
            $offer->appendChild($location);
            $price = $xml->createElement('price');
            $price->appendChild($xml->createElement('value', $col14));
            $price->appendChild($xml->createElement('currency', $col15));
            $offer->appendChild($price);
            $salesAgent = $xml->createElement('sales-agent');
            $phoneArr = StringHelper::explode($col16, ',', true, true);
            foreach ($phoneArr as $phone) {
                $salesAgent->appendChild($xml->createElement('phone', $phone));
            }
            $salesAgent->appendChild($xml->createElement('organization', $col17));
            $salesAgent->appendChild($xml->createElement('url', $col18));
            $salesAgent->appendChild($xml->createElement('category', $col19));
            $salesAgent->appendChild($xml->createElement('photo', $col20));
            $offer->appendChild($salesAgent);
            $offer->appendChild($xml->createElement('rooms', $col21));
            $offer->appendChild($xml->createElement('new-flat', $col22));
            $offer->appendChild($xml->createElement('building-name', $col23));
            $offer->appendChild($xml->createElement('yandex-building-id', $col24));
            $offer->appendChild($xml->createElement('yandex-house-id', $col25));
            $offer->appendChild($xml->createElement('floor', $col26));
            $offer->appendChild($xml->createElement('floors-total', $col27));
            $imgArr = StringHelper::explode($col28, ',', true, true);
            foreach ($imgArr as $img) {
                $offer->appendChild($xml->createElement('image', $img));
            }
            $offer->appendChild($xml->createElement('building-state', $col29));
            $offer->appendChild($xml->createElement('ready-quarter', $col30));
            $offer->appendChild($xml->createElement('built-year', $col31));
            $offer->appendChild($xml->createElement('description', $col32));
            $area = $xml->createElement('area');
            $area->appendChild($xml->createElement('value', $col33));
            $area->appendChild($xml->createElement('unit', $col34));
            $offer->appendChild($area);

            $root->appendChild($offer);
        }
        $xml->appendChild($root);
        $xml->save($this->_pathOutXml . DS . 'yandex-' . $this->randomName . '.xml');
        return true;
    }

    /**
     * @param string $file
     * @return bool
     */
    private function convertAvito($file)
    {
        if (!$workSheet = $this->getWorkSheet($file)) return false;
        $xml = new DOMDocument('1.0', 'UTF-8');
        $root = $xml->createElement('Ads');
        foreach ($workSheet->getRowIterator(4) as $rn => $row) {
            $col1 = $workSheet->getCellByColumnAndRow(1, $rn)->getValue();
            $col2 = $workSheet->getCellByColumnAndRow(2, $rn)->getValue();
            $col3 = $workSheet->getCellByColumnAndRow(3, $rn)->getValue();
            $col4 = $workSheet->getCellByColumnAndRow(4, $rn)->getValue();
            $col5 = $workSheet->getCellByColumnAndRow(5, $rn)->getValue();
            $col6 = $workSheet->getCellByColumnAndRow(6, $rn)->getValue();
            $col7 = $workSheet->getCellByColumnAndRow(7, $rn)->getValue();
            $col8 = $workSheet->getCellByColumnAndRow(8, $rn)->getValue();
            $col9 = $workSheet->getCellByColumnAndRow(9, $rn)->getValue();
            $col10 = $workSheet->getCellByColumnAndRow(10, $rn)->getValue();
            $col11 = $workSheet->getCellByColumnAndRow(11, $rn)->getValue();
            $col12 = $workSheet->getCellByColumnAndRow(12, $rn)->getValue();
            $col13 = $workSheet->getCellByColumnAndRow(13, $rn)->getValue();
            $col14 = $workSheet->getCellByColumnAndRow(14, $rn)->getValue();
            $col15 = $workSheet->getCellByColumnAndRow(15, $rn)->getValue();
            $col16 = $workSheet->getCellByColumnAndRow(16, $rn)->getValue();
            $col17 = $workSheet->getCellByColumnAndRow(17, $rn)->getValue();
            $col18 = $workSheet->getCellByColumnAndRow(18, $rn)->getValue();
            $col19 = $workSheet->getCellByColumnAndRow(19, $rn)->getValue();
            $col20 = $workSheet->getCellByColumnAndRow(20, $rn)->getValue();
            $col21 = $workSheet->getCellByColumnAndRow(21, $rn)->getValue();
            $col22 = $workSheet->getCellByColumnAndRow(22, $rn)->getValue();
            $col23 = $workSheet->getCellByColumnAndRow(23, $rn)->getValue();
            $col24 = $workSheet->getCellByColumnAndRow(24, $rn)->getValue();
            $ad = $xml->createElement('Ad');
            $ad->appendChild($xml->createElement('Id', $col1));
            $ad->appendChild($xml->createElement('Category', $col2));
            $ad->appendChild($xml->createElement('OperationType', $col3));
            $ad->appendChild($xml->createElement('DateBegin', $col4));
            $ad->appendChild($xml->createElement('DateEnd', $col5));
            $ad->appendChild($xml->createElement('Region', $col6));
            $ad->appendChild($xml->createElement('City', $col7));
            $ad->appendChild($xml->createElement('Description', $col8));
            $ad->appendChild($xml->createElement('Price', $col9));
            $ad->appendChild($xml->createElement('CompanyName', $col10));
            $ad->appendChild($xml->createElement('ManagerName', $col11));
            $ad->appendChild($xml->createElement('EMail', $col12));
            $ad->appendChild($xml->createElement('ContactPhone', $col13));
            $images = $xml->createElement('Images');
            $imgArr = StringHelper::explode($col14, ',', true, true);
            foreach ($imgArr as $img) {
                $image = $xml->createElement('Image');
                $image->setAttribute('url', $img);
                $images->appendChild($image);
            }
            $ad->appendChild($images);
            $ad->appendChild($xml->createElement('Rooms', $col15));
            $ad->appendChild($xml->createElement('Square', $col16));
            $ad->appendChild($xml->createElement('Floor', $col17));
            $ad->appendChild($xml->createElement('Floors', $col18));
            $ad->appendChild($xml->createElement('HouseType', $col19));
            $ad->appendChild($xml->createElement('MarketType', $col20));
            $ad->appendChild($xml->createElement('NewDevelopmentId', $col21));
            $ad->appendChild($xml->createElement('PropertyRights', $col22));
            $ad->appendChild($xml->createElement('CadastralNumber', $col23));
            $ad->appendChild($xml->createElement('DateBegin2', $col24));
            $root->appendChild($ad);
        }
        $xml->appendChild($root);
        $xml->save($this->_pathOutXml . DS . 'avito-' . $this->randomName . '.xml');
        return true;
    }

    /**
     * @param string $file
     * @return bool
     */
    private function convertCian($file)
    {
        if (!$workSheet = $this->getWorkSheet($file)) return false;
        $xml = new DOMDocument('1.0', 'UTF-8');
        $root = $xml->createElement('feed');
        foreach ($workSheet->getRowIterator(4) as $rn => $row) {
            $col1 = $workSheet->getCellByColumnAndRow(1, $rn)->getValue();
            $col2 = $workSheet->getCellByColumnAndRow(2, $rn)->getValue();
            $col3 = $workSheet->getCellByColumnAndRow(3, $rn)->getValue();
            $col4 = $workSheet->getCellByColumnAndRow(4, $rn)->getValue();
            $col5 = $workSheet->getCellByColumnAndRow(5, $rn)->getValue();
            $col6 = $workSheet->getCellByColumnAndRow(6, $rn)->getValue();
            $col7 = $workSheet->getCellByColumnAndRow(7, $rn)->getValue();
            $col8 = $workSheet->getCellByColumnAndRow(8, $rn)->getValue();
            $col9 = $workSheet->getCellByColumnAndRow(9, $rn)->getValue();
            $col10 = $workSheet->getCellByColumnAndRow(10, $rn)->getValue();
            $col11 = $workSheet->getCellByColumnAndRow(11, $rn)->getValue();
            $col12 = $workSheet->getCellByColumnAndRow(12, $rn)->getValue();
            $col13 = $workSheet->getCellByColumnAndRow(13, $rn)->getValue();
            $col14 = $workSheet->getCellByColumnAndRow(14, $rn)->getValue();
            $col15 = $workSheet->getCellByColumnAndRow(15, $rn)->getValue();
            $col16 = $workSheet->getCellByColumnAndRow(16, $rn)->getValue();
            $col17 = $workSheet->getCellByColumnAndRow(17, $rn)->getValue();
            $col18 = $workSheet->getCellByColumnAndRow(18, $rn)->getValue();
            $col19 = $workSheet->getCellByColumnAndRow(19, $rn)->getValue();
            $col20 = $workSheet->getCellByColumnAndRow(20, $rn)->getValue();
            $col21 = $workSheet->getCellByColumnAndRow(21, $rn)->getValue();
            $col22 = $workSheet->getCellByColumnAndRow(22, $rn)->getValue();
            $col23 = $workSheet->getCellByColumnAndRow(23, $rn)->getValue();
            $col24 = $workSheet->getCellByColumnAndRow(24, $rn)->getValue();
            $col25 = $workSheet->getCellByColumnAndRow(25, $rn)->getValue();
            $col26 = $workSheet->getCellByColumnAndRow(26, $rn)->getValue();
            $col27 = $workSheet->getCellByColumnAndRow(27, $rn)->getValue();
            $col28 = $workSheet->getCellByColumnAndRow(28, $rn)->getValue();
            $col29 = $workSheet->getCellByColumnAndRow(29, $rn)->getValue();
            $col30 = $workSheet->getCellByColumnAndRow(30, $rn)->getValue();
            $col31 = $workSheet->getCellByColumnAndRow(31, $rn)->getValue();
            $col32 = $workSheet->getCellByColumnAndRow(32, $rn)->getValue();
            $col33 = $workSheet->getCellByColumnAndRow(33, $rn)->getValue();
            $col34 = $workSheet->getCellByColumnAndRow(34, $rn)->getValue();
            $object = $xml->createElement('object');
            $object->appendChild($xml->createElement('Category', $col1));
            $object->appendChild($xml->createElement('ExternalId', $col2));
            $object->appendChild($xml->createElement('Description', $col3));
            $object->appendChild($xml->createElement('Address', $col4));
            $coordinates = $xml->createElement('Coordinates');
            $coordinates->appendChild($xml->createElement('Lat', $col5));
            $coordinates->appendChild($xml->createElement('Lng', $col6));
            $object->appendChild($coordinates);
            $phones = $xml->createElement('Phones');
            $phoneschema = $xml->createElement('PhoneSchema');
            $phoneschema->appendChild($xml->createElement('CountryCode', $col7));
            $phoneschema->appendChild($xml->createElement('Number', $col8));
            $phones->appendChild($phoneschema);
            $object->appendChild($phones);
            $underground = $xml->createElement('Underground');
            $underground->appendChild($xml->createElement('TransportType', $col9));
            $underground->appendChild($xml->createElement('Time', $col10));
            $underground->appendChild($xml->createElement('Id', $col11));
            $object->appendChild($underground);
            $object->appendChild($xml->createElement('TotalArea', $col12));
            $object->appendChild($xml->createElement('MinArea', $col13));
            $object->appendChild($xml->createElement('MaxArea', $col14));
            $object->appendChild($xml->createElement('FloorNumber', $col15));
            $object->appendChild($xml->createElement('ConditionType', $col16));
            $object->appendChild($xml->createElement('IsOccupied', $col17));
            $photos = $xml->createElement('Photos');
            $imgArr = StringHelper::explode($col18, ',', true, true);
            foreach ($imgArr as $key => $img) {
                $photoschema = $xml->createElement('PhotoSchema');
                $photoschema->appendChild($xml->createElement('FullUrl', $img));
                $photoschema->appendChild($xml->createElement('IsDefault', ($key === 0) ? 'True' : 'False'));
                $photos->appendChild($photoschema);
            }
            $object->appendChild($photos);
            $businessshoppingcenter = $xml->createElement('BusinessShoppingCenter');
            $businessshoppingcenter->appendChild($xml->createElement('Id', $col19));
            $object->appendChild($businessshoppingcenter);
            $building = $xml->createElement('Building');
            $building->appendChild($xml->createElement('FloorsCount', $col20));
            $building->appendChild($xml->createElement('BuildYear', $col21));
            $building->appendChild($xml->createElement('TotalArea', $col22));
            $building->appendChild($xml->createElement('Type', $col23));
            $building->appendChild($xml->createElement('ClassType', $col24));
            $infrastructure = $xml->createElement('Infrastructure');
            $infrArr = StringHelper::explode($col25, ',', true, true);
            foreach ($infrArr as $tagName) {
                $infrastructure->appendChild($xml->createElement($tagName, 'true'));
            }
            $building->appendChild($infrastructure);
            $object->appendChild($building);
            $publishterms = $xml->createElement('PublishTerms');
            $terms = $xml->createElement('Terms');
            $publishtermschema = $xml->createElement('PublishTermSchema');
            $services = $xml->createElement('Services');
            $services->appendChild($xml->createElement('ServicesEnum', $col26));
            $publishtermschema->appendChild($services);
            $terms->appendChild($publishtermschema);
            $publishterms->appendChild($terms);
            $object->appendChild($publishterms);
            $bargainterms = $xml->createElement('BargainTerms');
            $bargainterms->appendChild($xml->createElement('Price', $col27));
            $bargainterms->appendChild($xml->createElement('PriceType', $col28));
            $bargainterms->appendChild($xml->createElement('Currency', $col29));
            $bargainterms->appendChild($xml->createElement('PaymentPeriod', $col30));
            $bargainterms->appendChild($xml->createElement('VatType', $col31));
            $bargainterms->appendChild($xml->createElement('LeaseType', $col32));
            $includedoptions = $xml->createElement('IncludedOptions');
            $includedoptions->appendChild($xml->createElement('IncludedOptionsEnum', $col33));
            $bargainterms->appendChild($includedoptions);
            $bargainterms->appendChild($xml->createElement('ClientFee', $col34));
            $bargainterms->appendChild($xml->createElement('AgentFee', $col35));
            $object->appendChild($bargainterms);
            $root->appendChild($object);
        }
        $xml->appendChild($root);
        $xml->save($this->_pathOutXml . DS . 'cian-' . $this->randomName . '.xml');
        return true;
    }

}

/**/