<?php
//require_once 'init.php';

function setCache($content, $cacheId)
{
    if ($content == '') {
        return ;
    }
    $fileName = 'cash/'.md5($cacheId);
    /*if (strpos($str, 'Мы обнаружили, что запросы')) {
        if (file_exists($fileName)) {
        	unlink($fileName);
            return ;
        }
    }*/
    if (!file_exists('cash')) {
        mkdir('cash');
    }
    $f = fopen($fileName, 'w+');
    fwrite($f, $content);
    fclose($f);
}

function getCache($cacheId, $cashExpired=true, &$fileName='')
{
    if (!$cashExpired) {
        return ;
    }
    $fileName = 'cash/'.md5($cacheId);
    if (!file_exists($fileName)) {
        return false;
    }
    $time = time() - filemtime($fileName);
    if ($time > $cashExpired) {
        return false;
    }
    /*if (strpos($str, 'Мы обнаружили, что запросы')) {
        return ;
    }*/
    return file_get_contents($fileName);

}

function curlLoad($url, $cash=0)
{
    $cacheId = $url;
    if ($content = getCache($cacheId, $cash)) {
        return $content;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    $content = curl_exec($ch);
    curl_close($ch);

    setCache($content, $cacheId);
    return $content;
}

function parseAvitoPage($url)
{
    $content = curlLoad($url, $cash=3600);

    preg_matchx('~<div class="js-catalog_before-ads">.*?<div class="avito-ads-container avito-ads-container_desktop_low">~is', $content, $a);
    $innerContent = $a[0];

    $rows = preg_split('~<div class="item item_table~is', $innerContent);
    array_shift($rows);

    //preg_match_all('~<div class="item item_table.*?</div>\s*</div>\s*</div>~is', $innerContent, $rows);

    $data = [];
    foreach ($rows as $key => $rowContent) {
        //echo '<pre>'.htmlspecialchars($rowContent).'</pre>';

        preg_match('~<span itemprop="name">(.*?)</span>~i', $rowContent, $a);
        $name = $a[1];
        preg_match('~itemprop="price"\s*content="(.*?)"~i', $rowContent, $a);
        $price = $a[1];

        //echo '<br />-'.($key + 1).') '.$name.' --- <b>'.$price.'</b>';
        //exit;
        $data []= compact('name', 'price');
    }
    return $data;
}

function preg_matchx($regexp, $content, &$results)
{
    $res = preg_match($regexp, $content, $results);
    if (!$res) {
        echo '<div style="color:red">Ошибка preg_match - "'.htmlspecialchars($regexp).'"</div>';
    }
    return $res;
}

function preg_match_allx($regexp, $content, &$results)
{
    $res = preg_match_all($regexp, $content, $results);
    if (!$res) {
        echo '<div style="color:red">Ошибка preg_match_all - "'.htmlspecialchars($regexp).'"</div>';
    }
    return $res;
}



function parseAvitoAll($url, $fromPage=1, $maxPage=false)
{
    $dataAll = [];
    $page = $fromPage;
    while (true) {

        if ($page == 1) {
        	$urlCurrent = $url;
        } else {
            if (strpos($url, '?')) {
            	$urlCurrent = str_replace('?', '?p='.$page.'&', $url);
            } else {
                $urlCurrent = $url.'?='.$page;
            }
        }

        //echo '<br />'.$urlCurrent;

        $data = parseAvitoPage($urlCurrent);

        //var_dump(count($data));

        if (!count($data)) {
            break;
        }
        $dataAll = array_merge($dataAll, $data);

        if ($maxPage && $page == $maxPage) {
        	break;
        }
        $page ++;
    }
    return $dataAll;
}


$url = 'https://www.avito.ru/syktyvkar/avtomobili?radius=200';
$data = parseAvitoAll($url, $fromPage=1, $maxPage=5);

var_dump(count($data));


echo '<hr />';




