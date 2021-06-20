<?php
require "vendor/autoload.php";
require "btok.txt";

$BUCKET=1900;

use Abraham\TwitterOAuth\TwitterOAuth;

// Create db connection
$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";


$useTwi = false;
if($useTwi){

// create twitter connection
$twi = new TwitterOAuth($APIKEY, $APISECRET, $accesstoken, $accesssecret);
$content = $twi->get("account/verify_credentials");


$rootUsers = array("georgloesel","prefec2","kkklawitter","halleverkehrt");
$rootNames = array("_tolle-tausen.de", "tolle-tausen","tausen.de");

$lists = array();

foreach ($rootUsers as $mainUser){
  $q=['screen_name' => $mainUser, "reverse"=>true];
  $resp = $twi->get("lists/list", $q);
  if(! $resp ){
    print("error for $mainUser \n");
    continue;
  }
  foreach($resp as $l){
    foreach($rootNames as $n){
      if(strpos($l->name, $n) !== false){
        print("\n" . $mainUser . " " . $l->name . "\n");
        array_push($lists, $l->id);
      }
    }
  }
}

$lists = array_unique($lists);

print_r($lists);
flush();

$loopcount=1;
$next_cursor = -1;
$insert = $conn->prepare("replace INTO tausende_user (from_list, tweets, name, screen_name, description, followers_count, friends_count, location_name, since) values (?,?,?,?,?,?,?,?,?)");
if(! $insert){
  die("Error: " . $conn->error);
}
$insert->bind_param("sisssiiss",$list, $tweets, $name, $screen_name, $description, $followers_count, $friends_count, $location, $since);

foreach($lists as $list){
  do{
    $q=['count'=>$BUCKET, 'list_id'=>$list, 'cursor' => $next_cursor, 'include_entities'=>false,'skip_status'=>true];
    $req = "lists/members";
    $resp = $twi->get($req, $q);

    fwrite(STDERR,$loopcount . " " . $next_cursor . "\n");
    fflush(STDERR);
    $loopcount++;
    if(! $resp || ! $resp->users){
      fwrite(STDERR,"resp = " . print_r($resp));
      fflush(STDERR);
      sleep(70);
      $resp = $twi->get($req, $q);
      if(! $resp || ! $resp->users){
        sleep(70);
        $resp = $twi->get($req, $q);
      }
    }
    $users=$resp->users;
    $next_cursor=$resp->next_cursor_str;


    fwrite(STDERR,"writing users!");
    fflush(STDERR);
    foreach($users as $u){
      print_r($u);
      $tweets=$u->statuses_count;
      $name=$u->name;
      $screen_name=$u->screen_name;
      $description=$u->description;
      $followers_count=$u->followers_count;
      $friends_count = $u->friends_count;
      $location=$u->location;
      $date = strtotime($u->created_at);
      $since=(date("Y-m-d H:i:d",$date));
      if (!$insert->execute()) {
        echo "Execute failed: (" . $insert->errno . ") " . $insert->error;
      }
    }
    if($loopcount > 20){
      break;
    }
    if($loopcount > 2){
      sleep(70);
    }
  }
  while($next_cursor != 0);
}

}


$conn->query("update tausende_user set location_name='ungesagt' where location_name = ''");

$result=$conn->query("select id, location_name from tausende_user where latitude is null or longitude is null");
$update = $conn->prepare("update tausende_user set latitude=?, longitude=? where location_name=?");

if(! $update){
  die("Error: " . $conn->error);
}
$update->bind_param("sss",$lat, $lon, $loc);

$found = [];

if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    $loc = $row['location_name'];
    if(in_array($loc, $found)){
      continue;
    }
    if($loc > "" && $loc != 'ungesagt' && $loc != 'Earth'){
      $where = $row['location_name'];
      $url = $locationurl;
      $url .= "&q=" . $where;
      try{
        $ans = file_get_contents($url);
        # print($ans);
        $parsed = json_decode($ans);
        if(is_array($parsed) && count($parsed)> 0){
          $lat = $parsed[0]->lat;
          $lon = $parsed[0]->lon;
          if($lat < 49 || $lat > 53 || $lon < 7 || $lon > 14){
            continue;
          }
          print("name = '$loc', lat = '$lat', lon='$lon'\n");
          if (!$update->execute()) {
            echo "Execute failed: (" . $update->errno . ") " . $update->error;
          }
          $found[] = $loc;
          sleep(10);
        }
      }
      catch(exception $e){
        print_r($e);
      }
    }
  }
}

$conn->query("update tausende_user set latitude=49.5+2*rand(), longitude = 7.5+6.5*rand(), location_name=concat(location_name, '[geraten]') where latitude is null");
$conn->close();

?>
