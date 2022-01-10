<?php

session_start();

error_reporting( error_reporting() & ~E_NOTICE );

$allow_delete = true; 
$allow_upload = true; 
$allow_create_folder = true; 
$allow_direct_link = true; 
$allow_show_folders = true; 

$disallowed_extensions = ['php'];  
$hidden_extensions = ['php']; 

setlocale(LC_ALL,'en_US.UTF-8');

$tmp_dir = dirname($_SERVER['SCRIPT_FILENAME']);
if(DIRECTORY_SEPARATOR==='\\') $tmp_dir = str_replace('/',DIRECTORY_SEPARATOR,$tmp_dir);
$tmp = get_absolute_path($tmp_dir . '/' .$_REQUEST['file']);

if($tmp === false)
	err(404,'File or Directory Not Found');
if(substr($tmp, 0,strlen($tmp_dir)) !== $tmp_dir)
	err(403,"Forbidden");
if(strpos($_REQUEST['file'], DIRECTORY_SEPARATOR) === 0)
	err(403,"Forbidden");


if(!$_COOKIE['_sfm_xsrf'])
	setcookie('_sfm_xsrf',bin2hex(openssl_random_pseudo_bytes(16)));
if($_POST) {
	if($_COOKIE['_sfm_xsrf'] !== $_POST['xsrf'] || !$_POST['xsrf'])
		err(403,"XSRF Failure");
}

$file = $_REQUEST['file'] ?: '.';
if($_GET['do'] == 'list') {
	if (is_dir($file)) {
		$directory = $file;
		$result = [];
		$files = array_diff(scandir($directory), ['.','..']);
		foreach ($files as $entry) 
		
	if (!is_entry_ignored($entry, $allow_show_folders, $hidden_extensions)) {
		$i = $directory . '/' . $entry;
		$stat = stat($i);
	        $result[] = [
				'mtime' => $stat['mtime'],
	        	'size' => $stat['size'],
	        	'name' => basename($i),
	        	'path' => preg_replace('@^\./@', '', $i),
	        	'is_dir' => is_dir($i),
	        	'is_deleteable' => $allow_delete && ((!is_dir($i) && is_writable($directory)) || (is_dir($i) && is_writable($directory) && is_recursively_deleteable($i))),
	        	'is_readable' => is_readable($i),
	        	'is_writable' => is_writable($i),
	        	'is_executable' => is_executable($i),
	        ];
	    }
	} else {
		err(412,"Not a Directory");
	}
	echo json_encode(['success' => true, 'is_writable' => is_writable($file), 'results' =>$result]);
	exit;
} elseif ($_POST['do'] == 'delete') {
	if($allow_delete) {
		rmrf($file);
	}
	exit;
} elseif ($_POST['do'] == 'mkdir' && $allow_create_folder) {
	// don't allow actions outside root. we also filter out slashes to catch args like './../outside'
	$dir = $_POST['name'];
	$dir = str_replace('/', '', $dir);
	if(substr($dir, 0, 2) === '..')
	    exit;
	chdir($file);
	@mkdir($_POST['name']);
	exit;
} elseif ($_POST['do'] == 'upload' && $allow_upload) {
	foreach($disallowed_extensions as $ext)
		if(preg_match(sprintf('/\.%s$/',preg_quote($ext)), $_FILES['file_data']['name']))
			err(403,"Files of this type are not allowed.");

	$res = move_uploaded_file($_FILES['file_data']['tmp_name'], $file.'/'.$_FILES['file_data']['name']);
	exit;
} elseif ($_GET['do'] == 'download') {
	$filename = basename($file);
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	header('Content-Type: ' . finfo_file($finfo, $file));
	header('Content-Length: '. filesize($file));
	header(sprintf('Content-Disposition: attachment; filename=%s',
		strpos('MSIE',$_SERVER['HTTP_REFERER']) ? rawurlencode($filename) : "\"$filename\"" ));
	ob_flush();
	readfile($file);
	exit;
}

function is_entry_ignored($entry, $allow_show_folders, $hidden_extensions) {
	if ($entry === basename(__FILE__)) {
		return true;
	}

	if (is_dir($entry) && !$allow_show_folders) {
		return true;
	}

	$ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
	if (in_array($ext, $hidden_extensions)) {
		return true;
	}

	return false;
}

function rmrf($dir) {
	if(is_dir($dir)) {
		$files = array_diff(scandir($dir), ['.','..']);
		foreach ($files as $file)
			rmrf("$dir/$file");
		rmdir($dir);
	} else {
		unlink($dir);
	}
}
function is_recursively_deleteable($d) {
	$stack = [$d];
	while($dir = array_pop($stack)) {
		if(!is_readable($dir) || !is_writable($dir))
			return false;
		$files = array_diff(scandir($dir), ['.','..']);
		foreach($files as $file) if(is_dir($file)) {
			$stack[] = "$dir/$file";
		}
	}
	return true;
}

