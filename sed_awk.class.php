<?php
/**
* sed , awk, math
*@author Steve Benz (sed)
*@author bibby (awk, math)
*$Id: sed_awk.class.php,v 1.3 2008/06/13 16:04:15 abibby Exp $
** //*/

class sed_awk extends ziggi
{
    function sed_awk()
    {
        $this->words=array();
    }
    
    /***
	@param void
    //*/
    function parseBuffer()
    {
        $c=strtolower($this->getArg(0));
		
		//allowing the absence of CMD_CHAR for sed and awk
		if(substr($c,0,1)==CMD_CHAR)
			$c = substr($c,1);
			
        if(substr($c,0,2)=='s/')
		{
			$cA = explode('/',$this->getInput());
			if(($words = $this->chanSet()) !== FALSE)
				$this->pm(preg_replace('/'.$cA[1].'/',$cA[2],$words));
		}
		elseif(method_exists($this,$c) && $c!='chanSet')
			$this->$c();	
		else
            $this->words[$this->getOrigin()] = $this->getText();
     }
    
    
    /***
	@access private
	@param string channel/nick
	@return void
	keep channel specific buffers
    //*/
    function chanSet()
    {
		$chan = $this->getOrigin();
		if($this->piped())
			return $this->getText();
			
        return ($this->words[$chan] ? $this->words[$chan] : false);
    }
	
	/**
	AWK! 
	//*/
	function awk($pat=false)
	{
		$pat=($pat?$pat:$this->getArgText());
		$c = 'echo '.escapeshellarg($this->chanSet()).' | awk '.escapeshellarg($pat);
		echo "$c\n";
		$this->pm(shell_exec($c));
	}
	
	/**
	Math! (using awk)
	*/
	function math()
	{
		$math = ($this->piped() ? $this->getText() : $this->getArgText());
		$this->pm(shell_exec('echo|awk "{print '.$math.'}"'));
	}
}
?>
