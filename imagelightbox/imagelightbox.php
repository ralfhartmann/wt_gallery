<?
$numberDisplayLabel = explode('|',$IMLconf['numberDisplayLabel']); // Label explode on pipe symbol

// Header code for imagelightbox
$imlightbox = '
	<!-- imagelightbox code for wt_gallery start -->

			<style type="text/css">
				/*<![CDATA[*/
					#overlay { background-color: '.$IMLconf['bgColor'].';	}
					#imageData #bottomNavClose { '.$IMLconf['styleCloseButton'].' }
					.csc-textpic-caption { '.$IMLconf['cscCaption'].' }	
					#prevLink, #nextLink { background: transparent url(../images/blank.gif) no-repeat; }	
					#prevLink:hover, #prevLink:visited:hover { background: url('.$IMLconf['prevLinkImage'].') left 15% no-repeat; }
					#nextLink:hover, #nextLink:visited:hover { background: url('.$IMLconf['nextLinkImage'].') right 15% no-repeat; }	
					.presentationmodeAct a:link { '.$IMLconf['presModeStyleActNumber'].' }								
				/*]]>*/
			</style>
			
			<script type="text/javascript">
				/*<![CDATA[*/
					var resizeSpeed = '.$IMLconf['resizeSpeed'].';
					var fileLoadingImage = "'.$IMLconf['fileLoadingImage'].'";		
					var fileBottomNavCloseImage = "'.$IMLconf['fileBottomNavCloseImage'].'";
					var numberDisplayLabelFirst = "'.$numberDisplayLabel[0].'";
					var numberDisplayLabelLast = "'.$numberDisplayLabel[1].'";
				/*]]>*/
			</script>
		
		<link rel="stylesheet" href="'.t3lib_extMgm::siteRelPath('kj_imagelightbox2').'lightbox/css/lightbox.css" type="text/css" media="screen" />
		<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath('kj_imagelightbox2').'lightbox/js/prototype.js"></script>
		<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath('kj_imagelightbox2').'lightbox/js/scriptaculous.js?load=effects"></script>
		<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath('kj_imagelightbox2').'lightbox/js/lightbox.js"></script>
	<!-- imagelightbox code for wt_gallery end -->
';
/*		
	<script type="text/javascript" src="typo3temp/javascript_757c080409.js"></script>
*/
?>