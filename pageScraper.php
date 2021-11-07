<?php
/**
* Important notice
* The benzinga page scraper detailed here is for educational purposes only and should not be used without consent from benzinga.
**/

function scrapeBenzinga($elements, $xpath) {
    foreach ($elements as $n)
    {
    	# title
        $headline = $xpath->query('div[@class="listing-long-content listing-long-content-image"]//h3', $n);
        if($headline->length > 0) {
            $headline = trim($headline[0]->nodeValue);
        } else {
            $headline = 'None';
        }

    	$symbolList = array();
    	$symbols = $xpath->query('span[@class="tags"]', $n);
        if($symbols->length > 0) {
    		$symbols = preg_split('/,/', trim($symbols[0]->nodeValue));
    		foreach($symbols as $sym) {
    			array_push($symbolList, $sym);
    		}
    	}

        # symbols found in the content are displayed differently and handled here
        $symbols = $xpath->query('div[@class="listing-long-content listing-long-content-image"]//div//p', $n);
        if($symbols->length > 0) {
            $symbols = trim($symbols[0]->nodeValue);
    		$pattern = '#\((NYSE|NASDAQ|Nasdaq|nyse|Nyse|nasdaq)(.*?)\)#';
    		preg_match_all($pattern, $symbols, $match);
    		foreach($match[0] as $sym) {
    			array_push($symbolList, preg_split('/:/', $sym)[1]);
    		}
        }

        # Obtaining symbols from two locations, ensure they are unique
    	$symbolList = array_unique($symbolList);

        # date
        $date = $xpath->query('div[@class="listing-long-content listing-long-content-image"]//span[@class="date"]', $n);
        if($date->length > 0) {
    		$date = rtrim(explode("|", $date[0]->nodeValue)[0]);
    		$date = DateTime::createFromFormat('Y M j, g:i' . (preg_match('/am/', $date) ? 'a' : 'A'), $date);
    		$date = $date->format('Y-m-d H:i:s');
        } else {
            $date = 'None';
        }

        # Article original url
        $link = $xpath->query('div[@class="listing-long-content listing-long-content-image"]//h3//a', $n);
        if($link->length > 0) {
            $link = 'https://www.benzinga.com'.$link[0]->getAttribute("href");
        } else {
            $link = 'None';
        }

        # summary
        $summary = $xpath->query('div[@class="listing-long-content listing-long-content-image"]//div//p', $n);
        if($summary->length > 0) {
            $summary = trim($summary[0]->nodeValue);
        } else {
            $summary = 'None';
        }

        echo '<tr>';
        echo '<td>'.$date.'</td>';
        echo '<td>'.implode(",", $symbolList).'</td>';
        echo '<td>'.$headline.'</td>';
        echo '<td>'.$summary.'</td>';
        echo '<td>'.$link.'</td>';
        echo '</tr>';
    }
}

require_once(dirname(__FILE__) .'/curl.php');

$url = 'https://www.benzinga.com/news';
$content = getCurl($url);

# Process html
$dom = new DOMDocument();
@$dom->loadHTML($content); // We use @ here to suppress a bunch of parsing errors that we shouldn't need to care about too much.
$xpath = new DOMXPath($dom);
$elements = $xpath->query('//*[contains(@class, "view-content")]//li');

# Error logging
if(count($elements) == 0) {
	errorLog($url);
} else {
    echo '<table>';
    scrapeBenzinga($elements, $xpath);
    echo '</table>';
}

?>
