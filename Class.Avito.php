<?php


// Авито класс
class Avito {

    public $loadCard;
    public $loadStat;

    function __construct() {
        $this->curl = new Curl;
    }

    function getDate($dateString)
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

    function parseAll($url, $fromPage=1, $maxPage=false)
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

            $data = $this->parsePage($urlCurrent);

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

    function parsePage($url)
    {
        $content = $this->curl->load($url, $cash=3600);

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
            $row['date'] = $this->getDate($a[1]);

            preg_match('~data-item-url="([^"]+)"~i', $rowContent, $a);
            $row['url'] = 'https://www.avito.ru'.$a[1];

            preg_match('~data-item-id="(\d+)"~i', $rowContent, $a);
            $row['id'] = $a[1];

            preg_match('~>\s+([\d\s]+) км~i', $rowContent, $a);
            $row['probeg'] = preg_replace('~[^\d]~i', '', $a[1]);

            preg_match('~</p>\s+<p>([^<]+)</p>~i', $rowContent, $a);
            $row['region'] = $a[1];

            preg_match('~itemprop="price"\s*content="(\d+)"~i', $rowContent, $a);
            $row['price'] = $a[1];

            Log::get()->log($row['name']);

            if ($this->loadCard) {
            	$this->parseCard($row['url'], $row);
            }

            $data []= $row;
        }
        return $data;
    }

    function parseCard($url, &$row)
    {
        $cardContent = $this->curl->load($url, 86400);
        Log::get()->log(' card['.strlen($cardContent).']', 0);

        //var_dump(strlen($cardContent));

        // Извлекаем статистику
        $row['views-total'] = $row['views-today'] = 0;
        if (preg_match('~<a href="#" class="js-show-stat" data-config=\'\{ "type": "item", "url": "([^"]+)" \}\'>([^<]+)</a>~i', $cardContent, $a)) {
            $statValues = $a[2];
            if (preg_match('~(\d+)\s+\(\+(\d+)\)~i', $statValues, $b)) {
                $row['views-total'] = intval($b[1]);
                $row['views-today'] = intval($b[2]);
            } else {
                $row['views-total'] = intval($statValues);
            }

            if ($this->loadStat) {
                $statUrl = 'https://www.avito.ru'.$a[1];
                $statContent = $this->curl->load($statUrl, 86400);

                preg_match('~Дата подачи объявления: <strong>([^<]+)</strong>~i', $statContent, $a);
                $row['date-added'] = $a[1];
                Log::get()->log(' stat['.strlen($statContent).']['.$a[1].']', 0);

                $row['stat'] = [];
                if (preg_match('~data-chart=\'(.*?)\'~i', $statContent, $a)) {
                    $chart = json_decode($a[1], true);
                    foreach ($chart['columns'][0] as $k => $timestamp) {
                        if (!$k) {
                            continue;
                        }
                        $row['stat'][date('Y-m-d', $timestamp / 1000)] = $chart['columns'][1][$k];
                    }
                }
            }
        }

        preg_match('~<div class="item-description-text" itemprop="description">(.*?)</div>~is', $cardContent, $a);
        $row['text'] = $a[1];

        preg_match_all('~data-url="(//\d+.img.avito.st/1280[^"]+jpg)"~i', $cardContent, $a);
        $row['images'] = $a[1];

        preg_match_all('~<li class="item-params-list-item">\s*<span class="item-params-label">(.*?):\s+</span>(.*?)</li>~is', $cardContent, $a);
        $row['params'] = [];
        foreach ($a[1] as $k => $name) {
        	$row['params'][$name] = trim($a[2][$k]);
        }
    }

    function getCategoriesOptions($selected)
    {
        $categories = $this->getCategories();
        return $this->getOptions($categories, $selected);
    }

    function getOptions($categories, $selected='', $level=0)
    {
        if (!$categories) {
            return ;
        }
        $opts = '';
        foreach ($categories as $k => $v) {
            $value = 'https://www.avito.ru'.$v['url'];
            $add = '';
            if ($selected == $value) {
            	$add = ' selected';
            }
            $tab = str_repeat('-', $level * 4);
            // echo '<br />'.$tab.' <a href="'.$v['url'].'" target="_blank" data-categoryId="'.$v['categoryId'].'" data-params="id='.$v['id'].'&mcId='.$v['mcId'].'">'.$v['name'].'</a>';
            $opts .= '<option value="'.$value.'"'.$add.'>'.$tab.$v['name'].'</option>';
            if ($v['subs']) {
            	$opts .= $this->getOptions($v['subs'], $selected, $level + 1);
            }
        }
        return $opts;
    }

    function getCategories()
    {
        $content = $this->curl->load('https://www.avito.ru/', 86400*7);
        preg_match('~<div class=\'js-lateral-rubricator\' data-state=\'(.*?)\'>~is', $content, $a);
        $categories = json_decode($a[1], true)['categoryTree'][0]['subs'];
        return $categories;
    }


}