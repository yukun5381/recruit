<?php
session_start();

require("../../db.php");

$pdo = connectDB();

//var_dump($_POST);

$info = ""; //登録・削除の成功・失敗を通知
$companies_id = "";

//フォームに入力した年月日をDBに保存する形に変換


//予定登録フォームに入力した内容を保存

if (!empty($_POST["edit"])) {
  $startDate = null;
  $startTime = null;
  $endDate = null;
  $endTime = null;
  $deadlineDate = null;
  $deadlineTime = null;
  if (!empty($_POST["startDate"])) {
    $startDate = $_POST["startDate"];
    $startTime = $_POST["startTime"];
  }
  if (!empty($_POST["endDate"])) {
    $endDate = $_POST["endDate"];
    $endTime = $_POST["endTime"];
  }
  if (!empty($_POST["deadlineDate"])) {
    $deadlineDate = $_POST["deadlineDate"];
    $deadlineTime = $_POST["deadlineTime"];
  }
  //すでに存在する予定のorderNumの最大値を取得
  $sql = $pdo -> prepare("SELECT companies.id, MAX(plans.orderNum) as num FROM plans JOIN companies ON plans.companies_id = companies.id WHERE companies.id = :companies_id GROUP BY companies.id");
  $sql -> bindParam(":companies_id", $_POST["companies_id"], PDO::PARAM_STR);
  $sql -> execute();
  $data = $sql -> fetch();
  //その最大値＋１をフォームに入力した予定のorderNumとする
  if (!empty($data)) {
    $orderNum = $data["num"] + 1;
  } else {
    $orderNum = 1;
  }

  if (empty($_POST["edit_plans_id"])) { //新規作成のとき
    $sql = $pdo -> prepare("INSERT INTO plans (users_id, companies_id, event, detail, startDate, startTime, endDate, endTime, deadlineDate, deadlineTime, orderNum) VALUES (:users_id, :companies_id, :event, :detail, :startDate, :startTime, :endDate, :endTime, :deadlineDate, :deadlineTime, :orderNum)");
    $sql -> bindParam(":users_id", $_SESSION["id"], PDO::PARAM_STR);
    $sql -> bindParam(":companies_id", $_POST["companies_id"], PDO::PARAM_STR);
    $sql -> bindParam(":event", $_POST["event"], PDO::PARAM_STR);
    $sql -> bindParam(":detail", $_POST["detail"], PDO::PARAM_STR);
    $sql -> bindParam(":startDate", $startDate, PDO::PARAM_STR);
    $sql -> bindParam(":startTime", $startTime, PDO::PARAM_STR);
    $sql -> bindParam(":endDate", $endDate, PDO::PARAM_STR);
    $sql -> bindParam(":endTime", $endTime, PDO::PARAM_STR);
    $sql -> bindParam(":deadlineDate", $deadlineDate, PDO::PARAM_STR);
    $sql -> bindParam(":deadlineTime", $deadlineTime, PDO::PARAM_STR);
    $sql -> bindParam(":orderNum", $orderNum, PDO::PARAM_STR);
    $sql -> execute();
    if ($sql) {
      $info = "予定を登録しました";
    } else {
      $info = "予定の登録に失敗しました";
    }
  } else { //編集のとき
    $sql = $pdo -> prepare("UPDATE plans SET users_id = :users_id, companies_id = :companies_id, event = :event, detail = :detail, startDate = :startDate, startTime = :startTime, endDate = :endDate, endTime = :endTime, deadlineDate = :deadlineDate, deadlineTime = :deadlineTime, orderNum = :orderNum WHERE id = :plans_id");
    $sql -> bindParam(":users_id", $_SESSION["id"], PDO::PARAM_STR);
    $sql -> bindParam(":companies_id", $_POST["companies_id"], PDO::PARAM_STR);
    $sql -> bindParam(":event", $_POST["event"], PDO::PARAM_STR);
    $sql -> bindParam(":detail", $_POST["detail"], PDO::PARAM_STR);
    $sql -> bindParam(":startDate", $startDate, PDO::PARAM_STR);
    $sql -> bindParam(":startTime", $startTime, PDO::PARAM_STR);
    $sql -> bindParam(":endDate", $endDate, PDO::PARAM_STR);
    $sql -> bindParam(":endTime", $endTime, PDO::PARAM_STR);
    $sql -> bindParam(":deadlineDate", $deadlineDate, PDO::PARAM_STR);
    $sql -> bindParam(":deadlineTime", $deadlineTime, PDO::PARAM_STR);
    $sql -> bindParam(":plans_id", $_POST["edit_plans_id"], PDO::PARAM_STR);
    $sql -> bindParam(":orderNum", $orderNum, PDO::PARAM_STR);
    $sql -> execute();
    if ($sql) {
      $info = "予定を編集しました";
    } else {
      $info = "予定の編集に失敗しました";
    }
  }
}
if (!empty($_POST["delete"])) { //削除のとき
  $sql = $pdo -> prepare("DELETE FROM plans WHERE id = :edit_id");
  $sql -> bindParam(":edit_id", $_POST["edit_plans_id"]);
  $sql -> execute();
  if ($sql) {
    $info = "予定を削除しました";
  } else {
    $info = "予定の削除に失敗しました";
  }
}

