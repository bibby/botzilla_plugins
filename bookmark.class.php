<?php
/**
* class for one-liners, including links, even those that take arguments
* A file PERM_DATA.'bookmarks' keeps the list of lines like handle::text .
* to make use of variables, use $1, $2, etc, and they'll be replaced by Arg(1), Arg(2)
*@author bibby
*$Id: bookmark.class.php,v 1.2 2008/05/31 03:19:34 abibby Exp $
** //*/

class bookmark extends ziggi
{
	var $handles=array();
	
	function bookmark()
	{
		$this->storage = PERM_DATA.'bookmarks';
		$this->getHandles();
	}
	
	function parseBuffer()
	{
		if(substr( $this->getArg(0),0,1) != CMD_CHAR)
			return false;
		
		$c = trim(substr( $this->getArg(0), 1));
		$b=$this->getArg(1);
		switch ($c)
		{
			case 'bookmark':
				if($b == '')
					$this->pm("nothing to add");
				elseif ($this->handles[$b])
					$this->pm("handle exists already. try it if you don't believe me.");
				else
					$this->addLink($b);
					
				break;
				
			case 'unmark':
				if($b =='')
						$this->pm("what's the link handle?");
				elseif(!$this->handles[$b])
					$this->pm("doesn't exist, so I guess that's ok.");
				else
					$this->removeLink($b);
				
				break;
				
			case 'bookmarks':
				$this->bookmarks();
				break;
			
			default:
				if($this->handles[$c]==TRUE) 
					$this->pmLink($c);
				
				break;
		}
	}
	
	function getHandles()
	{
		$fs = new FileStorage($this->storage);
		$fs->read();
		if(is_array($fs->contents))
			foreach($fs->contents as $line)
			{
				$x = explode('::',$line,2);
				if(trim($x[0])!='')
					$this->handles[$x[0]]=true;
			}
	}
	
	function addLink($handle)
	{
		$link = join(' ',array_slice($this->getArgs(),2));
		$fs = new FileStorage($this->storage,FS_APPEND);
		$fs->write( $handle .'::'. $link);
		
		$this->pm("added $handle");
		$this->handles[$handle]=true;
	}
	
	function removeLink($handle)
	{
		$new = array();
		$fs = new FileStorage($this->storage);
		$fs->read();
		foreach($fs->contents as $line)
		{
			$line=trim($line);
			$x = explode('::',$line,2);
			if($x[0]!=$handle)
				$new[]=$line;
		}
		
		unset($this->handles[$handle]);
		
		$fs = new FileStorage($this->storage, FS_WRITE);
		$fs->write($new);
		$this->pm('toast.');
	}
	
	function pmLink($handle)
	{
		$fs = new FileStorage($this->storage);
		$fs->read();
		foreach($fs->contents as $line)
		{
			$x = explode('::',$line,2);
			if($x[0]==$handle)
			{
				$link = $x[1];
				// var replace
				foreach(range(1,9) as $i)
					$link=str_replace("\$$i",$this->getArg($i),$link);
						
				$this->pm($link);
				return;
			}
		}
	}
	
	function bookmarks()
	{
		$this->pm('Bookmarks: '.join(', ',array_keys($this->handles)));
	}
}
?>
