<?php
session_start();
require("../db.php");
if (!empty($_SESSION["id"])) {
  $_SESSION["id"] = "";
}
//データベース情報
$pdo = connectDB();
/*
$dsn = "mysql:dbname=ettodakara_01;host=157.112.147.201";
$db_user = "ettodakara_1";
$db_password = "yuki0821";
$pdo = new PDO($dsn, $db_user, $db_password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
*/

//値の初期化
$mail = "";
$password = "";
$error = "";
if (!empty($_POST["mail"]) && !empty($_POST["password"])) {
  //$mailには入力メールアドレス、$passwordには入力したパスワードを保存
  $mail = $_POST["mail"];
  $password = $_POST["password"];

  $sql = $pdo -> prepare("SELECT * FROM users WHERE mail = :mail");
  $sql -> bindParam(":mail", $mail, PDO::PARAM_STR);
  $sql -> execute();
  $result = $sql -> fetch();

  //$mailと一致するメールアドレスのユーザー情報を検索
  if ($result["password"] == $password) {
    //検索したユーザー情報のうち、パスワードと$passwordが一致していればログインできる
    $_SESSION["id"] = $result["id"]; //ユーザーIDを保存
    header("location: ../main/");
  } else {
    $error = "メールアドレスまたはパスワードが違います。";
  }
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>ログイン</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js" integrity="sha384-LtrjvnR4Twt/qOuYxE721u19sVFLVSA4hf/rRt6PrZTmiPltdZcI7q7PXQBYTKyf" crossorigin="anonymous"></script>
<style type="text/css">

body {
  background-image: url("../../leaves-pattern.png");
}

.form-signin input[type="email"] {
margin-bottom: -1px;
border-bottom-right-radius: 0;
border-bottom-left-radius: 0;
}
.form-signin input[type="password"] {
margin-bottom: 10px;
border-top-left-radius: 0;
border-top-right-radius: 0;
}
.login{
  width: 100%;
  max-width: 330px;
  align: center;
}
</style>
</head>
<body>
  <header>
    <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
      <div class="col-8">
        <a class="text-white h3" href="">就活管理アプリ Rak</a>
      </div>
      <div class="col-4">
      </div>
    </nav>
  </header>

  <main class="mt-5 row">
    <div class="col-6">
      <div class="h1 m-2">
        <p>就活をより楽に、</p>
        <p>計画的に、</p>
      </div>
    </div>

    <div class="col-6">
      <form method="post" class="form-signin m-2 login">
        <h1 class="h3 mb-3 fw-normal">ログイン</h1>
        <p style="color: red;">
          <?php
          if (!empty($error)) {
            echo $error;
          }
          ?>
        </p>
        <input type="email" name="mail" id="inputEmail" class="form-control" placeholder="メールアドレス" required autofocus>
        <input type="password" name="password" id="inputPassword" class="form-control" placeholder="パスワード" required>
        <button class="w-100 btn btn-lg btn-primary" type="submit">ログイン</button>
        <p class="mx-2 my-0">メールアドレス：test@test.jp</p>
        <p class="mx-2 my-0">パスワード：test</p>
        <p class="mx-2 my-0">でログインするとテスト用アカウントでログインできます。</p>
      </form>
    </div>
  </main>

</body>
</html>
