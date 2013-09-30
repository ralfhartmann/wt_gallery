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

class tx_wtgallery_div extends tslib_pibase {
	
	var $prefixId = 'tx_wtgallery_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_wtgallery_pi1.php';	// Path to any file in pi1 for locallang
	var $extKey = 'wt_gallery';	// The extension key.
	var $infoarray = array('comments', 'title', 'subject', 'author', 'recordtime', 'cam_brand', 'cam_model'); // sequence for TXT and EXIF information
	
	
	// Function getFiles() returns file array ($sort could be: random, ASC, DESC, newest, oldest)
	function getFiles($conf, $folder, $sort = 'ASC', $limit = '') {
		$files = t3lib_div::getFilesInDir($folder, $conf['main.']['file_extensions'], 1, 1); // Get all pictures (sort by name ASC AND with folders)
		
		// 1. sort array
		switch ($sort) { // sortmode
			case 'random': // shuffle array
				shuffle($files);
				break;
				
			case 'DESC': // alphabetical descendening
				arsort($files);
				break;
				
			case 'newest': // newest or 
			case 'oldest': // oldest files
				if (is_array($files)) { // if files is an array
					$newarray = array();
					foreach ($files as $value) { // one loop for every file
						$newarray[filemtime($value)] = $value; // $array[time] = pic.jpg
					}
					if ($sort == 'newest') krsort($newarray); // sort from key
					if ($sort == 'oldest') ksort($newarray); // sort from key
					
					$files = $newarray; // overwrite files array
				}
				break;
			
			case (strpos($method, '"') !== false): // " found
				$files[0] = $folder.str_replace('"', '', $sort); // special picture
				break;
			
			default: // default
			case 'ASC': // or ASC - so do nothing
				break;
		}
		
		// 2. rewrite keys of array
		$array = array();
		if (is_array($files)) { // if the array is filled
			foreach ($files as $key => $value) { // one loop for every key
				$array[] = $value; // rewrite key in new array
			}
		}
		
		// 3. return whole or part of array
		if (!empty($array)) { // if there is an entry
			if (empty($limit)) { // no limit
				return $array; // return complete array
			} else { // there is an entry for limit
				if (strlen($limit) > 6) { // return only a special picture like pic.jpg
					$temparray = array();
					if (is_array($array)) { // if is array
						foreach ($array as $key => $value) { // one loop for every picture in array
							if (t3lib_div::md5int($value) == $limit) { // if hash fits, return picture
								$temparray[] = $value; // $temparray[0] = fileadmin/pic.jpg
								return $temparray;
							}
						}
					}
				} else { // cut after X
					return array_slice($array, 0, $limit); // return only the first X values of the array
				}
			}
		}
	}
	
