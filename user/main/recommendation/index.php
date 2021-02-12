<?php
session_start();
date_default_timezone_set('Asia/Tokyo');

require("../../db.php");

$pdo = connectDB();

if (!empty($_POST["plansId"])) {
  //完了ボタンが押されたとき、その予定を完了状態にする
  $sql = $pdo -> prepare("UPDATE plans SET completed = '1' WHERE id = :id");
  $sql -> bindParam(":id", $_POST["plansId"], PDO::PARAM_STR);
  $sql -> execute();
  if (!$sql) {
    $info = "予定情報の更新に失敗しました";
  }
}

//予定情報を検索
$sql = $pdo -> prepare("SELECT plans.*, companies.name, companies.occupation FROM plans LEFT JOIN companies ON plans.companies_id = companies.id WHERE startDate >= CURRENT_DATE() AND startDate <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY) AND plans.users_id = :users_id ORDER BY plans.startDate, plans.startTime");
$sql -> bindParam(":users_id", $_SESSION["id"], PDO::PARAM_STR);
$sql -> execute();
if (!$sql) {
  $info = "予定情報の取得に失敗しました";
}
$plans = $sql -> fetchAll();

//締め切りのある予定を検索
$sql = $pdo -> prepare("SELECT plans.*, companies.name, companies.occupation, companies.URL FROM plans LEFT JOIN companies ON plans.companies_id = companies.id WHERE deadlineDate >= CURRENT_DATE() AND completed = '0' AND plans.users_id = :users_id ORDER BY plans.deadlineDate, plans.deadlineTime");
$sql -> bindParam(":users_id", $_SESSION["id"], PDO::PARAM_STR);
$sql -> execute();
if (!$sql) {
  $info = "予定情報の取得に失敗しました";
}
$deadlinePlans = $sql -> fetchAll();

//予定情報の成型
$newPlans = array();
foreach ($plans as $key => $plan) {
  $newPlans[$key]["startDate"] = (strtotime($plan["startDate"]) - strtotime(date("Y-m-d"))) / (24 * 60 * 60);
  $newPlans[$key]["startTime"] = date("H", strtotime($plan["startTime"])) * 60 + date("i", strtotime($plan["startTime"])); //時刻を分単位で取得
  $newPlans[$key]["endDate"] = (strtotime($plan["endDate"]) - strtotime(date("Y-m-d"))) / (24 * 60 * 60);
  $newPlans[$key]["endTime"] = date("H", strtotime($plan["endTime"])) * 60 + date("i", strtotime($plan["endTime"])); //時刻を分単位で取得

  // echo "{$plan["name"]} {$newPlans[$key]["startDate"]} {$newPlans[$key]["startTime"]} {$newPlans[$key]["endTime"]}<br>";
}

// var_dump($deadlinePlans);

// echo "<br>";

//空いている時間を取得
//初期値として1と設定（すべて空いている）
$freeTime = array();
$start = 10 * 60;
$end = 24 * 60;
for ($date = 0; $date < 7; $date++) {
  for ($time = 4; $time < 28 * 60; $time++) {
    if ($time < $start || $end <= $time) {
      $freeTime[$date][$time] = 0;
    } else {
      $freeTime[$date][$time] = 1;
    }
  }
}

//予定情報を使って予定を埋めていく（値を0にする）
$buffer = 30; //予定の前後30分には提案されない
foreach ($newPlans as $key => $newPlan) {
  $date = $newPlan["startDate"];
  for ($time = $newPlan["startTime"] - $buffer; $time < $newPlan["endTime"] + $buffer; $time++) {
    //開始時刻から終了時刻までループする
    $freeTime[$date][$time] = 0; //開始日付と終了日付が同じならこれでおけ
  }
}
// for ($date = 0; $date < 7; $date++) {
//   for ($time = 0; $time < 24 * 60; $time++) {
//     echo $freeTime[$date][$time]." ";
//     if ($time % 60 === 59) {
//       echo "<br>";
//     }
//   }
//   echo "<br>";
// }

