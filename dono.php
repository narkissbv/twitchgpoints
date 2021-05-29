<?php
  header("Access-Control-Allow-Origin: *");
  header("Expires: on, 01 Jan 1970 00:00:00 GMT");
  header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
  header("Cache-Control: no-store, no-cache, must-revalidate");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache");

  include_once('db_connect.php');
  include_once('config.php');

  #{readapi.https://cindy.narxx.com/dono.php?user={user.name}&amount={amount}&type=dono}
  $username = mysqli_real_escape_string($link, $_GET['user']);
  $amount = mysqli_real_escape_string($link, $_GET['amount']);
  $type = mysqli_real_escape_string($link, $_GET['type']);
  $gain = '';
  if ($type == "dono") {
    // remove the $ from the dono string
    $gain = substr($amount,1);
    $gain = (float) $gain;
    $gain = (int) ($gain * 100);
  } else if ($type == 'bits') {
    $gain = (int) $amount;
  }
  $type = mysqli_real_escape_string($link, $_GET['type']);

  #echo "username: $username, amount: $amount, type: $type, currency: $currency";

  $sql = "SELECT * FROM `points` WHERE username='$username'";
  $user_rs = mysqli_query($link, $sql);
  if (mysqli_num_rows($user_rs) > 0) {
    $row = mysqli_fetch_assoc($user_rs);
    $new_amount = $row['amount'] + $gain;
  } else {
    // add daily points to new commers
    $sql = "INSERT INTO `points` (username, amount) VALUES ('$username', 0)";
    mysqli_query($link, $sql);
    $new_amount = $gain + $daily_reward;
  }
  $sql = "UPDATE `points`
    SET amount=$new_amount
    WHERE username='$username'";
  mysqli_query($link, $sql);
  if ($type === 'dono') {
    echo "$username tipped $amount, and gained $gain Panda Points BrokeBack";
  } else if ($type === 'bits') {
    echo "$username cheered $amount, and gained $gain Panda Points BrokeBack";
  }
?>
