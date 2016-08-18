<?php
//$proxies = file("/var/www/stage.tacticalarbitrage.com/public_html/proxieslist");
exec("/usr/local/bin/proxieslist.sh",$proxies);

$mc = curl_multi_init ();
$running_count = count($proxies);
echo 'running '.$running_count.PHP_EOL;
$alive_count = 0;
$dir = '/root/docker-openvpn-tinyproxy/';
$bad = $dir.'bad.lst';
for ($thread_no = 0; $thread_no<count ($proxies); $thread_no++)
{
	list($name, $proxy) = explode(" - ", $proxies[$thread_no]);
	$c [$thread_no] = curl_init ();
	curl_setopt ($c [$thread_no], CURLOPT_URL, "http://www.vpngate.net/api/iphone/");
	curl_setopt ($c [$thread_no], CURLOPT_HEADER, 0);
	curl_setopt ($c [$thread_no], CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($c [$thread_no], CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt ($c [$thread_no], CURLOPT_TIMEOUT, 20);
	curl_setopt ($c [$thread_no], CURLOPT_PROXY, trim ($proxy));
	curl_setopt ($c [$thread_no], CURLOPT_PROXYTYPE, 0);
	curl_multi_add_handle ($mc, $c [$thread_no]);
}
file_put_contents('proxies.csv','');
do {
	while (($execrun = curl_multi_exec ($mc, $running)) == CURLM_CALL_MULTI_PERFORM);
		if ($execrun != CURLM_OK) break;
	while ($done = curl_multi_info_read ($mc)){
		$content = curl_multi_getcontent($done['handle']);
		echo $proxies [array_search($done['handle'],$c)].PHP_EOL;
		file_put_contents('proxies.csv',$content,FILE_APPEND);
		curl_multi_remove_handle ($mc, $done ['handle']);
		}
} while ($running);
	curl_multi_close ($mc);
$fhandle = fopen('proxies.csv', 'r');
$line = fgets($fhandle);
rewind($fhandle);
$count = 0;
$parsed = 0;
while (!feof($fhandle))
{
        $single = fgetcsv($fhandle, 0, ',');
        if($count > 1 && !empty($single[14])
			&& !strpos(file_get_contents($bad),$single[0])
			// && $single[2]>500000
			// && $single[3]<20 
			//&& ($single[6] == 'US' || $single[6] == 'CA')
				){
                $vpn_file = $dir.'vpn_fast/'.$single[0].'.'.$single[6];
                touch($vpn_file);
                $row = base64_decode($single[14]);
                file_put_contents($vpn_file, $row);
                //var_dump($single);
                //echo $vpn_file;
                //break;
		$parsed++;
        }
        $count++;
}
echo $parsed." proxies parsed\r\n";
$newconfigs = array_filter(scandir($dir.'vpn_fast/'), function($item){
                return !is_dir($item);
        });
foreach($newconfigs as $config){
	file_put_contents($dir.'vpn_fast/'.$config,PHP_EOL.'auth-user-pass vpnbook.auth'.PHP_EOL,FILE_APPEND);	
}
$return = exec("mv -n ".$dir."vpn_fast/* ".$dir."configs/; rm -f ".$dir."vpn_fast/*");
?>