//何時から何分間空きがあるかのデータを取得（60分ごと）
$freeList = array();
for ($date = 0; $date < 7; $date++) {
  $index = 0;
  $flg = 0; //flgが1の間は予定が空いている
  $minute = 0;
  for ($time = 4; $time < 28 * 60; $time++) {
    if ($freeTime[$date][$time] === 1) {
      if ($flg === 0) {
        //flgが0→1になる時刻を取得
        $freeList[$date][$index]["startTime"] = $time;
        $minute = 0;
        $flg = 1;
      } else if ($minute === 60) {
        //60分に達したら次の配列に移る
        $index++;
        $freeList[$date][$index]["startTime"] = $time;
        $minute = 0;
      }
      $minute++;
      if ($time === 28 * 60 - 1 && $minute < 60) {
        //日付が変わる前、60分未満のとき使えないのでリセット
        unset($freeList[$date][$index]);
      }
    } else if ($freeTime[$date][$time] === 0 && $flg === 1) {
      //flgが1→0になる
      if ($minute < 60) {
        //60分未満のとき使えないのでリセット
        unset($freeList[$date][$index]);
      } else {
        $index++;
      }
      $flg = 0;
    }
  }
}

//モードの取得
$mode = [
  "easy" => 6,
  "normal" => 4,
  "hard" => 3,
  "expert" => 2
];

if (empty($_POST["mode"])) {
  $select = "normal";
} else {
  $select = $_POST["mode"];
}

//前後のモードを取得
switch ($select) {
  case "easy":
  $easy = "easy";
  $difficult = "normal";
  break;

  case "normal":
  $easy = "easy";
  $difficult = "hard";
  break;

  case "hard":
  $easy = "normal";
  $difficult = "expert";
  break;

  case "expert":
  $easy = "hard";
  $difficult = "expert";
  break;

  default:
  break;
}

$recommend = array();

$span = $mode[$select];
$listIndex = 0;
$planIndex = 0;
foreach ($freeList as $key => $list) {
  foreach ($list as $value) {
    if ($planIndex >= count($deadlinePlans)) {
      break; //予定がなくなったらループ終了
    }
    $date = date("m/d", strtotime("+{$key} day"));
    $startHour = intval($value['startTime'] / 60);
    $startMinute = $value['startTime'] % 60;
    if ($startMinute < 10) {
      $startMinute = "0".$startMinute;
    }
    $endHour = $startHour + 1;
    if ($listIndex % $span === 0) {
      //$span回に1回だけ予定を埋める
      $recommend[] = [
        "id" => $deadlinePlans[$planIndex]["id"],
        "date" => $date,
        "start" => "{$startHour}:{$startMinute}",
        "end" => "{$endHour}:{$startMinute}",
        "companyName" => $deadlinePlans[$planIndex]['name'],
        "event" => $deadlinePlans[$planIndex]['event'],
        "URL" => $deadlinePlans[$planIndex]["URL"],
        "deadlineDate" => date("m/d", strtotime($deadlinePlans[$planIndex]['deadlineDate'])),
        // "deadlineTime" => $deadlinePlans[$planIndex]['deadlineTime']
        "deadlineTime" => date("H:i", strtotime($deadlinePlans[$planIndex]['deadlineTime']))
      ];
      // echo "{$date}: {$startHour}:{$startMinute}～{$endHour}:{$startMinute}";
      // echo "... {$deadlinePlans[$planIndex]['name']} {$deadlinePlans[$planIndex]['event']}";
      $planIndex++;
      // echo "<br>";
    }
    $listIndex++;
  }
}
// var_dump($recommend);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>企業一覧</title>
<meta name="viewport" content="width=device-width">