if (!empty($_POST["company_btn"])) { //会社の新規作成ボタンを押したとき、データベースに登録
  $sql = $pdo -> prepare("INSERT INTO companies (users_id, name, occupation) VALUES (:users_id, :name, :occupation)");
  $sql -> bindParam(":users_id", $_SESSION["id"], PDO::PARAM_STR);
  $sql -> bindParam(":name", $_POST["new_company"], PDO::PARAM_STR);
  $sql -> bindParam(":occupation", $_POST["new_occupation"], PDO::PARAM_STR);
  $sql -> execute();
  if ($sql) {
    $info = "会社情報を登録しました";
  } else {
    $info = "会社情報の登録に失敗しました";
  }
  $companies_id = $pdo -> lastInsertId();
}

//送信された年月データを受け取る
if (!empty($_POST["year"]) && !empty($_POST["month"])) {
  $year = $_POST["year"];
  $month = $_POST["month"];
} else {
//現在の年月を取得
$year = date("Y");
$month = date("m"); //2桁
//$month = 2;
}
$month = sprintf("%02d", $month);

//画面遷移用に前月と次月の情報を定義
$year_of_previous_month = $year;

if ($month <= 1) {
  $year_of_previous_month -= 1;
  $previous_month = 12;
} else {
  $previous_month = $month - 1;
}

$year_of_next_month = $year;

if ($month >= 12) {
  $year_of_next_month += 1;
  $next_month = 1;
} else {
  $next_month = $month + 1;
}

//月末日を取得
$last_day = date("j", mktime(0, 0, 0, $month+1, 0, $year));

$calendar = array();
$j = 0;

//1日から月末の日までループ
for ($i=1; $i <= $last_day; $i++) {
  //曜日を取得
  $week = date("w", mktime(0, 0, 0, $month, $i, $year));

  // 1日の場合
    if ($i == 1) {

        // 1日目の曜日までをループ
        for ($s = 1; $s <= $week; $s++) {

            // 前半に空文字をセット
            $calendar[$j]['day'] = '';
            $j++;

        }

    }

    // 配列に日付をセット
    $calendar[$j]['day'] = sprintf("%02d", $i);
    $j++;

    // 月末日の場合
    if ($i == $last_day) {

        // 月末日から残りをループ
        for ($e = 1; $e <= 6 - $week; $e++) {

            // 後半に空文字をセット
            $calendar[$j]['day'] = '';
            $j++;

        }

    }
}

//表示するための予定を取得
$plans = array();

//DBからユーザー・年・月・日が一致する予定情報($result)を取得、インデックスは適当
$startDate1 = "{$year}-{$month}-01";
$startDate2 = "{$year}-{$month}-{$last_day}";

