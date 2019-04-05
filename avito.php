<?php

spl_autoload_register(function ($class_name) {
    include 'Class.'.$class_name.'.php';
});

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

function drawArray($data)
{
    echo '$cookie = [';
    foreach ($data as $k => $v) {
        echo '<br />&nbsp;&nbsp;&nbsp;&nbsp;'."'$v',";
    }
    echo '<br />];';
}

function cookieArray($cookie)
{
    echo drawArray(explode('; ', $cookie));
}

$avito = new Avito;

if ($_POST['action'] == 'parseCard') {

    $avito->parseCard($_POST['url'], $row);

    echo '<pre>'; print_r($row); echo '</pre>';
    exit;
}


if ($_POST['action'] == 'parsePhone') {

    // Подготавливаем входные параметры
    $url = $_POST['url'];
    preg_match('~_(\d+)$~i', $url, $a);
    $id = $a[1];
    $cardContent = $avito->curl->load($url, 0);

    // Определяем phoneUrl
    $avitoContact = new AvitoContact;
    $phoneUrl = $avitoContact->getPhoneUrl($cardContent, $id);

    // Грузим картинку по phoneUrl
    $imgContent = $avito->curl->load($phoneUrl, 0);

    // Разбираем ее и сохраняем в файл
    $img = json_decode($imgContent);
    $avitoContact->saveInFile($img->image64, 'phone.png');

    // Распознаем файл
    $result = $avitoContact->recognize('phone.png');

    echo '<p>URL: '.$phoneUrl.'</p>';

    echo '<p><img src="'.$img->image64.'" alt="" /></p>';

    echo '<p><a href="#" onclick="jQuery(\'#debugOutput\').slideToggle(); return false;">Цветовая схема</a></p>';
    echo '<div id="debugOutput" style="display:none;">'.$avitoContact->debugOutput.'</div>';

    if ($result) {
        echo '<h2 class="text-success">Результат - '.$result.'</h2>';
    } else {
        echo '<h2 class="text-danger">Ничего не получилось</h2>';
    }
    exit;
}


/*
// преобразовать строку кук в массив
$cookies = 'сюда копировать куки-строку с браузера';
cookieArray($cookies);
exit;
*/

/*
// load avito test
$content = $avito->curl->load('https://www.avito.ru/', 0);
$avito->curl->debug($content);
exit;
*/

/*
// proxy test
$content = $avito->curl->load('https://avito.ru/', 0, [
    CURLOPT_PROXY => '176.58.123.125',
    CURLOPT_PROXYPORT => 3128,
    // CURLPROXY_SOCKS4, CURLPROXY_SOCKS5, CURLPROXY_SOCKS4A или CURLPROXY_SOCKS5_HOSTNAME
    CURLOPT_PROXYTYPE => CURLPROXY_HTTP,
    CURLOPT_TIMEOUT => 5
]);
$avito->curl->debug($content);
exit;
*/


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
    body {
        background-repeat: no-repeat;
        background-position: 0 0;
        background-size: cover;
        background-image: url(images/avitus5.jpg);
        background-blend-mode: normal;
    }
    h1 {margin:20px 0 15px; font-size:24px;}
    .avito-form > div {margin-right:10px;}
    .form-inline .form-group {margin-bottom:10px;}
    /* лоадер на css */
    #loader {
        border: 5px solid #f3f3f3; /* Light grey */
        border-top: 5px solid #3498db; /* Blue */
        border-radius: 50%;
        width: 52px;
        height: 52px;
        animation: spin 2s linear infinite;
        position:absolute;
        top:7px; left:10px;
        display:none;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    </style>
  </head>
  <body>

<div id="loader"></div>


<div class="container-fluid">

    <h1>Парсер Авито</h1>

    <form class="form-inline avito-form" method="post">
      <div class="form-group input-group">
        <span class="input-group-addon"><a href="<?=$url?>" target="_blank">URL</a></span>
        <input type="text" class="form-control" name="url" value="<?=$url?>" style="width:400px;">
      </div>

      <?php $options = $avito->getCategoriesOptions($_POST['cat-url']) ?>
      <div class="form-group">
        <select name="cat-url" class="form-control">
        <option value="">Использовать url</option>
        <?php echo $options; ?>
        </select>
      </div>


      <div class="form-group">
        <label>Показать как</label>
        <?php
        $showAs = [
            'table' => 'Таблицей',
            'print_r' => 'print_r',
            'excel' => 'Excel',
            'json' => 'JSON'
        ];
        ?>
        <select name="display" class="form-control">
            <?php
            foreach ($showAs as $k => $v) {
                $add = '';
                if ($_POST['display'] == $k) {
                	$add = ' selected';
                }
                echo '<option'.$add.' value="'.$k.'">'.$v.'</option>';
            }
            ?>
        </select>
      </div>
      <div class="form-group">
        <label>Загружать до</label>
        <input type="number" class="form-control" name="maxPage" value="<?=POST('maxPage', 1)?>" style="width:70px;">
      </div>
      <div class="form-group">
        <label>Sleep</label>
        <input type="text" class="form-control" name="sleep_min" value="<?=POST('sleep_min', 2)?>" style="width:50px;">
        <input type="text" class="form-control" name="sleep_max" value="<?=POST('sleep_max', 5)?>" style="width:50px;">
      </div>
      <div class="checkbox">
        <label><input type="checkbox" <?php if ($_POST['load-card']) echo 'checked' ?> name="load-card" value="1"> Загружать карточку</label>
      </div>
      <div class="checkbox">
        <label><input type="checkbox" <?php if ($_POST['load-stat']) echo 'checked' ?> name="load-stat" value="1"> Загружать статистику</label>
      </div>
      <button type="submit" class="btn btn-default">Выполнить</button>
    </form>



