<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Alex <alex@wunschtacho.de>
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
require_once(t3lib_extMgm::extPath('wt_gallery').'pi1/class.tx_wtgallery_sec.php'); // load security class

/**
 * Plugin 'WT Gallery' for the 'wt_gallery' extension.
 *
 * @author	Alex <alex@wunschtacho.de>
 * @package	TYPO3
 * @subpackage	tx_wtgallery
 */
class tx_wtgallery_pi1 extends tslib_pibase {
	
	var $prefixId = 'tx_wtgallery_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_wtgallery_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'wt_gallery';	// The extension key.
	var $cachefolders = 'uploads/tx_wtgallery/single/,uploads/tx_wtgallery/popup/,uploads/tx_wtgallery/thumbs/,uploads/tx_wtgallery/wmarked/,uploads/tx_wtgallery/cat/'; // All folders where cached files are in
	var $minphpversion = '4.2.0';
	var $cache = 1;
	var $pi_USER_INT_obj = 0;
	var $pi_checkCHash = true;
	
	// Function main to get values and to initiate plugin
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexForm();
		$this->security = t3lib_div::makeInstance('tx_wtgallery_sec'); // Create new instance for security class
		$this->minphpversion = t3lib_div::int_from_ver($this->minphpversion); // set min php version
		$this->piVars = $this->security->sec($this->piVars); // security class
		
		
		// This sets the title of the page for use in indexed search results:
		if ($this->internal['currentRow']['title']) $GLOBALS['TSFE']->indexedDocTitle=$this->internal['currentRow']['title'];
		
		// Load values from Flexform or TYPOSCRIPT
		$view = t3lib_div::trimExplode(',',$this->getvalue('choose','pageconfig','single','No definition what to show (single- or listview)',0),1);
		$template = $this->getfile($this->getvalue('template','pageconfig','EXT:wt_gallery/pi1/template.html','No HTML Template found, please insert a path in the flexform or TypoScript',1),'No HTML Template found, check the path to your HTML Template');
		$picfolder = $this->getvalue('pic_folder','pageconfig','','No or invalid picture folder',1);
		$watermark_pic = $this->getvalue('watermark_pic','config_single','','',1);
		$watermark_alpha = $this->getvalue('watermark_alpha','config_single','50','Invalid Watermark Alpha',0);
		$showcat_single = $this->getvalue('showcat','config_single','0','Invalid showcat value for singleview',0);
		$showcat_list = $this->getvalue('showcat','config_list','1','Invalid showcat value for listview',0);
		$allow_fe_cache_delete = $this->getvalue('allow_fe_cache_delete','pageconfig','1','Invalid allow_fe_cache_delete option',0);
		$basename = '.'.$this->getvalue('generate_basename','pageconfig','jpg','No basename set',0);
		$content = '';
		
		// include Function to delete old cached files in cache folders
		$this->deleteOldStuff($this->cachefolders,0);
		
		// if &tx_wtgallery_pi1[delete]= delete ALL files in the folder(s)
		if($this->piVars['delete'] AND $allow_fe_cache_delete == '1') { 
			$this->deleteOldStuff($this->cachefolders,$this->piVars['delete']); // delete it at once!
			$content = $this->msgwrap('Folder deltete done - '.$this->pi_linkToPage('Reload this page',$GLOBALS["TSFE"]->id,'','').' now',0); // Delete Message
		}
		
		// include Function to switch the view
		if($picfolder AND t3lib_div::int_from_ver(phpversion()) >= $this->minphpversion AND (!$this->piVars['delete'] OR ($this->piVars['delete'] AND $allow_fe_cache_delete != '1')) AND $this->foldercheck($this->cachefolders)) { // Show only if 1. admin enters a picturefolder and 2. cache deleting isn't active and 3. cache folders existing and 4. PHP Version is ok
			for($i=0;$i<count($view);$i++) { // One loop for every selected plugin
				$content .= $this->switchView($view[$i],$template,$picfolder,$watermark_pic,$watermark_alpha,$showcat_single,$showcat_list,$basename); // Opens Main Switch function
			}
		}
		
		// Checks if min PHP 4.2
		if (t3lib_div::int_from_ver(phpversion()) <= $this->minphpversion) {
			$content = $this->msgwrap('Wrong PHP version',1); // Overwrite $content with error message
		}
		
