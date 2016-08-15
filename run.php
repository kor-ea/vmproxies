<?php
$file = "/var/www/stage.tacticalarbitrage.com/public_html/proxieslist";
exec("/usr/local/bin/proxieslist.sh",$proxies);
$mc = curl_multi_init ();
$running_count = count($proxies);
echo 'running '.$running_count.PHP_EOL;
$alive_count = 0;
$dir = '/root/docker-openvpn-tinyproxy/';
for ($thread_no = 0; $thread_no<count ($proxies); $thread_no++)
{
	list($name, $proxy) = explode(" - ", $proxies[$thread_no]);
	$c [$thread_no] = curl_init ();
	curl_setopt ($c [$thread_no], CURLOPT_URL, "http://speedtest.ftp.otenet.gr/files/test1Mb.db");
	curl_setopt ($c [$thread_no], CURLOPT_HEADER, 0);
	curl_setopt ($c [$thread_no], CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($c [$thread_no], CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt ($c [$thread_no], CURLOPT_TIMEOUT, 10);
	curl_setopt ($c [$thread_no], CURLOPT_PROXY, trim ($proxy));
	curl_setopt ($c [$thread_no], CURLOPT_PROXYTYPE, 0);
	curl_multi_add_handle ($mc, $c [$thread_no]);
}

file_put_contents($file,"");
do {
	while (($execrun = curl_multi_exec ($mc, $running)) == CURLM_CALL_MULTI_PERFORM);
		if ($execrun != CURLM_OK) break;
	while ($done = curl_multi_info_read ($mc)){
		$content = curl_multi_getcontent($done['handle']);
		$curlinfo = curl_getinfo($done['handle'],CURLINFO_SIZE_DOWNLOAD);
		list($name, $proxy) = explode(" - ", trim ($proxies [array_search ($done['handle'], $c)]));
		//var_dump($curlinfo);
		if ($content && $curlinfo > 1000000 && strpos($content,"500") == 0) {
			echo $name.PHP_EOL;
			file_put_contents($file, $proxy.PHP_EOL, FILE_APPEND);
			$alive_count++;
		}else{
			echo $proxies [array_search ($done['handle'], $c)];
			echo ' killing '.exec('docker kill '.preg_replace('/\//','',$name)).PHP_EOL;
		}
		curl_multi_remove_handle ($mc, $done ['handle']);
		}
} while ($running);
	curl_multi_close ($mc);
echo $alive_count." of ".$running_count." alive.".PHP_EOL;
$remove = exec("docker ps -a | grep 'Exited' | awk '{print $1}' | xargs --no-run-if-empty docker rm");
if($alive_count < 20 ){
	$newconfigs = array_filter(scandir($dir.'configs/'), function($item){
		return !is_dir($item);
	});
	for($i =1; $i <=50; $i++){
		$config = $newconfigs[array_rand($newconfigs)];
		$result = exec('docker run --name '.$config.' -d --device=/dev/net/tun:/dev/net/tun --cap-add=NET_ADMIN -v='.$dir.':/etc/openvpn vm ./configs/'.$config);
		echo 'starting '.$result." - ".$config.PHP_EOL;
		
	}
}





?>
