<?php
/**
http://xkcd.com/37/
@author Dave Gucwa
$Id: sweetass.class.php,v 1.2 2008/06/10 17:06:56 dgucwa Exp $
** //*/

class sweetass extends ziggi
{
	function sweetass()
	{
	}
	
	function parseBuffer()
	{
		if($this->isEmpty())
			return false;
		
		if (preg_match('/[a-z]-ass [a-z]/i', $this->getInput(), $matches))
			$this->pm(preg_replace('/-(ass) /i', ' \1-', $this->getInput()));
	}
}
?>
