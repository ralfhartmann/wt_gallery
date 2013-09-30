<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Alex Kellner <alexander.kellner@einpraegsam.net>
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
require_once(t3lib_extMgm::extPath('wt_gallery').'lib/class.tx_wtgallery_div.php'); // load div class
require_once(t3lib_extMgm::extPath('wt_gallery').'lib/class.tx_wtgallery_dynamicmarkers.php'); // file for dynamicmarker functions

class tx_wtgallery_single extends tslib_pibase {
	
	var $prefixId = 'tx_wtgallery_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_wtgallery_single.php';	// Path to any file in pi1 for locallang
	var $extKey = 'wt_gallery';	// The extension key.
	
	function start($conf, $piVars, $cObj) {
		// config
		$this->conf = $conf;
		$this->piVars = $piVars;
		$this->cObj = $cObj;
		$this->pi_loadLL();
		$this->div = t3lib_div::makeInstance('tx_wtgallery_div'); // Create new instance for div class
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_wtgallery_dynamicmarkers'); // Create new instance for dynamicmarker function
		$this->tmpl = $this->markerArray = array(); // init
		$this->tmpl['single'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.']['single']), '###WTGALLERY_SINGLE###'); // Load HTML Template
		
		// let's go
		$startpath = $this->div->validatePicturePath($this->piVars['category'] ? $this->piVars['category'] : $this->conf['main.']['path']); // startpath from piVars or from ts
		if (!isset($this->piVars['show'])) { // GET param show is not set
			$files = $this->div->getFiles($this->conf, $startpath, $this->conf['single.']['order'], 1); // get pictures (limit 1)
		} else { // GET param show was set
			$files = $this->div->getFiles($this->conf, $startpath, $this->conf['single.']['order'], $this->piVars['show']); // get pictures (limit 1)
		}
		
		$row = array ( // write $row for .field in ts
			'picture' => $files[0], // first entry of files array (e.g. fileadmin/pic1.jpg)
			'tstamp' => filemtime($files[0]), // timestamp of file
			'filename' => $this->div->fileInfo($files[0], 'filename'), // like pic
			'dirname' => $this->div->fileInfo($files[0], 'dirname'), // like fileadmin/pics
			'basename' => $this->div->fileInfo($files[0], 'basename'), // like pic.jpg
			'extension' => $this->div->fileInfo($files[0], 'extension'), // like jpg
			'listview_link' => tslib_pibase::pi_linkTP_keepPIvars_url(array('show' => ''), 0, 0, $this->conf['list.']['pid_list']) // link to list view
		);
		$this->cObj->start($row, 'tt_content'); // enable .field in typoscript for singleview
		
		if (!empty($this->conf['single.']['width'])) $this->conf['single.']['image.']['file.']['width'] = $this->conf['single.']['width'];  // set width from config (e.g. flexform if not empty)
		if (!empty($this->conf['single.']['height'])) $this->conf['single.']['image.']['file.']['height'] = $this->conf['single.']['height'];  // set width from config (e.g. flexform if not empty)
		$this->markerArray['###IMAGE###'] = $this->cObj->cObjGetSingle($this->conf['single.']['image'], $this->conf['single.']['image.']); // values from ts
		
		$metarow = $this->div->EXIForTXT($row['picture'], $this->conf['single.']['metainformation']); // get metainformation
		$this->cObj->start($metarow, 'tt_content'); // enable .field in typoscript for singleview
		$this->markerArray['###TEXT###'] = $this->cObj->cObjGetSingle($this->conf['single.']['text'], $this->conf['single.']['text.']); // values from ts
		
		// ###LISTVIEWLINK###
		$row = array ( // write $row for .field in ts
			'listview_link' => tslib_pibase::pi_linkTP_keepPIvars_url(array('show' => ''), 0, 0, $this->conf['list.']['pid_list'])
		);
		if ($this->conf['single.']['pid_single'] == $this->conf['list.']['pid_list']) $row = array(); // clear if listview and singleview in the same page
		$this->cObj->start($row, 'tt_content'); // enable .field in typoscript for singleview
		if ($this->conf['single.']['pid_single'] == $this->conf['list.']['pid_list']) $this->markerArray['###LISTVIEWLINK###'] = $this->cObj->cObjGetSingle($this->conf['single.']['listviewlink'], $this->conf['single.']['listviewlink.']); // values from ts
		
		
		$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl['single'], $this->markerArray, array(), array()); // substitute Marker in Template
		$this->content = $this->dynamicMarkers->main($this->conf, $this->cObj, $this->content); // Fill dynamic locallang or typoscript markers
		$this->content = preg_replace("|###.*?###|i", "", $this->content); // Finally clear not filled markers
		
		if (!empty($this->content) && $this->markerArray['###IMAGE###']) return $this->content; // return HTML if $content is not empty and if there is a picture
	}	
	

}
	

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_single.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_single.php']);
}

?>