<?php


/*

        $avito = new AvitoContact;
        $contactUrl = $avito->getAvitoContact($cardContent, $row['id']);

        //var_dump($contact); exit;

        $contactData = curlLoad($contactUrl);
        echo '<pre>'.htmlspecialchars($contactData).'</pre>';


*/

class AvitoContact {

     /**
     * $offxetX - режем картинку с верху (т.е. начинаем читать с этого пикселя сверху)
     * $offxetY - режем снизу, т.к. читаем не до полной высоты картинки
     * $offxetL - режем картинку по ширине с начала (т.е. на чинаем читать с этого пикселя)
     * $white - код цвета пропуска, фон
     */
    function __construct()
    {
        $this->offxetX = 13;
        $this->offxetY = 6;
        $this->whitePixel = 2147483647;
        $this->offxetL = 2;
    }



    // 1 этап. Найти урл картинки и сохранить ее в файл.

    /**
     * Получить контент картинки контакта по контенту объявления
     */
    function getPhoneUrl($content, $id, $referrer='', $proxy='', &$contact='')
    {

        $regexp = '~avito.item.phone = \'(.*?)\';~i';
        preg_match($regexp, $content, $a);
        $pkey = self::phoneDemixer($a[1], $id);
        if (empty($pkey)) {
            $this->error('Ошибка получения контакта авито, пустой pkey (regexp '.htmlspecialchars($regexp).')');
            return ;
        }
        if (strpos($content, $id) === false) {
            $this->error('Ошибка получения контакта авито, в контенте не найден предложенный ID объявления ('.$id.')');
            return ;
        }
        $contact = 'https://www.avito.ru/items/phone/'.$id.'?pkey='.$pkey.'&vsrc=r';
        return $contact;
    }

    /**
     * Особый авито код демиксер для использования в загрузке контакта
     */
    private function phoneDemixer($key, $id)
    {
        preg_match_all('~[0-9a-f]+~i', $key, $pre);
        $pre = $pre[0];

        if ($id % 2 === 0) {
        	$pre = array_reverse($pre);
        }
        $mixed = implode('', $pre);
        $s = strlen($mixed);
        $r = '';
        for ($k = 0; $k < $s; ++$k) {
            if ($k % 3 === 0) {
                $r .= substr($mixed, $k, 1);
            }
        }
        return $r;
    }

    /**
     * Сохраняет контент base64 картинки в файл
     */
    function saveInFile($image, $filename)
    {
        $image = explode(',', $image)[1];
        $a = fopen($filename, 'wb');
        fwrite($a, base64_decode($image));
        fclose($a);
    }



    // 2 этап. Распознавание!

    /**
     * Распознать файл и получить номер
     */
    function recognize($image)
    {

        $imageScheme = $this->getImageScheme($image);
        //echo '<pre>'; print_r($imageScheme); echo '</pre>'; exit;
        $phoneNumber = $this->recognizeByScheme($imageScheme);

        return $phoneNumber;
    }

    /**
     * Собственно проходим по изображению и собираем в $data его схему - 1 и 0.
     */
    function getImageScheme($image, $columnFrom=false, $columnTo=false)
    {

        $size = getimagesize($image);
        if (!$size) {
            $this->error('Ошибка разбора картинки '.$image.' - это не изображение?');
            return ;
        }
        $img = strpos($image, 'png') ? imagecreatefrompng($image) : imagecreatefromjpeg($image);

        $w = $size[0];//x
        $h = $size[1];//y

        $data = array();
        $dataColumn = array();

        $this->showall = 0;
        $columnIndex = 0;
        $this->rows = '';
        $this->colorStat = array();
        for($x = 0; $x < $w; $x ++) {
            if ($x < $this->offxetL) {
                continue;
            }
            // $data это основной массив в который сохраняем все 0 и 1 найденных цветов пикселей в колонке
            $dataColumn = array();
            $foundedOneFilled = 0;
            $width = -$this->offxetX + $h - $this->offxetY;
            //e/cho '<br />'.$width;
        	for ($y = $this->offxetX; $y < ($h - $this->offxetY); $y++){
                // запись в масив каждой точки ее значения
                $pixel = imagecolorat($img, $x, $y);
                //if ($this->showall) echo ' '.$pix;
                $this->colorStat [$pix]++;
                if ($pixel >= $this->whitePixel) {
                    $dataColumn []= 0;
                } else {
                    $dataColumn []= 1;
                    $foundedOneFilled = 1;
                }
                // белый фон записываем как 0, все остальные пиксели как 1
                /*if ($d > 50) {
                	break;
                }*/
        	}

            // пропускаем черточку
            if (array_sum($dataColumn) == 4 && $dataColumn[18].$dataColumn[19].$dataColumn[20].$dataColumn[21] == '1111') {
                continue;
            }

            // Добавляем колонку только если нашли хотя бы 1 заполненную ячейку не белого цвета
            if ($foundedOneFilled == 1) {
                $data []= $dataColumn;
                if ($columnIndex >= $columnFrom && (!$columnTo || $columnIndex <= $columnTo)) {
                    $t = 0;
                    // для наглядности выводим значения полученного массива в браузер
                    foreach($dataColumn as $key => $r) {
                        $t ++;
                        $this->rows .= '<span title="'.$columnIndex.'" style="color:'.(!$r ? 'green' : 'red; background-color:blue;').'">'.$r.'</span>'."<br/>";
                        if ($t == $width) {
                            $this->rows .= '</td><td>';
                            $t = 0;
                        }
                    }
                }
                $columnIndex ++;
            }
        }

        // echo '<pre>'; print_r($data); echo '</pre>';

        // Сорфимровать отладочную таблицу для карты
        if (!$this->rows) {
            $this->debugOutput = 'Нет строк';
        } else {
            $this->debugOutput .= '<table style="margin-bottom:20px;"><tr><td>';
            $this->debugOutput .= $this->rows;
            $this->debugOutput .= '</td></tr></table> ';
        }

        return $data;
    }


