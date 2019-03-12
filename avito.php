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
        if (!strpos($content, 'Location: https://www.avito.ru/blocked')) {
        	return $content;
        }
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, 1);

    $headers = array(
        ':authority: www.avito.ru',
        ':method: GET',
        ':path: '.str_replace('https://www.avito.ru', '', $url),
        ':scheme: https',
        'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
        //'accept-encoding: gzip, deflate, br',
        'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,de;q=0.6,vi;q=0.5',
        'cache-control: max-age=0',
        'cookie: f=5.0c4f4b6d233fb90636b4dd61b04726f147e1eada7172e06c47e1eada7172e06c47e1eada7172e06c47e1eada7172e06cb59320d6eb6303c1b59320d6eb6303c1b59320d6eb6303c147e1eada7172e06c8a38e2c5b3e08b898a38e2c5b3e08b890df103df0c26013a0df103df0c26013a2ebf3cb6fd35a0ac0df103df0c26013a8b1472fe2f9ba6b984dcacfe8ebe897bfa4d7ea84258c63d59c9621b2c0fa58f897baa7410138ead3de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe207b7a18108a6dcd6f8ee35c29834d631c9ba923b7b327da7e87a84e371fc60d21abab15028c5344d5e61d702b2ac73f7ef858355226994bc0e687930c3e21aed9b875d7b97b5db61daa659585eecd48d4938c41efda3055a8f1786dad6fd98129e82118971f2ed64956cdff3d4067aa532311436535e092c55e252f6e73349e33de19da9ed218fe23de19da9ed218fe2b9e742668625d8bf514b821ed3011c9e517083b9c0063862',
        'upgrade-insecure-requests: 1',
        'referer: '.$url,
        'user-agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36'
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $content = curl_exec($ch);
    curl_close($ch);

    $rx = '~^(HTTP.*?)(\r\n\r\n|\n\n)~is';
    preg_match($rx, $content, $a);
    $header = $a[0];
    $content = str_replace($header, '', $content);

    if (strpos($header, 'Location: https://www.avito.ru/blocked')) {
        echo '<h3>Заблокировали</h3>';
        echo '<pre>'.$header.'</pre>';
        exit;
    }

    sleep(rand(2, 5));

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
