<?php
session_start();
date_default_timezone_set('Asia/Tokyo');

require("../../db.php");

$pdo = connectDB();

// var_dump($_POST);

function week($num) {
  switch ($num) {
    case 0:
      return "日";
      break;
    case 1:
      return "月";
      break;
    case 2:
      return "火";
      break;
    case 3:
      return "水";
      break;
    case 4:
      return "木";
      break;
    case 5:
      return "金";
      break;
    case 6:
      return "土";
      break;

    default:
      return "";
      break;
  }
}

//確保した面接予定をDBに登録
if (!empty($_POST["setTime"])) {
  $sql = $pdo -> prepare("INSERT INTO interviewPlans (users_id, companies_id, interviewDate, startTime, endTime) VALUES (:users_id, :companies_id, :interviewDate, :startTime, :endTime)");
  $sql -> bindParam(":users_id", $_SESSION["id"], PDO::PARAM_STR);
  $sql -> bindParam(":companies_id", $_POST["companies_id"], PDO::PARAM_STR);
  $sql -> bindParam(":interviewDate", $_POST["date"], PDO::PARAM_STR);
  $sql -> bindParam(":startTime", $_POST["startTime"], PDO::PARAM_STR);
  $sql -> bindParam(":endTime", $_POST["endTime"], PDO::PARAM_STR);
  $sql -> execute();

  //再読み込みによる二重登録を防止
  header("Location: ./");
}

//面接日時を削除
if (!empty($_POST["id"])) {
  $sql = $pdo -> prepare("DELETE FROM interviewPlans WHERE id = :id");
  $sql -> bindParam(":id", $_POST["id"], PDO::PARAM_STR);
  $sql -> execute();
}

//予定情報を検索
$sql = $pdo -> prepare("SELECT plans.*, companies.name, companies.occupation FROM plans LEFT JOIN companies ON plans.companies_id = companies.id WHERE startDate >= CURRENT_DATE() AND plans.users_id = :users_id ORDER BY plans.startDate, plans.startTime");
$sql -> bindParam(":users_id", $_SESSION["id"], PDO::PARAM_STR);
$sql -> execute();
if (!$sql) {
  $info = "予定情報の取得に失敗しました";
}
$plans = $sql -> fetchAll();

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

//確保中の日時リストを取得
$sql = $pdo -> prepare("SELECT interviewPlans.*, companies.name FROM interviewPlans join companies on interviewPlans.companies_id = companies.id WHERE interviewPlans.users_id = :users_id");
$sql -> bindParam("users_id", $_SESSION["id"], PDO::PARAM_STR);
$sql -> execute();
if (!$sql) {
  $info = "確保中の日時の取得に失敗しました";
}
$interviewPlans = $sql -> fetchAll();

//確保中の日時情報の成形
$newInterviewPlans = array();
foreach ($interviewPlans as $key => $plan) {
  $newInterviewPlans[$key]["date"] = (strtotime($plan["interviewDate"]) - strtotime(date("Y-m-d"))) / (24 * 60 * 60);
  $newInterviewPlans[$key]["startTime"] = date("H", strtotime($plan["startTime"])) * 60 + date("i", strtotime($plan["startTime"])); //時刻を分単位で取得
  $newInterviewPlans[$key]["endTime"] = date("H", strtotime($plan["endTime"])) * 60 + date("i", strtotime($plan["endTime"])); //時刻を分単位で取得
}
// var_dump($newInterviewPlans);

