<?php

## FIRST - generate a static resource file, or fetch it dynamically
## READ:  http://botzilla.org/wp/?p=62
## something like: http://boston.redsox.mlb.com/ticketing-client/json/Game.tiksrv?team_id=111&sport_id=1&site_section=%27SCHEDULE%27&begin_date=20090101&end_date=20091231&year=2009
/**
* Let's go Red Sox!
*@author bibby <bibby@surfmerchants.com>
$Id: RedSox.class.php,v 1.2 2009/04/24 18:10:18 abibby Exp $
** //*/
//search for '###!!' for things you need to know

# IF PHP4, I recommend this class. php5 can use json_decode
# http://pear.php.net/pepr/pepr-proposal-show.php?id=198
require_once(BZ_CLS_PATH.'Services_JSON.class.php');

// interface obj
class RedSox extends ziggi
{
	function RedSox()
	{
		$this->data = new RedSox_Data();
	}
	
	function parseBuffer()
	{
		if($this->isEmpty())
			return;
			
		$a = $this->getArgs();
		if(substr($a[0],0,1)!=CMD_CHAR)
			return;
			
		$c = substr($a[0],1);
		
		
		if($a[1] == 'last' && $c=='sox')
		{
			$this->pm( $this->data->getLastGame()->getData()->display() );
			return;
		}
		
		if($a[1] == 'next' && $c=='sox')
		{
			$this->pm( $this->data->getNextGame()->listing );
			return;
		}
		
		if($c=='sox')
		{
			$g = $this->data->getTodaysGame();
			if(!$g)
			{
				$this->pm("no game today :(");
				return;
			}
			else
			{
				$this->pm( $g->getData()->display() );
				return;
			}
		}
	}
	
	function decode($json)
	{
		$j = new Services_JSON();
		return $j->decode($json);
	}
}


// data obj
class RedSox_Data
{
	var $data='';
	var $dataFile = '';
	var $games = array();
	var $json;
	
	function RedSox_Data()
	{
		###!! You need to fetch this file!
		// I'm not being dynamic. perhaps you will?
		$this->dataFile = PERM_DATA.'redsox_2009.json';
		
		$this->readDataFile();
	}
	
	function readDataFile()
	{
		if(!is_file($this->dataFile) || !is_readable($this->dataFile))
			return false;
		$d = RedSox::decode( file_get_contents($this->dataFile) );
		
		if(!is_object($d))
			return;
		
		foreach($d->events->game as $g)
			$this->games[$g->schedule_id] = new SoxGameListing($g);
	}
	
	function getNextGame()
	{
		$t=time();
		$next = null;
		foreach($this->games as $g)
		{
			if($t < $g->time)
			{
				if(is_null($next))
					$next = $g;
				else
					if($g->time < $next->time)
						$next = $g;
			}
		}
		
		return $next;
	}
	
	function getTodaysGame()
	{
		$fmt = "Y/m/d";
		$today = date($fmt);
		foreach($this->games as $g)
			if(date($fmt,$g->time) == $today)
				return $g;
		
		return false;
	}
	
	
	function getLastGame()
	{
		$t=time();
		$next = null;
		
		foreach($this->games as $g)
		{
			if($t > $g->time)
			{
				if(is_null($next))
					$next = $g;
				else
					if($g->time > $next->time)
						$next = $g;
			}
		}
		
		return $next;
	}
}

// caldendar data
class SoxGameListing
{
	var $listing = '';
	var $time = '';
	var $sched_id = 0;
	
	function SoxGameListing($dataObj)
	{
		$this->buildFrom($dataObj);
	}
	
	function buildFrom($dataObj)
	{
		$this->time = strtotime($dataObj->game_time_local);
		$time = date('m/d/Y H:i', $this->time);
		$this->sched_id = $dataObj->schedule_id;
		$this->listing = join(' ',array( $dataObj->away_name_short,'@',$dataObj->home_name_short,' ',$time));
	}
	
	function getData()
	{
		$d = date("Y-m-d");
		$url = "http://mlb.mlb.com/lookup/json/named.schedule_pk.bam?calendar_event_id='14-$this->sched_id-$d'";
		$data = file_get_contents($url);
		if(!$data)
			return;
			
		$data = RedSox::decode(substr($data, strpos($data,"/",2)+1));
		return new SoxGame( $data->schedule_pk->queryResults->row);
	}
}


// a single game
class SoxGame
{
	function SoxGame($data)
	{
		foreach(get_object_vars($data) as $k=>$v)
			$this->$k=$v;
	}
	
	function display()
	{
		$t = date("m/d h:i",strtotime($this->game_time_et));
		$x = $this->game_status_ind;
		return $this->away_name_city . ":".intval($this->away_score)." @ " . $this->home_name_city . ":" . intval($this->home_score) . "  (" . $x .") $t";
	}
}
?>
