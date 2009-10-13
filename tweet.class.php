<?php
/**
* Twitter Gitter!
*@author bibby <bibby@surfmerchants.com>
$Id: Tweet.class.php,v 1.4 2009/09/22 18:21:58 abibby Exp $
** //*/

class Tweet extends ziggi
{
	function Tweet()
	{
		$this->url_fmt = 'http://twitter.com/statuses/show/{num}.json'; 
	}
	
	function parseBuffer()
	{
		$cmd = $this->getCmd();
		
		if($cmd == 'tweet' && is_numeric($this->getArg(1)))
			$this->getTweet($this->getArg(1));
			
	}
	
	function getTweet($num)
	{
		$url = str_replace('{num}',$num,$this->url_fmt);
		
		$json = `wget --quiet -O - $url`;
		$obj = json_decode($json);
		if(is_object($obj))
		{
			$str = $obj->text
			. " - ".$obj->user->name
			. " (@".$obj->user->screen_name.") ";
			
			$this->pm($str);
		}
	}
}
?>
