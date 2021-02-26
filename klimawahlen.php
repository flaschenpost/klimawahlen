<?php
require "vendor/autoload.php";
require "btok.txt";

$BUCKET=3;

use Abraham\TwitterOAuth\TwitterOAuth;

function check_or_create_user($conn, $userdat){
  $find = $conn->prepare("select * from tweep where id = ?");
  $find->bind_param("s",$userdat->id_str);
  $find->execute();
  $result = $find->get_result(); // get the mysqli result
  $user = $result->fetch_assoc(); // fetch data   
  if($user){
    return $userdat->id_str;
  }
  else{
    $insert = $conn->prepare("insert into tweep set id=?, is_blocked=0, handle=?, screenname=?");
    $insert->bind_param("sss",$userdat->id_str,$userdat->name, $userdat->screen_name);
    $insert->execute();
    return $userdat->id_str;
  }
}

function save_tweet($conn, $status){
}
// create twitter connection
$twi = new TwitterOAuth($APIKEY, $APISECRET, $accesstoken, $accesssecret);
$content = $twi->get("account/verify_credentials");

// Create db connection
$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";

$newcron= $conn->prepare("insert into cronrun set tweets=0");
$newcron->execute();
$cronid = $conn->insert_id;

$sql = "SELECT id, hashtag, lastid FROM hashtag";
$result = $conn->query($sql);

print_r( " numrows = " . $result->num_rows);
$refetch = [];

while($hashrow = $result->fetch_assoc()) {
  $q = ['q' => '#'.$hashrow['hashtag'], 'count'=>$BUCKET, 'tweet_mode'=>'extended'];
  if($hashrow['lastid']){
    $q['since_id'] = $hashrow['lastid'];
  }
  $statuses = $twi->get("search/tweets", $q);
  $insert = $conn->prepare("INSERT INTO tweet (id, tweep, created_at, maintext,cronrun,complete,hashtag, thread, parent, son) VALUES (?,?,?,?,?,?,?,?,?,?)");
  
  $insert->bind_param("ssssssssss",$id_str, $userid, $created_at, $text, $cronid, $fulltext, $hashrow['id'], $thread, $reply_to, $son);
  $cnt = 0;
  $son=0;
  foreach($statuses->statuses as $status){
    print("status $cnt\n");
    print_r($status);
    $cnt++;
    $id_str = $status->id_str;
    if(! $id_str){
      continue;
    }
    $created_at=$status->created_at;
    $text = $status->full_text;
    $fulltext = json_encode($status);
    $userid = check_or_create_user($conn, $status->user);
    $thread=$id_str;
    if($status->in_reply_to_status_id_str){
      $reply_to=$status->in_reply_to_status_id_str;
      $refetch[$reply_to] = ['main'=>$id_str, 'id'=>$id_str, 'hash'=>$hashrow['id'], 'reply_to' => $reply_to];
    }
    else{
      $reply_to=0;
    }
    $insert->execute();
  }
}



// threads
while ($refetch){
  $next_fetch = [];
  foreach(array_chunk($refetch, 100) as $chunk){
    $sep="";
    $ids="";
    foreach($chunk as $fetch){
      $ids .= $sep . $fetch['reply_to'];
      $sep=",";
    }
    print_r($ids);
    $statuses = $twi->get("statuses/lookup", ['id'=>$ids, 'tweet_mode'=>'extended']);
    print_r($statuses);

    $insert = $conn->prepare("INSERT INTO tweet (id, tweep, created_at, maintext,cronrun,complete,hashtag, thread, parent, son) VALUES (?,?,?,?,?,?,?,?,?,?)");

    $insert->bind_param("ssssssssss",$id_str, $userid, $created_at, $text, $cronid, $fulltext, $hashrow['id'], $thread, $reply_to, $son);

    $cnt = 0;
    foreach($statuses as $status){
      print("status $cnt\n");
      print_r($status);
      $cnt++;
      $id_str = $status->id_str;
      if(! $id_str){
        continue;
      }
      $son_data = $refetch[$id_str];

      $created_at=$status->created_at;
      $text = $status->full_text;
      $fulltext = json_encode($status);
      $userid = check_or_create_user($conn, $status->user);
      $thread=$son_data['main'];
      $son = $son_data['id'];
      $insert->execute();
      if($status->in_reply_to_status_id_str){
        $reply_to=$status->in_reply_to_status_id_str;
        $next_fetch[$reply_to] = ['main'=>$son_data['main'], 'id'=>$id_str, 'hash'=>$hashrow['id'], 'reply_to' => $reply_to];
      }
    }
    print_r($statuses);
  }
  $refetch=$next_fetch;
}

$conn->close();

//for($statuses

// $statues = $twi->post("statuses/update", ["status" => "Gar nicht so schwer, was mit der #TwitterAPI zu schreiben. Vielleicht wirds ja noch was mit ner kleinen Sammelseite für #Klimawahlen."]);
?>
