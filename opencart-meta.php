<?php

function metax()
{

//var_dump(123); exit;

    function loadUrl($url, $expired=0) {
        if (empty($url)) {
            echo 'loadUrl: передан пустой url';
            return ;
        }
        $fileName = 'cash/'.md5($url).'.html';
        if ($expired) {
            if (!file_exists('cash')) mkdir('cash');
            if (file_exists($fileName)) {
                if ($expired > 10) {
                    if (filemtime($fileName) >= time() - $expired) {
                        return file_get_contents($fileName);
                    }
                } else {
                    return file_get_contents($fileName);
                }
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:43.0) Gecko/20100101 Firefox/43.0');
        $content = curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            echo 'Curl error ['.curl_errno($ch).']: '.$error;
        }
        curl_close($ch);
        if ($content != '') {
            if (!file_exists('cash')) mkdir('cash');
            $f = fopen($fileName, 'w+');
            fwrite($f, $content);
            fclose($f);
        }
        return $content;
    }

    $content = 'https://urs-tuning.ru/hyundai/grand-santa-fe/reshetki-radiatora/	Решетки радиатора Grand Santa fe
https://urs-tuning.ru/kia/svetodiodnye-moduli-v-fary-mohave.html	Светодиодные модули в фары Mohave для Kia, производитель exLed | Купить запчасти KIA на Urs-tuning.ru
https://urs-tuning.ru/hyundai/obves-veloster-1159.html	Обвес Veloster для Hyundai, производитель Sequence X | Купить запчасти HYUNDAI на Urs-tuning.ru	ОБВЕС VELOSTER ДЛЯ HYUNDAI';

    $lines = explode("\n", $content);

    foreach ($lines as $key => $line) {
        if ($key > 10) {
        	break;
        }
        list($url, $title, $h1) = explode("\t", $line);

        $content = loadUrl($url, 86400);

        $where = [];
        if ($title) {
        	$where []= ' meta_title="'.$this->db->escape($title).'"';
        }
        if ($h1) {
        	$where []= ' meta_h1="'.$this->db->escape($h1).'"';
        }

        if (preg_match('~product-category-([\d_]+)~i', $content, $a)) {
            $id = explode('_', $a[1]);
            $id = array_pop($id);
            //echo '<br />category '.$id;
            $sql = 'update oc_category_description set '.implode(', ', $where).' '.$add.' where category_id='.$id;
            echo '<br />'.$sql;
             /*//$this->db->query($sql);
            echo '<br />'.$sql;
            continue;*/
        }

        if (preg_match('~"product-product-(\d+)"~i', $content, $a)) {
            //echo ' - PRODUCT '.$a[1];
            $id = $a[1];
            echo '<br />product '.$id;

            $sql = 'update oc_product_description set '.implode(',', $where).' '.$add.' where product_id='.$id;
            echo '<br />'.$sql;
            /*$this->db->query($sql);
            echo '<br />'.$sql;
            continue;*/
        }
    }


}