		return $this->pi_wrapInBaseClass($content);
	}
	
	// Function switchView try to show the right plugin to the right time :)
	function switchView($view,$template,$picfolder,$watermark_pic,$watermark_alpha,$showcat_single,$showcat_list,$basename) { // Use Single- or Thumbnail- or Categoryview
		if($view=='single') { // if Singleview selected
			if(t3lib_div::get_dirs($picfolder) AND $showcat_single == 1 AND $this->piVars['cat']) { // if: subfolders available, category mode on, show content of category 
				$content = $this->single_view_standard($template,$picfolder.$this->sanitizeGetVar($this->piVars['cat'],'path').'/',$watermark_pic,$watermark_alpha,$basename); // show single pic of subfolder
			} 
			elseif(!t3lib_div::get_dirs($picfolder) AND $showcat_single == 1 AND $this->piVars['cat']) { // if: subfolders not available, category mode on, show content of category 
				$content = $this->msgwrap('Error happens, '.$this->pi_linkToPage('Reload page',$GLOBALS["TSFE"]->id,'','').' without GET or POST variables',1); // Error
			}
			elseif(t3lib_div::get_dirs($picfolder) AND $showcat_single == 0 AND $this->piVars['cat']) { // if: subfolders available, category mode off, show content of category 
				$content = $this->single_view_standard($template,$picfolder.$this->sanitizeGetVar($this->piVars['cat'],'path').'/',$watermark_pic,$watermark_alpha,$basename); // show single pic of subfolder
			}
			elseif(!t3lib_div::get_dirs($picfolder) AND $showcat_single == 0 AND $this->piVars['cat']) { // if: subfolders not available, category mode off, show content of category 
				$content = $this->msgwrap('Error happens, '.$this->pi_linkToPage('Reload page',$GLOBALS["TSFE"]->id,'','').' without GET or POST variables',1); // Error
			}
			elseif(t3lib_div::get_dirs($picfolder) AND $showcat_single == 1 AND !$this->piVars['cat']) { // if: subfolders available, category mode on, show content of picfolder 
				$content = $this->category_view($template,$picfolder,$watermark_pic,$watermark_alpha,$basename); // show category mode
			}
			elseif(!t3lib_div::get_dirs($picfolder) AND $showcat_single == 1 AND !$this->piVars['cat']) { // if: subfolders not available, category mode on, show content of picfolder 
				$content = $this->single_view_standard($template,$picfolder,$watermark_pic,$watermark_alpha,$basename); // show picture from $picfolder
			}
			elseif(t3lib_div::get_dirs($picfolder) AND $showcat_single == 0 AND !$this->piVars['cat']) { // if: subfolders available, category mode off, show content of picfolder 
				$content = ''; // show nothing - singleview and categoryview disabled
			}
			elseif(!t3lib_div::get_dirs($picfolder) AND $showcat_single == 0 AND !$this->piVars['cat']) { // if: subfolders not available, category mode off, show content of picfolder  
				$content = $this->single_view_standard($template,$picfolder,$watermark_pic,$watermark_alpha,$basename); // show picture from $picfolder
			}
		}
		elseif($view=='list') { // if Thumbnailview (List) selected
			if(t3lib_div::get_dirs($picfolder) AND $showcat_list == 1 AND $this->piVars['cat']) { // if: subfolders available, category mode on, show content of category 
				$content = $this->list_view($template,$picfolder.$this->sanitizeGetVar($this->piVars['cat'],'path').'/',$watermark_pic,$watermark_alpha,$basename); // show list of pictures from subfolder
			} 
			elseif(!t3lib_div::get_dirs($picfolder) AND $showcat_list == 1 AND $this->piVars['cat']) { // if: subfolders not available, category mode on, show content of category 
				$content = $this->msgwrap('Error happens, '.$this->pi_linkToPage('Reload page',$GLOBALS["TSFE"]->id,'','').' without GET or POST variables',1); // Error
			}
			elseif(t3lib_div::get_dirs($picfolder) AND $showcat_list == 0 AND $this->piVars['cat']) { // if: subfolders available, category mode off, show content of category 
				$content = $this->list_view($template,$picfolder.$this->sanitizeGetVar($this->piVars['cat'],'path').'/',$watermark_pic,$watermark_alpha,$basename); // show list of pictures from subfolder
			}
			elseif(!t3lib_div::get_dirs($picfolder) AND $showcat_list == 0 AND $this->piVars['cat']) { // if: subfolders not available, category mode off, show content of category 
				$content = $this->msgwrap('Error happens, '.$this->pi_linkToPage('Reload page',$GLOBALS["TSFE"]->id,'','').' without GET or POST variables',1); // Error
			}
			elseif(t3lib_div::get_dirs($picfolder) AND $showcat_list == 1 AND !$this->piVars['cat']) { // if: subfolders available, category mode on, show content of picfolder 
				$content = $this->category_view($template,$picfolder,$watermark_pic,$watermark_alpha,$basename); // show category mode
			}
			elseif(!t3lib_div::get_dirs($picfolder) AND $showcat_list == 1 AND !$this->piVars['cat']) { // if: subfolders not available, category mode on, show content of picfolder 
				$content = $this->list_view($template,$picfolder,$watermark_pic,$watermark_alpha,$basename); // show list of pictures from $picfolder
			}
			elseif(t3lib_div::get_dirs($picfolder) AND $showcat_list == 0 AND !$this->piVars['cat']) { // if: subfolders available, category mode off, show content of picfolder 
				$content = ''; // show nothing listview and categoryview disabled
			}
			elseif(!t3lib_div::get_dirs($picfolder) AND $showcat_list == 0 AND !$this->piVars['cat']) { // if: subfolders not available, category mode off, show content of picfolder  
				$content = $this->list_view($template,$picfolder,$watermark_pic,$watermark_alpha,$basename); // show list of pictures from $picfolder
			}
		
		}
		elseif($view!=0 AND $view!=1) $content = $this->msgwrap('Wrong showing parameter',1); // Error
		if($showcat_single != 0 AND $showcat_single != 1) $content = $this->msgwrap('Wrong parameter for category mode (Allowed only 0 or 1)',1); // Error
		
		return $content;
	}
	
	// Function category_view for categories
	function category_view($template,$picfolder,$watermark_pic,$watermark_alpha,$basename) {
		$width = $this->getvalue('image_width','config_category','150','No width for category picture',0);
		$quality = $this->getvalue('image_quality','config_category','75','No quality for category images',0);
		$ratio = $this->getvalue('category_ratio','config_category','','',0);
		$catperpage = $this->getvalue('catperpage','config_category','10','',0);
		$category_no = intval($this->piVars['catno']);
		$order = $this->getvalue('order','config_category','newest','No order for category view',0);
		$filter_array['filter_grey'] = $this->getvalue('filter_greyscale','special_effects_category','0','No value for filter_greyscale',0); // Special Effect Filter
		$filter_array['filter_soft'] = $this->getvalue('filter_soft','special_effects_category','0','No value for filter_soft',0); // Special Effect Filter
		$filter_array['filter_bright'] = $this->getvalue('filter_bright','special_effects_category','0','No value for filter_bright',0); // Special Effect Filter
		$filter_array['filter_contrast'] = $this->getvalue('filter_contrast','special_effects_category','0','No value for filter_contrast',0); // Special Effect Filter
		$roundedcorner_array['roundedcorner'] = $this->getvalue('roundedcorner_active','special_effects_category','0','No value for activating roundedcorner_active',0); // Rounded Corners
		$roundedcorner_array['radius'] = $this->getvalue('roundedcorner_radius','special_effects_category','20','No value for activating roundedcorner_active',0); // Rounded Corners
		$roundedcorner_array['border'] = $this->getvalue('roundedcorner_border','special_effects_category','3','No value for activating roundedcorner_active',0); // Rounded Corners
		$roundedcorner_array['bordercolor'] = $this->getvalue('roundedcorner_bordercolor','special_effects_category','#444444','No value for activating roundedcorner_active',0); // Rounded Corners
		$roundedcorner_array['backgroundcolor'] = $this->getvalue('roundedcorner_backgroundcolor','special_effects_category','#ffffff','No value for activating roundedcorner_active',0); // Rounded Corners
		$roundedcorner_array['innerradiussubtract'] = $this->getvalue('roundedcorner_innerradiussubtract','special_effects_category','3','No value for substract inner radius',0); // Rounded Corners 
		$folders = t3lib_div::get_dirs($picfolder); // Get all subfolders in the picture folder
		
		$tmpl["outer"] = $this->cObj->getSubpart($template,"###TEMPLATE_CATEGORY###"); // Work on subpart
		$tmpl["inner"] = $this->cObj->getSubpart( $tmpl["outer"],"###TEMPLATE_CATEGORY_CONTENT###"); // Work on subpart
				
		if (file_exists($picfolder.$folders[0])) { // Only if there are folders
			
			$folders = $this->OrderFolders($picfolder,$folders,$order); // Sort Folders with function
			
			if ($category_no) $start = $catperpage * $category_no; else $start = 0; 
			$stop = $start + $catperpage;
			
			$j = 0; $i = 0;
			foreach ($folders as $value) { // One loop for every subfolder
				$k = $i + 1;
				if($i >= $start AND $k <= $stop) { 
					$picture = $this->categoryPicture($picfolder,$value); // get picture to show
					
					if($picture) $markerArray['###CATEGORY_PIC###'] = $this->createNewPicture($picture,$this->generateHash($picture,'',$quality,$ratio,$quality,$watermark_pic,$watermark_alpha,$roundedcorner_array,$filter_array).$basename,'uploads/tx_wtgallery/cat/','','',$width,$quality,$ratio,'wtgallery_category_pic','',$GLOBALS["TSFE"]->id,array('cat'=>$value),$filter_array,$roundedcorner_array); // show picture if wanted
					$markerArray['###CATEGORY_TXT###'] = $this->readTextComment($picfolder,$value,$template,1); // show description
					$markerArray['###CATEGORY_FOLDER###'] = $this->pi_linkTP_keepPIvars($value,array('cat'=>$value),$this->cache,1,$GLOBALS["TSFE"]->id); // show name of folder with link
					
					$content_item .= $this->cObj->substituteMarkerArrayCached($tmpl["inner"],$markerArray, array(), array());
					$j++;
				}
				$i++;
			}
		
			/* PAGEBROWSER  */
			$pages = ceil($i / $catperpage);
			if ($category_no == '') $category_no = '0';
			$act_page = $category_no + '1';
			
			if($category_no < $pages AND $catperpage > '0') { // Show only if this category exists
				$markerArray['###PAGEBROWSER_FROM###'] = $act_page;
				$markerArray['###PAGEBROWSER_TO###'] = $pages;
				
				$fwd = $category_no + '1'; $back = $category_no - '1';
				$markerArray['###PAGEBROWSER_BACK###'] = $this->pi_linkTP_keepPIvars($this->pi_getLL('pagebrowser_back'), array('catno'=>$back), $this->cache, 1, $GLOBALS["TSFE"]->id)."\n";
				$markerArray['###PAGEBROWSER_FWD###'] = $this->pi_linkTP_keepPIvars($this->pi_getLL('pagebrowser_fwd'), array('catno'=>$fwd), $this->cache, 1, $GLOBALS["TSFE"]->id)."\n";
				if ($pages == $act_page) $markerArray['###PAGEBROWSER_FWD###'] = ''; // Clear Marker at the end
				if ($act_page == '1') $markerArray['###PAGEBROWSER_BACK###'] = ''; // Clear Marker at beginning
				
				$subpartArray['###INNER###'] = $content_item;
				$markerArray['###CATEGORY_HEADER###'] = $this->pi_getLL('category_header');
			
			} else { // wrong pagebrowser - show error
				$markerArray['###CATEGORY_PIC###'] = $this->msgwrap('GET variable "&tx_wtgallery_pi1[catno]='.$category_no.'" not valid '.$this->pi_linkToPage('Reload page',$GLOBALS["TSFE"]->id,'',''),1); // Error
			}
			
			$content = $this->cObj->substituteMarkerArrayCached($tmpl["outer"],$markerArray, $subpartArray,$wrappedSubpartArray); // Returns HTML Template with wrapped Markers
			$content = preg_replace("|###.*###|i","",$content); // Finally clear not filled markers
			return $content;
		}
	}
	
	// Function categoryPicture returns a picture name of the current category
	function categoryPicture($folder,$value) {
		$catpicture = $this->getvalue('picture','config_category','random','No definition for showing category pictures',0);
		$subfiles_array = t3lib_div::getFilesInDir($folder.$value,'jpg,jpeg,gif,png','','1'); // Reads all files in current subfolder
		
		switch ($catpicture) {
		
			case 'off': // if off do nothing - $picture will be empty
   				break;
				
			case 'defined':
				if (file_exists($folder.$value.'.jpg')) { // if there is a picture folder.jpg for folder
					$picture = $folder.$value.'.jpg';
				} else { // if there is no picture
					foreach ($subfiles_array as $subfiles) { // One loop for every subfile
						$picture = $folder.$value.'/'.$subfiles; // take the first picture
						break; // break after first picture
					} 
				}
				return $picture;
				
			case 'random':
				shuffle($subfiles_array); // Array mixing like rand
				foreach ($subfiles_array as $subfiles) { // One loop for every subfile
					$picture = $folder.$value.'/'.$subfiles;
					break; // break after first picture
				} 
				return $picture;
				
			case 'oldest':
				foreach ($subfiles_array as $subfiles) { // One loop for every subfile
					$newarray[$subfiles] = filemtime($folder.$value.'/'.$subfiles);
				} 
				asort($newarray);
				foreach ($newarray as $file => $age) {
					$picture = $folder.$value.'/'.$file; // choose first file (oldest)
					break; // break after first picture
				}
				return $picture;
				
			case 'newest':
				foreach ($subfiles_array as $subfiles) { // One loop for every subfile
					$newarray[$subfiles] = filemtime($folder.$value.'/'.$subfiles);
				} 
				arsort($newarray);
				foreach ($newarray as $file => $age) {
					$picture = $folder.$value.'/'.$file; // choose first file (newest)
					break; // break after first picture
				}
				return $picture;
				
			case 'alphabeticalASC': // if alphabeticalASC
   				asort($subfiles_array); // Array sorting ASC
				foreach ($subfiles_array as $subfiles) { // One loop for every subfile
					$picture = $folder.$value.'/'.$subfiles;
					break; // break after first picture
				} 
				return $picture;
				
			case 'alphabeticalDESC': // if alphabeticalDESC
   				arsort($subfiles_array); // Array sorting DESC
				foreach ($subfiles_array as $subfiles) { // One loop for every subfile
					$picture = $folder.$value.'/'.$subfiles;
					break; // break after first picture
				} 
				return $picture;
				
			default: // if undefined show error and $picture will be empty
				echo $this->msgwrap('show category picture method: '.$catpicture.' not allowed - allowed: off, defined, random, oldest, newest, alphabeticalASC, alphabeticalDESC',1); // Error
				break;
		}
	}
	
	// Function list_view to create all thumb pictures
	function list_view($template,$picfolder,$watermark_pic,$watermark_alpha,$basename) {
		
		// Load values
		$template = $this->cObj->getSubpart($template,"###TEMPLATE_LIST###");
		$single_site = $this->getvalue('single_site','config_list',$GLOBALS["TSFE"]->id,'PID of single page plugin undefined',0);
		$width = $this->getvalue('list_width','config_list','100','No width for list view',0);
		$ratio = $this->getvalue('list_ratio','config_list','','',0);
		$cols = $this->getvalue('width_number','config_list','3','Number of cols for list view undefined',0);
		$rows = $this->getvalue('height_number','config_list','3','Number of rows for list view undefined',0);
		$quality = $this->getvalue('list_quality','config_list','75','Picturequality for list view undefined',0);
		$filter_array['filter_grey'] = $this->getvalue('filter_greyscale','special_effects_list','0','No value for filter_greyscale',0); // Special Effect Filter
		$filter_array['filter_soft'] = $this->getvalue('filter_soft','special_effects_list','0','No value for filter_soft',0); // Special Effect Filter
		$filter_array['filter_bright'] = $this->getvalue('filter_bright','special_effects_list','0','No value for filter_bright',0); // Special Effect Filter
		$filter_array['filter_contrast'] = $this->getvalue('filter_contrast','special_effects_list','0','No value for filter_contrast',0); // Special Effect Filter
		$roundedcorner_array['roundedcorner'] = $this->getvalue('roundedcorner_active','special_effects_list','0','No value for activating roundedcorner_active',0); // Rounded Corners
		$roundedcorner_array['radius'] = $this->getvalue('roundedcorner_radius','special_effects_list','20','No value for activating roundedcorner_active',0); // Rounded Corners
		$roundedcorner_array['border'] = $this->getvalue('roundedcorner_border','special_effects_list','3','No value for activating roundedcorner_active',0); // Rounded Corners
		$roundedcorner_array['bordercolor'] = $this->getvalue('roundedcorner_bordercolor','special_effects_list','#444444','No value for activating roundedcorner_active',0); // Rounded Corners
		$roundedcorner_array['backgroundcolor'] = $this->getvalue('roundedcorner_backgroundcolor','special_effects_list','#ffffff','No value for activating roundedcorner_active',0); // Rounded Corners
		$roundedcorner_array['innerradiussubtract'] = $this->getvalue('roundedcorner_innerradiussubtract','special_effects_list','3','No value for substract inner radius',0); // Rounded Corners 

		// read GET variables
		$currentpic = $this->sanitizeGetVar($this->piVars['picid'],'path');
		$pagebrowser = intval($this->piVars['thumbid']);
		if ($pagebrowser == '') $pagebrowser = '0'; // If no GET Variables, the first picture is active
		$category_set = $this->sanitizeGetVar($this->piVars['cat'],'path');
		
		$files = t3lib_div::getFilesInDir($picfolder,'jpg,jpeg,gif,png','','1');
		$x = $cols * $rows; // Number of pictures pro page
		if ($pagebrowser != '') $start = $x * $pagebrowser; else $start = '0'; // Startpicture
		$stop = $start + $x; // Stoppicture
		$i=0;  // all pics in directory - for pagebrowser
		$j=1; // all pics on page - for pagebreak
			
		if (count($files) > 0) { // Only if there are files
			foreach ($files as $picture) { // Choose every picture
				$k = $i + 1;
				if ($i >= $start AND $k <= $stop) {	// maxrows
					$markerArray['###PIC###'] .= '<div '; // Start Marker with opening a DIV Container
					if ($cols != '1') { // Only if Columns more than 1
						if (fmod($j,$cols) != '1') $markerArray['###PIC###'] .= 'style="float: left;"'; // non break
						elseif (fmod($j,$cols) == '1') $markerArray['###PIC###'] .= 'style="float: left; clear: left;"'; // break
					}
					
					// Add another class to the first and the last picture of the list
					if ($cols > 1) { // Add additional class if columns more than 1
						if(($i+1)/$cols == round(($i+1)/$cols)) { // If the current picture is the last of the row (current / cols == integer)
							$classadd_div = ' wtgallery_listpic_lastofrow'; // Additional class for DIV Container
							$classadd_pic = ' wtgallery_listpic_pic_lastofrow'; // Additional class for Picture
						} elseif (fmod($i+1,$cols) == '1') { // If the current picture is the first of the row
							$classadd_div = ' wtgallery_listpic_firstofrow'; // Additional class for DIV Container
							$classadd_pic = ' wtgallery_listpic_pic_firstofrow'; // Additional class for Picture
						} else { // If current is not the first and not the last in the row
							$classadd_div = ''; $classadd_pic = ''; // No additional class
						}
					}
					// Current picture separating with different classes (show the current picture in a special style)
					if ($currentpic) { // The visitor has choosen a special picture
						if ($picture == $currentpic) { // The current picture of the loop == the picture that the visitor has choosen
							$markerArray['###PIC###'] .= ' class="wtgallery_listpic wtgallery_listpic_act'.$classadd_div.'">'; // Close DIV
							$markerArray['###PIC###'] .= $this->createNewPicture($picfolder.$picture,$this->generateHash($picture,$picfolder,$quality,$ratio,$quality,$watermark_pic,$watermark_alpha,$roundedcorner_array,$filter_array).$basename,'uploads/tx_wtgallery/thumbs/','','',$width,$quality,$ratio,'wtgallery_listpic_pic wtgallery_listpic_pic_act'.$classadd_pic,'',$single_site,array('picid'=>$picture,'cat'=>$category_set,'thumbid'=>$pagebrowser),$filter_array,$roundedcorner_array); // Generate Thumb
						} else { // The current picture of the loop is not the picture that the visitor has choosen
							$markerArray['###PIC###'] .= ' class="wtgallery_listpic wtgallery_listpic_no'.$classadd_div.'">'; // Close DIV
							$markerArray['###PIC###'] .= $this->createNewPicture($picfolder.$picture,$this->generateHash($picture,$picfolder,$quality,$ratio,$quality,$watermark_pic,$watermark_alpha,$roundedcorner_array,$filter_array).$basename,'uploads/tx_wtgallery/thumbs/','','',$width,$quality,$ratio,'wtgallery_listpic_pic wtgallery_listpic_pic_no'.$classadd_pic,'',$single_site,array('picid'=>$picture,'cat'=>$category_set,'thumbid'=>$pagebrowser),$filter_array,$roundedcorner_array); // Generate Thumb
						}
					} else { // Else: no picture is selected, take first one
						if ($i == '0') { // If the current picture of the loop is the first one
							$markerArray['###PIC###'] .= ' class="wtgallery_listpic wtgallery_listpic_act'.$classadd_div.'">'; // Close DIV
							$markerArray['###PIC###'] .= $this->createNewPicture($picfolder.$picture,$this->generateHash($picture,$picfolder,$quality,$ratio,$quality,$watermark_pic,$watermark_alpha,$roundedcorner_array,$filter_array).$basename,'uploads/tx_wtgallery/thumbs/','','',$width,$quality,$ratio,'wtgallery_listpic_pic wtgallery_listpic_pic_act'.$classadd_pic,'',$single_site,array('picid'=>$picture,'cat'=>$category_set,'thumbid'=>$pagebrowser),$filter_array,$roundedcorner_array); // Generate Thumb
						} else { // If the current picture is not the first one
							$markerArray['###PIC###'] .= ' class="wtgallery_listpic wtgallery_listpic_no'.$classadd_div.'">'; // Close DIV
							$markerArray['###PIC###'] .= $this->createNewPicture($picfolder.$picture,$this->generateHash($picture,$picfolder,$quality,$ratio,$quality,$watermark_pic,$watermark_alpha,$roundedcorner_array,$filter_array).$basename,'uploads/tx_wtgallery/thumbs/','','',$width,$quality,$ratio,'wtgallery_listpic_pic wtgallery_listpic_pic_no'.$classadd_pic,'',$single_site,array('picid'=>$picture,'cat'=>$category_set,'thumbid'=>$pagebrowser),$filter_array,$roundedcorner_array); // Generate Thumb
						}
					}
					$markerArray['###PIC###'] .= '</div>'."\n"; // Close DIV
					
					$j++;
				}
				$i++;
			}
			
			/* PAGEBROWSER  */
			$pages = ceil($i / $x);
			$act_page = $pagebrowser + '1';
			
			if($pagebrowser <= $pages AND $pagebrowser >= '0') { // Only if given Pagebrowser make sense
				$markerArray['###PAGEBROWSER_PAGE_FROM###'] = $act_page;
				$markerArray['###PAGEBROWSER_PAGE_TO###'] = $pages;
				
				// Marker ###PAGEBROWSER_FWD### AND ###PAGEBROWSER_BACK### - Forward and Backward Markers
				$fwd = $pagebrowser + '1'; $back = $pagebrowser - '1';
				$this->local_cObj = t3lib_div::makeInstance('tslib_cObj'); // Generate Object
				
				if($pages != $act_page) { // ###PAGEBROWSER_FWD###
					$typolink_conf_fwd = array("parameter" => $GLOBALS["TSFE"]->id, "additionalParams" => '&'.$this->prefixId.'[thumbid]'.'='.rawurlencode($fwd).'&'.$this->prefixId.'[picid]'.'='.rawurlencode($currentpic).'&'.$this->prefixId.'[cat]'.'='.rawurlencode($category_set),"useCacheHash" => 1); // Preconfigure the typolink FWD
					$wrappedSubpartArray['###LINK_FWD###'] = $this->local_cObj->typolinkWrap($typolink_conf_fwd); // Replace the FWD link Marker
					$markerArray['###PAGEBROWSER_FWD###'] = $this->pi_getLL('pagebrowser_fwd'); // Fill marker with locallang content
				}
				if($act_page != '1') { // ###PAGEBROWSER_BACK###
					$typolink_conf_back = array("parameter" => $GLOBALS["TSFE"]->id, "additionalParams" => '&'.$this->prefixId.'[thumbid]'.'='.rawurlencode($back).'&'.$this->prefixId.'[picid]'.'='.rawurlencode($currentpic).'&'.$this->prefixId.'[cat]'.'='.rawurlencode($category_set),"useCacheHash" => 1); // Preconfigure the typolink BACK
					$wrappedSubpartArray['###LINK_BACK###'] = $this->local_cObj->typolinkWrap($typolink_conf_back); // Replace the FWD link Marker
					$markerArray['###PAGEBROWSER_BACK###'] = $this->pi_getLL('pagebrowser_back'); // Fill marker with locallang content
				}
				if($this->piVars['cat']) { // ###UP###
					$typolink_conf_up = array("parameter" => $GLOBALS["TSFE"]->id, "additionalParams" => ''); // Preconfigure the typolink UP
					$wrappedSubpartArray['###LINK_UP###'] = $this->local_cObj->typolinkWrap($typolink_conf_up); // Replace the FWD link Marker
					$markerArray['###UP###'] = $this->pi_getLL('up'); // Fill marker with locallang content
				}
			} else { // wrong pagebrowser - show error
				$markerArray['###PIC###'] = $this->msgwrap('GET variable "&tx_wtgallery_pi1[cat]='.$pagebrowser.'" not valid '.$this->pi_linkToPage('Reload page',$GLOBALS["TSFE"]->id,'',''),1); // Error
			}
		
		} else echo $this->msgwrap('There are no pictures to show',1); // Error
		
		$content = $this->cObj->substituteMarkerArrayCached($template,$markerArray,array(),$wrappedSubpartArray); // Returns HTML Template with wrapped Markers
		$content = preg_replace("|###.*###|i","",$content); // Finally clear not filled markers
		return $content;
	}
	
	// Function single_view_standard to create single picture content
	function single_view_standard($template,$picfolder,$watermark_pic,$watermark_alpha,$basename) {
	
		// Load values
		$template_single = $this->cObj->getSubpart($template,"###TEMPLATE_SINGLE###"); // work on Subpart
		$width = $this->getvalue('single_width','config_single','400','No width for single view defined',0);
		$quality = $this->getvalue('single_quality','config_single','75','No picture quality for single view defined',0);
		$comments_active = $this->getvalue('show_comments','config_single','0','No definition of showing comments defined',0);
		$ratio = $this->getvalue('single_ratio','config_single','0','No ratio for single view defined',0);
		$list_site = $this->getvalue('list_site','config_single',$GLOBALS["TSFE"]->id,'No PID of list view defined',0);
		$popup = $this->getvalue('show_popup','config_popup','1','No popup definition',0);
		$popup_width = $this->getvalue('popup_width','config_popup','800','No popup width defined',0);
		$popup_quality = $this->getvalue('quality','config_popup','75','No popup quality defined',0);
		$filter_array['filter_grey'] = $this->getvalue('filter_greyscale','special_effects_single','0','No value for filter_greyscale',0); // Special Effect Filter
		$filter_array['filter_soft'] = $this->getvalue('filter_soft','special_effects_single','0','No value for filter_soft',0); // Special Effect Filter
		$filter_array['filter_bright'] = $this->getvalue('filter_bright','special_effects_single','0','No value for filter_bright',0); // Special Effect Filter
		$filter_array['filter_contrast'] = $this->getvalue('filter_contrast','special_effects_single','0','No value for filter_contrast',0); // Special Effect Filter
		$roundedcorner_array['roundedcorner'] = $this->getvalue('roundedcorner_active','special_effects_single','0','No value for activating roundedcorner_active',0); // Rounded Corners
		$roundedcorner_array['radius'] = $this->getvalue('roundedcorner_radius','special_effects_single','20','No value for activating roundedcorner_active',0); // Rounded Corners
		$roundedcorner_array['border'] = $this->getvalue('roundedcorner_border','special_effects_single','3','No value for activating roundedcorner_active',0); // Rounded Corners
		$roundedcorner_array['bordercolor'] = $this->getvalue('roundedcorner_bordercolor','special_effects_single','#444444','No value for activating roundedcorner_active',0); // Rounded Corners
		$roundedcorner_array['backgroundcolor'] = $this->getvalue('roundedcorner_backgroundcolor','special_effects_single','#ffffff','No value for activating roundedcorner_active',0); // Rounded Corners
		$roundedcorner_array['innerradiussubtract'] = $this->getvalue('roundedcorner_innerradiussubtract','special_effects_single','3','No value for substract inner radius',0); // Rounded Corners 
		
		// Imagelightbox part
		if($popup=='imagelightbox' AND t3lib_extMgm::isLoaded('kj_imagelightbox2',0)) { // If imagelightbox should be activated AND imagelightbox is loaded
			$IMLconf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['kj_imagelightbox2']); // Get imagelightbox configuration
			include t3lib_extMgm::extPath('wt_gallery') . 'imagelightbox/imagelightbox.php'; // Include Header Code
			$GLOBALS['TSFE']->pSetup["headerData."]['100'] = 'COA';
			$GLOBALS['TSFE']->pSetup["headerData."]['100.']['10'] = "TEXT";
			$GLOBALS['TSFE']->pSetup["headerData."]['100.']['10.']['value'] = $imlightbox;
		}

		// Main part
		$files = t3lib_div::getFilesInDir($picfolder,'jpg,jpeg,gif,png','','1');
		if (count($files) > 0) { 
			if (!$this->piVars['picid'] OR !file_exists($picfolder.$this->sanitizeGetVar($this->piVars['picid'],'path'))) { // If no image for SingleView is selected or if requested picture don't exists, take first one	
				foreach ($files as $filename) {
					$file = $filename;
					break; // Stop after first loop
				}
			} else $file = $this->sanitizeGetVar($this->piVars['picid'],'path'); // Get Filename from GET variable
			$filename_cached = $this->generateHash($file,$picfolder,$width,$ratio,$quality,$watermark_pic,$watermark_alpha,$roundedcorner_array,$filter_array).$basename; // Generate Hash like 1209A2215654646.jpg			
			
			// Generate a special Popup if needed
			if (($popup == '1' || $popup == 'imagelightbox' || $popup == 'perfectlightbox') AND !file_exists('uploads/tx_wtgallery/popup/'.$filename_cached)) { // Generate Popup Cache file if needed
				$this->createNewPicture($picfolder.$file,$filename_cached,'uploads/tx_wtgallery/popup/',$watermark_pic,$watermark_alpha,$popup_width,$popup_quality,'','','','','','',''); // resize and cache image if necessary
			} 
			
			// Main Marker ###PIC###
			$markerArray['###PIC###'] = $this->createNewPicture($picfolder.$file,$filename_cached,'uploads/tx_wtgallery/single/',$watermark_pic,$watermark_alpha,$width,$quality,$ratio,'wtgallery_singlepic_pic',$popup,'','',$filter_array,$roundedcorner_array);
			
			// Marker ###ENLARGE###
			if($popup != '0') $markerArray['###ENLARGE###'] = $this->pi_getLL('enlarge');
			
			// Marker ###BACKLINK### - if not same page generate 'back'-button to go back to the list view
			if ($list_site != $GLOBALS["TSFE"]->id) {
				$markerArray['###BACKLINK###'] = $this->pi_linkTP_keepPIvars($this->pi_getLL('backtolist'), array('picid'=>$filename,'thumbid'=>intval($this->piVars['thumbid']),'cat'=>$this->sanitizeGetVar($this->piVars['cat'],'path')), $this->cache, 1, $list_site);
			}
			
			// Marker ###COMMENT###
			$markerArray['###COMMENT###'] = $this->generateComment($comments_active,$picfolder,$file,$template);
			
			// Marker ###FWD### AND ###BACK### - Forward and Backward Markers
			$file_array = $this->stepbystep($file,$files); // Get files before and after current object
			$this->local_cObj = t3lib_div::makeInstance('tslib_cObj'); // Generate Object
			
			if($file_array['fwd']) { // ###FWD###
				$typolink_conf_fwd = array("parameter" => $GLOBALS["TSFE"]->id, "additionalParams" => '&'.$this->prefixId.'[cat]'.'='.rawurlencode($this->sanitizeGetVar($this->piVars['cat'],'path')).'&'.$this->prefixId.'[picid]'.'='.rawurlencode($file_array['fwd']).'&'.$this->prefixId.'[thumbid]'.'='.intval($this->piVars['thumbid']),"useCacheHash" => 1); // Preconfigure the typolink FWD
				$wrappedSubpartArray['###LINK_FWD###'] = $this->local_cObj->typolinkWrap($typolink_conf_fwd); // Replace the FWD link Marker
				$markerArray['###FWD###'] = $this->pi_getLL('fwd');
			}
			
			if($file_array['back']) { // ###BACK###
				$typolink_conf_back = array("parameter" => $GLOBALS["TSFE"]->id, "additionalParams" => '&'.$this->prefixId.'[cat]'.'='.rawurlencode($this->sanitizeGetVar($this->piVars['cat'],'path')).'&'.$this->prefixId.'[picid]'.'='.rawurlencode($file_array['back']).'&'.$this->prefixId.'[thumbid]'.'='.intval($this->piVars['thumbid']),"useCacheHash" => 1); // Preconfigure the typolink BACK
				$wrappedSubpartArray['###LINK_BACK###'] = $this->local_cObj->typolinkWrap($typolink_conf_back); // Replace the BACK link Marker
				$markerArray['###BACK###'] = $this->pi_getLL('back');
			}
			
			if($this->piVars['cat']) { // Marker ###UP### if single view shows a subfolder generate 'up'-button
				if($list_site == $GLOBALS["TSFE"]->id) $up_target = $GLOBALS["TSFE"]->id; else $up_target = $list_site; // Target same page or back to list view if on another page
				$typolink_conf_up = array("parameter" => $up_target, "additionalParams" => ''); // Preconfigure the typolink BACK
				$wrappedSubpartArray['###LINK_UP###'] = $this->local_cObj->typolinkWrap($typolink_conf_up); // Replace the BACK link Marker
				$markerArray['###UP###'] = $this->pi_getLL('up');
			}
			
		} else echo $this->msgwrap('No pictures in folder: '.$picfolder,1);
		
		$content = $this->cObj->substituteMarkerArrayCached($template_single,$markerArray,array(),$wrappedSubpartArray); // Returns HTML Template with wrapped Markers
		$content = preg_replace("|###.*###|i","",$content); // Finally clear not filled markers
		return $content;
	}
	
	
	