// from: http://php.net/manual/en/function.realpath.php#84012
function get_absolute_path($path) {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $absolutes = [];
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return implode(DIRECTORY_SEPARATOR, $absolutes);
    }

function err($code,$msg) {
	http_response_code($code);
	echo json_encode(['error' => ['code'=>intval($code), 'msg' => $msg]]);
	exit;
}

function asBytes($ini_v) {
	$ini_v = trim($ini_v);
	$s = ['g'=> 1<<30, 'm' => 1<<20, 'k' => 1<<10];
	return intval($ini_v) * ($s[strtolower(substr($ini_v,-1))] ?: 1);
}
$MAX_UPLOAD_SIZE = min(asBytes(ini_get('post_max_size')), asBytes(ini_get('upload_max_filesize')));
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

 <title>VIRTUAL OFFICE</title>

      <script src = "https://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.js"></script> 
      <script src="http://cdnjs.cloudflare.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
      <script src="http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.0.0/moment.min.js"></script>
      
      <!--===============================================================================================-->
      
	  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
      <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	  <link rel="icon" href="images/flder.png">

	  <!--===============================================================================================-->
	  
    <style>
	h1  {
		font-family: Cooper Black;
		text-shadow: 2px 2px #E2F516;
		font-size: 29px;
		}

	h2 {
		font-family: Cooper Black;
		text-shadow: 2px 2px #E2F516;
		font-size: 29px;
		}

	body {
		font-family: 'Bahnschrift SemiBold';
		font-size: 14px;
		width:1024;
		height: auto;
		margin:0;
		background-color: lightblue;
		}

	p{
		text-align: center;  
		color: black;   
		font-family: 'Bahnschrift SemiBold';
		font-size: 15px; 
		}

	.file{
		padding-left:0.2em;
		padding-right:0.2em;
	}

	/*--------------HEADER PAGE---------------*/
	.header {
		padding: 10px 16px;
		background: #0C090A;
		height: 110px;
		}

	.content {
		padding: 16px;
		}

	.sticky {
		position: fixed;
		top: 0;
		width: 100%;
		}

	.sticky + .content {
		padding-top: 102px;
		}

	/*----------------------------------*/

	th {
		font-weight: normal; 
		color: whitesmoke; 
		background-color: #0C090A; 
		padding:.5em 1em .5em .2em;
		text-align: left;
		cursor:pointer;
		user-select: none;
	}

	th .indicator {
		margin-left: 6px 
	}

	thead {
		border-top: 1px solid #82CFFA; 
		border-bottom: 1px solid #96C4EA;
		border-left: 1px solid #E7F2FB;
		border-right: 1px solid #E7F2FB; 
		}

	#top {
		height:52px;
		}

	#mkdir {
		display:inline-block;
		float: right;
		padding-top:16px;
		margin-right: 15px;
		}

	label { 
		display:block; 
		font-size:11px; 
		color:#0f1620;
		} 

	#file_drop_target {
		width:500px; 
		padding:12px 0; 
		border: 4px dashed #777;
		font-size:12px;
		color:#0f1620;
		text-align:center;
		float:right;
		margin-right:20px;
		}

	#file_drop_target.drag_over {
		border: 4px dashed #96C4EA; 
		color: #96C4EA;
		}

	#upload_progress {
		padding: 4px 0;
		}
		
	#upload_progress .error {
		color:#a00;
		}

	#upload_progress > div { 
		padding:3px 0;
		}

	.no_write #mkdir, .no_write #file_drop_target {
		display:none;
		}

	.progress_track {
		display:inline-block;
		width:200px;
		height:10px;
		border:1px solid #333;
		margin: 0 4px 0 10px;
		}

	.progress {
		background-color:#82CFFA;
		height:10px; 
		}

	footer {
		font-size:11px; 
		color:#000; 
		padding:4em 0 0;
		text-align: left;
		}
		
	#breadcrumb { 
		padding-top:34px; 
		font-size:15px; 
		color:#aaa;
		display:inline-block;
		float:left;
		}

	#folder_actions {
		width: 50%;
		float:right;
		}

	a, a:visited { 
		color:#00c; 
		text-decoration: none;
		}

	a:hover {
		text-decoration: underline;
		}

	.sort_hide{ 
		display:none;
		}

	table {
		border-collapse: collapse;
		width:100%;
		}

	thead {
		max-width: 1024px;
		}

	td { 
		padding:.2em 1em .2em .2em; 
		border-bottom:1px solid #def;
		height:30px; 
		font-size:12px;
		white-space: nowrap;
		border-bottom: 1px solid #272e38;
		}

	td.first {
		font-size:14px;
		white-space: normal;
		}

	td.empty { 
		color:#777; 
		font-style: italic; 
		text-align: center;
		padding:3em 0;
		}

	.is_dir .size {
		color:transparent;
		font-size:0;
		}

	.is_dir .size:before {
		content: "--"; 
		font-size:14px;
		color:#333;
		}

	.is_dir .download{
		visibility: hidden;
		}

	#dirname{  
		width: 200px;  
		height: 30px;  
		border: none;  
		border-radius: 3px;  
		padding-left: 3px;  
		}

	#create{  
		width: 100px;  
		height: 35px;  
		border: none;  
		border-radius: 7px;  
		padding-left: 8px;  
		color: white; 
		background: #f44336;
		font-family: 'Bahnschrift SemiBold';
		} 

	#create:hover{  
		background: #A52A2A; 
		color: white; 
		}

	a.delete {
		display:inline-block;
		background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAADtSURBVHjajFC7DkFREJy9iXg0t+EHRKJDJSqRuIVaJT7AF+jR+xuNRiJyS8WlRaHWeOU+kBy7eyKhs8lkJrOzZ3OWzMAD15gxYhB+yzAm0ndez+eYMYLngdkIf2vpSYbCfsNkOx07n8kgWa1UpptNII5VR/M56Nyt6Qq33bbhQsHy6aR0WSyEyEmiCG6vR2ffB65X4HCwYC2e9CTjJGGok4/7Hcjl+ImLBWv1uCRDu3peV5eGQ2C5/P1zq4X9dGpXP+LYhmYz4HbDMQgUosWTnmQoKKf0htVKBZvtFsx6S9bm48ktaV3EXwd/CzAAVjt+gHT5me0AAAAASUVORK5CYII=) no-repeat scroll 0 2px;
		color:#d00;	
		margin-left: 15px;
		font-size:11px;
		padding:0 0 0 13px;
		}

	.name {
		background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAABAklEQVRIie2UMW6DMBSG/4cYkJClIhauwMgx8CnSC9EjJKcwd2HGYmAwEoMREtClEJxYakmcoWq/yX623veebZmWZcFKWZbXyTHeOeeXfWDN69/uzPP8x1mVUmiaBlLKsxACAC6cc2OPd7zYK1EUYRgGZFkG3/fPAE5fIjcCAJimCXEcGxKnAiICERkSIcQmeVoQhiHatoWUEkopJEkCAB/r+t0lHyVN023c9z201qiq6s2ZYA9jDIwx1HW9xZ4+Ihta69cK9vwLvsX6ivYf4FGIyJj/rg5uqwccd2Ar7OUdOL/kPyKY5/mhZJ53/2asgiAIHhLYMARd16EoCozj6EzwCYrrX5dC9FQIAAAAAElFTkSuQmCC) no-repeat scroll 0px 12px;
		padding:15px 0 10px 40px;
		}

	.is_dir .name {
		background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAADdgAAA3YBfdWCzAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAI0SURBVFiF7Vctb1RRED1nZu5977VQVBEQBKZ1GCDBEwy+ISgCBsMPwOH4CUXgsKQOAxq5CaKChEBqShNK222327f79n0MgpRQ2qC2twKOGjE352TO3Jl76e44S8iZsgOww+Dhi/V3nePOsQRFv679/qsnV96ehgAeWvBged3vXi+OJewMW/Q+T8YCLr18fPnNqQq4fS0/MWlQdviwVqNpp9Mvs7l8Wn50aRH4zQIAqOruxANZAG4thKmQA8D7j5OFw/iIgLXvo6mR/B36K+LNp71vVd1cTMR8BFmwTesc88/uLQ5FKO4+k4aarbuPnq98mbdo2q70hmU0VREkEeCOtqrbMprmFqM1psoYAsg0U9EBtB0YozUWzWpVZQgBxMm3YPoCiLpxRrPaYrBKRSUL5qn2AgFU0koMVlkMOo6G2SIymQCAGE/AGHRsWbCRKc8VmaBN4wBIwkZkFmxkWZDSFCwyommZSABgCmZBSsuiHahA8kA2iZYzSapAsmgHlgfdVyGLTFg3iZqQhAqZB923GGUgQhYRVElmAUXIGGVgedQ9AJJnAkqyClCEkkfdM1Pt13VHdxDpnof0jgxB+mYqO5PaCSDRIAbgDgdpKjtmwm13irsnq4ATdKeYcNvUZAt0dg5NVwEQFKrJlpn45lwh/LpbWdela4K5QsXEN61tytWr81l5YSY/n4wdQH84qjd2J6vEz+W0BOAGgLlE/AMAPQCv6e4gmWYC/QF3d/7zf8P/An4AWL/T1+B2nyIAAAAASUVORK5CYII=) no-repeat scroll 0px 10px;
		padding:15px 0 10px 40px;
		}

	.download {
		background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAB2klEQVR4nJ2ST2sTQRiHn5mdmj92t9XmUJIWJGq9NHrRgxQiCtqbl97FqxgaL34CP0FD8Qv07EHEU0Ew6EXEk6ci8Q9JtcXEkHR3k+zujIdUqMkmiANzmJdnHn7vzCuIWbe291tSkvhz1pr+q1L2bBwrRgvFrcZKKinfP9zI2EoKmm7Azstf3V7fXK2Wc3ujvIqzAhglwRJoS2ImQZMEBjgyoDS4hv8QGHA1WICvp9yelsA7ITBTIkwWhGBZ0Iv+MUF+c/cB8PTHt08snb+AGAACZDj8qIN6bSe/uWsBb2qV24/GBLn8yl0plY9AJ9NKeL5ICyEIQkkiZenF5XwBDAZzWItLIIR6LGfk26VVxzltJ2gFw2a0FmQLZ+bcbo/DPbcd+PrDyRb+GqRipbGlZtX92UvzjmUpEGC0JgpC3M9dL+qGz16XsvcmCgCK2/vPtTNzJ1x2kkZIRBSivh8Z2Q4+VkvZy6O8HHvWyGyITvA1qndNpxfguQNkc2CIzM0xNk5QLedCEZm1VKsf2XrAXMNrA2vVcq4ZJ4DhvCSAeSALXASuLBTW129U6oPrT969AK4Bq0AeWARs4BRgieMUEkgDmeO9ANipzDnH//nFB0KgAxwATaAFeID5DQNatLGdaXOWAAAAAElFTkSuQmCC) no-repeat scroll 0px 5px;
		padding:4px 0 4px 20px;
		}
		
