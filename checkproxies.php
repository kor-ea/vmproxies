<?php
$file = "/var/www/stage.tacticalarbitrage.com/public_html/proxieslist";
exec("/usr/local/bin/proxieslist.sh",$proxies);
$mc = curl_multi_init ();
echo count($proxies)."\r\n";
for ($thread_no = 0; $thread_no<count ($proxies); $thread_no++)
{
	list($name, $proxy) = explode(" - ", $proxies[$thread_no]);
	$c [$thread_no] = curl_init ();
	curl_setopt ($c [$thread_no], CURLOPT_URL, "http://ifconfig.ca");
	curl_setopt ($c [$thread_no], CURLOPT_HEADER, 0);
	curl_setopt ($c [$thread_no], CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($c [$thread_no], CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt ($c [$thread_no], CURLOPT_TIMEOUT, 20);
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
		if ($content && strpos($content,"500") == 0) {
			list($name, $proxy) = explode(" - ", trim ($proxies [array_search ($done['handle'], $c)]));
			echo $name."\r\n";
			file_put_contents($file, $proxy."\r\n", FILE_APPEND);
		}else{
			echo $proxies [array_search ($done['handle'], $c)]."\r\n";
		}
		curl_multi_remove_handle ($mc, $done ['handle']);
		}
} while ($running);
	curl_multi_close ($mc);
?>