	// Get info of a file (extension, filename, etc...)
	function fileInfo($file, $mode = 'filename') { // $mode could be: dirname (fileadmin/pics), basename (pic.jpg), extension (jpg), filename (pic)
		if ($file) {
			$pathinfo = pathinfo($file); // get file infos
			
			if ($mode == 'filename') { // if filename should be returned
				return basename($file, '.'.$pathinfo['extension']); // return basename
			} elseif ($mode == 'currentfolder') {
				return str_replace('/', '' ,substr($pathinfo['dirname'], strrpos($pathinfo['dirname'], '/'))); // return current folder (subfolder of folder/subfolder/pic.jpg)
			} else {
				return $pathinfo[$mode]; // return part of array
			}
		}
	}
	
	
	// Function check4errors() shows errormessage if there is an error
	function check4errors($value, $msg = 'Error found', $mode = 1, $die = 0) {
		
		switch ($mode) { // mode
			case 1: // check if value is not empty
				if ($value == '') { // if $value is empty
					$error = $this->extKey . ' Error: ' . $msg . '!'; // set errormessage
				}
				break;
				
			case 2: // should be a valid path
				if ($value != '' && !empty($this->conf['main.']['path'])) { // if category is set
					if (strpos($value, $this->conf['main.']['path']) === false) { // category contains not the mainpath!
						$error = $this->extKey . ' Error: ' . $msg . '!'; // set errormessage
					}
				}
				if ($value != '' && (!t3lib_div::validPathStr($value) || strpos($value, '..') !== false)) { // if $value is not empty AND path is not valid
					$error = $this->extKey . ' Error: ' . $msg . '!'; // set errormessage
				}
				break;
				
			case 3: // check if file exits
				if (!file_exists($value)) { // if $value (file) don't exits
					$error = $this->extKey . ' Error: ' . $msg . '!'; // set errormessage
				}
				break;
		}
			
		if (isset($error)) { // if there is an error
			$error = '<div style="background-color: #A71B42; border: 1px solid black; padding: 5px; color: white; font-weight: bold;">'.$error.'</div>';
			if (!$die) return $error; 
			else die ($error);
		}
		
	}
	
	
	// Function validatePicturePath() adds slash at the end if this is missing
	function validatePicturePath($path) {
		if ($path) { // if picture path exists 
			if (substr($path, -1, 1) != '/') $path .= '/'; // add slash at the end if this is missing
			if (substr($path, 0, 1) == '/') $path = substr($path, 1); // remove first slash if exits
			if (!t3lib_div::validPathStr($path)) { // picture path is not valid
				die ($this->extKey . ': Picture path not valid - please correct it!'); // stop script
			}
			return $path;
		}
	}
	
	
	// Function markersClassStyle() returns markerArray with definitions of ###CLASS### (firstofrow, centerofrow, lastofrow)
	function markersClassStyle($i, $mode = 'list', $conf) {
		// config
		$markerArray = array();
		
		// let's start
		if ($conf[$mode.'.']['columns'] > 0) { // only if columns where set via flexform or ts
			if(($i+1) / $conf[$mode.'.']['columns'] == round(($i+1) / $conf[$mode.'.']['columns'])) { // If the current picture is the last of the row (current / cols == integer)
				$markerArray['###CLASS###'] = 'wtgallery_'.$mode.'_lastofrow'; // Additional class for DIV Container
			} elseif (fmod($i+1, $conf[$mode.'.']['columns']) == '1') { // If the current picture is the first of the row
				$markerArray['###CLASS###'] = 'wtgallery_'.$mode.'_firstofrow'; // Additional class for DIV Container
			} else { // If current is not the first and not the last in the row
				$markerArray['###CLASS###'] = 'wtgallery_'.$mode.'_centerofrow'; // No additional class
			}
			return $markerArray;
		}
		
	}
	
	
	// Function rowWrapper() wraps content with a div (so every row in list view gets its own parent DIV container)
	function rowWrapper($content, $i, $mode = 'list', $max, $conf) {
		if ($conf['main.']['DIVforRows']) { // if DIV container for every row is activated in constants
			// config
			$addcleardiv = '<div class="clear"></div>';
			$j = ceil(($i+1) / $conf[$mode.'.']['columns']); // row counter
			
			// let's start
			if(($i+1) / $conf[$mode.'.']['columns'] == round(($i+1) / $conf[$mode.'.']['columns']) || ($i+1) == $max) { // If the current picture is the last of the row (current / cols == integer) OR current pictures is the last overall
				$content .= $addcleardiv.'</div>'; // add closing DIV tag
			} elseif (fmod($i+1, $conf[$mode.'.']['columns']) == '1') { // If the current picture is the first of the row
				$content = '<div class="'.$mode.'_row '.$mode.'_row_'.$j.'">'.$content; // add starting DIV tag
			} 
		}
		
		return $content;
	}
	
	
	// Function EXIForTXT() returns wanted meta information of a txt file
	// possibilities for $mode = 'TXT', 'EXIF', 'TXT/EXIF', 'EXIF/TXT', ''
	function EXIForTXT($file, $mode = 'TXT/EXIF') {
		if (!empty($file)) { // only if isset
			// config
			$array = $tmp_array = array(); // init array
			$mode_array = array_reverse(t3lib_div::trimExplode('/', $mode, 1)); // split mode on ,
			
			// let's go
			for ($i=0; $i<count($mode_array); $i++) { // one loop for every set mode
				switch ($mode_array[$i]) {
					case 'EXIF': // if exif should be shown
						$tmp_array = $this->readEXIF($file); // get EXIF information of a picture
						break;
						
					case 'TXT': // if txt file content should be shown
						$tmp_array = $this->readTXT($file); // get TXT information of a picture
						break;
						
					default: // default: do nothing
						$tmp_array = array(); // empty array
						break;	
				}
				$array = array_merge((array) $array, (array) $tmp_array); // add to existing array
			}
			
			if (!empty($array)) return $array;
		}
	}
	
	
	// Function readEXIF() reads EXIF information of a given picture ($file could be 'fileadmin/pic.jpg')
	function readEXIF($file) {
		if(file_exists($file) && function_exists('exif_read_data')) { // if file exists AND EXIF function exists
			$info = exif_read_data($file, 'EXIF', 1, 0); // get exif of image
			
			// make EXIF array
			if ($info['WINXP']['Comments']) $array[$this->infoarray[0]] = $info['WINXP']['Comments']; // comments
			if ($info['WINXP']['Title']) $array[$this->infoarray[1]] = $info['WINXP']['Title']; // title
			if ($info['WINXP']['Subject']) $array[$this->infoarray[2]] = $info['WINXP']['Subject']; // subject
			if ($info['WINXP']['Author']) $array[$this->infoarray[3]] = $info['WINXP']['Author']; // author
			if ($info['EXIF']['DateTimeOriginal']) $array[$this->infoarray[4]] = $info['EXIF']['DateTimeOriginal']; // recordtime original
			if ($info['IFD0']['Make']) $array[$this->infoarray[5]] = $info['IFD0']['Make']; // camera brand
			if ($info['IFD0']['Model']) $array[$this->infoarray[6]] = $info['IFD0']['Model']; // camera model
			if ($GLOBALS['TSFE']->metaCharset == 'utf-8') $array = array_map('utf8_encode', (array) $array); // utf8 encode on all values of array if utf8 system
			
			if (!empty($array)) return $array;
			
		}
	}
	
	
	// Function readTXT() reads text file of any picture ($file could be 'fileadmin/pic.jpg')
	function readTXT($file) {
		// config
		$postfix = '.txt'; // default postfix like pic.jpg.txt
		if (!empty($GLOBALS['TSFE']->tmpl->setup['config.']['language']) && file_exists($file.'.'.$GLOBALS['TSFE']->tmpl->setup['config.']['language'].$postfix)) $postfix = '.'.$GLOBALS['TSFE']->tmpl->setup['config.']['language'].'.txt'; // rewrite postfix with language postfix pic.jpg.txt => pic.jpg.en.txt
		
		// let's go
		if (file_exists($file) && file_exists($file.$postfix)) { // if picture exists and txt file to picture exists
			$content = t3lib_div::getURL($file.$postfix); // read txtfile
			$contentarray = t3lib_div::trimExplode('|', $content, 1); // split on '|'
			for ($i=0; $i<count($contentarray); $i++) { // one loop for every splitted part in array
				$array[$this->infoarray[$i]] = $contentarray[$i]; // rewrite array
			}
			
			if (!empty($array)) return $array;
		}
	}
	
	
	// Function getFolderStructure() returns array with recursive folder list ($folder could be 'fileadmin/pics/')
	function getFolderStructure($folder, $level = 1, $limit = 10) {
		if ($level <= $limit) { // only if limit not reached yet
			$array = array(); // init new array
			$folders = t3lib_div::get_dirs($folder); // array with folders
			
			if (is_array($folders)) { // if there are folders in the array
				foreach ($folders as $key => $value) { // one loop for every folder
					$array[$value] = $this->getFolderStructure($folder.$value.'/', $level + 1, $limit); // recursive open of own function
				}
			}
			if (is_array($folders)) return $array; // if there where folders, return array
			else return 0; // if there where no folders return 0
		}
	}
	
	
	// Function init() enables $this->conf
	function init($conf) {
		$this->conf = $conf;
	}

}
	

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/lib/class.tx_wtgallery_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/lib/class.tx_wtgallery_div.php']);
}

?>