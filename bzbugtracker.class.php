<?php
/**
* botzilla bug tracker
*@author bibby
*$Id: bzbugtracker.class.php,v 1.1 2009/01/11 17:57:27 abibby Exp $

Commands

addBug {bug text}  # adds a bug item, assigned to you
assignBug {bug#} {toPerson=you}  # assigns a bug to a person
deleteBug {bug#} # completely removes a bug item
bugStatus {bug#} {toStatus#} # sets a status to a bug. Provide no args to get a list of statuses
bugText {bug#} {new bug text} # sets the description of the bug
closeBug {bug#} # sets a bug status to Resolved
showBug {bug#} # prints the bug data
openBugs # prints open bug tickets
** //*/

define('BZB_ASSIGNED',0);
define('BZB_CLOSED',1);
define('BZB_LATER',2);

class bzbugtracker extends ziggi
{
	var $bugs = array();
	
	function bzbugtracker()
	{
		$this->bugfile = PERM_DATA."bz_bugs"; // file that stores the bugs
		$this->statuses=array( 
			BZB_ASSIGNED=>'Assigned',
			BZB_CLOSED=>'Resolved',
			BZB_LATER=>'Later/Ignore' 
		);
		$this->read();
	}
	
	/** JSON functions. If you're running PHP 4, this requires the 
	PEAR:PEPr Services_JSON class.  
	http://pear.php.net/pepr/pepr-proposal-show.php?id=198
	**/
	
	/**
	@access public
	@param mixed
	@return string json
	*/
	function encode($what)
	{
		if(function_exists('json_encode'))
			return json_encode($what);
		else
		{
			require_once BZ_CLS_PATH.'Services_JSON.class.php';
			$json = new Services_JSON();
			return $json->encode($what);
		}
	}
	
	/**
	@access public
	@param string json
	@return mixed
	*/
	function decode($what)
	{
		if(function_exists('json_decode'))
			return json_decode($what);
		else
		{
			require_once BZ_CLS_PATH.'Services_JSON.class.php';
			$json = new Services_JSON();
			return $json->decode($what);
		}
	}
	
	/*** parseBuffer **
	Every ziggi must have this. It listens to input and responds
	*/
	function parseBuffer()
	{
		if($this->isEmpty()) return;
		$cmd = $this->getArg(0);
		if(substr($cmd,0,1)!=CMD_CHAR) return;
		$cmd = substr($cmd,1);
		$bug = (int)$this->getArg(1);
		switch($cmd)
		{
			case 'addBug':
				$i = $this->addBug( $this->getArgText() );
				$this->showBug($i);
				break;
			case 'assignBug':
				if($bug)
				{
					$to = $this->getArg(2)?$this->getArg(2):$this->getUser();
					if( $this->assignBug($bug, $to ) )
						$this->pm("BZBug $bug assigned to $to");
					else
						$this->pm("did not understand..  bug#=$bug ; assignto=$to ??");
				}
				break;
			case 'deleteBug':
				if($bug)
				{
					$this->deleteBug($bug);
					$this->pm("toast");
				}
				break;
			case 'bugStatus':
				if($bug)
				{
					$status = (int)$this->getArg(2);
					if($status!==FALSE)
					{
						$this->setStatus($bug,$status);
					}
					$bug = $this->getBug($bug);
					$this->pm("BZBug ".$bug['id']." status = ". $bug['status']);
				}
				else
				{
					foreach($this->statuses as $k=>$v)
						$this->pm("$k - $v");
						$this->pm("to set: .bugStatus {bug#} {status#}");
				}
			
				break;
			case 'bugText':
				if($bug)
				{
					$text = $this->getArgs();
					array_shift($text);
					array_shift($text);
					$text = join(" ",$text);
					if($text)
					{
						$this->setDescript($bug,$text);
					}
					
					$this->showBug($bug);
				}
				break;
			case 'closeBug':
				if($bug)
				{
					if( $this->setStatus($bug,BZB_CLOSED) )
					{
						$this->pm("BZBug $bug resolved. ".$this->thx());
					}
				}
				break;
			case 'showBug':
				if($bug)
					$this->showBug($bug);
				break;
			case 'openBugs':
				$this->openBugs();
				break;
		}
	}
	
	/**
	Reads the storage file to get bug items
	@access private
	@return void
	*/
	function read()
	{
		$this->bugs = array();
		$fs = new FileStorage( $this->bugfile, FS_READ);
		$fs->read();
		
		$bugs = $this->decode( current($fs->contents) );
		if(is_array($bugs))
		{
			foreach($bugs as $b)
				$this->bugs[]=get_object_vars($b);
		}
	}
	
