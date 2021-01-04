<?php


require __DIR__.'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

$config = include __DIR__ .'/config.php';
$episode_file = $config['episode_file'];
$feed_url = $config['feed_url'];

$client = new Client([    
    'allow_redirects' => true,
    'connect_timeout' => 20,
    'read_timeout' => 20,
    'timeout' => 40,
    'cookies' => true,
    //'debug' => true,
    //'proxy' => 'http://127.0.0.1:8123',
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.103 Safari/537.36 ', //
    ],
]);

try {
    
    if( !$config['debug'] ) {
        $response = $client->get($feed_url);
        $feed = (string) $response->getBody();        
    }else{
        $feed = file_get_contents('pan.test.xml');
    }
    $reader = new Sabre\Xml\Reader();
    $reader->xml($feed);
    $result = $reader->parse();
    
    $latest_item = $result['value'][0]['value'][13]['value'];
    
    $latest = array();
    $latest['episode'] = $latest_item[1]['value'];
    
    if( !file_exists($episode_file) || intval(file_get_contents($episode_file)) <= $latest['episode']) {

        $latest['title'] = $latest_item[0]['value'];    
        $latest['name'] = $latest_item[2]['value'];
        $latest['link'] = $latest_item[4]['value'];
        $latest['pubDate'] = $latest_item[7]['value'];
        $latest['enclosure'] = $latest_item[8]['attributes']['url'];

        $latest['update'] = sprintf('内核恐慌更新了%d期:《%s》',$latest['episode'],$latest['name']);

        $bark_url = $config['bark']['tpl'];
        foreach(array_keys($latest) as $key) {
            $bark_url = str_replace('{'.$key.'}',urlencode($latest[$key]),$bark_url);
        }        
        $bark_res = $client->get($bark_url);

        file_put_contents($config['episode_file'],$latest['episode']);

    }

} catch (RequestException $e) {

    echo $e->getRequest();
    if ($e->hasResponse()) {
        echo $e->getResponse();
    }
    
}

exit;