/*--------------CLOCK---------------*/

	#clock{
		width:370px;
		padding:0px;
		margin:2px auto 10px;
		position:relative;
		border-radius: 10px;
	}

	#clock:after{
		content:'';
		position:absolute;
		width:400px;
		height:20px;
		border-radius:100%;
		left:50%;
		margin-left:-200px;
		bottom:2px;
		z-index:-1;
	}

	#clock .display{
		text-align:center;
		padding: 30px 20px 20px;
		border-radius:6px;
		position:relative;
		height: 52px;
	}


	/*-------------------------
		Dark color theme
	--------------------------*/

	#clock.dark{
		background-color:#272e38;
		color:#cacaca;
	}

	#clock.dark:after{
		box-shadow:0 4px 10px rgba(0,0,0,0.3);
	}

	#clock.dark .digits div span{
		background-color:#cacaca;
		border-color:#cacaca;	
	}

	#clock.dark .alarm{
		background:url('../img/alarm_dark.jpg');
	}

	#clock.dark .display{
		background-color:#0f1620;
		box-shadow:0 1px 1px rgba(0,0,0,0.08) 
		inset, 0 1px 1px #2d3642;
	}

	#clock.dark .digits div.dots:before,
	#clock.dark .digits div.dots:after{
		background-color:#cacaca;
	}


	/*-------------------------
		The Digits
	--------------------------*/

	#clock .digits div{
		text-align:left;
		position:relative;
		width: 28px;
		height:50px;
		display:inline-block;
		margin:0 4px;
	}

	#clock .digits div span{
		opacity:0;
		position:absolute;

		-webkit-transition:0.25s;
		-moz-transition:0.25s;
		transition:0.25s;
	}

	#clock .digits div span:before,
	#clock .digits div span:after{
		content:'';
		position:absolute;
		width:0;
		height:0;
		border:5px solid transparent;
	}

	#clock .digits .d1{			
		height:5px;
		width:16px;
		top:0;
		left:6px;
	}

	#clock .digits .d1:before{	
		border-width:0 5px 5px 0;
		border-right-color:inherit;
		left:-5px;
	}

	#clock .digits .d1:after{	
		border-width:0 0 5px 5px;
		border-left-color:inherit;
		right:-5px;
	}

	#clock .digits .d2{			
		height:5px;
		width:16px;
		top:24px;
		left:6px;
	}

	#clock .digits .d2:before{	
		border-width:3px 4px 2px;
		border-right-color:inherit;
		left:-8px;
	}

	#clock .digits .d2:after{	
		border-width:3px 4px 2px;
		border-left-color:inherit;
		right:-8px;
	}

	#clock .digits .d3{			
		height:5px;
		width:16px;
		top:48px;
		left:6px;
	}

	#clock .digits .d3:before{	
		border-width:5px 5px 0 0;
		border-right-color:inherit;
		left:-5px;
	}

	#clock .digits .d3:after{	
		border-width:5px 0 0 5px;
		border-left-color:inherit;
		right:-5px;
	}

	#clock .digits .d4{			
		width:5px;
		height:14px;
		top:7px;
		left:0;
	}

	#clock .digits .d4:before{	
		border-width:0 5px 5px 0;
		border-bottom-color:inherit;
		top:-5px;
	}

	#clock .digits .d4:after{	
		border-width:0 0 5px 5px;
		border-left-color:inherit;
		bottom:-5px;
	}

	#clock .digits .d5{			
		width:5px;
		height:14px;
		top:7px;
		right:0;
	}

	#clock .digits .d5:before{	
		border-width:0 0 5px 5px;
		border-bottom-color:inherit;
		top:-5px;
	}

	#clock .digits .d5:after{	
		border-width:5px 0 0 5px;
		border-top-color:inherit;
		bottom:-5px;
	}

	#clock .digits .d6{			
		width:5px;
		height:14px;
		top:32px;
		left:0;
	}

	#clock .digits .d6:before{	
		border-width:0 5px 5px 0;
		border-bottom-color:inherit;
		top:-5px;
	}

	#clock .digits .d6:after{	
		border-width:0 0 5px 5px;
		border-left-color:inherit;
		bottom:-5px;
	}

	#clock .digits .d7{			
		width:5px;
		height:14px;
		top:32px;
		right:0;
	}

	#clock .digits .d7:before{	
		border-width:0 0 5px 5px;
		border-bottom-color:inherit;
		top:-5px;
	}

	#clock .digits .d7:after{	
		border-width:5px 0 0 5px;
		border-top-color:inherit;
		bottom:-5px;
	}


	/* 1 */

	#clock .digits div.one .d5,
	#clock .digits div.one .d7{
		opacity:1;
	}

	/* 2 */

	#clock .digits div.two .d1,
	#clock .digits div.two .d5,
	#clock .digits div.two .d2,
	#clock .digits div.two .d6,
	#clock .digits div.two .d3{
		opacity:1;
	}

	/* 3 */

	#clock .digits div.three .d1,
	#clock .digits div.three .d5,
	#clock .digits div.three .d2,
	#clock .digits div.three .d7,
	#clock .digits div.three .d3{
		opacity:1;
	}

	/* 4 */

	#clock .digits div.four .d5,
	#clock .digits div.four .d2,
	#clock .digits div.four .d4,
	#clock .digits div.four .d7{
		opacity:1;
	}

	/* 5 */

	#clock .digits div.five .d1,
	#clock .digits div.five .d2,
	#clock .digits div.five .d4,
	#clock .digits div.five .d3,
	#clock .digits div.five .d7{
		opacity:1;
	}

	/* 6 */

	#clock .digits div.six .d1,
	#clock .digits div.six .d2,
	#clock .digits div.six .d4,
	#clock .digits div.six .d3,
	#clock .digits div.six .d6,
	#clock .digits div.six .d7{
		opacity:1;
	}


	/* 7 */

	#clock .digits div.seven .d1,
	#clock .digits div.seven .d5,
	#clock .digits div.seven .d7{
		opacity:1;
	}

	/* 8 */

	#clock .digits div.eight .d1,
	#clock .digits div.eight .d2,
	#clock .digits div.eight .d3,
	#clock .digits div.eight .d4,
	#clock .digits div.eight .d5,
	#clock .digits div.eight .d6,
	#clock .digits div.eight .d7{
		opacity:1;
	}

	/* 9 */

	#clock .digits div.nine .d1,
	#clock .digits div.nine .d2,
	#clock .digits div.nine .d3,
	#clock .digits div.nine .d4,
	#clock .digits div.nine .d5,
	#clock .digits div.nine .d7{
		opacity:1;
	}

	/* 0 */

	#clock .digits div.zero .d1,
	#clock .digits div.zero .d3,
	#clock .digits div.zero .d4,
	#clock .digits div.zero .d5,
	#clock .digits div.zero .d6,
	#clock .digits div.zero .d7{
		opacity:1;
	}


	/* The dots */

	#clock .digits div.dots{
		width:5px;
	}

	#clock .digits div.dots:before,
	#clock .digits div.dots:after{
		width:5px;
		height:5px;
		content:'';
		position:absolute;
		left:0;
		top:14px;
	}

	#clock .digits div.dots:after{
		top:34px;
	}


	/*-------------------------
		The Alarm
	--------------------------*/


	#clock .alarm{
		width:16px;
		height:16px;
		bottom:20px;
		background:url('../img/alarm_light.jpg');
		position:absolute;
		opacity:0.2;
	}

	#clock .alarm.active{
		opacity:1;
	}


	/*-------------------------
		Weekdays
	--------------------------*/


	#clock .weekdays{
		font-size:12px;
		position:absolute;
		width:100%;
		top:10px;
		left:0;
		text-align:center;
	}


	#clock .weekdays span{
		opacity:0.2;
		padding:0 10px;
	}

	#clock .weekdays span.active{
		opacity:1;
	}


	/*-------------------------
			AM/PM
	--------------------------*/


	#clock .ampm{
		position:absolute;
		bottom:20px;
		right:20px;
		font-size:12px;
	}




		</style>
	</head>

