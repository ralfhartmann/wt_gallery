<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "wt_gallery".
 *
 * Auto generated 30-09-2013 14:29
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'WT Gallery',
	'description' => 'Easy gallery with separate single- and thumbnailview and with subgallery mode. A crop function enables styled images (e.g. quadratic thumbnails). Rounded Corners and filters like greyscale available. Wallpaper- and Imagelightbox function added.',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '2.6.3',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => 'uploads/tx_wtgallery/single, uploads/tx_wtgallery/thumbs, uploads/tx_wtgallery/wmarked, uploads/tx_wtgallery/popup, uploads/tx_wtgallery/cat',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Alexander Kellner (wunschtacho)',
	'author_email' => 'alex@wunschtacho.de',
	'author_company' => '',
	'CGLcompliance' => NULL,
	'CGLcompliance_note' => NULL,
	'constraints' => 
	array (
		'depends' => 
		array (
			'php' => '4.2.0-0.0.0',
			'typo3' => '3.5.0-0.0.0',
		),
		'conflicts' => 
		array (
		),
		'suggests' => 
		array (
		),
	),
);

?>