	/**
	Writes bug items to file
	@access private
	@return void
	*/
	function write()
	{
		$fs = new FileStorage( $this->bugfile, FS_WRITE);
		$fs->write( $this->encode($this->bugs) );
	}
	
	/**
	Finds a bug item by id
	@access private
	@param int bug number
	@param bool return internal array index instead of the bug item
	@return mixed : array if bug, int if getIndex=true
	*/
	function getBug($num,$getIndex=false)
	{
		foreach($this->bugs as $i=>$b)
		{
			if($b['id'] == $num)
				return ($getIndex? $i:$b);
		}
		return false;
	}
	
	/**
	Adds a trackable item
	@access private
	@param string bug description
	@return int bug id
	*/
	function addBug($txt)
	{
		$i=1;
		foreach($this->bugs as $b)
		{
			if($b['id'] >= $i)
				$i=$b['id']+1;
		}
		
		$this->bugs[] = array(
			'id'=>$i,
			'assigned'=>$this->getUser(),
			'bug'=>$txt,
			'status'=>$this->statuses[0]
		);
		
		$this->write();
		return $i;
	}
	
	/**
	Removes a bug item
	@access private
	@param int bug number
	@return void
	*/
	function deleteBug($num)
	{
		if(($i = $this->getBug($num,true))!==FALSE)
		{	
			unset($this->bugs[$i]);
			$this->bugs = array_values($this->bugs); #reindex
			$this->write();
		}
	}
	
	/**
	Sets a property to a bug
	@access private
	@param int bug id
	@param property name
	@param string property value
	@return bool success
	*/
	function setToBug($bug,$prop,$to)
	{
		$bug = $this->getBug($bug,TRUE);
		if(!$this->bugs[$bug])
			return false;
		$this->bugs[$bug][$prop] = $to;
		$this->write();
		return true;
	}
	
	/**
	Sets a bug status
	@access private
	@param int bug id
	@param int status id
	@return void
	*/
	function setStatus($bug,$status)
	{
		return $this->setToBug( $bug, 'status', $this->statuses[$status]?$this->statuses[$status]:$status);
	}
	
	/**
	Sets a developer to fix bug
	@access private
	@param int bug id
	@param string someone's nick
	@return void
	*/
	function assignBug($bug, $user)
	{
		return $this->setToBug( $bug, 'assigned', $user);
	}
	
	/**
	changes the bug text
	@access private
	@param int bug id
	@param string bug text
	@return void
	*/
	function setDescript($bug,$txt)
	{
		return $this->setToBug( $bug, 'bug', $txt);	
	}
	
	/**
	Prints the bug
	@access private
	@param int bug id
	@param bool return text instead of printing to channel
	@return void or string
	*/
	function showBug($num,$noPrint=FALSE)
	{
		$bug = $this->getBug($num);
		if(!$bug)
		{
			$this->pm("could'nt find bug $num");
			return;
		}
		
		$s = sprintf("BZBug %d; Assigned to %s; Status: %s; %s ", $bug['id'], $bug['assigned'], $bug['status'], $bug['bug']);
		if($noPrint)
			return $s;
		else
			$this->pm($s);
	}
	
	/**
	returns a random thank you message
	@access public
	@return string kthxbye
	*/
	function thx()
	{
		$thx=array(
			"Yay! I so happy!",
			":D :D :D",
			"awe. some.",
			"Thanks a lot, really.",
			"You're the best!",
			"I feel better already"
		);
		return $thx[array_rand($thx)];
	}
	
	
	/**
	prints open bug items. If too many, will send it as a PM to the asker
	@access private
	@return void
	*/
	function openBugs()
	{
		$open = array();
		foreach( $this->bugs as $b)
		{
			$si = array_search($b['status'],$this->statuses);
			if($si >= BZB_CLOSED)
				continue;
			$open[] = $this->showBug($b['id'],TRUE);
		}
		
		$num_bugs = count($open);
		
		if($num_bugs < 5)
			$this->pm($open);
		elseif($num_bugs == 0)
		{
			$this->pm("yay, no open bugs!");
		}
		else
		{
			$this->pm("$num_bugs found, sending you a private message, ".$this->getUser());
			foreach($open as $l)
				$this->pm($l, $this->getUser());
		}
	}
}
?>