<!------------------------------------ HTML ------------------------------------>  

<body>

<div class="header" id="myHeader">
<div id="top">
    <div id="clock" class="dark">
		<div class="display">
		<div class="weekdays"></div>
		<div class="ampm"></div>
		<div class="alarm"></div>
		<div class="digits"></div>
        </div>             
    </div>
   </div>
</div>

     <br>

	 <h1><center><span class="txt-rotate" data-period="2000" data-rotate='[ "WELCOME TO VIRTUAL OFFICE OF SPb!!!"]'></span></center></h1>

	 <!-- <?php 
      echo "<p>Hye, Welcome <font color=red class='text'>Ms. Siti Nazihah</font> have a nice day!</p>";
     ?> -->

	 <?php 
      echo "<p>Hye pretty <font color=red class='text'>Miss Rosnani</font> have a nice day! All is well insha Allah</p>";
     ?> 

    <br>

	<div class="file">
    <?php if($allow_create_folder): ?>
	<form action="?" method="post" id="mkdir"/>
		<label for=dirname>Create New Folder</label><input id=dirname type=text name=name value="" placeholder="Enter file name"/>
		<input type="submit" value="CREATE" id="create"/>

		<a href="https://calendar.google.com/calendar/u/0/r?tab=kc&pli=1"><i class="fa fa-calendar" style="font-size:30px;color:black"></i></a>	
	</form>

   <?php endif; ?>

   <?php if($allow_upload): ?>

	<div id="file_drop_target">
		Drag Files Here To Upload
		<b>or</b>
		<input type="file" multiple />
	</div>
   <?php endif; ?>
	<div id="breadcrumb">&nbsp;</div>

	<div id="upload_progress"></div>
	<table id="table"><thead><tr>
		<!-- <th><span>&#10003;</span></th> -->
		<th>Name</th>
		<th>Size</th>
		<th>Modified</th>
		<th>Permissions</th>
		<th>Actions</th>
	</tr></thead>
	
	<tbody id="list">

	</tbody>

	</table>

	<footer> &copy; by Siti Nazihah Binti Mohd Kail</footer>
	</div>


