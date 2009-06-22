<?
/**
* calZilla
* Checks Google Calendar for whats happening.
* @author Tristan Schneiter <tristans@surfmerchants.com>
* $Id: calZilla.class.php,v 1.5 2008/08/11 
**/
require_once(BOTZILLA_CLS_PATH.'XML.class.php');

class calZilla extends ziggi
{
	var $vacationCal = "Your office's vacation caldndar name";
	var $vacationURL = "";
	var $config = array(
		'Email'=>'you@yourdomain.com',
		'Passwd'=>'urpasswd',
		'source'=>'googleCalZiggi', 
		'service'=>'cl'
	);
	var $calendarURLArray = array();
	var $authToken = 0;
	
	/***
	//*/
	function calZilla()
	{		
		$this->loginToGoogleCal();
		$this->getAllCalendars();
	}	
	
	
	/***
	//*/
	function parseBuffer()
	{
		if($this->isEmpty())
			return false;
		
		$c=strtolower($this->getArg(0));
	
		$cmds= array(
			CMD_CHAR.'today',
			CMD_CHAR.'calsearch',
			CMD_CHAR.'calrange',
			CMD_CHAR.'nexthour',
			CMD_CHAR.'thisafternoon',
			CMD_CHAR.'thismorning',
			CMD_CHAR.'vacation',
			CMD_CHAR.'daysout',
			CMD_CHAR.'tomorrow',
			CMD_CHAR.'comingweek',
			CMD_CHAR.'pastweek',
			CMD_CHAR.'caldate',
			CMD_CHAR.'thisweek',
			CMD_CHAR.'twodaysbeforetheendofthelastdayofthethirddayofthesecondmonthofnextyear',
			CMD_CHAR.'createcalevent',
			CMD_CHAR.'listcals'
		);
		
		//makeDate -
		//open dialog with user when asked to makedate. Must be able to shortcut the dialog, though.
		
		
		if(in_array($c,$cmds))
		{
			switch($this->getArg(0))
			{
				case $cmds[0]:
					$this->fetchDateRange(date("Y-m-d")."T00:00:00",date("Y-m-d")."T23:59:59", $this->getArgText());
					break;
				case $cmds[1]:
					$this->fetchCalQuery($this->getArgText());
					break;
				case $cmds[2]:
					$this->fetchDateQuery($this->getArg(1),$this->getArg(2));
					break;
				case $cmds[3]:
					$this->fetchDateRange(date("Y-m-d\TH:i:s"),date("Y-m-d\TH:i:s", strtotime("+1 hour")), $this->getArgText());
					break;
				case $cmds[4]:
					$this->fetchDateRange(date("Y-m-d")."T00:00:00",date("Y-m-d")."T12:00:00", $this->getArgText());
					break;
				case $cmds[5]:
					$this->fetchDateRange(date("Y-m-d")."T12:00:01",date("Y-m-d")."T23:59:59", $this->getArgText());
					break;
				case $cmds[6]:
				case $cmds[7]:
					$this->fetchVacationDays($this->getArgText());
					break;
				case $cmds[8]:
					$this->fetchDateRange(date("Y-m-d", strtotime("+1 day"))."T00:00:00",date("Y-m-d", strtotime("+1 day"))."T23:59:59", $this->getArgText());
					break;
				case $cmds[9]:
					$this->fetchDateRange(date("Y-m-d")."T00:00:00", date("Y-m-d", strtotime("+7 days"))."T23:59:59", $this->getArgText());
					break;
				case $cmds[10]:
					$this->fetchDateRange(date("Y-m-d", strtotime("-7 day"))."T00:00:00", date("Y-m-d", strtotime("-1 days"))."T23:59:59", $this->getArgText());
					break;
				case $cmds[11]:
					$this->fetchDateQuery($this->getArg(1),$this->getArg(1));
					break;
				case $cmds[12]:
					$this->fetchDateRange(date("Y-m-d", strtotime("last monday"))."T00:00:00", date("Y-m-d", strtotime("friday"))."T23:59:59", $this->getArgText());
					break;
				case $cmds[13]:
					$this->pm("Go to hell, Jeff.");
					break;
				case $cmds[14]:
					$this->createCalEvent($this->getArg(1), $this->getArgText());
					break;
				case $cmds[15]:
					$this->listCals();
					break;
			}
			
		}
	}
	
