<?php



class Curl {

    function __construct()
    {
        $this->sleepMin = 2;
        $this->sleepMax = 5;
    }

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

    function load($url, $cash=0)
    {
        $this->fromCash = false;
        $cacheId = $url;
        if ($content = $this->getCache($cacheId, $cash)) {
            if (!strpos($content, 'Location: https://www.avito.ru/blocked')) {
                $this->fromCash = true;
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

        sleep(rand($this->sleepMin, $this->sleepMax));

        $file = fopen('log.txt', 'a+');
        fwrite($file, "\n".date('Y-m-d H:i:s').' '.$url);
        fclose($file);

        $this->setCache($content, $cacheId);
        return $content;
    }
}