    /**
     * Получаем маску распознавания
     */
    function getMask()
    {
        $maskFile = 'avito-mask.php';
        if (!file_exists($maskFile)) {
            $this->error('Не существует файла маски '.$maskFile);
            return ;
        }

        include $maskFile;
        if (!is_array($mask)) {
            $this->error('Это не маска ('.$maskFile.')');
            return ;
        }
        return $mask;
    }

    // Сохранение колонок для распознавания в режиме отладки
    function makeColumnData($imageScheme, $columnFrom, $columnTo)
    {
        $index = 0;
        $textarea = '';
        foreach ($imageScheme as $columnIndex => $column) {
            if ($columnIndex >= $columnFrom && $columnIndex <= $columnTo) {
                foreach ($column as $k => $v) {
                    $textarea .= $index." => '$v', ";
                    $index ++;
                }
                $textarea .= "\n";
            }
        }
        return $textarea;
    }


    function recognizeByScheme($imageScheme)
    {

        $mask = $this->getMask();
        if (!$mask) {
            return ;
        }

        // Допуск похожести
        $dopusk = 3;
        $phoneNumber = '';
        $columnsSet = array();

        //$process = '<h2>Процесс распознования:</h2>';


/*
        echo '<br /><b>Маски и количества колонок у них:</b>';
        foreach ($mask as $k => $v) {
            echo '<br />'.$k.' - '.count($v).'';
        }
        echo '<hr />';
*/

        $debug = 0;

        // Проходим по каждому столбцу изображения. Аккумулируем его в $columnsSet - там собирается набор.
        // Для каждого прохода проверяем по каждой маске, совпадает ли набранный набор с какой-то маской. Если ок, то
        // посимвольно сверяем маску с набором. Если схожеть больше 3, то значит нашли. Обнуляем идем дальше.
        // Если $columnsSet достиг макс. предела в 70 (шире цифр пока нет) - то выходим. Значит здесь косяк.
        foreach ($imageScheme as $aindex => $column) {

            // Все колонки по очереди объединяем в набор колонок до тех пор пока либо найдем подходящую под него маску
            // либо выйдем за пределы ширины букв и завершим с ошибкой
        	foreach ($column as $it) {
        		$columnsSet [] = $it;
        	}


            if ($debug) {
            	echo '<br />Колонка '.$aindex;
            }

        	foreach ($mask as $key => $mk) {

                if ($debug) {
                    echo '<span style="color:#ccc"> - '.count($columnsSet).' == '.count($mk).' </span>';
                }

        		if (count($columnsSet) == count($mk)) {

                    if ($debug) {
            		    echo "<div> +++ $aindex / $key - проверяем совпадает ли набор с $key</div>";
                    }

                    // Сравниваем посимвольно массив маски с собранным массивом картинки
                    // Сколько символов совпадает?
                    $countEqual = 0;
        			foreach ($columnsSet as $i => $nit) {
        				if ($nit == $mk[$i]) {
                            $countEqual ++;
                        }
        			}

        			$cnm = count($columnsSet);

                    // Да, мы нашли эту цифру!
                    // Коичество либо полностью совпадает, либо находится в границах допустимого
        			if ($countEqual == count($mk) || ($countEqual > count($mk) - $dopusk && $countEqual < count($mk) + $dopusk)) {
                        $phoneNumber .= $key;
                        $columnsSet = array();
                        if ($debug) {
                            echo '<div>Нашли число <span style="color:red">'.$key.'</span>! Итого наш телефон уже такой: <b>'.$out.'</b></div>';
                            echo "<p> selected = $key with $countEqual (<b>$out</b>)</p>";
                        }
                    }

        		}

                if (count($columnsSet) > 900) {
                    $this->error = 'Достигли предела и не нашли подходящую маску для текущего набора символов (количество достигло '.count($columnsSet).')';
                    //echo '<pre>'; print_r($columnsSet); echo '</pre>';
                    //echo '<pre>'; print_r($mask[4]); echo '</pre>';
                    break 2;
                }
        	}
        }
        return $phoneNumber;
    }

    function error($text)
    {
        echo '<div class="alert alert-danger">'.$text.'</div>';
    }
}