/* #########################   Here begins the part of the subfunctions - Main functions on top ############################ */



	// function createNewPicture
	// @param string:	$image				filename of source image			e.g.: fileadmin/start.jpg
	// @param string: 	$image_result		filename of cached image			e.g.: finish.jpg
	// @param string:	$thumbPath			path of cache-folder				e.g.: fileadmin/finish_folder/
	// @param string: 	$watermark_path 	path+filename of watermark image	e.g.: fileadmin/watermark.jpg
	// @param int: 		$watermark_alpha	opacity of watermark image			e.g.: 50
	// @param int:		$width				width of resized image				e.g.: 400
	// @param int: 		$quality			JPG quality							e.g.: 70
	// @param string:	$ratio				aspect-ratio of resized image		e.g.: 1:1
	// @param sring:	$class				Generate a class					e.g.: wt_gallery_singlepic_pic
	// @param int: 		$js_popup			Wrap with JS?						e.g.: 1
	// @param int:		$href_pid			Wraps image with a link to PID		e.g.: 1
	// @param string:	$href_params_array	Params Array (GET Variables)		e.g.: array($this->prefixId.'[picid]'=>'test.jpg',$this->prefixId.'[thumbid]'=>'5')
	// @param string:	$filter_array		Filter Array						e.g.: array($test'=>'test')
	// @param string:	$roundedcorner_arrayRoundedcorner Array					e.g.: array($test'=>'test')
	
	// Example use: 	$this->createNewPicture('fileadmin/apfel.jpg','test.jpg','uploads/tx_wtgallery/thumbs/','','','200','90','1:1','wtgallery_listpic_pic','','','','');
	// Example return:	<img src="uploads/tx_wtgallery/thumbs/test.jpg" width="200" class="wtgallery_listpic_pic" alt="apfel" title="apfel" />
	
	function createNewPicture($image,$image_result,$thumbPath,$watermark_path,$watermark_alpha,$width,$quality,$ratio,$class,$js_popup,$href_pid,$href_params_array,$filter_array,$roundedcorner_array) {
		
		$title = substr(substr($image,0,strrpos($image,'.')),strrpos($image,'/')+1); // Title from test.jpg is test
		
		if (!file_exists($thumbPath.$image_result) AND file_exists($image)) { // Only generate a new picture, if the picture don't exist and the start picture exists
			$image_info = getimagesize($image);
       
	   		// Part included to function on 03.04.2007 - Bugfix (Extension removes to much of a landscape picture)
			if ($ratio == '0' OR strpos($ratio,':') == 0) { // If no ratio or value nonsense - no :
					$aspect_array[0] = 1;
					$aspect_array[1] = $image_info[1] / $image_info[0];
					$x = $image_info[0];
					$y = $image_info[1];
			} else {
					$aspect_array = explode(":", $ratio);
					if ($aspect_array[0] == $aspect_array[1]) { // when 1:1
							if ($image_info[0] > $image_info[1]) { // landscape format
									$y = $image_info[1];
									$x = $image_info[1]; // max height
							} else { // portrait format
									$y = $image_info[0];
									$x = $image_info[0]; // max width
							}
					}
					if ($aspect_array[0] > $aspect_array[1]) { // Aspect 2:1 or similar
							$x = $image_info[0];
							$y = intval($image_info[0] / $aspect_array[0] * $aspect_array[1]);
					}
					if ($aspect_array[0] < $aspect_array[1]) { // Aspect 1:2 or similar
							$y = $image_info[1];
							$x = intval($image_info[1] * $aspect_array[0] / $aspect_array[1]);
					}
			}			
			
			if ($width == 'auto') {
				$width = $x; // Patch on 14.09.2007: original picture width for single pictures
			} else {
				if ( strpos($width,"m") ) { // rezize image to max width if it is too big
					$width = intval(preg_replace("/m/","",$width));
					if ( $x <= $width ) $width = $x;
				}
			}

			
			$height = intval($width * $y / $x);
		
			// get extension
			switch($image_info[2]) {
				case "1":
					$oldPic=ImageCreateFromGIF($image);
				break;
				
				case "2":
					$oldPic=ImageCreateFromJPEG($image);
				break;
				
				case "3":
					$oldPic=ImageCreateFromPNG($image);
				break;
				
				default:
					return false;
				break;
			}
			
			$tmpPic=imageCreateTrueColor($x,$y);
			$newPic=imageCreateTrueColor($width,$height);
			ImageCopy($tmpPic,$oldPic,0,0,0,0,$x,$y);
			ImageCopyResampled($newPic,$tmpPic,0,0,0,0,$width,$height,$x,$y);
			
			// Watermark area
			if ($watermark_path != '' AND file_exists($watermark_path))  {
				$watermark_info = getimagesize($watermark_path);
				$watermark_pos_x = max(0,$width - $watermark_info[0]);
				$watermark_pos_y = max(0,$height - $watermark_info[1]);
				$watermark_alpha = max(0,$watermark_alpha);
				$watermark_alpha = min(100,$watermark_alpha);
				
				switch($watermark_info[2]) {
					case "1":
						$watermarkPic=ImageCreateFromGIF($watermark_path);
					break;
					
					case "2":
						$watermarkPic=ImageCreateFromJPEG($watermark_path);
					break;
					
					case "3":
						$watermarkPic=ImageCreateFromPNG($watermark_path);
					break;
					
					default:
						return false;
					break;
				}
				
				// enable alphablending
				imagealphablending($newPic, TRUE);
				imagealphablending($watermarkPic, TRUE);
				imagecolortransparent($watermarkPic, imagecolorat($watermarkPic, 1, 1));
				imagecopymerge($newPic, $watermarkPic, $watermark_pos_x, $watermark_pos_y, 0, 0, $watermark_info[0], $watermark_info[1], $watermark_alpha);
			}
			
			/* Filter begin */
			if(t3lib_div::int_from_ver(phpversion()) >= t3lib_div::int_from_ver('5.0.0') AND ($filter_array['filter_grey'] == 1 OR $filter_array['filter_soft'] == 1 OR $filter_array['filter_bright'] != 0 OR $filter_array['filter_contrast'] != 0)) { // All filter only with PHP5
				if($filter_array['filter_grey'] == 1) imagefilter($newPic, IMG_FILTER_GRAYSCALE);
				if($filter_array['filter_soft'] == 1) imagefilter($newPic, IMG_FILTER_GAUSSIAN_BLUR);	
				if($filter_array['filter_bright'] != 0) imagefilter($newPic, IMG_FILTER_BRIGHTNESS, $filter_bright);
				if($filter_array['filter_contrast'] != 0) imagefilter($newPic, IMG_FILTER_CONTRAST, $filter_contrast);
			} else {
				if($filter_array['filter_grey'] == 1 OR $filter_array['filter_soft'] == 1 OR $filter_array['filter_bright'] != 0 OR $filter_array['filter_contrast'] != 0) {
					echo $this->msgwrap('You\'re PHP version is too low for image filter (current version: '.phpversion().' / needed version: 5 or higher)',1);
				}
			}
			/* Filter end */
		
			// Generating Image file
			if(substr($image_result, -3) == 'png') { // Basename is png
				imagepng($newPic, $thumbPath.$image_result); // Output final image
			} elseif(substr($image_result, -3) == 'jpg') { // Basename is jpg
				ImageJPEG($newPic,$thumbPath.$image_result,$quality); // Output final image
			} else echo $this->msgwrap('Only .jpg or .png allowed in basename: '.$image_result,1);
			t3lib_div::fixPermissions($thumbPath.$image_result); // Set right file permissions
			
			// Style me with special effects like rounded corners or gdlib filters etc...
			if($roundedcorner_array['roundedcorner'] == 1 && function_exists('imagerotate')) { // Only if imagerotate() is availabe and styleMe is needed
				$this->styleMe($thumbPath.$image_result,$thumbPath.$image_result,$roundedcorner_array['radius'],t3lib_extMgm::extPath('wt_gallery').'images/',$roundedcorner_array['border'],$roundedcorner_array['bordercolor'],$quality,$roundedcorner_array['backgroundcolor'],$roundedcorner_array['innerradiussubtract']);
			} elseif ($roundedcorner_array['roundedcorner'] == 1 && !function_exists('imagerotate')) { // Error if imagerotate() is not available
				echo $this->msgwrap('Your server don\'t works with PHP function imagerotate()',1);
			}
		}
		
		if($image_result != '.jpg' AND $image_result != '.png') { // If there is a name before the extension (only if file really exists)
			$image_info = getimagesize($thumbPath.$image_result); // Image information
			$width = $image_info[0]; // imagewidth
			$imagetag = '<img src="'.$thumbPath.rawurlencode($image_result).'" width="'.$width.'" class="'.$class.'" alt="'.htmlentities($title).'" title="'.htmlentities($title).'" />';
			$content = $imagetag; // standard 
			if ($href_pid) $content = $this->pi_linkTP_keepPIvars($imagetag, $href_params_array, $this->cache, 1, $href_pid); // If $href than wrap with a tag
			
			// Popup activated
			if ($js_popup == '1') { // If popup is allowed
				$popup_width = $this->getvalue('popup_width','config_popup','800','No popup width defined',0);
				$popup_height = $this->getvalue('popup_height','config_popup','600','No popup height defined',0);
				$content = '<a href="uploads/tx_wtgallery/popup/'.$image_result.'" target="FEopenLink" onclick="vHWin=window.open(\'uploads/tx_wtgallery/popup/'.$image_result.'\',\'FEopenLink\',\'scrollbars=1,width='.$popup_width.',height='.$popup_height.'\');vHWin.focus();return false;" title="'.htmlentities($title).'">';
				$content .= $imagetag;
				$content .= '</a>';
				
			} elseif ($js_popup == 'wallpaper') { // Show the original picture (wallpaper)
				$content = '<a href="'.substr($image,strpos($image,'fileadmin')).'" target="_blank">'.$imagetag.'</a>';
				
				
			} elseif ($js_popup == 'imagelightbox' AND t3lib_extMgm::isLoaded('kj_imagelightbox2',0)) { // Use Lightbox for Linktag (imagelightbox) if needed and if extension loaded
				$content = '<a href="uploads/tx_wtgallery/popup/'.$image_result.'" rel="lightbox" title="" id="0" showNumberDisplay="0" kjtag="&lt;a href=&quot;uploads/tx_wtgallery/popup/'.$image_result.'&quot; target=&quot;_blank&quot;&gt;&lt;img src=&quot;typo3conf/ext/kj_imagelightbox2/lightbox/images/save.gif&quot; border=&quot;0&quot; title=&quot;Save image&quot;&quot;&lt;/a&gt; &nbsp; &nbsp;&lt;a href=&quot;typo3conf/ext/kj_imagelightbox2/res/print.php?image='.$image.'&quot; target=&quot;_blank&quot; &gt;&lt;img src=&quot;typo3conf/ext/kj_imagelightbox2/lightbox/images/print.gif&quot; border=&quot;0&quot; title=&quot;Print image&quot;&quot;&lt;/a&gt; &nbsp;">';
				$content .= $imagetag;
				$content .= '</a>';
			
			} elseif ($js_popup == 'perfectlightbox' AND t3lib_extMgm::isLoaded('perfectlightbox',0)) {
				$content = '<a href="uploads/tx_wtgallery/popup/'.$image_result.'" rel="lightbox" title="">';
    			$content .= $imagetag;
				$content .= '</a>';
			}
			
		} else { // Error - File don't exist	
			echo $this->msgwrap('Error in extension - wrong file to show',1);
		}
		
		return $content;
	}
	
	// Function styleMe to get special effects like rounded corners or gdlib filters...
	// Sourcefile, Destinationfile, Radius in px of corner, Source of cornerfile, border in px or empty, border color in hex, Quality in percent, Backgroundcolor in hex, Inner Radius is smaller than outer
	function styleMe($source,$destination,$corner_radius,$corner_source_path,$border,$bordercolor,$quality,$backgroundcolor,$innerradiussubtract) {
		$corner_source_resource = $this->giveMeACornerSource($corner_radius,$corner_source_path);
			
		/* Rounded Corners begin */
		if ($border > '0') { // Generate Background-Picture
			$bordercolor_array = $this->HexToRGB($bordercolor); // Border color HEX to RGB
			
			// Corner
			$corner_source = imagecreatefrompng($corner_source_resource); // PHP4 - Corner: create empty picture
			$corner_width = imagesx($corner_source); // PHP4 - Corner: Get Picture width
			$corner_height = imagesy($corner_source); // PHP4 - Corner: Get Picture height
			$corner_resized = ImageCreateTrueColor($corner_radius, $corner_radius); // PHP 4.0.6 - Corner: create empty picture
			ImageCopyResampled($corner_resized, $corner_source, 0, 0, 0, 0, $corner_radius, $corner_radius, $corner_width, $corner_height); // PHP 4.0.6 - Corner New
			$corner_width = imagesx($corner_resized); // PHP4 - Corner: Get Picture width
			$corner_height = imagesy($corner_resized); // PHP4 - Corner: Get Picture height
			$image = imagecreatetruecolor($corner_width, $corner_height); // PHP 4.0.6 - Corner: Get Picture height
			$size = getimagesize($source); // PHP4 - Size of the original Pic in an Array
			$size2[0] = $size[0] + 2 * $border;
			$size2[1] = $size[1] + 2 * $border;
			
			$image = imagecreatetruecolor($size2[0],$size2[1]) or die ('Error on creating empty picture'); // PHP 4.0.6 - create empty picture
			$black = ImageColorAllocate($image,0,0,0); // PHP4 - Get all black areas in the source picture
			
			// Top-left corner
			$dest_x = 0;  
			$dest_y = 0;  
			imagecolortransparent($corner_resized, $black); // PHP4 
			imagecopymerge($image, $corner_resized, $dest_x, $dest_y, 0, 0, $corner_width, $corner_height, 100); // PHP 4.0.1
			
			// Bottom-left corner
			$dest_x = 0;  
			$dest_y = $size2[1] - $corner_height; 
			$rotated = imagerotate($corner_resized, 90, 0); // PHP 4.3.0
			imagecolortransparent($rotated, $black); // PHP4  
			imagecopymerge($image, $rotated, $dest_x, $dest_y, 0, 0, $corner_width, $corner_height, 100); // PHP 4.0.1  
			
			// Bottom-right corner
			$dest_x = $size2[0] - $corner_width;  
			$dest_y = $size2[1] - $corner_height;  
			$rotated = imagerotate($corner_resized, 180, 0); // PHP 4.3.0
			imagecolortransparent($rotated, $black); // PHP4  
			imagecopymerge($image, $rotated, $dest_x, $dest_y, 0, 0, $corner_width, $corner_height, 100); // PHP 4.0.1  
			
			// Top-right corner
			$dest_x = $size2[0] - $corner_width;  
			$dest_y = 0;  
			$rotated = imagerotate($corner_resized, 270, 0); // PHP 4.3.0
			imagecolortransparent($rotated, $black); // PHP4  
			imagecopymerge($image, $rotated, $dest_x, $dest_y, 0, 0, $corner_width, $corner_height, 100); // PHP 4.0.1  
			
			// Corner deleting
			$backgroundcolor_array = $this->HexToRGB($backgroundcolor); // Backgroundcolor HEX to RGB
			$background = imagecolorallocate($image, $backgroundcolor_array['red'], $backgroundcolor_array['green'], $backgroundcolor_array['blue']); // search for a special color in the source picture
			
			if(substr($destination, -3) == 'jpg') { // Fill Corners only with color if JPG
				imagefill($image, 1, 1, $background); // Fill Corners with Backgroundcolor - left top
				imagefill($image, $size2[0]-1, 0, $background); // Fill Corners with Backgroundcolor - right top
				imagefill($image, $size2[0]-1, $size2[1]-1, $background); // Fill Corners with Backgroundcolor - right bottom
				imagefill($image, 0, $size2[1]-1, $background); // Fill Corners with Backgroundcolor - left bottom
			} elseif(substr($destination, -3) == 'png') { // Make Corners transparent if PNG
				$maketrans = imagecolorallocate($image,255,255,255); // Search for color to make transparent
				imagecolortransparent($image, $maketrans); // special color = transparent - only for PNG
			}
			
			$border_color = imagecolorallocate($image, $bordercolor_array['red'], $bordercolor_array['green'], $bordercolor_array['blue']); // Color nearly White 
			imagefill($image, $corner_width, $corner_height, $border_color); // Fill Picture with BorderColor
		
		}
		
		
		//////////// Picture Foreground ////////////////////
		
		
		// Corner
		if($border) {
			$corner_radius = $corner_radius - $innerradiussubtract; // inner border should be smaller
			$corner_source_resource = $this->giveMeACornerSource($corner_radius,$corner_source_path); // Get new resource file if exists
		}
		$corner_source = imagecreatefrompng($corner_source_resource); // PHP4 - Corner: create empty picture
		$corner_width = imagesx($corner_source); // PHP4 - Corner: Get Picture width
		$corner_height = imagesy($corner_source); // PHP4 - Corner: Get Picture height
		$corner_resized = ImageCreateTrueColor($corner_radius, $corner_radius); // PHP 4.0.6 - Corner: create empty picture
		ImageCopyResampled($corner_resized, $corner_source, 0, 0, 0, 0, $corner_radius, $corner_radius, $corner_width, $corner_height); // PHP 4.0.6 - Corner New
		$corner_width = imagesx($corner_resized); // PHP4 - Corner: Get Picture width
		$corner_height = imagesy($corner_resized); // PHP4 - Corner: Get Picture height
		$image2 = imagecreatetruecolor($corner_width, $corner_height); // PHP 4.0.6 - Corner: Get Picture height
		
		// Source Picture
		if(substr($destination, -3) == 'png') $image2 = imagecreatefrompng($source); // Read from PNG
		elseif(substr($destination, -3) == 'jpg') $image2 = imagecreatefromjpeg($source); // Read from JPG
		else echo $this->msgwrap('Function StyleMe: Only .jpg or .png allowed in basename: '.$image_result,1);
		$size = getimagesize($source); // PHP4
		$black = ImageColorAllocate($image2,0,0,0); // PHP4 - Get all black areas in the source picture
		if(t3lib_div::int_from_ver(phpversion()) >= t3lib_div::int_from_ver('5.0.0')) imagefilter($image2, IMG_FILTER_BRIGHTNESS, -1); //PHP5 Brightness -1 so there will be no color with #ffffff
		
		// Top-left corner
		$dest_x = 0;  
		$dest_y = 0;  
		imagecolortransparent($corner_resized, $black); // PHP4 
		imagecopymerge($image2, $corner_resized, $dest_x, $dest_y, 0, 0, $corner_width, $corner_height, 100); // PHP 4.0.1
		
		// Bottom-left corner
		$dest_x = 0;  
		$dest_y = $size[1] - $corner_height; 
		$rotated = imagerotate($corner_resized, 90, 0); // PHP 4.3.0
		imagecolortransparent($rotated, $black); // PHP4  
		imagecopymerge($image2, $rotated, $dest_x, $dest_y, 0, 0, $corner_width, $corner_height, 100); // PHP 4.0.1  
		
		// Bottom-right corner
		$dest_x = $size[0] - $corner_width;  
		$dest_y = $size[1] - $corner_height;  
		$rotated = imagerotate($corner_resized, 180, 0); // PHP 4.3.0
		imagecolortransparent($rotated, $black); // PHP4  
		imagecopymerge($image2, $rotated, $dest_x, $dest_y, 0, 0, $corner_width, $corner_height, 100); // PHP 4.0.1  
		
		// Top-right corner
		$dest_x = $size[0] - $corner_width;  
		$dest_y = 0;  
		$rotated = imagerotate($corner_resized, 270, 0); // PHP 4.3.0
		imagecolortransparent($rotated, $black); // PHP4  
		imagecopymerge($image2, $rotated, $dest_x, $dest_y, 0, 0, $corner_width, $corner_height, 100); // PHP 4.0.1  
		
		// Corner deleting
		$trans = imagecolorallocate($image2, 255, 255, 255); // search for a special color in the source picture
		imagecolortransparent($image2, $trans); // special color = transparent	
		
		if ($border > '0') { // If a border should be generated
			imagecopymerge($image, $image2, $border, $border, 0, 0, $size[0], $size[1], 100); // PHP 4.0.1 - lay picture in front of the background
		} else { // No border should be generated
			$image = $image2;
			
			// Corner deleting
			$backgroundcolor_array = $this->HexToRGB($backgroundcolor); // Backgroundcolor HEX to RGB
			$background = imagecolorallocate($image, $backgroundcolor_array['red'], $backgroundcolor_array['green'], $backgroundcolor_array['blue']); // search for a special color in the source picture
			
			if(substr($destination, -3) == 'jpg') { // Fill Corners only with color if JPG
				imagefill($image, 1, 1, $background); // Fill Corners with Backgroundcolor - left top
				imagefill($image, $size[0]-1, 0, $background); // Fill Corners with Backgroundcolor - right top
				imagefill($image, $size[0]-1, $size[1]-1, $background); // Fill Corners with Backgroundcolor - right bottom
				imagefill($image, 0, $size[1]-1, $background); // Fill Corners with Backgroundcolor - left bottom
			} elseif(substr($destination, -3) == 'png') { // Make Corners transparent if PNG
				$maketrans = imagecolorallocate($image,255,255,255); // Search for color to make transparent
				imagecolortransparent($image, $maketrans); // special color = transparent - only for PNG
			}
			
		}
		
		/* Rounded Corners end */
		
		
		// Generating Image file
		if(substr($destination, -3) == 'png') { // Basename is png
			imagepng($image,$destination); // Output final image
		} elseif(substr($destination, -3) == 'jpg') { // Basename is jpg
			ImageJPEG($image,$destination,$quality); // Output final image
		} else echo $this->msgwrap('StyleMe: Only .jpg or .png allowed in basename: '.$image_result,1);
		t3lib_div::fixPermissions($destination); // Set right file permissions
			
		
		// Delete created temporary picture
		if($border > '0') imagedestroy($image); 
		if($image2) imagedestroy($image2); 
		if($corner_source) imagedestroy($corner_source); 
		if($corner_resized) imagedestroy($corner_resized); 
		
		return $destination;
	}
	
	
	
	// Function getvalue to get the value from Flexform or Typoscript. If empty use Standard, if still empty echo ERROR
	// $ts_ff 			= 	Flexform or TypoScript Name
	// $cat				=	Flexform- or TypoScript Category
	// $standard		=	If there is no Flexform- or TypoScript Entry, use this value
	// $errormsg		=	If there is no Flexform-, TypoScript- and Standard Value, echo $errormsg;
	// $fileexist_check	=	Check if file exist, if not: echo $errormsg; (should only used if it is a file)
	function getvalue($ts_ff,$cat,$standard,$errormsg,$fileexist_check) {
		$value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $ts_ff, $cat); // Load Value from Flexform
		if (!$value) $value = $this->conf[$cat.'.'][$ts_ff]; // Use TS if Flexform is empty
		if ($value == "") $value = $standard;
		if ($value == "" AND $errormsg) echo $this->msgwrap($errormsg,1); // Errormessage if still no value
		
		$imgExtensionVariables = 'generate_basename';
		$dimensionVariables = 'width';
		$chooseVariables = 'choose';
		$colorVariables = 'roundedcorner_bordercolor,roundedcorner_backgroundcolor';
		$intVariables = 'delete_cache,allow_fe_cache_delete,list_site,show_comments,quality,watermark_alpha,showcat,single_site,rows,cols,catperpage,roundedcorner_active,roundedcorner_radius,roundedcorner_border,roundedcorner_innerradiussubtract,filter_greyscale,filter_soft,filter_bright,filter_contrast';
		$pathVariables = 'watermark_pic,pic_folder';
		
		if (t3lib_div::inList($dimensionVariables,$ts_ff)) preg_match('/^([0-9]*m*|auto)$/',$value) ? $value : $standard ; // sanitize all dimension vars
		if (t3lib_div::inList($colorVariables,$ts_ff)) preg_match('/^#[0-9a-fA-F]{6}$/',$value) ? $value : $standard ; // sanitize all color vars
		if (t3lib_div::inList($imgExtensionVariables,$ts_ff)) t3lib_div::inList('jpg,png',$value) ? $value : $standard ; // sanitize all image extension vars
		if (t3lib_div::inList($intVariables,$ts_ff)) $value = intval($value); // sanitize all int variables
		if (t3lib_div::inList($chooseVariables,$ts_ff)) t3lib_div::inList('list,single',$value) ? $value : $standard ; // sanitize all image extension vars
		
		if(t3lib_div::inList($pathVariables,$ts_ff)) { // check if given paths are valid
			if (t3lib_div::isAbsPath($value) OR !t3lib_div::validPathStr($value)) {
				echo $this->msgwrap($errormsg,1);
				$value = '';
			} else {
				$value = t3lib_div::getFileAbsFileName($value);
				// add a slash at the end if there is no slash : TODO: check for Windows installation and add backslash insdead!
				if(($ts_ff == 'pic_folder') AND (substr($value, -1, 1) != '/')) $value .= '/'; 
			}
		}
		
		if ($fileexist_check == 1 AND !file_exists($value) AND $standard == '' AND $value != "") {
			echo $this->msgwrap($errormsg,1); // Errormessage if file don't exist
			$value = '';
		}
		
		return $value;
	}
	
	// Function giveMeACornerSource searches for fitting filenames to a number (e.g.: Border = 2 so we need the file rounded_corner_02.png)
	function giveMeACornerSource($corner_width,$corner_source_path) {
		$files = t3lib_div::getFilesInDir($corner_source_path,'png',0,1); // Search for all PNG in the path order by filename
		if (count($files) > 0) { // Only if there are values
			$i=1;
			foreach ($files as $filename) { // One loop for every png
				if(strpos($filename, (string)$corner_width) > 0) { // If $corner_width is part of a filename there 
					return $corner_source_path.$filename;  // return filename with fitting size
				}
				if($i == count($files)) return $corner_source_path.$filename; // return filename of the last entry (only if there is no fitting file)
				$i++;
			}
		} 
	}
	
	// Returns MD5 Hash for a picture | example: $this->generateHash('apple.jpg','fileadmin/pic/','200','1:1','80','fileadmin/logo.jpg','80',$array1,$array2);
	function generateHash($name,$folder,$width,$ratio,$quality,$watermark,$watermark_alpha,$array_roundedcorners,$array_filters) {
		$string_filters = implode(":", $array_filters); // Generate a string from the whole array
		$string_roundedcorners = implode(":", $array_roundedcorners); // Generate a string from the whole array
		if(file_exists($folder.$name)) return md5(md5(file_get_contents($folder.$name)).':'.$width .':'.$ratio.':'.$quality.':'.$watermark.':'.$watermark_alpha.':'.$string_filters.':'.$string_roundedcorners); 
	}
	
	// Get basename of a file | test from test.php
	function getBasename($file) {
		$array = pathinfo($file);
		return basename($file,'.'.$array["extension"]);
	}
	
	// Function getAllFilesOfaFolder to get all files from x folders like this $file[0][0] = folder/picture.jpg
	function getAllFilesOfaFolder($folder) {
		$folder_array = explode(',',$folder);
		for($i=0;$i<count($folder_array);$i++) { // Join all folders
			$files = t3lib_div::getFilesInDir($folder_array[$i],'jpg,jpeg,gif,png','','1'); // Read files of current folder
			$j=0; foreach ($files as $filename) {
				$value[$i][$j] = $folder_array[$i].$filename;
				$j++;
			}
		}
		return $value;
	}
	
	// Function generateComment generate a Comment on TXT or EXIF informations
	function generateComment($comments_active,$picfolder,$filename,$template) {
		switch($comments_active) {
				// TXT standard
				case "0":
					return $this->readTextComment($picfolder,$filename,$template,0);
					break;
				// deactivate Comment
				case "1":
					return ''; // Return nothing
					break;
				// TXT
				case "2":
					return $this->readTextComment($picfolder,$filename,$template,0);
					break;
				
				// EXIF
				case "3":
					$exifData = $this->readExif($picfolder.$filename,$template);
					return $exifData;
					break;
				
				// Text/EXIF
				case "4":
					$exifData = $this->readExif($picfolder.$filename,$template);
					if ($this->readTextComment($picfolder,$filename,$template,0) == '') {
						return $exifData;
					} else {
						return $this->readTextComment($picfolder,$filename,$template,0);
					}
					break;
				
				// EXIF/Text
				case "5":
					$exifData = $this->readExif($picfolder.$filename,$template);
					if ($exifData == '') {
						return $this->readTextComment($picfolder,$filename,$template,0);
					} else {
						return $exifData;
					}
					break;
				
				default: 
					return $this->msgwrap('Wrong parameter for showing comments: "'.$comments_active.'" (Only allowed: 0, 1, 2, 3, 4, 5 - see manual for details)',1);
					break;
			}
	}
	
	// Function getfile to load file or give an ERROR
	function getfile($file,$errormsg) {
		if (!$this->cObj->fileResource($file)) echo $this->msgwrap($errormsg,1);
		else return $this->cObj->fileResource($file);
	}
	
	// Function readTextComment to find the fitting txt file
	function readTextComment($picfolder,$filename,$template,$choose) {
		$filewithoutextension = $this->FileWithoutExtension($filename);
		
		if ($choose == 0) { // choose test.txt as an description to the picture test.jpg
			$template = $this->cObj->getSubpart($template,"###TEMPLATE_COMMENT###"); // work on Subpart
			if (file_exists($picfolder.$filename)) {
				if (file_exists($picfolder.$filewithoutextension.'.txt')) {
					$array = file($picfolder.$filewithoutextension.'.txt');
					for ($i = 0; $i < count($array); $i++){
						$comment .= $array[$i] . " \n";
					}
					$teil = explode("|", $comment); // Split comment on pipe symbol
					$markerArray['###TITLE_COMMENT###'] = $teil[0];
					$markerArray['###COMMENT_TEXT###'] = $teil[1];
				}
				else { // clear Marker
					$markerArray['###TITLE_COMMENT###'] = '';
					$markerArray['###COMMENT_TEXT###'] = '';
				}
			}
			$comment = $this->cObj->substituteMarkerArrayCached($template,$markerArray,array(),$wrappedSubpartArray); // Returns HTML Template with wrapped Markers
			return $comment;
			
		} elseif ($choose == 1) { // choose test.txt as an description to the folder fileadmin/pictures/test/
			if (file_exists($picfolder.$filename.'.txt')) {
				$array = file($picfolder.$filename.'.txt');
				for ($i = 0; $i < count($array); $i++){
					$comment .= $array[$i] . " \n";
				}
			}
			return $comment;
		} else echo $this->msgwrap('Error in function readTextComment - $choose is not 0 or 1',1);
	}
	
	// Function readExif to show EXIF informations
	function readExif($image,$template) {
		if(file_exists($image)) $image_info = getimagesize($image);
		if ($image_info[2] == 2 AND t3lib_div::inArray(get_loaded_extensions(),'exif')) { // check for correct image-type and whether PHP is compiled with EXIF-support
			$template_exif = $this->cObj->getSubpart($template,"###TEMPLATE_EXIF###"); // work on Subpart
			
			//ini_set('exif.encode_unicode', 'UTF-8'); //Set encoding to Unicode, if not set in php.ini
			$exif_array = exif_read_data($image, TRUE, FALSE); // Load all EXIF informations from the original Pic in an Array
			$exif_array['Comments'] = htmlentities(str_replace("\n", "<br />", $exif_array['Comments'])); // Linebreak
			
			$markerArray['###TITLE_EXIF###'] = $exif_array['Title'];
			$markerArray['###SUBJECT_EXIF###'] = $exif_array['Subject'];
			$markerArray['###COMMENT_EXIF###'] = $exif_array['Comments'];
			$markerArray['###AUTHOR_EXIF###'] = $exif_array['Author'];
			$markerArray['###KEYWORDS_EXIF###'] = $exif_array['Keywords'];
			
			$content = $this->cObj->substituteMarkerArrayCached($template_exif,$markerArray,array(),$wrappedSubpartArray); // Returns HTML Template with wrapped Markers
			if($exif_array['Title'] OR $exif_array['Subject'] OR $exif_array['Comments'] OR $exif_array['Author'] OR $exif_array['Keywords']) return $content; // Return EXIF Informations only if there are datas
		} 
		if (!t3lib_div::inArray(get_loaded_extensions(),'exif')) { // If there is no EXIF Support at your installation
			echo $this->msgwrap('No EXIF - support on current PHP version',1);
			return FALSE;
		}
	}
	
	// Function deleteOldStuff to delete all cached files, which are to old
	function deleteOldStuff($allfolder,$now) {
		$seconds_to_delete = $this->getvalue('delete_old_cache_files','pageconfig','100','No time for deleting cache files defined',0) * 60 * 60 * 24; // How old are cache files allowed to be (days x 60 x 60 x 24 are seconds)
		
		if (!$now) { // Standardfunction - delte old files
			if ($seconds_to_delete != 'off') { // Only if not turned off by TypoScript
				$files_array = $this->getAllFilesOfaFolder($allfolder); // Get all Files from the four cache folders
				if($files_array) { // if there are files to delete
					foreach ($files_array as $folder) { // Choose array level 1 - all folders
						foreach ($folder as $file) { // Choose array level 2 - all files
							if(time() - $seconds_to_delete > filemtime($file)) { // If File is too old
								unlink($file); // Delete current file
							}
						}
					}
				}
			}
		} elseif ($now) { // Delete NOW - &tx_wtgallery_pi1[delete]=all
			if($now == 'all') { // If all files should be deleted
				$folder_array = explode(',',$allfolder);
				for($i=0;$i<count($folder_array);$i++) {
					$dp = opendir($folder_array[$i]);
					while($file = readdir($dp)) {
						$name = $folder_array[$i] . "/" . $file;
						if ($file != "." && $file != "..") {
							if (!is_dir($name)) unlink($name);
						}
					}
				}
			} elseif($now == 'cat' OR $now == 'popup' OR $now == 'single' OR $now == 'thumbs' OR $now == 'wmarked') { // Delete NOW - &tx_wtgallery_pi1[delete]= cat or popup or single or thumbs or wmarked
				$dp = opendir('uploads/tx_wtgallery/'.$now);
				while($file = readdir($dp)) {
					$name = 'uploads/tx_wtgallery/'. $now . "/" . $file;
					if ($file != "." && $file != "..") {
						if (!is_dir($name)) unlink($name);
					}
				}
			} else echo $this->msgwrap('Not allowed GET value - use all, cat, popup, single, thumbs or wmarked',1); // Error Msg if wrong parameter
		}
	}
	
	// Function FileWithoutExtension to get file for file.jpg or test for test.jpeg
	function FileWithoutExtension($file) {
		$file_array = pathinfo($file); // Get infos of the file
		$extension_length = strlen($file_array["extension"])+1;
		$filewithoutextension = substr($file, 0, -$extension_length); 
		return $filewithoutextension;
	}
 	
	// Function sanitizeGetVar to clean user input
	function sanitizeGetVar($var,$type) {
		switch ($type) {
			case 'path': // disallow directory traversal
				$var = t3lib_div::validPathStr($var) ? $var : '';
				break;
			default: // 
				echo $this->msgwrap('error sanitizing user input',1); // Error
				break;
		}
		return $var;
	}
	
	// Function OrderFolders to order category folders as wanted
	function OrderFolders($picfolder,$folders,$order) {
		if($order=='random') shuffle($folders);
		elseif($order=='alphabeticalASC') asort($folders);
		elseif($order=='alphabeticalDESC') arsort($folders);
		elseif($order=='newest' OR $order=='oldest') {
			if (file_exists($picfolder.$value)) {
				foreach ($folders as $value) $newarray[$value] = filemtime($picfolder.$value);
				if($order=='newest') arsort($newarray); elseif($order=='oldest') asort($newarray);
				$i=0; $folders = '';
				foreach ($newarray as $value => $age) {
					$folders[$i] = $value;
					$i++;
				}
			}
		}
		else echo $this->msgwrap('Wrong parameter for category sorting (allowed: random, alphabeticalASC, alphabeticalDESC, newest, oldest',1); // Errormessage if file don't exist
		return $folders;
	}
	
	// Function stepbystep to go to the next or last picture in the singleview (Marker ###FWD### and ###BACK###)
	function stepbystep($current_file,$all_files) {
		if (count($all_files) > 0) { // Foreach only if there are files
			$i=0; // counter is 0
			foreach ($all_files as $filename) { // one loop for every file
				$filename_array[$i] = $filename; // Every file is written to an array
				if($current_file == $filename) $current = $i; // Current id
				$i++; // increase counter
			}
			$content['current'] = $filename_array[$current]; // write current file to array
			$content['back'] = $filename_array[$current-1]; // write file before current file to array
			$content['fwd'] = $filename_array[$current+1]; // write file after current file to array
		}
		return $content;
	}
	
	// Function HexToRGB to change #ffffff to 255 255 255
	function HexToRGB($hexcolor) {
		if (substr($hexcolor, 0, 1) != '#') $hexcolor = '#'.$hexcolor; // add # at the front if needed
		$hexcolor_array['red'] = hexdec(substr($hexcolor,1,2));
		$hexcolor_array['green'] = hexdec(substr($hexcolor,3,2));
		$hexcolor_array['blue'] = hexdec(substr($hexcolor,5,2));
		if($hexcolor_array['red'] == 255 AND $hexcolor_array['green'] == 255 AND $hexcolor_array['blue'] == 255) $hexcolor_array['red'] = 254; // Don't allow #ffffff
		
		//print_r($hexcolor_array);
		return $hexcolor_array;
	}
	
	// Function LLtoMarker use the name of the Marker to fill it with the same variable from locallang.xml (###TEST### with test)
	function LLtoMarker($list,$add) {
		$array = explode(",", $list); // Explode list
		for($i=0;$i<count($array);$i++) { // One loop for every Marker
			if($this->pi_getLL($array[$i])) { 
				$markerArray['###'.strtoupper(strtolower($array[$i])).'###'] = $this->pi_getLL($array[$i]); // If LL variable, fill marker
				if($add[$array[$i]]) { // If there is an addition behind the marker
					$markerArray['###'.strtoupper(strtolower($array[$i])).'###'] .= $add[$array[$i]]; // add addition behind
				}
			}
			else $markerArray['###'.strtoupper(strtolower($array[$i])).'###'] = ''; // else clear marker
		}
		return $markerArray;
	}
	
	// Function foldercheck checks if there are all needed folders
	function foldercheck($folders) {
		$folderarray = explode(',',$folders);
		for($i=0;$i<count($folderarray);$i++) { // One loop for eache cache folder
			if(!file_exists($folderarray[$i])) $error = 1; // folder does not exist
		}
		if($error == 1) { // Error happens - Folder does not exist
			echo $this->msgwrap('One or more cache folders are not existing, use: TYPO3 Backend / Ext Manager / WT Gallery / Make updates',1);
			return FALSE;
		}
		else return TRUE;
	}
	
	// Function msgwrap to wrap messages (like errormessages): $kind == 0 means positive message / $kind == 1 means error message
	function msgwrap($error,$kind) {
		if($kind == 1) {
			return '<div style="background-color: #A71B42; border: 1px solid black; padding: 5px; color: white; font-weight: bold; -moz-border-radius: 6px;">'.strtoupper($this->extKey).' ERROR (tt_content uid '.$this->cObj->data['uid'].'): '.$error.'!</div>';
		} elseif ($kind == 0) {
			return '<div style="background-color: green; border: 1px solid black; padding: 5px; color: white; font-weight: bold; -moz-border-radius: 6px;">'.strtoupper($this->extKey).' Usermessage: '.$error.'!</div>';		
		}
	}
}
	
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_gallery/pi1/class.tx_wtgallery_pi1.php']);
}

?>
