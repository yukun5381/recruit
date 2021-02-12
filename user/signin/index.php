<?php
session_start();
require("../db.php");
$pdo = connectDB();

if (!empty($_SESSION["id"])) {
  $_SESSION["id"] = "";
}

$nickname = "";
$mail = "";
$password = "";
$password2 = "";

$error = array();

if (!empty($_POST["nickname"])) {
  $nickname = $_POST["nickname"];
}

$count = 1;

if (!empty($_POST["mail"]) && !empty($_POST["password"]) && !empty($_POST["password2"])) {
  $mail = $_POST["mail"];
  $password = $_POST["password"];
  $password2 = $_POST["password2"];
  //入力したメールアドレスがすでに存在していないかを調べる
  $sql = $pdo -> prepare("SELECT * FROM users WHERE mail = :mail");
  $sql -> bindParam(":mail", $mail, PDO::PARAM_STR);
  $sql -> execute();
  $result = $sql -> fetchAll();

  $count = count($result);

  if ($count > 0) {
    //DB内に入力したメールアドレスが既に存在しているとき
    $error[] = "メールアドレスはすでに登録されています";
  }
  if ($password != $password2) {
    //パスワード２つが一致していないとき
    $error[] = "パスワードとパスワード（再入力）が一致していません";
  }
  if (empty($error)) {
    //メールアドレスが存在していないかつパスワード２つが一致しているとき
    //データベースに登録
    
    $sql = $pdo -> prepare("INSERT INTO users (name, mail, password) VALUES (:name, :mail, :password)");
    $sql -> bindParam(":name", $nickname, PDO::PARAM_STR);
    $sql -> bindParam(":mail", $mail, PDO::PARAM_STR);
    $sql -> bindParam(":password", $password, PDO::PARAM_STR);
    $sql -> execute();


    //$_SESSION["id"] = $result["id"]; //ユーザーIDを保存
    $_SESSION["id"] = 5;
    //メイン画面へ遷移
    $alert =
    "<script type='text/javascript'>
      const check = window.confirm('ニックネーム：{$nickname}\\n メールアドレス：{$mail}\\n パスワード：{$password}\\n これでよろしいですか？');
      if(check){
        window.location.href = '../main/';
      }
    </script>";

    echo $alert;
    //header("location: ../main/");
  }
}


?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>新規登録</title>
</head>
<body>

  <h1>新規登録　※は必須</h1>

  <p style="color: red;">
    <?php

    if (!empty($error)) {
      foreach ($error as $value) {
        echo $value."<br>";
      }
    }
    ?>
  </p>

  <form class="" action="" method="post">
    <p>
      ニックネーム
      <input type="text" name="nickname" value="<?php echo $nickname; ?>" placeholder="">
    </p>
    <p>
      メールアドレス※
      <input type="text" name="mail" value="<?php echo $mail; ?>" placeholder="name@example.jp" pattern="([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+" title="メールアドレスの形式で入力してください。" required>
    </p>
    <p>
      パスワード※
      <input type="password" name="password" value="" placeholder="半角英数4文字以上20文字以下" pattern="^[a-zA-Z\d]{4,20}$" title="半角英数4文字以上20文字以下で入力してください。" required>
    </p>
    <p>
      パスワード（再入力）※
      <input type="password" name="password2" value="" required>
    </p>
    <p>
      <input type="submit" name="submit" value="登録">
    </p>
  </form>

  <a href="../login">ログイン</a> <br>
  <a href="../main">ホーム画面</a>

  <!-- user/login/へ遷移 -->
</body>
</html>