<style type="text/css">
.jumbotron-extend{
  background-image: url("../../../leaves-pattern.png");
}
table {
  border: 1px solid black;
  border-collapse: collapse;
  background-color: white;
}
th {
  border: 1px solid black;
  border-collapse: collapse;
}
td {
  border: 1px solid black;
  border-collapse: collapse;
}
.btn-content {
  padding: 12px 4px !important;
}
td .table-btn {
  width: 100%;
  padding: 8px 4px;
}
</style>

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js" integrity="sha384-LtrjvnR4Twt/qOuYxE721u19sVFLVSA4hf/rRt6PrZTmiPltdZcI7q7PXQBYTKyf" crossorigin="anonymous"></script>

<script type="text/javascript">
  function display_create_company_form() {
    const form = document.getElementById("create_company_form");
    const btn = document.getElementById("create_company_btn");
    if (form.style.display == "" || form.style.display == "none") {
      form.style.display = "block";
      btn.style.display = "none";
    }
  }

  function delete_company(name) {
    const check = window.confirm("企業「" + name + "」を削除してもよろしいですか？");
    if (!check) {
      return false;
    }
  }
</script>

</head>
<body class="jumbotron jumbotron-extend">
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

  <h1>自動日程計画</h1>
  <p>あなたが予定に入れたES・適性検査などの予定を自動で計画します。</p>
  <div class="">
    <div class="">
      <form class="" action="" method="post">
        <input type="hidden" name="mode" value="<?php echo $easy; ?>">
        <input type="submit" class="btn btn-primary" name="" value="余裕を持たせる">
      </form>
    </div>
    <p>難易度：<?php echo $select; ?></p>
    <div class="">
      <form class="" action="" method="post">
        <input type="hidden" name="mode" value="<?php echo $difficult; ?>">
        <input type="submit" class="btn btn-danger" name="" value="予定を詰める">
      </form>
    </div>
  </div>
  <div class="">
    <div class="">
      <table class="p-0 container table table-bordered table-striped bg-light">
        <tr class="row m-0">
          <th class="col-lg-2 col-md-2 col-sm-4 col-xs-4">おすすめの日時</th>
          <th class="col-lg-2 col-md-2 col-sm-4 col-xs-4">会社名</th>
          <th class="col-lg-2 col-md-2 col-sm-4 col-xs-4">選考</th>
          <th class="col-lg-2 col-md-2 col-sm-4 col-xs-4">締切日時</th>
          <th class="col-lg-2 col-md-2 col-sm-4 col-xs-4">マイページ</th>
          <th class="col-lg-2 col-md-2 col-sm-4 col-xs-4">完了</th>
        </tr>
        <?php foreach ($recommend as $value): ?>
        <tr class="row m-0">
          <td class="col-lg-2 col-md-2 col-sm-4 col-xs-4">
            <?php
            echo $value["date"]."<br>";
            echo "{$value['start']}～{$value['end']}";
            ?>
          </td>
          <td class="col-lg-2 col-md-2 col-sm-4 col-xs-4"><?php echo $value["companyName"]; ?></td>
          <td class="col-lg-2 col-md-2 col-sm-4 col-xs-4"><?php echo $value["event"]; ?></td>
          <td class="col-lg-2 col-md-2 col-sm-4 col-xs-4">
            <?php
            echo $value['deadlineDate']." ".$value['deadlineTime'];
            ?>
          </td>
          <td class="col-lg-2 col-md-2 col-sm-4 col-xs-4 btn-content">
            <?php if(!empty($value["URL"])): ?>
            <a href="<?php echo $value["URL"]; ?>" class="btn btn-primary table-btn" target="_blank" rel="noopener noreferrer">マイページ</a>
            <?php endif; ?>
          </td>
          <td class="col-lg-2 col-md-2 col-sm-4 col-xs-4 btn-content">
            <form class="" action="" method="post">
              <input type="hidden" name="mode" value="<?php echo $select; ?>">
              <input type="hidden" name="plansId" value="<?php echo $value["id"]; ?>">
              <input type="submit" class="btn btn-dark table-btn" value="完了">
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>

</body>
</html>
