<?php



class Curl {

    static $cashDir = 'cash';

    function __construct()
    {
        $this->sleepMin = 0;
        $this->sleepMax = 0;
    }

    function setCache($content, $cacheId)
    {
        if ($content == '') {
            return ;
        }
        $fileName = self::$cashDir.'/'.md5($cacheId);
        if (!file_exists(self::$cashDir)) {
            mkdir(self::$cashDir, 0777);
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
        $fileName = self::$cashDir.'/'.md5($cacheId);
        if (!file_exists($fileName)) {
            return false;
        }
        $time = time() - filemtime($fileName);
        if ($time > $cashExpired) {
            return false;
        }
        return file_get_contents($fileName);
    }

    function load($url, $cash=0, $opts=[])
    {
        $this->fromCash = false;
        $cacheId = $url;
        if ($content = $this->getCache($cacheId, $cash)) {
            if (!strpos($content, 'Location: https://www.avito.ru/blocked')) {
                $this->fromCash = true;
            	return $content;
            }
        }
        if (strpos($url, 'http') !== 0) {
            echo 'Неправильный урл запроса "'.$url.'"';
            return false;
        }

        $this->url = $url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $cookie = [
            'u=2fgvungc.qhxzcu.g1mrdbtn8s',
            /*'_ym_uid=1554073340974296533',
            '_ym_d=1554073340',
            '_ga=GA1.2.814288055.1554073340',
            '_fbp=fb.1.1554073340437.824272448',
            'buyer_tooltip_location=0',
            'crto_uid=253dda0ab477a555420d3f405db1b3c7',
            '__gads=ID=74e89d17751b05a5:T=1554073430:S=ALNI_MbOKN2U3YrZqv9FNED47akb18Ha6A',
            'v=1554219350',
            'buyer_selected_search_radius0=200',
            'sx=H4sIAAAAAAACAwXB2wqAIAwA0H%2FZcw%2BaG4p%2FU0NmjBBZF0L8984ZUDSetXbdL8SkrzRJCa2ZQh7wQIbNQj%2Fc%2Bvm7qSGzMTcxEbUkygoLFMieCIOjSG7OH6TMtTtUAAAA',
            'dfp_group=59',
            'weborama-viewed=1',
            'abp=1',
            '_gid=GA1.2.1539975770.1554219347',
            '_ym_visorc_34241905=w',
            'f=5.0c4f4b6d233fb90636b4dd61b04726f147e1eada7172e06c47e1eada7172e06c47e1eada7172e06c47e1eada7172e06cb59320d6eb6303c1b59320d6eb6303c1b59320d6eb6303c147e1eada7172e06c8a38e2c5b3e08b898a38e2c5b3e08b890df103df0c26013a0df103df0c26013a2ebf3cb6fd35a0ac0df103df0c26013a8b1472fe2f9ba6b984dcacfe8ebe897bfa4d7ea84258c63d59c9621b2c0fa58f897baa7410138ead3de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe23de19da9ed218fe207b7a18108a6dcd6f8ee35c29834d631c9ba923b7b327da7e87a84e371fc60d21abab15028c5344d5e61d702b2ac73f71c754c08cff1b841be955198f1b1b5bd9b875d7b97b5db61daa659585eecd48d4938c41efda3055a8f1786dad6fd98129e82118971f2ed64956cdff3d4067aa5091ac58b0b2b290b3d84c5444fc776a33de19da9ed218fe23de19da9ed218fe2e5c2340ab6d15e6006adf30cf986c8a3263e604e6466ce4c',
            '_ym_isad=1',
            'rheftjdd=rheftjddVal',
            'anid=removed',
            'sessid=3ad5faaa9b1944f3ef0b88dcd9ecf5c2.1554219713',
            'buyer_location_id=648630',*/
        ];

        $headers = array(
            ':authority: www.avito.ru',
            ':method: GET',
            ':path: '.str_replace('https://www.avito.ru', '', $url),
            ':scheme: https',
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            //'accept-encoding: gzip, deflate, br',
            'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,de;q=0.6,vi;q=0.5',
            'cache-control: max-age=0',
            'cookie: '.implode('; ', $cookie),
            'upgrade-insecure-requests: 1',
            'referer: '.$url,
            'user-agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/'.rand(60,72).'.0.'.rand(1000,9999).'.121 Safari/537.36'
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($opts) {
            foreach ($opts as $k => $v) {
            	curl_setopt($ch, $k, $v);
            }
        }

        $content = curl_exec($ch);
        if (!$content) {
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                echo '<p style="color:red">Curl error: '.$error.'</p>';
            }
            $this->header = '';
            return false;
        }
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);


        $this->header = substr($content, 0, $headerSize);
        $content = substr($content, $headerSize);

        if (strpos($this->header, 'Location: https://www.avito.ru/blocked')) {
            echo '<h3>Заблокировали</h3>';
            echo '<pre>'.$this->header.'</pre>';
            exit;
        }

        if ($this->sleepMin > 0) {
        	sleep(rand($this->sleepMin, $this->sleepMax));
        }

        $file = fopen('log.txt', 'a+');
        fwrite($file, "\n".date('Y-m-d H:i:s').' '.$url);
        fclose($file);

        if (strlen($content) > 1000) {
        	$this->setCache($content, $cacheId);
        }
        return $content;
    }

    public function debug($content)
    {
        echo '<p><a href="'.$this->url.'" target="_blank">'.$this->url.'</a></p>';
        if ($this->header) {
        	echo '<pre class="text-warning">'.trim($this->header).'</pre>';
        } else {
            echo '<p class="text-danger">Пустая шапка</p>';
        }
        if (empty($content)) {
            echo '<p class="text-danger">Пустой контент</p>';
        } else {
            echo '<p class="text-success">Контент - '.strlen($content).' байт</p>';
        }
        if ($content) {
            $debug = self::$cashDir.'/debug.html';
            fwrite($a = fopen($debug, 'w+'), $content); fclose($a);
            echo '<iframe src="'.$debug.'" style="width:300px; height:200px; border:1px solid #ccc"></iframe>';
        }
    }
}