//空いている時間を取得
//初期値として1と設定（すべて空いている）
$freeTime = array();
if (!empty($_POST["start"])) {
  $start = date("H", strtotime($_POST["start"])) * 60 + date("i", strtotime($_POST["start"]));
} else {
  $start = 10 * 60; //9時スタート
}
if (!empty($_POST["end"])) {
  $end = date("H", strtotime($_POST["end"])) * 60 + date("i", strtotime($_POST["end"]));
} else {
  $end = 18 * 60; //19時終了
}
for ($date = 0; $date <= 30; $date++) {
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
$buffer = 0;
foreach ($newInterviewPlans as $key => $plan) {
  $date = $plan["date"];
  for ($time = $plan["startTime"] - $buffer; $time < $plan["endTime"] + $buffer; $time++) {
    //開始時刻から終了時刻までループする
    $freeTime[$date][$time] = 0; //開始日付と終了日付が同じならこれでおけ
  }
}


//何時から何分間空きがあるかのデータを取得
$freeList = array();
for ($date = 0; $date < 30; $date++) {
  $index = 0;
  $flg = 0; //flgが1の間は予定が空いている
  $minute = 0;
  for ($time = 4; $time < 28 * 60; $time++) {
    if ($freeTime[$date][$time] === 1 && $flg === 0) {
      //flgが0→1になる時刻を取得
      $freeList[$date][$index]["startTime"] = $time;
      $minute = 0;
      $flg = 1;
      $minute++;
    } else if ($freeTime[$date][$time] === 0 && $flg === 1) {
      //flgが1→0になる
      $freeList[$date][$index]["endTime"] = $time;
      $index++;
      $flg = 0;
    }
  }
}
// var_dump($freeList);


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

//会社情報を取得
$companies = array();

$sql = $pdo -> prepare("SELECT * FROM companies WHERE users_id = :users_id");
$sql -> bindParam("users_id", $_SESSION["id"], PDO::PARAM_STR);
$sql -> execute();
if (!$sql) {
  $info = "会社情報の取得に失敗しました";
}
$companies = $sql -> fetchAll();




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

  <div class="">
    <h1>面接日程調整</h1>
    <p>現在の予定から、面接に使うことのできる時間帯を表示します。企業の日程調整のためにとってある時間帯は除外されます。</p>

    <p>（編集中：この画面は正常に動きません）</p>

    <div class="">
      <h2>設定可能日時リスト</h2>
      <p>今日から30日後までに面接が可能な日時を表示します。</p>
      <p>時間帯を設定：</p>
      <form class="" action="" method="post">
        <?php
        $startTime = sprintf("%02d", intval($start / 60)).":".sprintf("%02d", ($start % 60));
        $endTime = sprintf("%02d", intval($end / 60)).":".sprintf("%02d", ($end % 60));
        ?>
        <input type="time" name="start" value="<?php echo $startTime; ?>">
        ～
        <input type="time" name="end" value="<?php echo $endTime; ?>">
        <input class="btn btn-primary" type="submit" name="setting" value="時間帯を設定する">
      </form>
      <table class="p-0 container table table-bordered table-striped bg-light">
        <tr class="row m-0">
          <th class="col">日付</th>
          <th class="col">時刻</th>
        </tr>
        <?php foreach ($freeList as $key => $free): ?>
          <?php foreach ($free as $value): ?>
            <?php
            $date = date("m/d", strtotime("+{$key} day"));
            $weekNum = date("w", strtotime("+{$key} day"));
            $week = week($weekNum);
            $startHour = intval($value["startTime"] / 60);
            $startHour = sprintf("%02d", $startHour);
            $startMinute = $value["startTime"] % 60;
            $startMinute = sprintf("%02d", $startMinute);
            $startTime = "{$startHour}:{$startMinute}";
            $endHour = intval($value["endTime"] / 60);
            $endHour = sprintf("%02d", $endHour);
            $endMinute = $value["endTime"] % 60;
            $endMinute = sprintf("%02d", $endMinute);
            $endTime = "{$endHour}:{$endMinute}";
            ?>
            <tr class="row m-0">
              <td class="col"><?php echo "{$date} ({$week})"; ?></td>
              <td class="col"><?php echo "{$startTime}～{$endTime}"; ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </table>
    </div>

    <div class="">
      <h2>確保中の日時リスト</h2>
      <p>面接日時が決まったら、解放ボタンを押して設定可能日時を更新しましょう。</p>
      <table class="p-0 container table table-bordered table-striped bg-light">
        <tr class="row m-0">
          <th class="col-md-3 col-6">日付</th>
          <th class="col-md-3 col-6">時刻</th>
          <th class="col-md-3 col-6">会社名</th>
          <th class="col-md-3 col-6">解放/削除</th>
        </tr>
        <?php foreach ($interviewPlans as $plan): ?>
          <tr class="row m-0">
            <?php
            $date = date("m/d", strtotime($plan["interviewDate"]));
            $weekNum = date("w", strtotime($plan["interviewDate"]));
            $week = week($weekNum);
            $startTime = date("H:i", strtotime($plan["startTime"]));
            $endTime = date("H:i", strtotime($plan["endTime"]));
            $company = $plan["name"];
            $id = $plan["id"];
            ?>
            <td class="col-md-3 col-6"><?php echo "{$date} ({$week})"; ?></td>
            <td class="col-md-3 col-6"><?php echo "{$startTime}～{$endTime}"; ?></td>
            <td class="col-md-3 col-6"><?php echo $company; ?></td>
            <td class="col-md-3 col-6">
              <form action="" method="post">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <button class="btn btn-danger" type="submit" name="delete">削除</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>

    <div class="">
      <h2>面接予定を確保</h2>

      <form class="" action="" method="post">
        <div class="">
          <label>
            会社名：
            <select class="" name="companies_id" id="form_company" required>
              <option value="">選択してください</option>
              <?php foreach ($companies as $value): ?>
                <option value="<?php echo $value['id']; ?>"><?php echo $value["name"]; ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
        <div class="">
          <label>
            日付：
            <input type="date" name="date" value="<?php echo date("Y-m-d"); ?>" required>
          </label>
        </div>
        <div class="">
          時刻：
          <input type="time" name="startTime" value="10:00" required>
          ～
          <input type="time" name="endTime" value="18:00" required>
        </div>
        <div class="">
          <input class="btn btn-primary" type="submit" name="setTime" value="面接予定を確保">
        </div>
      </form>
    </div>
  </div>


</body>
</html>
