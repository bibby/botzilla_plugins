<?php
/**
* you're a ___
*@author bibby
*$Id: ur.class.php,v 1.3 2008/06/16 13:39:58 abibby Exp $
** //*/

class ur extends ziggi
{
	/***
	//*/
	function ur()
	{
		$this->words=array();
	}
	
	/***
	//*/
	function parseBuffer()
	{
		if($this->isEmpty())
			return false;
		
		$c=$this->getArg(0);
		
		$cmds= array(CMD_CHAR.'ur'
				,CMD_CHAR.'ura'
				,CMD_CHAR.'u'
				,CMD_CHAR.'urmom'
				,CMD_CHAR.'my');
		
		if(in_array($c,$cmds))
		{
			switch($this->getArg(0))
			{
				case $cmds[0]:
					$this->youre($this->getArg(1));
					break;
				case $cmds[1]:
					$this->youre_a($this->getArg(1));
					break;
				case $cmds[2]:
					$this->you($this->getArg(1));
					break;
				case $cmds[3]:
					$this->yourmom($this->getArg(1));
					break;
				case $cmds[4]:
					$this->my($this->getArg(1));
					break;
			}
		}
		else
		{
			if(trim($this->getInput()!=''))
				$this->words[$this->getOrigin()] = array_reverse(explode(' ',$this->getInput()));
		}
	}
	
	
	/***
	//*/
	function chanSet($chan)
	{
		if($this->getText() != $this->getInput())
			return array_reverse(explode(" ",$this->getText()));
			
		return ($this->words[$chan] ? $this->words[$chan] : array());
	}
	
	/***
	//*/
	function words($num)
	{
		if(!$num) $num=1;
		if(is_array($words = $this->chanSet($this->getOrigin()) ) )
			$r= array_reverse(array_slice($words,0,$num));
			
		$len = intval($this->getArg(2));
		if($len>0)
			$r=array_slice($r,0,$len);
		
		return implode(' ',$r);
	}
	
	/***
	//*/
	function youre($num=1)
	{
		$words = $this->words($num);
		if($words)
			$this->pm("you're ".$words);
	}
	
	/***
	//*/
	function you($num=1)
	{
		$words = $this->words($num);
		if($words)
			$this->pm("you ".$words);
	}
	
	/***
	//*/
	function youre_a($num=1)
	{
		$words = $this->words($num);
		if($words)
		{
			if(in_array(  substr( strtolower($words) ,0,1) , array('a','e','i','o','u') ) )
				$this->pm("you're an ".$words);
			else
				$this->pm("you're a ".$words);
		}
	}
	
	/***
	//*/
	function yourmom($num=1)
	{
		$words = $this->words($num);
		if($words)
			$this->pm("your mom's ".$words);
	}
	
	/***
	//*/
	function my($num=1)
	{
		$words = $this->words($num);
		if($words)
			$this->pm("I'll show you my ".$words);
	}
}
?>
