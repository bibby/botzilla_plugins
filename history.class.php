<?php
/**
* Records a breif channel history, and allows ^{int} to recall it. (good for piping)
*@author bibby <bibby@surfmerchants.com>
*$Id: history.class.php,v 1.2 2008/06/10 04:05:48 abibby Exp $
*/

define('HISTORY_MAX',16);
class history extends ziggi
{
	function history()
	{
		$this->channels=array();
	}
	
	function parseBuffer()
	{
		//don't allow piped text to record.
		if($this->piped())
			return false;
		
		$this->record();
		if(strpos($this->getText(),CARET_CHAR)!==FALSE)
		{
			$linesup=intval(substr($this->getInput(), strpos(" ".$this->getInput(),CARET_CHAR) ) );
			if($linesup>0)
				$this->pm($this->getLine($linesup));
		}
	}
	
	function record()
	{
		$o=$this->getOrigin();
		if(!is_array( $this->channels[$o] ) )
			$this->channels[$o]=array();
		
		array_unshift($this->channels[$o],$this->getInput());
		
		while(count($this->channels[$o]) > HISTORY_MAX)
			$cya = array_pop($this->channels[$o]);
	}
	
	function getLine($i=0)
	{
		$o=$this->getOrigin();
		return $this->channels[$o][$i];
	}
}
?>
