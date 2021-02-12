<?php
function connectDB() {
  $dsn = "mysql:dbname=ettodakara_01;host=157.112.147.201";
  $db_user = "ettodakara_1";
  $db_password = "yuki0821";
  $pdo = new PDO($dsn, $db_user, $db_password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

  return $pdo;
}

?>
