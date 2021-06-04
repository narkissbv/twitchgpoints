<?php
  header("Access-Control-Allow-Origin: *");
  header("Expires: on, 01 Jan 1970 00:00:00 GMT");
  header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
  header("Cache-Control: no-store, no-cache, must-revalidate");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache");

  include_once('db_connect.php');
  include_once('config.php');

  if (isset($_GET['user'])) {
    // set global variable
    $username = mysqli_real_escape_string($link, $_GET['user']);

    // create new record for new users
    $sql = "SELECT * FROM `points` WHERE username={$username}";
    $user_rs = mysqli_query($link, $sql);
    if (mysqli_num_rows($user_rs) == 0) {
      // create new user record
      $sql = "INSERT INTO `points` (username, amount) VALUES ('$username', $daily_reward)";
      mysqli_query($link, $sql);
    }    

    // check if user can claim daily points
    $sql = "SELECT `date`, amount, NOW() + INTERVAL 10 HOUR as now FROM `points` WHERE username='$username'";
    $date_rs = mysqli_query($link, $sql);
    $row = mysqli_fetch_assoc($date_rs);
    $date = substr($row['date'], 0, 10);
    $now = substr($row['now'], 0, 10);
    $amount = (int)$row['amount'];
    if ($date != $now) {
      $amount += $daily_reward;
      $sql = "UPDATE `points`
      SET amount={$amount}, `date`=NOW() + INTERVAL 10 HOUR
      WHERE username='{$username}'";
      mysqli_query($link, $sql);
      echo "*Daily $daily_reward Panda Points claimed!* ";
    }
  }

  if (isset($_GET['query'])) {
    $query = explode(" ", $_GET['query']);
    switch ($query[0]) {
      case 'bet':
        if ($username) {
          $sql = "SELECT amount FROM `points` WHERE username='{$username}'";
          $user_rs = mysqli_query($link, $sql);
          if (mysqli_num_rows($user_rs) > 0) {
            $row = mysqli_fetch_assoc($user_rs);
            $balance = (int)$row['amount'];
            $amount = $query[1];
            if ($amount == 'all') {
              $amount = $balance;
            }
            $bet = rand(0,1);
            if ($bet) {
              if ($amount > $balance) {
                $amount = $balance;
              }
              $balance += $amount;
              $sql = "UPDATE `points`
                      SET amount={$balance}
                      WHERE username='{$username}'";
              mysqli_query($link, $sql);
              echo "Congratulations! You won and gained {$amount} Panda Points! Your new balance is: {$balance}";
            } else {
              $balance -= $amount;
              if ($balance < 0) {
                $balance = 0;
              }
              $sql = "UPDATE `points`
                      SET amount={$balance}
                      WHERE username='{$username}'";
              mysqli_query($link, $sql);
              echo "You lost the bet! #feelsbadman :( Your new balance is: {$balance}";
            }
          } else {
            echo "missing params";
          }
        }
        break;
      case 'all':
        // SELECT ALL USERS
        $sql = "SELECT username, amount FROM `points` WHERE deleted = 0 ORDER BY amount DESC";
        $rs = mysqli_query($link, $sql);
        $response = array();
        while ($row = mysqli_fetch_assoc($rs)) {
          array_push($response, array(
            'name' => $row['username'],
            'points' => $row['amount']
          ));
        }
        if (isset($_GET['method']) && $_GET['method'] == 'json') {
          header('Content-Type: application/json');
          die(json_encode($response));
        } else {
          $size = count($response);
          $size = ($size < 10) ? $size : 10;
          echo "Top $size: ";
          for ($i = 0 ; $i < $size ; $i++) {
            echo "{$response[$i]['points']}: {$response[$i]['name']}, ";
          }
        }
        break;
      case 'set':
        $username = $query[1];
        $amount = $query[2];
        $token = $query[3];
        header('Content-Type: application/json');
        if ($username && $amount && $token == $set_token) {
          $sql = "UPDATE `points`
          SET amount={$amount}
          WHERE username='{$username}'";
          mysqli_query($link, $sql);
          die (json_encode(array(
            'success' => true,
            'message' => "{$username}'s balance is set to {$amount}"
          )));
        } else {
          die (json_encode(array(
            'success' => false,
            'message' => "Missing or invalid params"
          )));
        }
        break;
      case 'remove':
        if ($username) {
          $sql = "UPDATE `points`
          SET deleted=1
          WHERE username='{$username}'";
          mysqli_query($link, $sql);
          echo "Sorry to see you leave, {$username}..";
        } else {
          echo "missing params";
        }
        break;
      case 'join':
        if ($username) {
          $sql = "UPDATE `points`
          SET deleted=0
          WHERE username='{$username}'";
          mysqli_query($link, $sql);
          $sql = "SELECT amount from `points` WHERE username='{$username}'";
          $amount_rs = mysqli_query($link, $sql);
          $row = mysqli_fetch_assoc($amount_rs);
          $amount = $row['amount'];
          echo "Welcome, {$username}! Your balance is: {$amount}";
        } else {
          echo "missing params";
        }
        break;
      default:
        if (isset($_GET['user'])) {
          $username = mysqli_real_escape_string($link, $_GET['user']);
          $sql = "SELECT amount FROM `points` WHERE username='{$username}'";
          $user_rs = mysqli_query($link, $sql);
          if (mysqli_num_rows($user_rs) > 0) {
            $row = mysqli_fetch_assoc($user_rs);
            $amount = $row['amount'];
          } else {
            $sql = "INSERT INTO `points` (username, amount) VALUES ('{$username}', $daily_reward)";
            mysqli_query($link, $sql);
            $amount = $daily_reward;
          }
          $sql = "UPDATE `points`
                  SET amount=$amount
                  WHERE username='$username'";
          mysqli_query($link, $sql);
          die("{$username} has {$amount} Panda Points!");
        } else {
          die('missing params');
        }
        break;
    }
  }

?>