<?php
function connectDB() {
  $dsn = "mysql:dbname=********;host=********";
  $db_user = "********";
  $db_password = "********"; //伏せてある
  $pdo = new PDO($dsn, $db_user, $db_password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

  return $pdo;
}

?>
