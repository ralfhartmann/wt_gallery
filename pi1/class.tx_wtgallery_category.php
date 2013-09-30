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
require_once(t3lib_extMgm::extPath('wt_gallery').'lib/class.tx_wtgallery_category_pagebrowser.php'); // file for categorybrowser function

class tx_wtgallery_category extends tslib_pibase {
	
	var $prefixId = 'tx_wtgallery_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_wtgallery_category.php';	// Path to any file in pi1 for locallang
	var $extKey = 'wt_gallery';	// The extension key.
	
	function start($conf, $piVars, $cObj) {
		// config
		$this->conf = $conf;
		$this->piVars = $piVars;
		$this->cObj = $cObj;
		$this->pi_loadLL();
		$this->div = t3lib_div::makeInstance('tx_wtgallery_div'); // Create new instance for div class
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_wtgallery_dynamicmarkers'); // Create new instance for dynamicmarker function
		$this->pagebrowser = t3lib_div::makeInstance('tx_wtgallery_category_pagebrowser'); // Create new instance for categorybrowser function
		$this->tmpl = $this->markerArray = $this->outerMarkerArray = array(); $content_item = ''; // init
		$this->tmpl['category']['all'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['template.']['category']), '###WTGALLERY_CATEGORY###'); // Load HTML Template
		$this->tmpl['category']['item'] = $this->cObj->getSubpart($this->tmpl['category']['all'],'###ITEM###'); // work on subpart 2
		
		// let's go
		$startpath = $this->div->validatePicturePath($this->piVars['category'] ? $this->piVars['category'] : $this->conf['main.']['path']); // startpath from piVars or from ts
		$folders = t3lib_div::get_dirs($startpath); // Get all subfolders in the picture folder
		$folders_current = array_chunk((array) $folders, ($this->conf['category.']['rows'] * $this->conf['category.']['columns'])); // split array in parts for pagebrowser
		$this->overall = count($folders); // count all pictures
		$pointer = ($this->piVars['categorypointer'] > 0 ? $this->piVars['categorypointer'] : 0); // pointer
		
		if (count($folders_current[$pointer]) > 0) { // if there are folders
			for ($i=0; $i<count($folders_current[$pointer]); $i++) { // one loop for every folder in current folder
				// let's start
				$picture = $this->div->getFiles($this->conf, $startpath.$folders_current[$pointer][$i].'/', $this->conf['category.']['previewpicture_order'], 1); // get a picture from category
				$row = array ( // write $row for .field in ts AND for markers in html template
					'picture' => $picture[0], // first entry of files array (e.g. fileadmin/pic1.jpg)
					'tstamp' => filemtime($picture[0]), // timestamp of file
					'filename' => $this->div->fileInfo($picture[0], 'filename'), // like pic
					'dirname' => $this->div->fileInfo($picture[0], 'dirname'), // like fileadmin/pics
					'basename' => $this->div->fileInfo($picture[0], 'basename'), // like pic.jpg
					'extension' => $this->div->fileInfo($picture[0], 'extension'), // like jpg
					'currentfolder' => $this->div->fileInfo($picture[0], 'currentfolder') // like folder
				);
				$this->cObj->start($row, 'tt_content'); // enable .field in typoscript for singleview
				
				$this->markerArray = $this->div->markersClassStyle($i, 'category', $this->conf); // fill ###CLASS### and ###STYLE###
				if (!empty($this->conf['category.']['width'])) $this->conf['category.']['image.']['file.']['width'] = $this->conf['category.']['width'];  // set width from config (e.g. flexform if not empty)
				if (!empty($this->conf['category.']['height'])) $this->conf['category.']['image.']['file.']['height'] = $this->conf['category.']['height'];  // set height from config (e.g. flexform if not empty)
				$this->markerArray['###IMAGE###'] = $this->cObj->cObjGetSingle($this->conf['category.']['image'], $this->conf['category.']['image.']); // values from ts
				foreach ($row as $key => $value) { // one loop for every row entry
					$this->markerArray['###'.strtoupper($key).'###'] = $value; // fill marker with value of row
				}
				
				$metarow = $this->div->EXIForTXT($row['picture'], $this->conf['category.']['metainformation']); // get metainformation
				$this->cObj->start($metarow, 'tt_content'); // enable .field in typoscript for singleview
				$this->markerArray['###TEXT###'] = $this->cObj->cObjGetSingle($this->conf['category.']['text'], $this->conf['category.']['text.']); // values from ts
		
				$this->wrappedSubpartArray['###CATEGORYLINK###'] = $this->cObj->typolinkWrap( array("parameter" => $GLOBALS["TSFE"]->id, "additionalParams" => '&'.$this->prefixId.'[category]='.$this->div->fileInfo($picture[0], 'dirname'), "useCacheHash" => 1) ); // Link to same page with current folder
				
				$content_item .= $this->cObj->substituteMarkerArrayCached($this->tmpl['category']['item'], $this->markerArray, array(), $this->wrappedSubpartArray); // add inner html to variable
			} 
		}
		$this->num = $i; // current pictures for pagebrowser
		$subpartArray['###CONTENT###'] = $content_item; // work on subpart 3
		
		// fill outer markers
		$this->outerMarkerArray['###PAGEBROWSER###'] = $this->pagebrowser->start($this->conf, $this->piVars, $this->cObj, array('overall' => $this->overall, 'overall_cur' => ($this->conf['category.']['rows'] * $this->conf['category.']['columns']), 'pointer' => $pointer, 'perPage' => ($this->conf['category.']['rows'] * $this->conf['category.']['columns']))); // include categorybrowser
		
		$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl['category']['all'], $this->outerMarkerArray, $subpartArray); // Get html template
		$this->content = $this->dynamicMarkers->main($this->conf, $this->cObj, $this->content); // Fill dynamic locallang or typoscript markers
		$this->content = preg_replace("|###.*?###|i", "", $this->content); // Finally clear not filled markers
		if (!empty($this->content) && ($i > 0 || !empty($this->piVars['category']))) return $this->content; // return HTML if $content is not empty AND ( if there are pictures OR category was chosen )
	}	
	

}
	

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_category.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_category.php']);
}

?>