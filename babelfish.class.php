<?php
/**
* babelfish translation-and-retranslation plugin
*@author Dave
*$Id: babelfish.class.php,v 1.1 2008/12/15 19:30:56 dgucwa Exp $
** //*/

class babelfish extends ziggi
{
	function babelfish ()
	{
		
	}
	
	/***
	@param void
	//*/
	function parseBuffer()
	{
		if ($this->isEmpty())
			return false;

		if (preg_match('/^'.CMD_CHAR.'babel(fish)?( (.*))?/', $this->getInput(), $matches))
		{
			if ($this->piped())
				$textToTranslate = $this->getText();
			else
				$textToTranslate = $matches[2];
			
			$cmd = 'wget -O - "http://babelfish.yahoo.com/translate_txt?trtext='.urlencode($textToTranslate).'&lp=en_de" 2>/dev/null | grep "div id=\"result\""';
			$result = utf8_decode(`$cmd`);
			
			$result = str_replace('<div id="result"><div style="padding:0.6em;">', '', $result);
			$result = str_replace('</div></div>', '', $result);
			$result = trim($result);
			$result = preg_replace('/&amp;?/', '&', $result);
			$result = preg_replace('/&quot;?/', '"', $result);
			$result = preg_replace('/&#039;?/', "'", $result);
			
			$cmd = 'wget -O - "http://babelfish.yahoo.com/translate_txt?trtext='.urlencode($result).'&lp=de_en" 2>/dev/null | grep "div id=\"result\""';
			$result = `$cmd`;
			
			$result = str_replace('<div id="result"><div style="padding:0.6em;">', '', $result);
			$result = str_replace('</div></div>', '', $result);
			$result = trim($result);
			$result = preg_replace('/&amp;?/', '&', $result);
			$result = preg_replace('/&quot;?/', '"', $result);
			$result = preg_replace('/&#039;?/', "'", $result);
			
			$this->pm($result);
		}
	}
}
?>
