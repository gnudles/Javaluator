<?php
class Canvas {
	private $canvasRelPath;
	private $isColorBoxSet = false;
	
	private static function startsWith($haystack, $needle){
		return !strncmp($haystack, $needle, strlen($needle));
	}
	
	function Canvas() {
		$documentRoot = substr($_SERVER["SCRIPT_FILENAME"],0,strlen($_SERVER["SCRIPT_FILENAME"])-strlen($_SERVER["PHP_SELF"]));
		$canvasRoot = $documentRoot.substr(realpath(__DIR__),strlen(realpath($documentRoot)));
		$this->canvasRelPath = substr($canvasRoot, strlen($documentRoot));
		// Retrieve language code and relative url (relative to language - fr/toto -> toto)
		$dummy = explode("/",trim($_SERVER['REQUEST_URI'],"/"),2);
		$this->languageCode = $dummy[0];
		$this->relUrl = explode("?",$dummy[1])[0];
		// Parse the menu hierarchy
		$arr = array();
		$fic = fopen($documentRoot."/".$this->languageCode."/menu.csv", 'r');
		if (!$fic) {
			// unable to find the menu file
		} else {
			while (!feof($fic)) {
				$ligne = fgetcsv($fic, 0, "\t");
				$url = $ligne[0];
				$menu = $ligne[1];
				$title = $ligne[2];
				$arr[$menu] = $url;
				if ($url === $this->relUrl) {
					$this->title = $title;
					$this->menuPath = $menu;
				}
			}
			fclose($fic);
		}
		$this->menuHierarchy = $this->explodeTree($arr);
		$json = file_get_contents($documentRoot."/".$this->languageCode."/siteLocalization.json");
		$this->siteCustomTranslations = json_decode($json, false);
		$json = file_get_contents($canvasRoot."/translations/canvas_".$this->languageCode.".json");
		$this->translations = json_decode($json, false);
	}
	
	function setContentWidth($width) {
		$this->contentWidth = $width;
	}
	
	function setColorBox($boolean) {
		$this->isColorBoxSet = $boolean;
	}
	
	/**
	 * Explode any single-dimensional array into a full blown tree structure,
	 * based on the delimiters found in it's keys.
	 *
	 * @author  Kevin van Zonneveld <kevin@vanzonneveld.net>
	 * @author  Lachlan Donald
	 * @author  Takkie
	 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
	 * @version   SVN: Release: $Id: explodeTree.inc.php 89 2008-09-05 20:52:48Z kevin $
	 * @link    http://kevin.vanzonneveld.net/
	 *
	 * @param array   $array
	 * @param string  $delimiter
	 *
	 * @return array
	 */
	private function explodeTree($array, $delimiter = '/') {
	  if(!is_array($array)) return false;
	  $splitRE   = '/' . preg_quote($delimiter, '/') . '/';
	  $returnArr = array();
	  foreach ($array as $key => $val) {
		// Get parent parts and the current leaf
		$parts  = preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
		$leafPart = array_pop($parts);
	 
		// Build parent structure
		// Might be slow for really deep and large structures
		$parentArr = &$returnArr;
		foreach ($parts as $part) {
		  if (!isset($parentArr[$part])) {
			$parentArr[$part] = array();
		  } elseif (!is_array($parentArr[$part])) {
			$parentArr[$part] = array();
		  }
		  $parentArr = &$parentArr[$part];
		}
	 
		// Add the final part to the structure
		if (empty($parentArr[$leafPart])) {
		  $parentArr[$leafPart] = $val;
		}
	  }
	  return $returnArr;
	}

	private function plot($root, $arr, $delimiter='/', $mother_run=true) {
		if($mother_run){
			// the beginning of plotTree. We're at rootlevel
			echo "<ul class=\"sf-menu\">";
		} else {
			echo "<ul>";
		}
		foreach($arr as $k=>$v){
			// determine the destination url of this node.
			if (is_array($v)) {
				$url = "#";
			} else if (Canvas::startsWith($v,"http://") || Canvas::startsWith($v,"https://")) {
				$url = $v;
			} else {
				$url = "//".$_SERVER['SERVER_NAME']."/".$this->languageCode."/".$v ;
			}
			$cur = strpos($this->menuPath, $root.$k);
			// show the actual node
			echo "<li>";
			if (strlen($url)!=0) {
				echo "<a ";
				if (!(strpos($this->menuPath, $root.$k)===FALSE)) {
					echo "class=\"current\" ";
				}
				echo "href=\"".$url."\">";
			}
			echo $k;
			if (strlen($url)!=0) echo "</a>";
	 
			if(is_array($v)){
				// this is what makes it recursive, rerun for childs
				$this->plot($root.$k.$delimiter, $v, $delimiter, false);
			}
			echo "</li>\n";
		}
		echo "</ul>";
	}
	
