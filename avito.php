<?php



// Технические функции

function setCache($content, $cacheId)
{
    if ($content == '') {
        return ;
    }
    $fileName = 'cash/'.md5($cacheId);
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

function POST($key, $default='')
{
    if (array_key_exists($key, $_POST)) {
        return $_POST[$key];
    } else {
        return $default;
    }
}

function GET($key, $default='')
{
    if (array_key_exists($key, $_GET)) {
        return $_POST[$key];
    } else {
        return $default;
    }
}

// Авито функции

function getAvitoDate($dateString)
{
    $date = $dateString;

    $date = str_replace('&nbsp;', ' ', $date);
    $date = str_replace('Сегодня', date('Y-m-d'), $date);
    $date = str_replace('Вчера', date('Y-m-d', strtotime(date('Y-m-d').' -1 day')), $date);
    $date = trim($date);
    $date .= ':00';

    $date = strtotime($date);
    return $date;
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

        $row = [];

        preg_match('~<span itemprop="name">(.*?)</span>~i', $rowContent, $a);
        $row['name'] = $a[1];

        preg_match('~\d{4}~i', $row['name'], $a);
        $row['year'] = $a[0];

        preg_match('~data-absolute-date="\s*([^"]+)\s*"~i', $rowContent, $a);
        $row['date'] = getAvitoDate($a[1]);

        preg_match('~data-item-url="([^"]+)"~i', $rowContent, $a);
        $row['url'] = $a[1];

        preg_match('~data-item-id="(\d+)"~i', $rowContent, $a);
        $row['id'] = $a[1];

        preg_match('~>\s+([\d\s]+) км~i', $rowContent, $a);
        $row['probeg'] = preg_replace('~[^\d]~i', '', $a[1]);

        preg_match('~</p>\s+<p>([^<]+)</p>~i', $rowContent, $a);
        $row['region'] = $a[1];

        preg_match('~itemprop="price"\s*content="(\d+)"~i', $rowContent, $a);
        $row['price'] = $a[1];

        //echo '<pre>'; print_r($row); echo '</pre>';

        //exit;
        $data []= $row;
    }
    return $data;
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
$url = $_POST['url'] ?: $url;

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>Парсер Авито</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <style type="text/css">
    h1 {margin:20px 0 15px; font-size:24px;}
    </style>
  </head>
  <body>

<div class="container-fluid">

    <h1>Парсер Авито</h1>

    <form class="form-inline" method="post">
      <div class="form-group">
        <label><a href="<?=$url?>" target="_blank">URL</a></label>
        <input type="text" class="form-control" name="url" value="<?=$url?>" style="width:400px;">
      </div>
&nbsp;
      <div class="form-group">
        <label>Загружать до</label>
        <input type="number" class="form-control" name="maxPage" value="<?=POST('maxPage', 1)?>" style="width:70px;">
      </div>
      <button type="submit" class="btn btn-default">Выполнить</button>
    </form>



<?php

if ($_POST['url']) {
    
    $data = parseAvitoAll($_POST['url'], $fromPage=1, $_POST['maxPage']);

    echo '<hr />';
    echo '<pre>'; print_r($data); echo '</pre>';
}




?>

</div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
  </body>
</html>