$sql = $pdo -> prepare("SELECT plans.*, companies.name, companies.occupation FROM plans LEFT JOIN companies ON plans.companies_id = companies.id WHERE plans.startDate >= :startDate1 AND plans.startDate <= :startDate2 AND plans.users_id = :users_id ORDER BY plans.startDate, plans.StartTime");
$sql -> bindParam(":startDate1", $startDate1, PDO::PARAM_STR);
$sql -> bindParam(":startDate2", $startDate2, PDO::PARAM_STR);
$sql -> bindParam(":users_id", $_SESSION["id"], PDO::PARAM_STR);
$sql -> execute();
if (!$sql) {
  $info = "予定情報の取得に失敗しました";
}
$plans = $sql -> fetchAll();


//会社情報を取得
$companies = array();

$sql = $pdo -> prepare("SELECT * FROM companies WHERE users_id = :users_id");
$sql -> bindParam("users_id", $_SESSION["id"], PDO::PARAM_STR);
$sql -> execute();
if (!$sql) {
  $info = "会社情報の取得に失敗しました";
}
$companies = $sql -> fetchAll();

//予定編集のための予定情報を取得
if (!empty($_POST["plans_id"])) {
  $sql = $pdo -> prepare("SELECT plans.*, companies.name, companies.occupation FROM plans LEFT JOIN companies ON plans.companies_id = companies.id WHERE plans.id = :id");
  $sql -> bindParam(":id", $_POST["plans_id"], PDO::PARAM_STR);
  $sql -> execute();
  if (!$sql) {
    $info = "編集する予定情報の取得に失敗しました";
  }
  $edit_plan = $sql -> fetch();
} else {
  //編集でないとき
  $edit_plan["startDate"] = date("Y-m-d");
  $edit_plan["startTime"] = "10:00";
  $edit_plan["endDate"] = date("Y-m-d");
  $edit_plan["endTime"] = "11:00";
  $edit_plan["deadlineDate"] = date("Y-m-d");
  $edit_plan["deadlineTime"] = "23:59";
}

//予定作成フォームの分のリスト
// $minutes_list =  array("00", "05", "10", "15", "20", "25", "30", "35", "40", "45", "50", "55");

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>カレンダー</title>

<meta name="viewport" content="width=device-width">

<style type="text/css">
  body {
    background-color: #ffe8e0;
  }
  #new_plan {
    display: none;
    border: 1px solid black;
    background-color: #ccc;
    border-radius: 1rem;
    box-shadow: 0 10px 25px 0 rgba(0, 0, 0, .5);
    margin: 6px;
  }
  #create_company {
    display: none;
  }
  #form_company {
    width: 150px;
  }
  #plan {
    border: 1px solid black;
    background-color: orange;
  }
  #button_create_company_form {
    width: 100%;
    margin: 8px 0;
  }
  .jumbotron-extend{
    background-image: url("../../../leaves-pattern.png");
  }
  body .month {
    padding: 0;
    margin: 12px 0;
  }
  .row-calendar{
    width: 100%;
  }
  body .table {
    box-shadow: 0 10px 25px 0 rgba(0, 0, 0, .5);
    padding: 0;
  }
  .table .col-calendar{
    width: calc(100%/7);
    padding: 0;
  }
  .table tr td {
    padding: 6px;
    height: 100px;
  }
  .table tr th {
    padding: 6px;
    font-size: 1.5rem;
    text-align: center;
  }
  td .btn-detail {
    background-color: hsl(30, 80%, 60%);
    color: white;
    padding: 3px 6px;
    width: calc(100% - 8px);
  }
  td .table-date {
    font-size: 1.5rem;
    margin: 0;
    padding: 6px;
  }
  td:hover {
    background-color: #ccc;
    cursor: pointer;
  }
  div .side {
    padding: 0;
  }
  input[type=checkbox] {
    transform: scale(1.5);
  }
  input[type=submit] {
    width: 100%;
  }
  textarea {
    width: 100%;
  }
</style>

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js" integrity="sha384-LtrjvnR4Twt/qOuYxE721u19sVFLVSA4hf/rRt6PrZTmiPltdZcI7q7PXQBYTKyf" crossorigin="anonymous"></script>