	function openHead() {
		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"//www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		echo "<html xmlns=\"//www.w3.org/1999/xhtml\" xml:lang=\"".$this->languageCode."\" lang=\"".$this->languageCode."\">\n";
		echo "<head>\n";
		echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\"/>\n";
		echo "<meta name=\"keywords\" content=\"".$this->siteCustomTranslations->keyWords."\"/>\n";
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$this->canvasRelPath."/site.css.php";
		if (isset($this->contentWidth)) {
			echo "?contentWidth=$this->contentWidth";
		}
		echo "\"/>\n";
		$jqueryRoot=$this->canvasRelPath."/jquery/"; ?>
		<link rel="stylesheet" type="text/css" href="<?php echo $jqueryRoot;?>css/superfish.css" media="screen"/> 
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
		
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery.hoverintent/1.8.1/jquery.hoverIntent.min.js"></script> 
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/superfish/1.7.7/js/superfish.min.js"></script>
		
		<?php if ($this->isColorBoxSet) {?>
		<link rel="stylesheet" type="text/css" href="<?php echo $jqueryRoot;?>css/colorbox.css" media="screen"/> 
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery.colorbox/1.6.3/jquery.colorbox-min.js"></script>
		<?php }?>
		
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('ul.sf-menu').superfish();
			});
		</script>
		<?php
		echo "<meta name=\"description\" content=\"".$this->description."\"/>\n";
		echo "<title>".$this->title."</title>\n";
		echo "<script type=\"text/javascript\" src=\"https://apis.google.com/js/plusone.js\">";
		echo "{lang: '".$this->languageCode."'}";
		echo "</script>";
		echo "<script type=\"text/javascript\" src=\"".$this->canvasRelPath."/goup.js\"></script>\n";
		// Begin Cookie Consent plugin by Silktide - http://silktide.com/cookieconsent
		echo "<script type=\"text/javascript\">\n";
		$cv = $this->translations->cookieValidation;
		echo "window.cookieconsent_options = {\"message\":\"".$cv->message."\",\"dismiss\":\"".$cv->ok."\",\"learnMore\":\"".$cv->more."\",\"link\":\"https://www.google.com/policies/privacy/partners/\",\"theme\":\"dark-top\"};";
		echo "\n</script>\n";
		echo "<script type=\"text/javascript\" src=\"//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/1.0.9/cookieconsent.min.js\"></script>";
		// End Cookie Consent plugin
	}
	
	function closeHeadOpenContent() {
		echo "</head>\n";
		echo "<body>\n";
?>
		<div id="fb-root"></div>
		<script type="text/javascript">
		(function(d, s, id) {
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) {return;}
			js = d.createElement(s); js.id = id;
			js.src = "//connect.facebook.net/<?php echo $this->translations->fullLocale;?>/all.js#xfbml=1";
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));
			</script>
