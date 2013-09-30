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
require_once(t3lib_extMgm::extPath('wt_gallery').'lib/class.tx_wtgallery_list_pagebrowser.php'); // file for dynamicmarker functions

class tx_wtgallery_list extends tslib_pibase {
	
	var $prefixId = 'tx_wtgallery_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_wtgallery_list.php';	// Path to any file in pi1 for locallang
	var $extKey = 'wt_gallery';	// The extension key.
	
	function start($conf, $piVars, $cObj) {
		// config
		$this->conf = $conf;
		$this->piVars = $piVars;
		$this->cObj = $cObj;
		$this->pi_loadLL();
		$this->div = t3lib_div::makeInstance('tx_wtgallery_div'); // Create new instance for div class
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_wtgallery_dynamicmarkers'); // Create new instance for dynamicmarker function
		$this->pagebrowser = t3lib_div::makeInstance('tx_wtgallery_list_pagebrowser'); // Create new instance for pagebrowser function
		$this->tmpl = $this->markerArray = $this->outerMarkerArray = $subpartArray = array(); $content_item = ''; // init
		$this->tmpl['list']['all'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.']['list']), '###WTGALLERY_LIST###'); // Load HTML Template
		$this->tmpl['list']['item'] = $this->cObj->getSubpart($this->tmpl['list']['all'],'###ITEM###'); // work on subpart 2
		
		// let's go
		$startpath = $this->div->validatePicturePath($this->piVars['category'] ? $this->piVars['category'] : $this->conf['main.']['path']); // startpath from piVars or from ts
		$pictures = $this->div->getFiles($this->conf, $startpath, $this->conf['list.']['order'], $this->conf['list.']['limit']); // get all pictures from current folder
		$pictures_current = array_chunk((array) $pictures, ($this->conf['list.']['rows'] * $this->conf['list.']['columns'])); // split array in parts for pagebrowser
		$this->overall = count($pictures); // count all pictures
		$pointer = ($this->piVars['listpointer'] > 0 ? $this->piVars['listpointer'] : 0); // pointer
		
		if (count($pictures_current[$pointer]) > 0) { // if there are pictures
			for ($i=0; $i<count($pictures_current[$pointer]); $i++) { // one loop for every picture in current folder
				// let's start
				$row = array ( // write $row for .field in ts AND for markers in html template
					'picture' => $pictures_current[$pointer][$i], // first entry of files array (e.g. fileadmin/pic1.jpg)
					'tstamp' => filemtime($pictures_current[$pointer][$i]), // timestamp of file
					'filename' => $this->div->fileInfo($pictures_current[$pointer][$i], 'filename'), // like pic
					'dirname' => $this->div->fileInfo($pictures_current[$pointer][$i], 'dirname'), // like fileadmin/pics
					'basename' => $this->div->fileInfo($pictures_current[$pointer][$i], 'basename'), // like pic.jpg
					'extension' => $this->div->fileInfo($pictures_current[$pointer][$i], 'extension'), // like jpg
					'currentfolder' => $this->div->fileInfo($pictures_current[$pointer][$i], 'currentfolder'), // like folder
					'picturehash' => t3lib_div::md5int($pictures_current[$pointer][$i]), // like 12345678
					'pid_single' => ($this->conf['single.']['pid_single'] > 0 ? $this->conf['single.']['pid_single'] : $GLOBALS['TSFE']->id) // PID of single view
				);
				$this->cObj->start($row, 'tt_content'); // enable .field in typoscript for singleview
				
				$this->markerArray = $this->div->markersClassStyle($i, 'list', $this->conf); // fill ###CLASS### and ###STYLE###
				if (!empty($this->conf['list.']['width'])) $this->conf['list.']['image.']['file.']['width'] = $this->conf['list.']['width'];  // set width from config (e.g. flexform if not empty)
				if (!empty($this->conf['list.']['height'])) $this->conf['list.']['image.']['file.']['height'] = $this->conf['list.']['height'];  // set height from config (e.g. flexform if not empty)
				$this->markerArray['###IMAGE###'] = $this->cObj->cObjGetSingle($this->conf['list.']['image'], $this->conf['list.']['image.']); // values from ts
				foreach ($row as $key => $value) { // one loop for every row entry
					$this->markerArray['###'.strtoupper($key).'###'] = $value; // fill marker with value of row
				}
				
				$metarow = $this->div->EXIForTXT($row['picture'], $this->conf['list.']['metainformation']); // get metainformation
				$this->cObj->start($metarow, 'tt_content'); // enable .field in typoscript for singleview
				$this->markerArray['###TEXT###'] = $this->cObj->cObjGetSingle($this->conf['list.']['text'], $this->conf['list.']['text.']); // values from ts
				
				$this->wrappedSubpartArray['###SINGLELINK###'][0] = '<a href="'.tslib_pibase::pi_linkTP_keepPIvars_url(array('show' => t3lib_div::md5int($row['picture'])), 0, 0, ($this->conf['single.']['pid_single'] > 0 ? $this->conf['single.']['pid_single'] : 0)).'">'; // Link with piVars "show"
				$this->wrappedSubpartArray['###SINGLELINK###'][1] = '</a>'; // postfix for linkwrap
				
				$content_item .= $this->cObj->substituteMarkerArrayCached($this->tmpl['list']['item'], $this->markerArray, array(), $this->wrappedSubpartArray); // add inner html to variable
			} 
		}
		
		$this->num = $i; // current pictures for pagebrowser
		$subpartArray['###CONTENT###'] = $content_item; // work on subpart 3
		$this->outerMarkerArray['###PAGEBROWSER###'] = $this->pagebrowser->start($this->conf, $this->piVars, $this->cObj, array('overall' => $this->overall, 'overall_cur' => ($this->conf['list.']['rows'] * $this->conf['list.']['columns']), 'pointer' => $pointer, 'perPage' => ($this->conf['list.']['rows'] * $this->conf['list.']['columns']))); // includes pagebrowser function
		
		$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl['list']['all'], $this->outerMarkerArray, $subpartArray); // Get html template
		$this->content = $this->dynamicMarkers->main($this->conf, $this->cObj, $this->content); // Fill dynamic locallang or typoscript markers
		$this->content = preg_replace("|###.*?###|i", "", $this->content); // Finally clear not filled markers
		if (!empty($this->content) && $i > 0) return $this->content; // return HTML if $content is not empty and if there are pictures
	}	
	

}
	

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_list.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_list.php']);
}

?>