<?php
session_start();
date_default_timezone_set('Asia/Tokyo');

require("../db.php");

$pdo = connectDB();

$name = "";

if (empty($_SESSION["id"])) {
  // $_SESSION["id"] = 6;
  header("location: ../login/");
}

//現在の日時を取得
$year = date("Y");
$month = date("n");
$date = date("d");
//$date = 8;
//名前を取得
$sql = $pdo -> prepare("SELECT * FROM users WHERE id = :id");
$sql -> bindParam(":id", $_SESSION["id"], PDO::PARAM_STR);
$sql -> execute();
$result = $sql -> fetch();

$name = $result["name"];

//現在の年月日を取得
$ymd = $year."-".$month."-".$date;

//予定情報を取得（今日から1週間分）
$sql = $pdo -> prepare("SELECT plans.*, companies.name, companies.occupation FROM plans LEFT JOIN companies ON plans.companies_id = companies.id WHERE startDate >= CURRENT_DATE() AND startDate <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY) AND plans.users_id = :users_id ORDER BY plans.startDate, plans.startTime");
// $sql -> bindParam(":startDate", $ymd, PDO::PARAM_STR);
// $sql -> bindParam(":startDate2", $ymd2, PDO::PARAM_STR);
$sql -> bindParam(":users_id", $_SESSION["id"], PDO::PARAM_STR);
$sql -> execute();
if (!$sql) {
  $info = "予定情報の取得に失敗しました";
}
$plans = $sql -> fetchAll();

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>ホーム画面</title>
<meta name="viewport" content="width=device-width">

<style type="text/css">
  .jumbotron-extend{
    background-image: url("../../leaves-pattern.png");
  }
  .bg-gray{
    background-color: #aaa;
  }
  table {
    border: 1px solid black;
    border-collapse: collapse;
    background-color: white;
    margin: 10px 0;
  }
  .plan:hover {
    cursor: pointer;
  }
  .plan-time {
    background-color: #333;
    font-size: 20px;
    color: white;
    border-radius: 8px 0 0 8px;
  }
  .plan-time:hover {
    background-color: black;
  }
  .plan-name {
    background-color: white;
    font-size: 20px;
    border-radius: 0 8px 8px 0;
  }
  .plan-name:hover {
    background-color: #ccc;
  }

</style>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js" integrity="sha384-LtrjvnR4Twt/qOuYxE721u19sVFLVSA4hf/rRt6PrZTmiPltdZcI7q7PXQBYTKyf" crossorigin="anonymous"></script>
</head>
<body class="jumbotron jumbotron-extend">
  <header>
    <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
      <div class="col-8">
        <a class="text-white h3" href="">就活管理アプリ Rak</a>
      </div>
      <div class="col-4">
        <a href="./logout" class="btn btn-danger float-right">ログアウト</a>
      </div>
    </nav>
  </header>
  <h1>ホーム画面</h1>
  <p><?php echo $name."さんのマイページ"; ?></p>
  <main class="container">

    <div class="row">

      <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 bg-gray">
        <p class="m-2">
          <a href="./calendar" class="btn btn-light w-100">カレンダー</a>
        </p>
        <p class="m-2">
          <a href="./company" class="btn btn-dark w-100">企業一覧</a>
        </p>
        <!-- <p class="m-2">
          <a href="./mail" class="btn btn-light w-100">メール送信</a>
        </p>
        <p class="m-2">
          <a href="./config" class="btn btn-dark w-100">設定</a>
        </p> -->
        <p class="m-2">
          <a href="./manual" class="btn btn-light w-100">マニュアル</a>
        </p>
        <p class="m-2">
          <a href="./recommendation" class="btn btn-dark w-100">自動日程計画</a>
        </p>
      </div>

      <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 bg-gray">
        <p class="h3 m-2">今日の予定</p>
        <p class="m-2"><?php echo "{$year}年{$month}月{$date}日"; ?></p>
        <!-- 予定を1個ずつ表示 -->
        <?php foreach ($plans as $plan):?>
          <div class="container p-1">
            <?php if ($plan["startDate"] === date("Y-m-d")): ?>
            <div class="row m-0 plan" data-id="<?php echo $plan["id"]; ?>">
                <div class="plan-time col-lg-4 col-md-4 col-sm-5 col-xs-6">
                  <?php
                  $startTime = date("H:i", strtotime($plan["startTime"]));
                  $endTime = date("H:i", strtotime($plan["endTime"]));
                  ?>
                  <?php echo "{$startTime} ～ {$endTime}"; ?>
                </div>
                <div class="plan-name col-lg-8 col-md-8 col-sm-7 col-xs-6">
                  <div class="short-div">
                    <?php echo $plan['name']; ?>
                  </div>
                  <div class="short-div">
                    <?php echo $plan['event']; ?>
                  </div>
                </div>
            </div>
          <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <!-- <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 bg-gray">
        <p class="h3 m-2">今日の日程計画</p>

        <table width="100%">
          <tr class="row">
            <th class="col">会社名</th>
            <th class="col">締切日時</th>
            <th class="col">推奨日時</th>
          </tr>

          <tr class="row">
            <td class="col">A社</td>
            <td class="col">1/31 23:59</td>
            <td class="col">1/30 22:00～23:00</td>
          </tr>
        </table>
      </div> -->

    </div>

  </main>

  <script type="text/javascript">

  </script>
</body>
</html>