<!------------------------------------ SCRIPT ------------------------------------>  

<script>
$(function(){

var clock = $('#clock'),
alarm = clock.find('.alarm'),
ampm = clock.find('.ampm');

var digit_to_name = 'zero one two three four five six seven eight nine'.split(' ');

var digits = {};

var positions = [
'h1', 'h2', ':', 'm1', 'm2', ':', 's1', 's2'
];


var digit_holder = clock.find('.digits');

$.each(positions, function(){

if(this == ':'){
digit_holder.append('<div class="dots">');
}
else{

var pos = $('<div>');

for(var i=1; i<8; i++){
pos.append('<span class="d' + i + '">');
}

digits[this] = pos;

digit_holder.append(pos);
}

});


var weekday_names = 'MON TUE WED THU FRI SAT SUN'.split(' '),
weekday_holder = clock.find('.weekdays');

$.each(weekday_names, function(){
weekday_holder.append('<span>' + this + '</span>');
});

var weekdays = clock.find('.weekdays span');


(function update_time(){

var now = moment().format("hhmmssdA");

digits.h1.attr('class', digit_to_name[now[0]]);
digits.h2.attr('class', digit_to_name[now[1]]);
digits.m1.attr('class', digit_to_name[now[2]]);
digits.m2.attr('class', digit_to_name[now[3]]);
digits.s1.attr('class', digit_to_name[now[4]]);
digits.s2.attr('class', digit_to_name[now[5]]);


var dow = now[6];
dow--;

if(dow < 0){
dow = 6;
}

weekdays.removeClass('active').eq(dow).addClass('active');

ampm.text(now[7]+now[8]);

setTimeout(update_time, 1000);

})();

});
 
 
 //Navigation Bar
