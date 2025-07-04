<?php
error_reporting(0);

// If not being called from the shell, is not GR Product, and the REQUEST['override'] isn't set,
// tell the user to copy the link and paste into GR. This prevents unwanted excessive downloads.
if (!isGRProduct() && !isset($_REQUEST['override'])) {
	die('This product is intended to be viewed in <a href="http://www.grlevelx.com/">Gibson Ridge Software</a> '
		.'(GR2Analyst or GRLevel3) or <a href="https://supercellwx.net">SupercellWX</a>. Please copy the link '
        .'and paste into your Product\'s PlaceFile Manager:'
		.'<br /><br /><a href="' . getFullURL() . '">' . getFullURL() .'</a>.');
}



function generatePlacefileHeaders($data) {
	$hdr = ['Refresh','Threshold','Title','IconFile','Font'];
	foreach($hdr as $h) {
		if (is_array($data[$h])) {
			for ($i=0; $i<count($data[$h]); $i++) {
				$row = $h . ": " . ($i+1) . ",";
				for ($x=0; $x<count($data[$h][$i]); $x++) {
					if (!is_numeric($data[$h][$i][$x])) {
						$row .= '"' . $data[$h][$i][$x] .'",';
					} else {
						$row .= $data[$h][$i][$x] .',';
					}
				}
				$text .= substr($row,0,-1) . "\n";
			}
			//foreach($data[$h] as $row) {
			//	$text .= $h . ": \n";
			//}
		} else {
			$text .= $h . ": " . $data[$h] . "\n";
		}
	}
	return $text . "\n";
}


function log_access() {
    // If being run in the shell, presume it's a test and bypass this check
    if (php_sapi_name() == 'cli') {
        return;
    }
    $lat = (empty($_REQUEST['lat']) ? "null" : "'{$_REQUEST['lat']}'");
	$lon = (empty($_REQUEST['lon']) ? "null" : "'{$_REQUEST['lon']}'");
	$ver = (empty($_SERVER['HTTP_USER_AGENT']) ? "null" : "'{$_SERVER['HTTP_USER_AGENT']}'");
	$ip = (empty($_SERVER['REMOTE_ADDR']) ? "null" : "'{$_SERVER['REMOTE_ADDR']}'");
	$placefile = "'" . basename($_SERVER['SCRIPT_FILENAME']) . "'";
	$opts = get_pf_options();
	$opts = (empty($opts) ? "null" : "'{$opts}'");
	
	$sql = "insert into access_log values (now(), {$ip}, {$placefile}, {$opts}, {$lat}, {$lon}, {$ver})";
	$dbh->query($sql);
}

function get_pf_options() {
	$ignore = ['lat','lon','version','dpi'];
	foreach($_REQUEST as $key=>$val) {
		if (!in_array($key, $ignore)) {
			$ret .= $key . '=' . $val . '&';
		}
	}
	if (!empty($ret)) {
		return substr($ret, 0, -1);
	} else {
		return null;
	}
}

function get_pf_header() {
	$ret = "; ************************************************\n"
		. "; (c) 2025. Russ Kollmansberger (KC9YZQ)\n"
		. "; All rights reserved. Use of this data is granted only\n"
		. "; for the use with Gibson Ridge (GRLevel3 and GR2Analyst).\n"
		. "; Accuracy of the information within this placefile is not\n"
		. "; guaranteed in any way. Every effort has been made to\n"
		. "; ensure the accuracy of the data, however, numerous\n"
		. "; factors are involved and are uncontrollable.\n"
		. "; ************************************************\n\n";
	return $ret;
}

/*
 * Check if the requesting software is reporting either GRLevelX or SuperCellWX
 */
function isGRProduct() {
    // If being run in the shell, presume it's a test and bypass this check
    if (php_sapi_name() == 'cli') {
        return true;
    }
	$grProducts = ['grlevel3', 'gr2analyst', 'supercellwx'];
	foreach ($grProducts as $grProduct) {
		if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), $grProduct) !== false) {
			return true;
		}
	}
	return false;
}

function getFullURL() {
	// Program to display URL of current page. 
	
    $link = "http" . (isset($_SERVER['HTTPS']) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    return $link;

	// if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
	// 	$link = "https"; 
	// else
	// 	$link = "http"; 
	  
	// // Here append the common URL characters. 
	// $link .= "://"; 
	  
	// // Append the host(domain name, ip) to the URL. 
	// $link .= $_SERVER['HTTP_HOST']; 
	  
	// // Append the requested resource location to the URL 
	// $link .= $_SERVER['REQUEST_URI']; 
		  
	// // Print the link 
	// return $link; 
}

/*
 * For debugging, display this placefile with some styling to help identify errors
 */
function displayAsPrettyPage($text) {
	
	$text = preg_replace("/\"(.*)\"/m", '<span class="pfString">$0</span>', $text);
	$text = preg_replace("/;(.*)$/m", '<span class="pfComment">$0</span>', $text);
	$text = preg_replace("/^(\\S*):/m", '<span class="pfCommand">$0</span>', $text);
	$text = str_replace("\n", "<br />", $text);
	?>
	
	<html>
	<head><title>Review Placefile Product</title>
	<style>
	.pfString {
		color:green;
	}
	.pfComment {
		color:gray;
	}
	.pfCommand {
		color:blue;
	}
	</style>
	</head>
	<body>
	<div style="font-family:courier new;font-size:10pt;"><?php echo $text; ?></div>
	</body>
	</html>
	<?php
}

?>