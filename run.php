<?php
$alive_count = 0;
$us_count = 0;
$dir = '/root/vmproxies/';
$bad = $dir.'bad.lst';
$memcache = new Memcache;
$memcache->connect('localhost', 11211) or die ("Could not connect");
$runningpid = $memcache->get('pid');
echo "Pid from memcache ".$runningpid.PHP_EOL;
if ($runningpid && file_exists("/proc/".$runningpid)) {
	die ("Job is already running".PHP_EOL);	
}else{
	$memcache->set('pid',getmypid());
}
exec("/usr/local/bin/proxieslist.sh",$proxies);
$mc = curl_multi_init ();
$running_count = count($proxies);
echo 'running '.$running_count.PHP_EOL;
for ($thread_no = 0; $thread_no<count ($proxies); $thread_no++)
{
	list($name, $proxy) = explode(" - ", $proxies[$thread_no]);
	$c [$thread_no] = curl_init ();
	curl_setopt ($c [$thread_no], CURLOPT_URL, "http://speedtest.dal01.softlayer.com/downloads/test10.zip");
	curl_setopt ($c [$thread_no], CURLOPT_HEADER, 0);
	curl_setopt ($c [$thread_no], CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($c [$thread_no], CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt ($c [$thread_no], CURLOPT_TIMEOUT, 10);
	curl_setopt ($c [$thread_no], CURLOPT_PROXY, trim ($proxy));
	curl_setopt ($c [$thread_no], CURLOPT_PROXYTYPE, 0);
	curl_multi_add_handle ($mc, $c [$thread_no]);
}

//file_put_contents($file,"");
$alllist = "";
$uslist = "";
do {
	while (($execrun = curl_multi_exec ($mc, $running)) == CURLM_CALL_MULTI_PERFORM);
		if ($execrun != CURLM_OK) break;
	while ($done = curl_multi_info_read ($mc)){
		$content = curl_multi_getcontent($done['handle']);
		$curlinfo = curl_getinfo($done['handle'],CURLINFO_SIZE_DOWNLOAD);
		list($name, $proxy) = explode(" - ", trim ($proxies [array_search ($done['handle'], $c)]));
		if ($content && $curlinfo > 800000 && strpos($content,"500") == 0 ) {
			echo $name.' Got '.$curlinfo.' bytes.'.PHP_EOL;
//			file_put_contents($file, $proxy.PHP_EOL, FILE_APPEND);
			$alllist[$name] = $proxy;
			$alive_count++;
			if (strpos($name, 'US') || strpos($name, 'CA')){
				$uslist[$name] = $proxy;
				$us_count++;
			}
		}else{
			echo $proxies [array_search ($done['handle'], $c)];
			echo ' Got '.$curlinfo.' bytes. Killing '.exec('docker kill '.preg_replace('/\//','',$name)).PHP_EOL;
			if ($curlinfo < 100) {
//				file_put_contents($bad,$name.PHP_EOL, FILE_APPEND);
//				unlink($dir.'configs'.$name);
			}
		}
		curl_multi_remove_handle ($mc, $done ['handle']);
		}
} while ($running);
$memcache->set('all',$alllist);
$memcache->set('us',$uslist);
	curl_multi_close ($mc);
echo $alive_count." ALL of ".$running_count."  alive.".PHP_EOL;
echo $us_count." US of ".$running_count."  alive.".PHP_EOL;
if($alive_count < 50 ){
	$newconfigs = array_filter(scandir($dir.'configs/'), function($item){
		return !is_dir($item);
	});
	for($i =1; $i <=200; $i++){
		$config = $newconfigs[array_rand($newconfigs)];
		$result = exec('docker run --name '.$config.' -d --device=/dev/net/tun:/dev/net/tun --cap-add=NET_ADMIN -v='.$dir.'etc:/etc/openvpn -v='.$dir.'configs/'.$config.':/etc/'.$config.' vm /etc/'.$config);
		echo 'starting '.$result." - ".$config.PHP_EOL;
		
	}
}
echo 'Cleaning up...'.PHP_EOL;
//$remove = exec("docker ps -a |grep 'Exited' | grep -Eo 'vpn[0-9].*?' | xargs --no-run-if-empty rm ".$dir."configs/");
$remove = exec("docker ps -a | grep 'Exited' | grep -Eo 'vpn[0-9]*\..*' |xargs -l bash -c 'echo ".$dir."configs/$0' |xargs rm");
//$remove = exec("docker ps -a | grep 'Exited' | grep -Eo 'vpn[0-9].*?' |xargs -l bash -c 'echo $0 >>".$bad."'");
$remove = exec("docker ps -a | grep 'Exited' | awk '{print $1}' | xargs --no-run-if-empty docker rm -v");


?>