$("nav div").click(function() {
            $("ul").slideToggle();
            $("ul ul").css("display", "none");
      });

      $("ul li").click(function() {
            $("ul ul").slideUp();
            $(this).find('ul').slideToggle();
      });

      $(window).resize(function() {
            if($(window).width() > 768) {
                  $("ul").removeAttr('style');
            }
      }); 


//Marquee
$(function() {

var marquee = $("#marquee"); 
marquee.css({"overflow": "hidden", "width": "100%"});

// wrap "My Text" with a span (IE doesn't like divs inline-block)
marquee.wrapInner("<span>");
marquee.find("span").css({ "width": "50%", "display": "inline-block", "text-align":"center" }); 
marquee.append(marquee.find("span").clone()); // now there are two spans with "My Text"

marquee.wrapInner("<div>");
marquee.find("div").css("width", "200%");

var reset = function() {
    $(this).css("margin-left", "0%");
    $(this).animate({ "margin-left": "-100%" }, 20000, 'linear', reset);
};

reset.call(marquee.find("div"));
  
  });

//Text Style
var TxtRotate = function(el, toRotate, period) {
  this.toRotate = toRotate;
  this.el = el;
  this.loopNum = 0;
  this.period = parseInt(period, 10) || 2000;
  this.txt = '';
  this.tick();
  this.isDeleting = false;
};

