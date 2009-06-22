<?php
/**
* Search the reference.com family of sites for stuff
( encylopedic .reference was recently removed for being crappy)
.dict {term} 
.thes {term}
*@author bibby <bibby@surfmerchants.com>
$Id: dict_thes.class.php,v 1.1 2009-06-05 03:36:33 botzilla Exp $
*/

class dict_thes extends ziggi
{
	function dict_thes()
	{
	}
	
	function parseBuffer()
	{
		$c = $this->getArg(0);
		$cmd = strtolower(substr($c,1));
		if(substr($c,0,1)==CMD_CHAR && method_exists(  $this , $cmd ) )
			$this->$cmd($this->getArgText());
	}
	
	/***
	dictionary.com
	//*/
	function dict()
	{
		$data=file_get_contents('http://dictionary.reference.com/browse/'.urlencode($this->getArgText()));
		if(!$data) return;
		$exp = '/<tr> <td width="35" class="dnindex">.*<\/tr>/';
		preg_match($exp,$data,$matches);
		if(is_array($matches) && count($matches)>0)
		{
			$defs = explode('</tr>',current($matches));
			foreach($defs as $i=>$d)
			{
				$exp = '/<td.*<\/td>/';
				preg_match($exp,$d,$m);
				$defs[$i]=trim(strip_tags(current($m)));
			}
			
			$defs=array_filter($defs);
			$this->pm($defs);
		}
	}
	
	
	/***
	thesaurus.com
	//*/
	function thes()
	{
		$x = str_replace(array("\n","\t","\r"),'',file_get_contents('http://thesaurus.reference.com/browse/'.urlencode($this->getArgText())));
		$exp = '/<td valign="top" nowrap ><b>Main Entry:.*<\/span><\/td>/';
		preg_match($exp,$x,$matches);
		if(is_array($matches) && count($matches)>0)
		{
			$defs = explode('</tr>',current($matches));
			$allowed = 2;
			$out=array();
			foreach($defs as $i=>$d)
			{
				$exp = '/<td.*<\/td>/';
				preg_match($exp,$d,$m);
				$defs[$i]=trim(strip_tags(current($m)));
				while(strpos($defs[$i],"  ")!==FALSE)
					$defs[$i]=str_replace("  "," ",$defs[$i]);
					
				if(substr($defs[$i],0,4) == 'Main')
					$allowed--;
					
				if($allowed<0)
					break;
					
				$out[$i] = $defs[$i];
			}
			
			$output=array();
			$vi=0;
			foreach($out as $i=>$v)
			{
				$vi=$i;
				if(substr($v,0,4) == 'Main')
				{
					$vi=$i+1;
					$v.=". ";
				}
				$output[$vi].=$v;
			}
			
			$output=array_values(array_filter($output));
			$this->pm($output);
		}
	}
}
?>
