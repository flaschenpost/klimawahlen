<?php
require "vendor/autoload.php";
require "btok.txt";

$BUCKET=190;

use Abraham\TwitterOAuth\TwitterOAuth;

// create twitter connection
$twi = new TwitterOAuth($APIKEY, $APISECRET, $accesstoken, $accesssecret);
$content = $twi->get("account/verify_credentials");


// Create db connection
$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
$mainuser="KlimaVorAcht";
# $mainuser="WolffHch";

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";


$next_cursor="-1";

$loopcount=1;
do{
  fwrite(STDERR,$loopcount . " " . $next_cursor . "\n");
  fflush(STDERR);
  $loopcount++;
  $q=['screen_name' => $mainuser,'cursor' => $next_cursor, 'include_user_entities'=>false, 'count'=>$BUCKET];
  $resp = $twi->get("followers/list", $q);
  if(! $resp || ! $resp->users){
    sleep(70);
    $resp = $twi->get("followers/list", $q);
    print_r($resp);
    if(! $resp || ! $resp->users){
      sleep(70);
      $resp = $twi->get("followers/list", $q);
      print_r($resp);
    }
  }
  $users=$resp->users;
  $next_cursor=$resp->next_cursor_str;

  $insert = $conn->prepare("replace INTO follower (following_root, tweets, name, screen_name, description, followers_count, friends_count, since) values (?,?,?,?,?,?,?,?)");
  $insert->bind_param("sisssiis",$mainuser, $tweets, $name, $screen_name, $description, $followers_count, $friends_count, $since);

  foreach($users as $u){
    $tweets=$u->statuses_count;
    $name=$u->name;
    $screen_name=$u->screen_name;
    $description=$u->description;
    $followers_count=$u->followers_count;
    $friends_count = $u->friends_count;
    $date = strtotime($u->created_at);
    $since=(date("Y-m-d H:i:d",$date));
    if (!$insert->execute()) {
      echo "Execute failed: (" . $insert->errno . ") " . $insert->error;
    }
  }
  sleep(70);
}
while($next_cursor != 0);

$conn->close();

//for($statuses

// $statues = $twi->post("statuses/update", ["status" => "Gar nicht so schwer, was mit der #TwitterAPI zu schreiben. Vielleicht wirds ja noch was mit ner kleinen Sammelseite für #Klimawahlen."]);
?>