TxtRotate.prototype.tick = function() {
  var i = this.loopNum % this.toRotate.length;
  var fullTxt = this.toRotate[i];

  if (this.isDeleting) {
    this.txt = fullTxt.substring(0, this.txt.length - 1);
  } else {
    this.txt = fullTxt.substring(0, this.txt.length + 1);
  }

  this.el.innerHTML = '<span class="wrap">'+this.txt+'</span>';

  var that = this;
  var delta = 300 - Math.random() * 100;

  if (this.isDeleting) { delta /= 2; }

  if (!this.isDeleting && this.txt === fullTxt) {
    delta = this.period;
    this.isDeleting = true;
  } else if (this.isDeleting && this.txt === '') {
    this.isDeleting = false;
    this.loopNum++;
    delta = 500;
  }

  setTimeout(function() {
    that.tick();
  }, delta);
};

window.onload = function() {
  var elements = document.getElementsByClassName('txt-rotate');
  for (var i=0; i<elements.length; i++) {
    var toRotate = elements[i].getAttribute('data-rotate');
    var period = elements[i].getAttribute('data-period');
    if (toRotate) {
      new TxtRotate(elements[i], JSON.parse(toRotate), period);
    }
  }
  // INJECT CSS
  var css = document.createElement("style");
  css.type = "text/css";
  css.innerHTML = ".txt-rotate > .wrap { border-right: 0.08em solid #666 }";
  document.body.appendChild(css);
};