	// Returns the xml serialized results of a query run on Google.
	// Takes the URL that will be run
	// takes various other parameters to be used for more advanced queries (mostly authentication)
	// If you use special headers, you must include Auth in your special headers.
	// "Authorization: GoogleLogin auth=".$this->authToken; 
	function googleCalendarQuery($url, $noSerialize=false, $isPost=false, $postVars = array(), $specialHeaders = array())
	{
		$gc = curl_init();
		if(!empty($this->authToken))
			if(!empty($specialHeaders))
				curl_setopt($gc,CURLOPT_HTTPHEADER,$specialHeaders);
			else
				curl_setopt($gc,CURLOPT_HTTPHEADER,array("Authorization: GoogleLogin auth=".$this->authToken));
		if($isPost)
		{
			curl_setopt($gc, CURLOPT_CUSTOMREQUEST,'POST'); 
		}
		if(!empty($postVars))
			curl_setopt($gc, CURLOPT_POSTFIELDS, $postVars);
		curl_setopt($gc, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($gc,CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($gc, CURLOPT_URL, $url);
		$result = curl_exec($gc);
		if(!$noSerialize)
			$result = XML_unserialize($result);
		return(empty($result)?false:$result);
	}
	
	
	function loginToGoogleCal()
	{
		$results = $this->googleCalendarQuery("https://www.google.com/accounts/ClientLogin", true, true, $this->config);
		preg_match('/Auth=([\S]+)/',$results,$res);	
		$this->authToken=$res[1];
		return empty($res);
	}
	
	
	// Gets all the URLs for each calendar. We have to query them individually. Lame.
	function getAllCalendars()
	{
		$results = $this->googleCalendarQuery('http://www.google.com/calendar/feeds/default/allcalendars/full');
		if($results)
			foreach($results['feed']['entry'] as $result)
			{
				$this->calendarURLArray[] = array('fetch'=>$result['link']['0 attr']['href'], "title"=>$result['title']);
				if(strcasecmp($result['title'],$this->vacationCal)==0) //find our vacationCalendar URL.
					$this->vacationURL = $result['link']['0 attr']['href'];
			}
	}
	
	
	// loops through our calendar URLs and builds a results array.
	function fetchForAllCals($getString, $searchItems = null, $displayResults = true)
	{
		$return = array();
		//foreach($this->calendarURLArray['url'] as $calendarURL)
		foreach($this->calendarURLArray as $calendarURL)
		{
			$resultArray = $this->googleCalendarQuery($calendarURL['fetch']."?".$getString."&orderby=starttime&sortorder=ascending");
			if(is_array($resultArray['feed']['entry']))
				if(count($resultArray['feed']['entry']['id'])==1)
					$return[]=$resultArray['feed']['entry'];
				else
					foreach($resultArray['feed']['entry'] as $anEntry)
						$return[]=$anEntry;
		}
		// Because google cal's API sucks, we search all text fields with our query.
		// As such, we need to see that what we are searching for is actually in the content of the event.
		if(is_array($return) && is_array($searchItems)) 
			foreach($return as $eventID=>$anEvent)
			{
				var_dump($anEvent);
				$found = false;
				foreach($searchItems as $aSearchItem)
				{
					if(stripos($anEvent['title'],$aSearchItem)!==false)
						$found = true;
					if(stripos($anEvent['content'],$aSearchItem)!==false)
						$found = true;
				}
				if(!$found)
					unset($return[$eventID]);
			}
		if($displayResults)
			$this->createEventString($return);
		else
			return $return;
	}
	
	function createEventString($eventArray)
	{
		$entries = array();
		
		if(is_array($eventArray))
			foreach($eventArray as $anEntry)
				if(is_array($anEntry) && !empty($anEntry['title']))
				{
					$startTime = strtotime($anEntry['gd:when attr']['startTime']);
					// A bunch of crappy custom logic to make the date display prettier.
					$sameDate = false;
					if(strcasecmp(date("mdY", strtotime($anEntry['gd:when attr']['startTime'])),date("mdY", strtotime($anEntry['gd:when attr']['endTime'])))==0)
						$sameDate = true;
					if(date("h:iA",strtotime($anEntry['gd:when attr']['startTime']))!="12:00AM")
						$startDate = date("m/d/Y@h:iA", strtotime($anEntry['gd:when attr']['startTime']));
					else
						$startDate = date("m/d/Y", strtotime($anEntry['gd:when attr']['startTime']));
					if(date("h:iA",strtotime($anEntry['gd:when attr']['endTime']))!="12:00AM")
						$endDate = date("m/d/Y@h:iA", strtotime($anEntry['gd:when attr']['endTime']));
					else
						$endDate = date("m/d/Y", strtotime($anEntry['gd:when attr']['endTime'])-86400);
					if($sameDate)
						$endDate = date("h:iA",strtotime($anEntry['gd:when attr']['endTime']));
					if($endDate == $startDate)
						$totalDate = $startDate;
					else
						$totalDate = " $startDate to $endDate";
					$entries[] = array('entry'=>$anEntry['title'].": ".$totalDate,'startTime'=>$startTime);
				}
		if(!empty($entries))
			$entries = php_multisort($entries, array(array('key'=>'startTime','sort'=>'asc')));
		$this->displayArray($entries);
	}
	
	function displayArray($arrayToDisplay)
	{
		if(is_array($arrayToDisplay) && !empty($arrayToDisplay))
			foreach($arrayToDisplay as $atd)
				$this->pm($atd['entry']);
		else
			$this->pm("Nothing for ya.");
	}
	
	
	function fetchVacationDays($queryArg)
	{
		$startYear = date("Y")."-01-01T00:00:00";
		$resultArray = $this->googleCalendarQuery($this->vacationURL."?q=".$queryArg."&start-min=".$startYear);
		$daysOfVacation = 0;
		$results = $resultArray['feed']['entry'];
		foreach($results as $anEntry)
		{
			$difference = 0;
			$ak = array_keys($anEntry);
			if(!empty($anEntry["gd:when attr"]['endTime']))
				$difference = strtotime($anEntry["gd:when attr"]['endTime']) - strtotime($anEntry['gd:when attr']['startTime']);
			elseif($ak[0]=="startTime")
				$difference = strtotime($anEntry['endTime']) - strtotime($anEntry['startTime']);
			$daysOfVacation += round($difference/86400);
		}
		$linkWord = "";
		if(empty($queryArg))
		{
			$queryArg = "Everyone";
			$linkWord = "a total of ";
		}
		$this->pm($queryArg." has been out of the office for ".$linkWord.$daysOfVacation." days since ".date("Y")."-01-01");
	}
	
	
	function createCalEvent($calReference, $eventString)
	{
		if(!empty($eventString))
		{
			if(isset($this->calendarURLArray[$calReference-1]))
			{
				$url = $this->calendarURLArray[$calReference-1]['fetch'];
				$eventString = substr($eventString, strlen($calReference));
				$newEvent = "<entry xmlns='http://www.w3.org/2005/Atom' xmlns:gCal='http://schemas.google.com/gCal/2005'><content type=\"html\">".$eventString."</content><gCal:quickadd value=\"true\"/></entry>";
				$header = array();
				$header[] = "Host: www.google.com"; 
				$header[] = "MIME-Version: 1.0"; 
				$header[] = "Accept: text/xml"; 
				$header[] = "Authorization: GoogleLogin auth=".$this->authToken; 
				$header[] = "Content-length: ".strlen($newEvent); 
				$header[] = "Content-type: application/atom+xml"; 
				$header[] = "Cache-Control: no-cache"; 
				$header[] = "Connection: close \r\n";
				$header[] = $newEvent;
				$results = $this->googleCalendarQuery($url, false, true, null,$header);
				$this->createEventString($results);
			}
			else
				$this->pm("You picked an invalid calendar. Syntax: .createcalevent x event information - where x is your calendar number. Use .listcals to see a list of available calendars");
		}
		else
		{
			$this->pm("Usage: .createcalevent x event information");
			$this->pm("where x is the number corresponding to the calendar you want to post in");
			$this->listCals();
		}
	}
	
	function fetchCalQuery($queryArg)
	{
		$getString = "q=".$queryArg."&start-min=".date("Y-m-d\TH:i:s", strtotime("-1 week"));
		$this->fetchForAllCals($getString);
	}
	
	
	function fetchDateQuery($startDate, $endDate)
	{
		$timeStart = date('Y-m-d',strtotime($startDate))."T00:00:00";
		$timeEnd = date('Y-m-d', strtotime($endDate))."T23:59:59";
		$this->fetchDateRange($timeStart, $timeEnd);
	}
	
	function fetchDateRange($startQuery, $endQuery, $searchText=null)
	{
		if(!empty($searchText))
		{
			$searchBits = explode(" ",$searchText);
		}
		$searchString = "";
		if(!empty($searchText))
			$searchString="&q=".$searchText;
		$getString = 'start-min='.$startQuery."&start-max=".$endQuery.$searchString;
		$this->fetchForAllCals($getString, $searchBits);
	}
	
	function listCals()
	{
		$calStrings = array();
		$calCount = 0;
		foreach($this->calendarURLArray as $aCal)
		{
			$calCount++;
			$calStrings[] = "($calCount) ".$aCal['title'];
		}
		$calString = join(",",$calStrings);
		$this->pm($calString);
	}	
}

function php_multisort($data,$keys)
{
  // List As Columns
  foreach ($data as $key => $row) {
    foreach ($keys as $k){
      $cols[$k['key']][$key] = $row[$k['key']];
    }
  }
  // List original keys
  $idkeys=array_keys($data);
  // Sort Expression
  $i=0;
  foreach ($keys as $k){
    if($i>0){$sort.=',';}
    $sort.='$cols['.$k['key'].']';
    if($k['sort']){$sort.=',SORT_'.strtoupper($k['sort']);}
    if($k['type']){$sort.=',SORT_'.strtoupper($k['type']);}
    $i++;
  }
  $sort.=',$idkeys';
  // Sort Funct
  $sort='array_multisort('.$sort.');';
  eval($sort);
  // Rebuild Full Array
  foreach($idkeys as $idkey){
    $result[$idkey]=$data[$idkey];
  }
  return $result;
}

?>
