<?php
/**
* lolcat translation plugin
*@author Dave
*$Id: lolcat.class.php,v 1.1 2008/12/12 17:25:39 dgucwa Exp $
** //*/

class lolcat extends ziggi
{
	function lolcat ()
	{
		
	}
	
	/***
	@param void
	//*/
	function parseBuffer()
	{
		if ($this->isEmpty())
			return false;

		if (preg_match('/^'.CMD_CHAR.'lolcat( (.*))?/', $this->getInput(), $matches))
		{
			if ($this->piped())
				$textToTranslate = $this->getText();
			else
				$textToTranslate = $matches[2];
			
			$cmd = 'wget -O - "http://speaklolcat.com/?from='.urlencode($textToTranslate).'" 2>/dev/null | grep "textarea id=\"to\""';
			$result = `$cmd`;
			
			$result = strtolower($result);
			$result = str_replace('<textarea id="to" rows="3" cols="30">', '', $result);
			$result = str_replace('</textarea>', '', $result);
			$result = trim($result);
			
			$this->pm($result);
		}
	}
}
?>
