<?php
/**
* temporary reminders in memory
* use:	.remind this is a reminder @ tomorrow 10:15am
*@author bibby <bibby@surfmerchants.com>
*/

class reminder extends ziggi
{
	function reminder()
	{
		$this->msgs = array();
	}
	
	function parseBuffer()
	{
		if($this->isEmpty())
			$this->checkMsgs();
		
		$cmd = $this->getCmd();
			
		if($cmd == 'remind')
			$this->addMsg();
	}
	
	function addMsg()
	{
		$user = $this->getUser();
		$origin = $this->getOrigin();
		$str = $this->getArgText();
		
		$parts = explode('@', $str );
		if(count($parts) < 2)
		{
			$this->pm("could not parse a time. attempts to strtotime text to the right of '@'.  '.remind something to do @ 11:00am'");
			return;
		}
		
		$time = array_pop($parts);
		# rejoin the message if split by additional @s
		$msg = join('@',$parts);
		
		$utime = strtotime($time);
		if( $utime == 0)
		{
			$this->pm("could not parse a time. strotime said is false");
			return;
		}
		
		if( $utime <= time())
		{
			$this->pm("time appears to have happened already. aborting.");
			return;
		}
		
		array_push( $this->msgs , array
		(
			'user'=>$user,
			'origin'=>$origin,
			'msg'=>$msg,
			'time'=>$utime
		));
		
		$this->pm("k, $user. cya then.");
	}
	
	function checkMsgs()
	{
		$t = time();
		foreach($this->msgs as $i=>$m)
		{
			if($m['time'] <= $t)
			{
				$this->pm($m['user']." : ".$m['msg'] , $m['origin']);
				unset( $this->msgs[$i]);
			}
		}
	}
}
?>
