<?php
/**
* This class featches the current weather and forecast for a zip code from the weather channel's rss
*@author bibby <bibby@surfmerchants.com>
*$Id$
*
*@requires xml_parser.php
*http://www.phpclasses.org/browse/package/4.html
* by Manuel Lemos
* http://www.manuellemos.net/
*/
require_once(BZ_CLS_PATH.'xml_parser.php');

class WeatherChan extends ziggi
{
	function WeatherChan()
	{
		$this->cache='/tmp/botzilla_xml';
	}
	
	
	/*** parseBuffer ***
	@access
	@param
	@return
	*/
	function parseBuffer()
	{
		if($this->getArg(0) == CMD_CHAR.'weather')
			$this->weather($this->getArg(1));
			
		if($this->getArg(0) == CMD_CHAR.'forecast')
			$this->forecast($this->getArg(1));
	}
	
	
	/*** getWeather ***
	@access
	@param
	@return
	*/
	function getWeather($zip)
	{
		if(!$zip) $zip = '02111';
		$file=file_get_contents('http://rss.weather.com/weather/rss/local/'.$zip.'?cm_ven=LWO&cm_cat=rss&par=LWO_rss');
		$fs = new FileStorage($this->cache,FS_WRITE);
		$fs->write($file);
		
		$error=XMLParseFile($parser,$this->cache,0);
		
		if($error)
			return $error;
		else
		{
			$ret=array();
			foreach($parser->structure as $k => $v)
			{
				if(is_string($v) && strstr($v, 'For more')!==FALSE)
				{
					$v=str_replace('&deg;','°',$v);
					if(strstr($v,'---')!==FALSE)
					{
						$days = explode('---',$v);
						$junk = array_pop($days);
						$ret['forecast']=$days;
					}
					else
					{
						$v=strip_tags($v);
						$ret['current'] = substr($v,0, strpos($v,'For more')-1);
					}
				}
				if(count($ret)==2)
					break;
			}
		}
		
		return $ret;
	}
	
	
	/*** weather ***
	@access
	@param
	@return
	*/
	function weather($zip)
	{
		$w = $this->getWeather($zip);
		$this->pm( !is_array($w) ? "sorry, couldn't get zip $zip" : $w['current'] );
	}
	
	
	/*** forecast ***
	@access
	@param
	@return
	*/
	function forecast($zip)
	{
		$w = $this->getWeather($zip);
		$this->pm( !is_array($w) ? "sorry, couldn't get zip $zip" : $w['forecast'] );
	}
}

?>
