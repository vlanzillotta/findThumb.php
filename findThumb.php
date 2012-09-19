<?php

if($_GET["with_debug"] == 1){
    $DEBUG = 1;
    echo "Debugging <br />========================<br />";
}else{
    header('content-type: image/gif'); 
}

$fileName = ereg_replace("[^A-Za-z0-9]", "", $_GET["url"]);
$fileLocation = "cache/findThumb/";


if(!$DEBUG){
    if(file_exists($fileLocation.$fileName)){
        echo file_get_contents($fileLocation.$fileName);
        exit;
    }
}


$url = $_GET["url"];

$doc = new DOMDocument();
@$doc->loadHTMLFile($url);
$winner = array();






//look for OG:image values before looking through the entire page
$metaTags = $doc->getElementsByTagName("meta");
foreach ($metaTags as $metaTag) {
    if($metaTag->getAttribute('property') == "og:image" && $metaTag->getAttribute('content') != ""){
        $winner["source"] = $metaTag->getAttribute('content');
    }
}


if(!isset($winner["source"])){

    $ImageTags = $doc->getElementsByTagName("img");
    $imageSizes = array();
    foreach ($ImageTags as $tag) {
            $imageSrc =  $tag->getAttribute('src');
            if($DEBUG){echo $imageSrc;echo"<br />";}
            $url_parts = parse_url($url);

            if($DEBUG){echo $imageSrc;}
            if(!stristr($imageSrc, $url_parts["scheme"].":")){
                //adding scheme if it is missing
                $imageSrc = $url_parts["scheme"].":".$imageSrc;
            }

            $size = '';
            @$size = getimagesize($imageSrc);
            if($size){
                $imageSizes[$imageSrc] = $size;
            }
    }
    

    if($DEBUG){print_r($imageSizes);}
    


    foreach ($imageSizes as $ImageSource => $values) {
        $pixels = $values[0] * $values[1];
        $pixels = $pixels + runThroughExceptions($ImageSource, $values);

        if($pixels > $winner["pixels"] ){
            $winner["pixels"] = $pixels;
            $winner["source"] = $ImageSource;
        }
    }


}

if($DEBUG){
    print_r($winner);
}

//cache the image
$h = fopen($fileLocation.$fileName, "w");
fwrite($h, file_get_contents($winner["source"]));
fclose($h);

echo file_get_contents($winner["source"]);

function runThroughExceptions($url, $values){
    //check the url and image properties to try and stop adds or other garbage from showing up
    $returnVal = 0;

    $exWords = array();
    //word => bonus



    $exWords["advert"] = (-1000000);
    $exWords["logo"] = (10000);


    foreach($exWords as $term => $bonus){
        if(stristr($url, $term)){
            $returnVal += $bonus;
        }
    }

    return $returnVal;
}



?>