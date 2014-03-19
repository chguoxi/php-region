<?php

/**
 * PHP 全球地区数据类
 * 获取全球各国家，省份/州 ，市，区(仅中国大陆有)等地区数据。
 * 所有地区数据保存在本地的 ./data/loc_28.xml文件
 * 
 * @author goss, <chguoxi@gmail.com>
 * @license Common Public Attribution License Version 1.0 (CPAL-1.0)
 * @version 1.0
 */

class Region {
	/**
	 * XML文件存放位置
	 * 
	 * @var string
	 */
	private $regionFilename = './data/loc_28.xml';
	
	/**
	 * 关联数组缓存文件保存的路径
	 * 
	 * @var string
	 */
	private $cacheFilename = './data/loc';
	
	/**
	 * simpleXML 对象 
	 * 
	 * @var obj
	 */
	private $regionXML;
	
	/**
	 * 国家列表
	 * 
	 * @var array
	 */
	private $countryList = array();
	
	/**
	 * 获取的地区列表中地区名数据结构是 array('name'=>'香港','code'=>'81')
	 * 可以通过修改$nameKey和$codeKey属性修改这两个键值
	 * @var string
	 */
	private $nameKey = 'name';
	
	/**
	 * 请参见 $nameKey变量说明
	 * @var string
	 */
	private $codeKey = 'code';
	
	/**
	 * 地区的关联数组
	 * 
	 * @var array
	 * @example array('1_11_9'=>'门头沟');
	 *     其中 1 是"中国"的国家代码,11是"北京"的省份代码,9是"门头沟"的代码。
	 *     以此类推，如果只想中国的键值是  "1",北京的键值是 "1_11";
	 * 
	 */
	protected $associateArr = array();
	
	/**
	 * 构造函数,初始化xml对象
	 */
	public function __construct(){
		if ( !file_exists($this->regionFilename) ){
			die('找不到地区XML文件，请确认 regionFilename 文件路径是否正确');
		}
		$this->regionXML = simplexml_load_file($this->regionFilename);
	}
	
	/**
	 * 根据国家代码获取指定国家的XML对象
	 * 
	 * @param string $countryCode
	 */
	private function getCountryXML($countryCode){
		foreach ( $this->regionXML->CountryRegion as $key=>$country ){
			if ( (string)$country->attributes()->Code == (string)$countryCode ){
				$countryXML = $country;
				break;
			}
		}
		return isset($countryXML) ? $countryXML : false;
	}
	
	/**
	 * 根据国家和省份/州代码获取省份/州XML对象
	 * 
	 * @param string $countryCode 国家代码
	 * @param string $stateCode 省份/州代码
	 */
	private function getStateXML($countryCode,$stateCode){
		$countryXML = $this->getCountryXML($countryCode);
		if ($countryXML === false){
			return false;
		}
		foreach ( $countryXML->State as $key=>$state ){
			if ( (string)$state->attributes()->Code == (string)$stateCode ){
				$stateXML = $state;
				break;
			}
		}
		return isset($stateXML) ? $stateXML : false;
	}
	
	/**
	 * 获取指定城市的XML对象
	 * 
	 * @param string $countryCode 国家代码
	 * @param string $stateCode 省份/州代码
	 * @param string $cityCode 城市代码
	 * @return mixed
	 */
	private function getCityXML($countryCode,$stateCode,$cityCode){
		$stateXML = $this->getStateXML($countryCode, $stateCode);
		if ( $stateXML===false ){
			return false;
		}
		foreach ( $stateXML->City as $key=>$city ){
			if ( (string)$city->attributes()->Code == (string)$cityCode ){
				$cityXML = $city;
				break;
			}
		}
		return isset($cityXML) ? $cityXML : false;
	}
	
	/**
	 * 获取地区关联数组
	 * 
	 * @param object $regionXML
	 * @return array 二维数组
	 */
	private function toAssociateArr($regionXML){
		foreach ( $regionXML->children() as $key=>$xml ){
			if ( $xml->getName() == 'CountryRegion' ){
				//每次循环完一个国家的地区,就清空一次保存键值的变量
				$this->asscKey = array();
				$this->asscKey[0] = (string)$xml->attributes()->Code;
			}

			if ( $xml->getName() == 'State' ){
				$this->asscKey[1] = (string)$xml->attributes()->Code;
				//当前节点是 “省份” ，应该删除 “市”和“区” 的键值段，以下同理
				if ( isset($this->asscKey[2]) ){
					unset($this->asscKey[2]);
				}
				if ( isset($this->asscKey[3]) ){
					unset($this->asscKey[3]);
				}
			}

			if ( $xml->getName() == 'City' ){
				$this->asscKey[2] = (string)$xml->attributes()->Code;
				if ( isset($this->asscKey[3]) ){
					unset($this->asscKey[3]);
				}
			}

			if ( $xml->getName() == 'Region' ){
				$this->asscKey[3] = (string)$xml->attributes()->Code;
			}

			$cacheKey = implode('_', $this->asscKey);
			$this->AssociateArr[$cacheKey] = (string)$xml->attributes()->Name;
			//结束递归条件
			if ( $regionXML->children() ){
				$this->toAssociateArr($xml);
			}
		}
		
		return $this->AssociateArr;
	}
	
