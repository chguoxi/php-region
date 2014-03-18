php-region
==========

PHP 全球地区数据类,地区数据保存在 data/loc_28.xml 文件下，如果要使用简体中文以外的语言，翻译该文件的地区名称即可。

安装:

下载压缩文件，分别提取 lib/region.class.php 文件到你项目任意的程序可访问位置。默认情况下无需做任何配置，但如果你希望修改data/loc_28.xml 的文件路径，则需要修改 lib/region.class.php 的第19行和26行


private $regionFilename = '你的地区xml文件存放的位置';
	

private $cacheFilename = '你的缓存文件存放的位置';
	

	
使用示例：
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

//获取地区名称
$regionName = $rObj->getRegionName('1','11','1');
