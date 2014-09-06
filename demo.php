<?php
header('Content-type:text/html;Charset=utf-8');
require './lib/region.class.php';

$rObj = new Region();

//国家列表
$countryList = $rObj->getCountryList();
//中国省份列表
$provinceList = $rObj->getStateList('1');
//广东所有城市列表
$cityList = $rObj->getCityList('1', '44');
//深圳所有区列表
$areaList = $rObj->getAreaList('1', '44', '3');
//所有地区关联数组
$allAreaArr = $rObj->getAssociateArr();
