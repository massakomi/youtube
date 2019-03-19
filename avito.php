<?php

include_once 'Class.Avito.php';
include_once 'Class.Curl.php';

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

$avito = new Avito;

if ($_POST['action'] == 'parseCard') {

    $avito->parseCard($_POST['url'], $row);

    echo '<pre>'; print_r($row); echo '</pre>';
    exit;
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
    .avito-form > div {margin-right:10px;}
    </style>

    <!-- лоадер на css -->
    <style type="text/css">
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
      <div class="form-group">
        <label><a href="<?=$url?>" target="_blank">URL</a></label>
        <input type="text" class="form-control" name="url" value="<?=$url?>" style="width:400px;">
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

if ($_POST['url']) {

    $avito->loadCard = $_POST['load-card'];
    $avito->loadStat = $_POST['load-stat'];
    $avito->curl->sleepMin = $_POST['sleep_min'];
    $avito->curl->sleepMax = $_POST['sleep_max'];

    $data = $avito->parseAll($_POST['url'], $fromPage=1, $_POST['maxPage']);

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
        <td><a href="#" data-url="<?=$row['url']?>">load</a></td>
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
        $('[data-url]').click(function() {
            var url = $(this).data('url')
            $.post('', 'action=parseCard&url='+encodeURIComponent(url), function(data) {
                $('#results').html(data)
            });
        })
    });
    </script>

  </body>
</html>
