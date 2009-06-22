<?php
/**
* grep
*@author Dave Gucwa
*$Id: grep.class.php,v 1.1 2008/06/13 03:54:34 dgucwa Exp $
***/

class grep extends ziggi
{
	function grep ()
	{
	}
	
	/***
	@param void
	*/
	function parseBuffer()
	{
		if ($this->isEmpty())
			return false;
		
		// Relies on the history ziggi
		global $botzilla;
		$history =& $botzilla->ziggis['history'];
		if (!is_a($history, 'ziggi'))
			return;
		
		$input = $this->getInput();
		
		if (preg_match('/'.CMD_CHAR.'?grep (.*)/', $this->getInput(), $matches))
		{
			$search = $matches[1];
			
			// Sanitize the input for use with preg_match
			if (preg_match('`^/([^/]|\/)+/i?$`', $search))
				$searchRegex = $search;
			else
				$searchRegex = '/'.str_replace('/', '\/', $search).'/';
			
			// Is this shit piped?
			if ($this->piped())
				// If so, operate on the piped input
				$operationalLines = array($this->getText());
			else
				// Otherwise, operate on the channel history
				for ($i=HISTORY_MAX-1; $i>=0; $i--)
					$operationalLines[] = $history->getLine($i);
			
			foreach ($operationalLines as $line)
				// If any of the operational lines contain the search string, output them
				if ($line != $input AND preg_match($searchRegex, $line))
					$this->pm($line);
		}
	}
}
?>