//FUNCTION FOLDER 
(function($){
	$.fn.tablesorter = function() {
		var $table = this;
		this.find('th').click(function() {
			var idx = $(this).index();
			var direction = $(this).hasClass('sort_asc');
			$table.tablesortby(idx,direction);
		});
		return this;
	};
	$.fn.tablesortby = function(idx,direction) {
		var $rows = this.find('tbody tr');
		function elementToVal(a) {
			var $a_elem = $(a).find('td:nth-child('+(idx+1)+')');
			var a_val = $a_elem.attr('data-sort') || $a_elem.text();
			return (a_val == parseInt(a_val) ? parseInt(a_val) : a_val);
		}
		$rows.sort(function(a,b){
			var a_val = elementToVal(a), b_val = elementToVal(b);
			return (a_val > b_val ? 1 : (a_val == b_val ? 0 : -1)) * (direction ? 1 : -1);
		})
		this.find('th').removeClass('sort_asc sort_desc');
		$(this).find('thead th:nth-child('+(idx+1)+')').addClass(direction ? 'sort_desc' : 'sort_asc');
		for(var i =0;i<$rows.length;i++)
			this.append($rows[i]);
		this.settablesortmarkers();
		return this;
	}
	$.fn.retablesort = function() {
		var $e = this.find('thead th.sort_asc, thead th.sort_desc');
		if($e.length)
			this.tablesortby($e.index(), $e.hasClass('sort_desc') );

		return this;
	}
	$.fn.settablesortmarkers = function() {
		this.find('thead th span.indicator').remove();
		this.find('thead th.sort_asc').append('<span class="indicator">&darr;<span>');
		this.find('thead th.sort_desc').append('<span class="indicator">&uarr;<span>');
		return this;
	}
})(jQuery);
$(function(){
	var XSRF = (document.cookie.match('(^|; )_sfm_xsrf=([^;]*)')||0)[2];
	var MAX_UPLOAD_SIZE = <?php echo $MAX_UPLOAD_SIZE ?>;
	var $tbody = $('#list');
	$(window).on('hashchange',list).trigger('hashchange');
	$('#table').tablesorter();

	$('#table').on('click','.delete',function(data) {
		$.post("",{'do':'delete',file:$(this).attr('data-file'),xsrf:XSRF},function(response){
			list();
		},'json');
		return false;
	});

	$('#mkdir').submit(function(e) {
		var hashval = decodeURIComponent(window.location.hash.substr(1)),
			$dir = $(this).find('[name=name]');
		e.preventDefault();
		$dir.val().length && $.post('?',{'do':'mkdir',name:$dir.val(),xsrf:XSRF,file:hashval},function(data){
			list();
		},'json');
		$dir.val('');
		return false;
	});

    <?php if($allow_upload): ?>
	// file upload stuff
	$('#file_drop_target').on('dragover',function(){
		$(this).addClass('drag_over');
		return false;
	}).on('dragend',function(){
		$(this).removeClass('drag_over');
		return false;
	}).on('drop',function(e){
		e.preventDefault();
		var files = e.originalEvent.dataTransfer.files;
		$.each(files,function(k,file) {
			uploadFile(file);
		});
		$(this).removeClass('drag_over');
	});
	$('input[type=file]').change(function(e) {
		e.preventDefault();
		$.each(this.files,function(k,file) {
			uploadFile(file);
		});
	});


	function uploadFile(file) {
		var folder = decodeURIComponent(window.location.hash.substr(1));

		if(file.size > MAX_UPLOAD_SIZE) {
			var $error_row = renderFileSizeErrorRow(file,folder);
			$('#upload_progress').append($error_row);
			window.setTimeout(function(){$error_row.fadeOut();},5000);
			return false;
		}

		var $row = renderFileUploadRow(file,folder);
		$('#upload_progress').append($row);
		var fd = new FormData();
		fd.append('file_data',file);
		fd.append('file',folder);
		fd.append('xsrf',XSRF);
		fd.append('do','upload');
		var xhr = new XMLHttpRequest();
		xhr.open('POST', '?');
		xhr.onload = function() {
			$row.remove();
    		list();
  		};
		xhr.upload.onprogress = function(e){
			if(e.lengthComputable) {
				$row.find('.progress').css('width',(e.loaded/e.total*100 | 0)+'%' );
			}
		};
	    xhr.send(fd);
	}
	function renderFileUploadRow(file,folder) {
		return $row = $('<div/>')
			.append( $('<span class="fileuploadname" />').text( (folder ? folder+'/':'')+file.name))
			.append( $('<div class="progress_track"><div class="progress"></div></div>')  )
			.append( $('<span class="size" />').text(formatFileSize(file.size)) )
	};
	function renderFileSizeErrorRow(file,folder) {
		return $row = $('<div class="error" />')
			.append( $('<span class="fileuploadname" />').text( 'Error: ' + (folder ? folder+'/':'')+file.name))
			.append( $('<span/>').html(' file size - <b>' + formatFileSize(file.size) + '</b>'
				+' exceeds max upload size of <b>' + formatFileSize(MAX_UPLOAD_SIZE) + '</b>')  );
	}
<?php endif; ?>
	function list() {
		var hashval = window.location.hash.substr(1);
		$.get('?do=list&file='+ hashval,function(data) {
			$tbody.empty();
			$('#breadcrumb').empty().html(renderBreadcrumbs(hashval));
			if(data.success) {
				$.each(data.results,function(k,v){
					$tbody.append(renderFileRow(v));
				});
				!data.results.length && $tbody.append('<tr><td class="empty" colspan=5>This folder is empty</td></tr>')
				data.is_writable ? $('body').removeClass('no_write') : $('body').addClass('no_write');
			} else {
				console.warn(data.error.msg);
			}
			$('#table').retablesort();
		},'json');
	}
	function renderFileRow(data) {
		var $link = $('<a class="name" />')
			.attr('href', data.is_dir ? '#' + encodeURIComponent(data.path) : './'+ encodeURIComponent(data.path))
			.text(data.name);
		var allow_direct_link = <?php echo $allow_direct_link?'true':'false'; ?>;
        	if (!data.is_dir && !allow_direct_link)  $link.css('pointer-events','none');
		var $dl_link = $('<a/>').attr('href','?do=download&file='+ encodeURIComponent(data.path))
			.addClass('download').text('download');
		var $delete_link = $('<a href="#" />').attr('data-file',data.path).addClass('delete').text('delete');
		var perms = [];
		if(data.is_readable) perms.push('read');
		if(data.is_writable) perms.push('write');
		if(data.is_executable) perms.push('exec');
		var $html = $('<tr />')
			.addClass(data.is_dir ? 'is_dir' : '')
			.append( $('<td class="first" />').append($link) )
			.append( $('<td/>').attr('data-sort',data.is_dir ? -1 : data.size)
				.html($('<span class="size" />').text(formatFileSize(data.size))) )
			.append( $('<td/>').attr('data-sort',data.mtime).text(formatTimestamp(data.mtime)) )
			.append( $('<td/>').text(perms.join('+')) )
			.append( $('<td/>').append($dl_link).append( data.is_deleteable ? $delete_link : '') )
		return $html;
	}
	function renderBreadcrumbs(path) {
		var base = "",
			$html = $('<div/>').append( $('<a href=#><i class="fa fa-home" style="font-size:30px;color:black"></i></a></div>') );
		$.each(path.split('%2F'),function(k,v){
			if(v) {
				var v_as_text = decodeURIComponent(v);
				$html.append( $('<span/>').text(' ▸ ') )
					.append( $('<a/>').attr('href','#'+base+v).text(v_as_text) );
				base += v + '%2F';
			}
		});
		return $html;
	}
	function formatTimestamp(unix_timestamp) {
		var m = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
		var d = new Date(unix_timestamp*1000);
		return [m[d.getMonth()],' ',d.getDate(),', ',d.getFullYear()," ",
			(d.getHours() % 12 || 12),":",(d.getMinutes() < 10 ? '0' : '')+d.getMinutes(),
			" ",d.getHours() >= 12 ? 'PM' : 'AM'].join('');
	}
	function formatFileSize(bytes) {
		var s = ['bytes', 'KB','MB','GB','TB','PB','EB'];
		for(var pos = 0;bytes >= 1000; pos++,bytes /= 1024);
		var d = Math.round(bytes*10);
		return pos ? [parseInt(d/10),".",d%10," ",s[pos]].join('') : bytes + ' bytes';
	}
})
</script>

</body>
</html>