	/**
	 * 缓存二维地区数组，保存到文件
	 */
	private function cacheRegionList(){
		$regionArr = $this->toAssociateArr($this->regionXML);
		if ( !count($regionArr) ){
			return false;
		}
		$fh = fopen($this->cacheFilename, 'w+');
		fputs($fh, serialize($regionArr));
		fclose($fh);
	}
	
	/**
	 * 获取关联数组
	 * 
	 * @return array 二维数组
	 */
	public function getAssociateArr(){
		if ( !file_exists($this->cacheFilename) ){
			$this->cacheRegionList();
		}
		$regionStr = file_get_contents($this->cacheFilename);
		return unserialize($regionStr);
	}
	
	/**
	 * 获取所有国家列表
	 * 
	 * @return array
	 */
	public function getCountryList(){
		foreach ( $this->regionXML->CountryRegion as $key=>$country ){
			$countryArr = array();
			foreach ( $country->attributes() as $name=>$val ){
				$countryArr[strtolower($name)] = (string)$val;
			}
			$this->countryList[] = $countryArr;
		}
		return $this->countryList;
	}
	
	/**
	 * 获取指定国家的省份/州列表
	 * 
	 * @param string $countryCode 国家代码
	 * @return array
	 */
	public function getStateList( $countryCode="1" ){
		$stateList = array();

		$countryXML = $this->getCountryXML($countryCode);
		if ( $countryXML ===false ){
			return false;
		}
		
		foreach ($countryXML->State as $key=>$state){
			$state = array(
					$this->nameKey =>(string)$state->attributes()->Name,
					$this->codeKey =>(string)$state->attributes()->Code
					);
			$stateList[] = $state;
		}
		return $stateList;
	}
	
	/**
	 * 获取指定省份的城市列表
	 * 
	 * @param string $countryCode 国家代码
	 * @param string $stateCode 城市代码
	 */
	public function getCityList( $countryCode, $stateCode ){
		$cityList = array();
		$stateXML = $this->getStateXML($countryCode, $stateCode);
		if ( $stateXML ===false ){
			return false;
		}
		
		foreach ( $stateXML->City as $key=>$cityXML ){
			$city = array(
					$this->nameKey =>(string)$cityXML->attributes()->Name,
					$this->codeKey =>(string)$cityXML->attributes()->Code
					);
			$cityList[] = $city;
		}
		
		return $cityList;
	}
	
	/**
	 * 获取指定的城市的区/县列表
	 * 
	 * @param string $countryCode 国家代码
	 * @param string $stateCode 省份/州代码
	 * @param string $cityCode  城市代码
	 * @return array
	 */
	public function getAreaList($countryCode,$stateCode,$cityCode){
		$areaList = array();
		$cityXML = $this->getCityXML($countryCode, $stateCode, $cityCode);
		
		if ( $cityXML === false ){
			return false;
		}
		
		if ( isset($cityXML->Region) ){
			foreach($cityXML->Region as $key=>$areaXML){
				$area = array(
						$this->nameKey =>(string)$areaXML->attributes()->Name,
						$this->codeKey =>(string)$areaXML->attributes()->Code
				);
				$areaList[] = $area;
			}			
		}
		return count($areaList) ? $areaList : false;
	}
	
	/**
	 * 根据地区代码获取地区名
	 * 可变参数方法，参数数目与实现功能如下：
	 *     传入一个参数时，获取国家名;
	 *     传入两个参数时，获取省份名;
	 *     传入三个参数时，获取城市名;
	 *     传入四个参数时，获取区/县名
	 * 
	 * @return string
	 * @example 
	 *     getRegionName('1');              //返回 "中国"
	 *     getRegionName('1','51');         //返回 "四川"
	 *     getRegionName('1','51','3');     //返回 "自贡"
	 *     getRegionName('1','51','3','4'); //返回 "大安区"
	 */
	public function getRegionName(){
		if ( !file_exists($this->cacheFilename) ){
			$this->cacheRegionList();
		}
		$asscArr = $this->getAssociateArr();
		$key = implode('_', func_get_args());
		return isset($asscArr[$key]) ? $asscArr[$key] : false;
	}

}