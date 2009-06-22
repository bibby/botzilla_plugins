<?php
/**
* Sound the alarm!
* Message channels or nicks at a specific time of day
*@author bibby <bibby@surfmerchants.com>
*$Id: alarm.class.php,v 1.6 2009/01/12 17:21:58 abibby Exp $
* You are free to do what you wish with this code.
*/

class alarm extends ziggi
{
	function alarm()
	{
		$this->past=array();
		
		$burritos = array('Monday 11:30am', 'Friday 11:30am');
		
		// add your own alarms here
		$this->alarms=array
		(
			array
			(
				'channel'=>array('#yourchannel','#yourotherchannel'),
				'time'=>strtotime('today 11:30am'),
				'msg'=> 'Lunch time!',
				'recalc'=>'tomorrow'
			),
			array
			(
				'channel'=>'#yourchannel',
				'time'=>strtotime('Friday 2:00pm'),
				'msg'=>'Company meeting!',
				'recalc'=>'nextWeek'
			),
			array
			(
				'channel'=>'#yourchannel',
				'time'=>$this->nextAmong( $burritos ),
				'msg'=> 'Burrito time!',
				'recalc'=>'nextAmong',
				'recalc_options'=>$burritos
			)
		);
	}
	
	
	function parseBuffer()
	{
		$now = time();
		foreach($this->alarms as $dex=>$a)
		{
			//after, but not by a very wide margin
			if($now > $a['time'] && ($now-$a['time']) < 60 && !in_array($a['time'],$this->past))
			{
				//send alert.
				if(is_scalar($a['channel']))
					$a['channel'] = array($a['channel']);
					
				foreach($a['channel'] as $c)
					$this->pm($a['msg'],$c);
				
				//blacklist time
				$this->past[]=$a['time'];
				
				if($a['recalc'])
					$this->alarms[$dex]['time'] = $this->{$a['recalc']}($a['time'], $a['recalc_options']);
			}
		}
		
		return false;
	}
	
	/*** nextAmong ***
	@access private
	@param int current time, safe to ignore!
	@param array string-times
	@return int time
	*/
	function nextAmong($foo,$times)
	{
		$now = time();
		foreach($times as $t)
		{
			$atime = strtotime($t);
			if($atime > $now && !in_array($atime,$this->past))
				return $atime;
		}
	}
	
	/** time recalculators **/
	function nextWeek($time)
	{
		return $time + (86400 * 7);
	}
	
	function tomorrow($time)
	{
		return $time + 86400;
	}
}

?>
