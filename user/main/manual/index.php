<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>マニュアル</title>
<meta name="viewport" content="width=device-width">

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js" integrity="sha384-LtrjvnR4Twt/qOuYxE721u19sVFLVSA4hf/rRt6PrZTmiPltdZcI7q7PXQBYTKyf" crossorigin="anonymous"></script>
<style>
  main {
    width: 60%;
    margin: 50px auto 0;
    display: block;
  }
  h1 {
    font-size: 2em;
  }
  h2 {
    font-size: 1.5em;
  }
  .display {
    margin: 2em 0;
  }
</style>
</head>
<body>

<header>
  <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
    <div class="col-8">
      <a class="text-white h3" href="../">就活管理アプリ Rak</a>
    </div>
    <div class="col-4">
      <a href="../logout" class="btn btn-danger float-right">ログアウト</a>
    </div>
  </nav>
</header>

<main>
  <div class="display">
    <h1>メイン画面</h1>
    <p>ログインした後に表示されるページです。ここから各機能を利用することができます。</p>
    <p>簡単な今日の予定も見ることができます。</p>
    <h2>機能一覧</h2>
    <ul>
      <li>カレンダー機能</li>
      <li>企業一覧機能／企業編集機能</li>
      <li>メール送信機能　※開発中</li>
      <li>ES・適性検査などの日程計画機能　※開発中</li>
    </ul>
  </div>

  <div class="display">
    <h1>カレンダー画面</h1>
    <p>ユーザーの1か月の予定（選考）をカレンダーとして表示します。</p>
    <p>現在の月が最初に表示されますが、前月や次月のカレンダーも見ることができます。</p>
    <p>予定の新規登録、編集、削除もできます。</p>
    <p>就活以外の予定の登録もできます。ESなどの日程計画を立てるために使われるので、なるべく登録しておいてください。</p>
  </div>

  <div class="display">
    <h1>企業一覧画面</h1>
    <p>ユーザーが登録した企業（選考を受ける企業）を一覧で見ることができます。</p>
    <p>マイページのURLを登録しておくことで、「マイページ」ボタンからマイページにアクセスできます。</p>
    <p>企業ごとの予定を見たいとき、企業情報を編集したいときは「詳細」ボタンから企業詳細画面にアクセスすることで可能です。</p>
    <p>企業を削除したいときは「削除」ボタンを押してください。</p>
    <p>下方にある「企業を追加する」ボタンを押すと、新たに企業と予定を登録できます。</p>
  </div>

  <div class="">
    <h1>企業詳細画面</h1>
    <p>企業一覧画面で選択した企業の詳細や、その企業の予定を見ることができます。</p>
    <p></p>
  </div>
</main>

<footer></footer>

</body>
</html>