<?php		
		echo "<div id=\"page\"><div id=\"top\">";
		echo "<div id=\"flattr\"><script id='fb8le2w'>(function(i){var f,s=document.getElementById(i);f=document.createElement('iframe');f.src='//api.flattr.com/button/view/?uid=Fathzer&url='+encodeURIComponent(document.URL);f.title='Flattr';f.height=62;f.width=55;f.style.borderWidth=0;s.parentNode.insertBefore(f,s);})('fb8le2w');</script></div>";
		echo "<a href=\"//".$_SERVER['SERVER_NAME']."\"> <img src=\"".$this->canvasRelPath."/title.png\" alt=\"Home\" /></a>";
		echo "</div><!--end of top-->\n";
		echo "<div id=\"menuBar\">&nbsp;";
		// Generate the menu
		$this->plot("", $this->menuHierarchy);
		// Generate the language icons
		$languages = array("en");
		echo "<div id=\"localizedLink\">";
		foreach ($languages as &$lng) {
			if ($lng !== $this->languageCode) {
				echo "<a href=\"//".$_SERVER['SERVER_NAME']."/".$lng."/home\">";
				echo "<img alt=\"".$lng."\" src=\"".$this->canvasRelPath."/flags/".$lng.".png\"/>";
				echo "</a>";
			}
		}
		echo "</div>\n";
		echo "<div style=\"padding: 0px; margin:0px; clear:both;\"></div>";
		echo "\n</div><!--end of menuBar-->\n";
		echo "<div id=\"container\">\n";
		echo "<div id=\"content\">\n";
	}
	
	function generateHeader() {
		$this->openHead();
		$this->closeHeadOpenContent();
	}
	
	function generateFooter() {
		echo "</div><!--end of content-->\n";
		echo "<div id=\"advertising\">\n";
		$this->generateAdverstising();
		echo "</div><!--end of advertising-->\n";
		echo "<div style=\"clear:both;\"></div>\n";
		echo "</div><!--end of container-->\n";
		echo "<div id=\"footer\">\n";
		echo '<div id="ILike"><g:plusone href="http://javaluator.sourceforge.net\" size="medium"></g:plusone></div>'."\n";
		echo "<div class=\"fb-like\" data-href=\"http://javaluator.sourceforge.net\" data-send=\"false\" data-width=\"450\" data-show-faces=\"true\"></div>";
		echo "<div class=\"hosted\">\n";
		echo $this->hostedBy." <a class=\"SFLogo\" href=\"http://sourceforge.net/\"><img src=\"http://sflogo.sourceforge.net/sflogo.php?group_id=276272&amp;type=10\" alt=\"SourceForge.net\"/></a>\n";
		echo "</div>\n";
		echo "</div><!--end of footer-->\n";
		echo "</div><!--end of page-->\n";
		echo '<p id="back-top"><a href="#"><span></span>'.$this->translations->goupWording."</a></p>\n";
		echo "<script type=\"text/javascript\" src=\"//astesana.net/banner/charlie.js\"></script>\n";
		$this->generateGoogleAnalytics();
		echo "</body>\n";
		echo "</html>";
	}
	
	private function generateAdverstising() {
		echo "<span class=\"pubTitle\">".$this->translations->pubAreaTitle."</span> \n";
		echo '<div id="adContainer">';
		echo "<script type=\"text/javascript\"><!--\n";
		echo "google_ad_client = \"pub-4534386577866276\";\n";
		echo "google_ad_slot = \"2668678279\";\n";
		echo "google_ad_width = 160;\n";
		echo "google_ad_height = 600;\n";
		echo "//-->\n";
		echo "</script>\n";
		echo "<script type=\"text/javascript\" src=\"//pagead2.googlesyndication.com/pagead/show_ads.js\"></script>\n";
		echo "<script type=\"text/javascript\" src=\"".$this->canvasRelPath."/testAdBlocked.js\"></script>\n";
		echo '<script>testAdBlocked("'.$this->translations->adBlockMessage.'", \''.$this->getDonateButton()."');</script>";
		echo "</div>\n";
	}
	
	function getDonateButton() {
		if (isset($this->translations->paypalAlt)) {
			$button = "<input type=\"image\" src=\"https://www.paypalobjects.com/".$this->translations->fullLocale."/i/btn/btn_donate_SM.gif\" border=\"0\" name=\"submit\" alt=\"".$this->translations->paypalAlt."\">";
			return '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">'.
					'<input type="hidden" name="cmd" value="_s-xclick">'.
					'<input type="hidden" name="hosted_button_id" value="'.'SA5ASSD6NJYLW'.'">'.
					$button.'</form>';
		} else {
			return "";
		}
	}
	
	private function generateGoogleAnalytics() {
		echo "<script type=\"text/javascript\">\n";
		echo "var gaJsHost = ((\"https:\" == document.location.protocol) ? \"https://ssl.\" : \"http://www.\");\n";
		echo "document.write(unescape(\"%3Cscript src='\" + gaJsHost + \"google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E\"));\n";
		echo "</script>\n";
		echo "<script type=\"text/javascript\">\n";
		echo "try {\n";
		echo "var pageTracker = _gat._getTracker(\"".'UA-1810735-4'."\");\n";
		echo "pageTracker._trackPageview();\n";
		echo "} catch(err) {}</script>\n";
	}
}
?>
