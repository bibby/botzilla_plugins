<?php
/**
* CLI interface for messaging
* usage::
* echo -e "testing \n1 \n2" >> ~/botzilla/sayit/\#botzilla
*@author bibby <bibby@botzilla.org>
*$Id: sayit.class.php,v 1.4 2009/06/20 15:17:26 abibby Exp $
*/

define('SAYIT', BZ_PATH.'sayit/');

class SayIt extends ziggi
{
	function SayIt()
	{
		if(!is_dir(SAYIT))
			mkdir(SAYIT);
		
		// gives bot time to start up and settle in before listening
		$this->lastCheck = time() + 30;
		$this->checkInterval = 2;
	}
	
	function parseBuffer()
	{
		$t= time();
		if($t <= $this->lastCheck)
			return;
			
		$this->lastCheck = $t;
		
		$files = scandir(SAYIT);
		if(is_array($files))
		{
			foreach($files as $f)
			{
				if( substr($f,0,1) != '.')
				{
					$say = file(SAYIT.$f);
					if(is_array($say) && count($say))
					{
						$say=array_map('trim', $say);
						foreach($say as $s)
							$this->pm( $s, $f);
						unlink(SAYIT.$f);
					}
				}
			}
		}
	}
}
?>
