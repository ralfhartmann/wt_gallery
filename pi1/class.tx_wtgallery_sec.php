<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Alexander Kellner <alexander.kellner@einpraegsam.net>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_tslib.'class.tslib_pibase.php');

class tx_wtgallery_sec extends tslib_pibase {

	var $extKey = 'wt_gallery'; // Extension key
	var $secParams = array('cat' => 'text', 'picid' => 'text', 'thumbid' => 'int', 'delete' => '"all"'); // Allowed piVars
	
	// Function sec() is a security function against bad guys
	function sec($piVars) {
		if(isset($piVars) && is_array($piVars)) { // if piVars
			foreach ($piVars as $key => $value) {
				if (!is_array($piVars[$key])) { // first level
				
					if (array_key_exists($key, $this->secParams)) { // Allowed parameter
						if ($this->secParams[$key] === 'int') $piVars[$key] = intval($value); // show: should be an integer
						elseif ($this->secParams[$key] === 'text') { // show: should be text
							$piVars[$key] = $this->clean($piVars[$key]); // clean function
						}
						elseif (strpos($this->secParams[$key], '"') !== false) { // if a quote exists
							$piVars[$key] = str_replace('"','',$this->secParams[$key]);
						}
						else unset($piVars[$key]); // delete
					}
					else unset($piVars[$key]); // delete
					
				} else unset($piVars[$key]); // delete
			}
	
			return $piVars; // return cleaned piVars
		} 
	}
	
	// Function clean() uses strip_tags and addslashes of any value
	function clean($value) {
		if (!empty($value)) return addslashes(strip_tags(trim($value)));
	}
	
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_sec.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_sec.php']);
}

?>
