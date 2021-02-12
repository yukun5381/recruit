<?php
session_start();
if (!empty($_SESSION["id"])) {
  $_SESSION["id"] = "";
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>ログアウト</title>
</head>
<body>
  ログアウトしました。 <br>
  <a href="../../login">ログイン画面</a> <br>
  <!-- user/login/へ遷移 -->
</body>
</html>
