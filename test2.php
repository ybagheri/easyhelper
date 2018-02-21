<?php
require ("vendor".DIRECTORY_SEPARATOR."autoload.php");
use Symfony\Component\DomCrawler\Crawler;
$html=file_get_contents('https://core.telegram.org/bots/api');
$crawler = new Crawler($html);
// /table/tbody/tr
$Parameters=[];
$Type=[];
$Required=[];
$Description=[];
$Fields=[];
$counter=-1;
$innerCounter=-1;
$blnMethod=false;
$blnType=false;
$blnContinue=true;
$nodeValues = $crawler->filterXPath('//body/div/div/div/div/div/*')->each(function (Crawler $node, $i)use(&$params,&$counter,&$innerCounter,&$blnMethod,&$blnType,&$blnContinue) {
//    echo 'outer: '.$node->nodeName().PHP_EOL;
    switch (strtolower($node->nodeName())) {
        case 'table':
            $node->filterXPath('//tbody/tr')->each(
                function (Crawler $node, $i)use(&$params,&$counter,&$innerCounter,&$blnMethod,&$blnType,&$blnContinue) {
                    if($node->children()->first()->text() == 'Parameters' ){
                        $blnMethod=true;
                        $blnType=false;
                        $blnContinue=false;
                    }elseif($node->children()->first()->text() == 'Field'){
                        $blnType=true;
                        $blnMethod=false;
                        $blnContinue=false;
                    }
                    if($blnContinue && $innerCounter >-1){
                        $node->children()->each(function (Crawler $innterNode, $i)use(&$params,&$counter,&$innerCounter, &$Parameters,&$Fields,&$Type,&$Required,&$Description,&$blnMethod,&$blnType){
                            switch ($i){
                                case 0:
                                    if($blnMethod)
                                    $Parameters=$innterNode->text();
                                    elseif($blnType)
                                        $Fields=$innterNode->text();
                                    break;
                                case 1:
                                    $Type=$innterNode->text();
                                    break;
                                case 2:
                                    if($blnMethod)
                                        $Required=$innterNode->text();
                                    elseif($blnType)
                                        $Description=$innterNode->text();
                                    break;
                                case 3:
                                    $Description=$innterNode->text();
                                    break;
                            }
                        });
//            $params[] = ['Parameters' => $Parameters, 'Type' => $Type, 'Required' => $Required, 'Description' => $Description];
//                        echo  'counter: '.$counter.'   para: '.$Parameters.'   field '.$Fields.PHP_EOL;
                        if($blnMethod){
//                            var_dump($Parameters);
                            $params[$counter]['Parameters'][$innerCounter] ['Parameters'] = $Parameters;
                            $params[$counter]['Parameters'][$innerCounter]  ['Type'] = $Type;
                            $params[$counter] ['Parameters'][$innerCounter] ['Required'] = $Required;
                            $params[$counter] ['Parameters'] [$innerCounter]['Description'] = $Description;
//                        file_put_contents('tel.json',json_encode($params[$counter],true),FILE_APPEND);
                            $innerCounter++;
                        } elseif($blnType){
                            $params[$counter]['Field'][$innerCounter] ['Field'] = $Fields;
                            $params[$counter]['Field'][$innerCounter]  ['Type'] = $Type;
                            $params[$counter] ['Field'] [$innerCounter]['Description'] = $Description;
//                        file_put_contents('tel.json',json_encode($params[$counter],true),FILE_APPEND);
                            $innerCounter++;
                        }
                        $Fields=[];
                        $Parameters=[];
                        $Type=[];
                        $Required=[];
                        $Description=[];
                    }
                    $blnContinue=true;
                });
            break;
        case 'h4':
            $Method=$node->text();
//            echo 'method: '.$Method.' strposMethod : '.var_dump(strpos($Method,' ')).PHP_EOL;
            if(strpos($Method,' ')===false){
                $counter++;
                $innerCounter=0;
//                echo  'counter: '.$counter.PHP_EOL;
                $params[$counter]['Method']= $Method ;
//                echo ' i: '.$i.' '. $Method.PHP_EOL;
            }
            break;
    }
//    $node->each(function (Crawler $node, $i)use(&$value) {
//
//        echo 'inner: '. $node->nodeName().PHP_EOL;
//
//    });
});
foreach($params as &$p){
    if(isset($p['Field'])){
        $p['Type']= $p['Method'];
        unset($p['Method']);
    }
}
file_put_contents('tel.json',json_encode($params,true));
$method=[];
$type=[];
foreach($params as $p){
    if(isset($p['Method'])){
        $method[]=$p;
    }elseif(isset($p['Type'])){
        $type[]=$p;
    }
}
file_put_contents('method.json',json_encode($method,true));
file_put_contents('type.json',json_encode($type,true));