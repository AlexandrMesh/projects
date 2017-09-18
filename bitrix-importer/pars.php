<?php
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__) . "/../..");     // скрипт расположен : корень сайта/каталог/подкаталог/скрипт
// $_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__) . "/..");     // скрипт расположен : корень сайта/каталог/скрипт

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define('CHK_EVENT', true);

@set_time_limit(0);
@ignore_user_abort(true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

if (!CModule::IncludeModule("iblock")) {

    return;
}
if (!CModule::IncludeModule("search")) {

    return;
}

$file = 'https://domatv.by/local/parse/chekhly-na-kresla-ikea.json';

$string = file_get_contents($file);
$json = json_decode($string, true);

function getImg($img) {

    return CFile::MakeFileArray($img);
}

Bitrix\Main\Diag\Debug::startTimeLabel("run");
foreach($json as $key=>$offer) {
	
	$arFile = CFile::MakeFileArray($offer['img'][0]);
	$imageIdNew = CFile::SaveFile($arFile);
	$savefile = CFile::SaveFile($arFile);

	$arPictures = array_map('getImg', $offer['imgPhoto']);

	$PROP = [
	            "PARTNER_PRODUCT" => '13',
	            "PHOTO"           => $arPictures,
	            'ABOUT_BRAND'     => '<b>Страна производитель: </b>' . 'Италия'
	        ];
	
	$description_text = str_replace("                                ", ': ', str_replace("\n", NULL, trim(implode(";", $offer['description']))));
	$symbol_code = Cutil::translit($offer['title'] . $offer['price'], "ru", array("replace_space" => "-", "replace_other" => "-"));

	$arLoadProductArray = Array(
	  "MODIFIED_BY"    => $USER->GetID(), // элемент изменен текущим пользователем
	  "IBLOCK_SECTION_ID" => 682,          // элемент лежит в корне раздела
	  "IBLOCK_ID"      => 4,
	  "PROPERTY_VALUES"=> $PROP,
	  "NAME"           => $offer['title'],
	  "ACTIVE"         => "Y",  
	  "SORT"           => "501",          // активен
	  "CODE"           => $symbol_code,
	  "PREVIEW_TEXT"   => $offer['description'],
	  "PREVIEW_TEXT_TYPE" => 'html',
	  "DETAIL_TEXT"    => $offer['description'],
	  "DETAIL_TEXT_TYPE"     => 'html',
	  "PREVIEW_PICTURE" => CFile::MakeFileArray($imageIdNew),
	  "DETAIL_PICTURE"  => CFile::MakeFileArray($imageIdNew)
	  );
	
	$el = new CIBlockElement;
	if($PRODUCT_ID = $el->Add($arLoadProductArray)) {
	  echo "New ID: ".$PRODUCT_ID;
	}
	else {
	  echo "Error: ".$el->LAST_ERROR;
	}

	

	$arFields = array("ID" => $PRODUCT_ID, 'QUANTITY' => '2');
    if (CCatalogProduct::Add($arFields)) {
        // setItemPrice($PRODUCT_ID, $json);
        $PRICE_TYPE_ID = 1;

	    $arFields = Array(
	        "PRODUCT_ID"       => $PRODUCT_ID,
	        "CATALOG_GROUP_ID" => 1,
	        "PRICE"            => $offer['price'],
	        "CURRENCY"         => "BYR", //$item['currencyId'], // in yml-file code BYN
	        "QUANTITY_FROM"    => false,
	        "QUANTITY_TO"      => false
	    );

	    $res = CPrice::GetList(
	        array(),
	        array(
	            "PRODUCT_ID"       => $PRODUCT_ID,
	            "CATALOG_GROUP_ID" => 1
	        )
	    );

	    if ($arr = $res->Fetch()) {
	        CPrice::Update($arr["ID"], $arFields);
	    } else {
	        CPrice::Add($arFields);
	    }
    } else {
        echo 'Ошибка добавления параметров<br>';
    }
    $arFile = '';
	

};
Bitrix\Main\Diag\Debug::endTimeLabel("run");
echo "\n" . 'Time work: ' . Bitrix\Main\Diag\Debug::getTimeLabels()['run']['time'] . ' sec.' . "\n";