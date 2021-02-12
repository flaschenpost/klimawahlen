<?php
require "vendor/autoload.php";
require "btok.txt";

use Abraham\TwitterOAuth\TwitterOAuth;

$connection = new TwitterOAuth($APIKEY, $APISECRET, $accesstoken, $accesssecret);
$content = $connection->get("account/verify_credentials");

$statuses = $connection->get("search/tweets", ["q" => "#NieWiederCDU", "count"=>3]);
print_r($statuses);

$statues = $connection->post("statuses/update", ["status" => "Gar nicht so schwer, was mit der #TwitterAPI zu schreiben. Vielleicht wirds ja noch was mit ner kleinen Sammelseite für #Klimawahlen."]);
?>
