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

if (!class_exists('tslib_pibase')) require_once(PATH_tslib . 'class.tslib_pibase.php');

class user_wtgallery_list_pagebrowser extends tslib_pibase {
	
	var $prefixId = 'tx_wtgallery_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_wtgallery_list.php';	// Path to any file in pi1 for locallang
	var $extKey = 'wt_gallery';	// The extension key.

	// Function start() generates HMENU for typoscript
	function start($content='', $conf=array()) {
		// config
		global $TSFE;
		$cObj = $TSFE->cObj; // cObject
		$this->conf = $conf; // conf
		$this->pi_loadLL();
		$menuarray = array(); $conf['userFunc.']['pointer'] = 0;
		
		// let's go
		for($i=0; $i < ceil($conf['userFunc.']['overall'] / $conf['userFunc.']['perPage']); $i++) { // one loop for every page
			if ($conf['userFunc.']['pointer'] == intval($_GET[$this->prefixId]['listpointer'])) $menuarray[$i]['ITEM_STATE'] = 'ACT'; // act status for menu
			
			$menuarray[$i]['title'] = sprintf($this->pi_getLL('wtgallery_ll_pagebrowser_page', 'page '.($i+1)), ($i+1)); // menu label
			$menuarray[$i]['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(array('listpointer' => $conf['userFunc.']['pointer']), 1); // url for menu
			$conf['userFunc.']['pointer'] = ($i+1);
		};
		
		return $menuarray;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/lib/class.user_wtgallery_list_pagebrowser.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/lib/class.user_wtgallery_list_pagebrowser.php']);
}

?>