<script type="text/javascript">
  function new_display() { //予定の作成／非表示ボタンを押したとき、予定作成フォームの表示／非表示を切り替える
    const content = document.getElementById("new_plan");
    const btn = document.getElementById("new_plan_open");
    console.log(content);
    if (content.style.display == "" || content.style.display == "none") {
      content.style.display = "block";
      btn.value = "非表示";
    } else {
      content.style.display = "none";
      btn.value = "予定の作成";
    }
  }

  function edit_display() { //予定の作成／非表示ボタンを押したとき、予定作成フォームの表示／非表示を切り替える
    const content = document.getElementById("new_plan");
    const btn = document.getElementById("new_plan_open");
    console.log(content);
    if (content.style.display == "" || content.style.display == "none") {
      content.style.display = "block";
      btn.value = "非表示";
    }
  }

  function day_display(i) { //カレンダーの日付の数字を押したとき、予定作成フォームを表示し、日付のフォームの値に選択した日付を入れる
    //iには年月日が入る
    // console.log(i);
    const content = document.getElementById("new_plan");
    const btn = document.getElementById("new_plan_open");
    const date = document.getElementById("startDate");
    const date2 = document.getElementById("endDate");
    const date3 = document.getElementById("deadlineDate");
    const id = document.getElementById("hidden_id");
    const event = document.getElementById("form_event");
    const company = document.getElementById("form_company");
    //console.log(day.value);
    //console.log(btn.value);
    if (content.style.display == "" || content.style.display == "none") {
      content.style.display = "block";
      btn.value = "非表示";
    }
    // console.log(date);
    date.value = i;
    date2.value = i;
    date3.value = i;
    id.value = "";
    event.value = "";
    for (var k = 0; k < company.length; k++) {
      company.options[k].selected = false;
    }

    location.href = "#new_plan";
    // console.log(date.value);
  }

  function display_create_company_form() { //新しく会社を作成するボタンを押したとき、会社登録フォームを表示する
    const content = document.getElementById("create_company");
    const btn = document.getElementById("button_create_company_form");
    if (content.style.display == "" || content.style.display == "none") {
      content.style.display = "block";
    }
  }

  function create_company() {
    const name = document.getElementById("company_name").value;
    if (name) {
      const check = window.confirm("会社「" + name + "」を登録してもよろしいですか");
      if (check) {
        return true;
      } else {
        return false;
      }
    } else {
      alert("会社名が入力されていません");
      return false;
    }
  }

  function edit_plan() {
    const event = document.getElementById("form_event");
    if (!event) {
      alert("予定が入力されていません");
    }
  }

  function delete_plan() {
    const check = window.confirm("削除してもよろしいですか？");
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
  <?php //echo $edit_plan["id"]; ?>
  <h1>カレンダー</h1>
  <?php $monthDisplay = intval($month); ?>
  <h2><?php echo $year; ?>年<?php echo $monthDisplay; ?>月</h2>

  <?php //var_dump($companies) ?>

  <div class="container">
    <div class="row">
      <div class="col-lg-9 col-md-12 col-sm-12 col-xs-12 row month">
        <form class="col" action="" method="post">
          <input type="hidden" name="year" value="<?php echo $year_of_previous_month; ?>">
          <input type="hidden" name="month" value="<?php echo $previous_month; ?>">
          <input class="btn btn-danger" type="submit" name="previous" value="前の月へ">
        </form>
        <form class="col nav justify-content-end" action="" method="post">
          <input type="hidden" name="year" value="<?php echo $year_of_next_month; ?>">
          <input type="hidden" name="month" value="<?php echo $next_month; ?>">
          <input class="btn btn-success" type="submit" name="next" value="次の月へ">
        </form>
      </div>

      <table class="col-lg-9 col-md-12 col-sm-12 col-xs-12 container-fluid table table-bordered table-striped bg-light">
        <tr class="row-calender">
          <th class="col-calendar">日</th>
          <th class="col-calendar">月</th>
          <th class="col-calendar">火</th>
          <th class="col-calendar">水</th>
          <th class="col-calendar">木</th>
          <th class="col-calendar">金</th>
          <th class="col-calendar">土</th>
        </tr>

        <tr class="row-calender">
          <?php $cnt = 0; ?>
          <?php foreach ($calendar as $key => $value): ?>
            <td class="col-calendar" data-date="<?php echo $value["day"]; ?>" onclick="day_display('<?php echo "{$year}-{$month}-{$value['day']}"; ?>')">
              <?php
              $cnt++;
              if (!empty($value["day"])):
                //日付を書き込む
                //echo "<button type='button' class='date' onclick=day_display(".$value["day"].")>";
                $dateDisplay = intval($value["day"]);
                echo "<p class='table-date'>{$dateDisplay}</p>";
                //echo "</button>";

                //予定を書き込む
                foreach ($plans as $key => $plan):
                  $startDate = "{$year}-{$month}-{$value['day']}";

                  //echo $startDate;
                  if ($plan["startDate"] === $startDate):
                  ?>
                  <form class="" action="#new_plan" method="post">
                    <input type="hidden" name="plans_id" value="<?php echo $plan["id"]; ?>">
                    <input type="hidden" name="year" value="<?php echo $year; ?>">
                    <input type="hidden" name="month" value="<?php echo $month; ?>">
                    <button class="btn btn-detail m-1" name="button">
                      <?php
                      $startTime = date("H:i", strtotime($plan["startTime"]));
                      $endTime = date("H:i", strtotime($plan["endTime"]));
                      echo $plan["event"]."<br>";
                      echo $plan["name"]."<br>";
                      echo "{$startTime}～{$endTime}";
                      ?>
                    </button>
                  </form>
                  <?php
                  endif;
                endforeach;
              endif;
              ?>
            </td>
            <?php if ($cnt == 7): ?>
            </tr>
            <tr class="row-calender">
              <?php $cnt = 0; ?>
            <?php endif; ?>
          <?php endforeach; ?>
        </tr>
      </table>

      <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12 side">

        <input type="button" class="btn btn-dark m-2" id="new_plan_open" value="予定の作成" onclick="new_display()">

        <div id="new_plan">

          <p class="m-2">
            <?php if (!empty($edit_plan["id"])) {
              echo "予定の編集：";
            } else {
              echo "予定の登録：";
            }
            ?>
          </p>

          <form class="" action="./" method="post">

            <p class="m-2">
              <label>
                イベント
                <input type="text" id="form_event" name="event" value="<?php echo $edit_plan["event"]; ?>" placeholder="タイトルを入力" required>
              </label>
            </p>

            <p class="m-2">
              <label>
                会社名・部署名
                <select class="" name="companies_id" id="form_company">
                  <option value=''>選択してください</option>
                  <?php
                  foreach ($companies as $value) {
                    //会社の数だけプルダウンを作る
                    echo "<option value='".$value["id"]."'";
                    if ($edit_plan["companies_id"] == $value["id"]) {
                      echo " selected";
                    }
                    if ($companies_id == $value["id"]) {
                      echo " selected";
                    }
                    echo ">".$value["name"]." ".$value["occupation"]."</option>";
                  }
                  ?>
                </select>
              </label>
              <button type="button" class="btn btn-success" id="button_create_company_form" onclick="display_create_company_form()">新しく会社を追加する</button>
            </p>

            <p class="m-2" id="create_company">
              会社の作成
              <input type="text" id="company_name" name="new_company" value="" placeholder="会社名">
              <input type="text" name="new_occupation" value="" placeholder="部署名">
              <input type="submit" class="btn btn-success m-2" name="company_btn" value="新規作成" onclick="return create_company()">
            </p>

            <div class="container m-2" style="padding: 0">
              <div class="row">
                <div class="my-1 col-lg-12 col-md-2 col-sm-12 col-xs-12">
                  開始日時
                </div>
                <div class="my-1 col-lg-12 col-md-5 col-sm-6 col-xs-12">
                  <input id="startDate" type="date" name="startDate" value="<?php echo $edit_plan['startDate']; ?>">
                </div>
                <div class="my-1 col-lg-12 col-md-4 col-sm-4 col-xs-12">
                  <input id="startTime" type="time" name="startTime" value="<?php echo $edit_plan['startTime']; ?>">
                </div>
              </div>
            </div>

            <div class="container m-2" style="padding: 0">
              <div class="row">
                <div class="my-1 col-lg-12 col-md-2 col-sm-12 col-xs-12">
                  終了日時
                </div>
                <div class="my-1 col-lg-12 col-md-5 col-sm-6 col-xs-12">
                  <input id="endDate" type="date" name="endDate" value="<?php echo $edit_plan["endDate"]; ?>">
                </div>
                <div class="my-1 col-lg-12 col-md-4 col-sm-4 col-xs-12">
                  <input id="endTime" type="time" name="endTime" value="<?php echo $edit_plan['endTime']; ?>">
                </div>
              </div>
            </div>

            <div class="container m-2" style="padding: 0">
              <div class="row">
                <div class="my-1 col">
                  <label>日程が未定（締切を設定する） <input type="checkbox" id="checkbox" name="deadline" value="1"></label>
                </div>
              </div>
            </div>

            <div class="container m-2" style="padding: 0">
              <div class="row">
                <div class="my-1 col-lg-12 col-md-2 col-sm-12 col-xs-12">
                  締切日時
                </div>
                <div class="my-1 col-lg-12 col-md-5 col-sm-6 col-xs-12">
                  <input id="deadlineDate" type="date" name="deadlineDate" value="<?php echo $edit_plan['deadlineDate']; ?>" disabled>
                </div>
                <div class="my-1 col-lg-12 col-md-4 col-sm-4 col-xs-12">
                  <input id="deadlineTime" type="time" name="deadlineTime" value="<?php echo $edit_plan['deadlineTime']; ?>" disabled>
                </div>
              </div>
            </div>

            <div class="container m-2" style="padding: 0">
              <div class="row">
                <div class="my-1 col">
                  <label>締め切りが未定 <input type="checkbox" id="checkbox2" name="undecided" value="1" disabled></label>
                </div>
              </div>
            </div>

            <p class="m-2">
              <label>
                詳細
                <textarea name="detail" rows="3"><?php echo $edit_plan["detail"]; ?></textarea>
              </label>

              <!-- 編集のとき、plansのidを保存しておく -->
              <input type="hidden" id="hidden_id" name="edit_plans_id" value="<?php echo $edit_plan["id"]; ?>">

            </p>

            <p class="m-2">
              <input type="submit" class="btn btn-primary" name="edit" value="登録" onclick="return edit_plan()">
            </p>

            <p class="m-2">
              <input type="submit" class="btn btn-danger" name="delete" value="削除" onclick="return delete_plan()">
            </p>

          </form>
        </div>

      </div>

    </div>
  </div>

<script type="text/javascript">

<?php
if (!empty($edit_plan["id"])) {
  ?>
  edit_display();
  <?php
}
?>

check = document.getElementById("checkbox");
check2 = document.getElementById("checkbox2");

check.addEventListener("change", (e) => {
  if (check.checked) {
    //開始日時・終了日時をdisabledにし、締切日時を操作可能に
    document.getElementById("startDate").disabled = true;
    document.getElementById("startTime").disabled = true;

    document.getElementById("endDate").disabled = true;
    document.getElementById("endTime").disabled = true;

    document.getElementById("deadlineDate").disabled = false;
    document.getElementById("deadlineTime").disabled = false;

    check2.disabled = false;
  } else {
    document.getElementById("startDate").disabled = false;
    document.getElementById("startTime").disabled = false;

    document.getElementById("endDate").disabled = false;
    document.getElementById("endTime").disabled = false;

    document.getElementById("deadlineDate").disabled = true;
    document.getElementById("deadlineTime").disabled = true;

    check2.disabled = true;
    check2.checked = false;
  }
});

check2.addEventListener("change", (e) => {
  if (check2.checked) {
    document.getElementById("deadlineDate").disabled = true;
    document.getElementById("deadlineTime").disabled = true;
  } else if (check.checked) {
    document.getElementById("deadlineDate").disabled = false;
    document.getElementById("deadlineTime").disabled = false;
  }
});
</script>

</body>
</html>