<?php


$url = $_POST['cat-url'] ?: $_POST['url'];

if ($url) {

    $avito->loadCard = $_POST['load-card'];
    $avito->loadStat = $_POST['load-stat'];
    $avito->curl->sleepMin = $_POST['sleep_min'];
    $avito->curl->sleepMax = $_POST['sleep_max'];

    $data = $avito->parseAll($url, $fromPage=1, $_POST['maxPage']);

?>

<hr />


<div class="row">
    <div class="col-md-6">
<?php

    if ($_POST['display'] == 'print_r') {
    	echo '<pre>'; print_r($data); echo '</pre>';
    }

    if ($_POST['display'] == 'table') {
    	// echo '<pre>'; print_r($data); echo '</pre>';
        ?>

<table class="table table-condensed table-bordered table-hover" style="width:auto">
<tr>
    <th>Название</th>
    <th>Цена</th>
    <th>Год</th>
    <th>Дата</th>
    <th>&nbsp;</th>
</tr>
<?php
foreach ($data as $k => $row) {
    ?>
    <tr>
        <td><a href="<?=$row['url']?>" target="_blank"><?=$row['name']?></a></td>
        <td class="text-right"><?=number_format($row['price'], 0, ' ', ' ')?></td>
        <td><?=$row['year']?></td>
        <td><?=date('Y-m-d H:i:s', $row['date'])?></td>
        <td>
        <a href="#" data-url="<?=$row['url']?>" class="label label-info">load</a>
        <a href="#" data-phone="<?=$row['url']?>" class="label label-warning">разбор телефона</a>
        </td>
    </tr>
    <?php
}
?>
</table>

        <?php

?>
    </div>
    <div class="col-md-6" id="results">

    </div>
</div>
<?php

    }



} elseif ($_GET['action'] == 'contact') {
    $avitoContact = new AvitoContact;

    $imageScheme = $avitoContact->getImageScheme('phone.png', $_POST['columnFrom'], $_POST['columnTo']);

    ?>
    <h3>Разбор телефона</h3>

    <form method="post" class="form-inline">

        <div class="form-group">
            <label>Показать колонку с индекса</label>
            <input type="text" name="columnFrom" class="form-control" style="width:75px;" value="<?=$_POST['columnFrom']?>" />
            до
            <input type="text" name="columnTo" class="form-control" style="width:75px;" value="<?=$_POST['columnTo']?>" />
        </div>

        <input class="btn btn-info" type="submit" value="Показать">
    </form>

        <hr />

    <?php
    echo $avitoContact->debugOutput;

    if ($_POST['columnTo']) {
    	$textarea = $avitoContact->makeColumnData($imageScheme, $_POST['columnFrom'], $_POST['columnTo']);
        echo '<textarea style="width:100%; height:200px; white-space: nowrap; font-size: 12px;">'.$textarea.'</textarea>';
    }

    $phoneNumber = $avitoContact->recognizeByScheme($imageScheme);

    if ($avitoContact->error) {
        echo '<p class="alert alert-danger">'.$avitoContact->error.'</p>';
    }

    if ($phoneNumber) {
        $phoneNumber = 'Найдено значение - <b>'.$phoneNumber.'</b>';
    } else {
        $phoneNumber = 'Не найдено ни одного символа';
    }
    

    echo '<p class="badge" style="margin-top:10px; font-size:60px;">'.$phoneNumber.'</p><br /><br />';

} else {
    ?>
    <br />
    <div class="row">
        <div class="col-sm-12">
            <!-- <div class="jumbotron">
            <h1>Всем Привет!</h1>
            <p>Для начала поиска нажмите кнопку в форме</p>
            <p><a class="btn btn-primary btn-lg" href="https://www.youtube.com/channel/UC_OWLEdM9zNSF-JDUH3tsww/featured" role="button">Начать поиск</a></p>
            </div> -->
        </div>
    </div>
    <?php
}




?>

</div>


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>


    <script type="text/javascript">

    $(document).ready(function(){

        $(document).ajaxStart(function() {
            $('#loader').show();
        });
        $(document).ajaxStop(function() {
            $('#loader').hide();
        });

        $('[type="submit"]').click(function() {
            setTimeout(function(obj) {
                obj.disabled = true;
            }, 100, this);
        })

        $('[data-url]').click(function(e) {
            e.preventDefault()
            var url = $(this).data('url')
            $.post('', 'action=parseCard&url='+encodeURIComponent(url), function(data) {
                $('#results').html(data)
            });
        })

        $('[data-phone]').click(function(e) {
            e.preventDefault()
            var url = $(this).data('phone')
            $.post('', 'action=parsePhone&url='+encodeURIComponent(url), function(data) {
                $('#results').html(data)
            });
        })
    });
    </script>

  </body>
</html>
