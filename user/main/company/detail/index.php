<?php
session_start();
require("../../../db.php");

date_default_timezone_set("Asia/Tokyo");

$pdo = connectDB();

$year = date("Y");
$month = date("n");
$date = date("d");

// var_dump($_POST);

if(!empty($_POST["add_company_btn"])){
  //企業情報の更新ボタンが押されたとき、更新された企業情報を登録
  //まずはcompaniesをアップデート
  $sql = $pdo -> prepare("UPDATE companies SET name = :name, occupation = :occupation, URL = :URL, detail = :detail WHERE id = :id");
  $sql -> bindParam(":name", $_POST["name"], PDO::PARAM_STR);
  $sql -> bindParam(":occupation", $_POST["occupation"], PDO::PARAM_STR);
  $sql -> bindParam(":URL", $_POST["URL"], PDO::PARAM_STR);
  $sql -> bindParam(":detail", $_POST["detail"], PDO::PARAM_STR);
  $sql -> bindParam(":id", $_POST["companies_id"], PDO::PARAM_STR);
  $sql -> execute();
  //次にplansに登録されているデータを削除
  $sql = $pdo -> prepare("DELETE FROM plans WHERE companies_id = :companies_id");
  $sql -> bindParam(":companies_id", $_POST["companies_id"], PDO::PARAM_STR);
  $sql -> execute();
  //最後にplansにフォームの内容を1個ずつ登録
  for ($index = 1; $index <= $_POST["max_index"]; $index++) {
    if (!empty($_POST["event"][$index])) {
      //登録する値
      $event = $_POST["event"][$index];
      //$detail = $_POST["detail"][$index];
      $detail = "";
      $startDate = null;
      $startTime = null;
      $endDate = null;
      $endTime = null;
      $deadlineDate = null;
      $deadlineTime = null;
      $completed = "0";

      if (!empty($_POST["start_year"][$index])) {
        $startDate = "{$_POST['start_year'][$index]}-{$_POST['start_month'][$index]}-{$_POST['start_date'][$index]}";
        $startTime = "{$_POST['start_hour'][$index]}:{$_POST['start_minute'][$index]}:00";
      }
      if (!empty($_POST["end_year"][$index])) {
        $endDate = "{$_POST['end_year'][$index]}-{$_POST['end_month'][$index]}-{$_POST['end_date'][$index]}";
        $endTime = "{$_POST['end_hour'][$index]}:{$_POST['end_minute'][$index]}:00";
      }
      if (!empty($_POST["deadline_year"][$index])) {
        $deadlineDate = "{$_POST['deadline_year'][$index]}-{$_POST['deadline_month'][$index]}-{$_POST['deadline_date'][$index]}";
        $deadlineTime = "{$_POST['deadline_hour'][$index]}:{$_POST['deadline_minute'][$index]}:00";
      }
      if (!empty($_POST["completed"][$index])) {
        $completed = "1";
      }
      $sql = $pdo -> prepare("INSERT INTO plans (users_id, companies_id, event, detail, startDate, startTime, endDate, endTime, deadlineDate, deadlineTime, orderNum, completed) VALUES (:users_id, :companies_id, :event, :detail, :startDate, :startTime, :endDate, :endTime, :deadlineDate, :deadlineTime, :orderNum, :completed)");
      $sql -> bindParam(":users_id", $_SESSION["id"], PDO::PARAM_STR);
      $sql -> bindParam(":companies_id", $_POST["companies_id"], PDO::PARAM_STR);
      $sql -> bindParam(":event", $event, PDO::PARAM_STR);
      $sql -> bindParam(":detail", $detail, PDO::PARAM_STR);
      $sql -> bindParam(":startDate", $startDate, PDO::PARAM_STR);
      $sql -> bindParam(":startTime", $startTime, PDO::PARAM_STR);
      $sql -> bindParam(":endDate", $endDate, PDO::PARAM_STR);
      $sql -> bindParam(":endTime", $endTime, PDO::PARAM_STR);
      $sql -> bindParam(":deadlineDate", $deadlineDate, PDO::PARAM_STR);
      $sql -> bindParam(":deadlineTime", $deadlineTime, PDO::PARAM_STR);
      // $sql -> bindParam(":orderNum", $index, PDO::PARAM_STR);
      $sql -> bindParam(":orderNum", $_POST["order"][$index], PDO::PARAM_STR);
      $sql -> bindParam(":completed", $completed, PDO::PARAM_STR);

      $sql -> execute();
    }
  }
}

//会社情報を取得
$sql = $pdo -> prepare("SELECT * FROM companies WHERE companies.users_id = :users_id AND companies.id = :companies_id");
$sql -> bindParam(":users_id", $_SESSION["id"], PDO::PARAM_STR);
$sql -> bindParam(":companies_id", $_POST["companies_id"], PDO::PARAM_STR);
$sql -> execute();
$details = $sql -> fetch();

//予定情報を取得
$sql = $pdo -> prepare("SELECT * FROM plans WHERE users_id = :users_id AND companies_id = :companies_id ORDER BY orderNum, startTime, startDate, endTime, endDate");
$sql -> bindParam(":users_id", $_SESSION["id"], PDO::PARAM_STR);
$sql -> bindParam(":companies_id", $_POST["companies_id"], PDO::PARAM_STR);
$sql -> execute();
$plans = $sql -> fetchAll();
//var_dump($_POST);
// var_dump($plans);

$minutes_list =  array("00", "05", "10", "15", "20", "25", "30", "35", "40", "45", "50", "55");

//var_dump($_POST);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>企業詳細</title>
<meta name="viewport" content="width=device-width">

<style type="text/css">
.jumbotron-extend{
  background-image: url("../../../../leaves-pattern.png");
}
table {
  border: 1px solid black;
  border-collapse: collapse;
  background-color: white;
}
th {
  border: 1px solid black;
  border-collapse: collapse;
  width: 120px;
  height: 40px;
}
td {
  border: 1px solid black;
  border-collapse: collapse;
  width: 120px;
  height: 80px;
}
#event_form_dummy {
  display: none;
}
.one_event_form {
  border: 1px solid black;
  border-collapse: collapse;
  margin: 10px 0;
}
#create_company_form{
  max-width: 600px;
  margin-right: auto;
  margin-left: auto;
}

</style>

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js" integrity="sha384-LtrjvnR4Twt/qOuYxE721u19sVFLVSA4hf/rRt6PrZTmiPltdZcI7q7PXQBYTKyf" crossorigin="anonymous"></script>

<script type="text/javascript">
  window.onload = function() {
    <?php foreach ($plans as $plan): ?>
    <?php
    if (!empty($plan["startDate"])) {
      $start_year = date("Y", strtotime($plan["startDate"]));
      $start_month = idate("m", strtotime($plan["startDate"]));
      $start_date = idate("d", strtotime($plan["startDate"]));
      $start_hour = idate("H", strtotime($plan["startTime"]));
      $start_minute = date("i", strtotime($plan["startTime"]));
    } else {
      $start_year = "";
      $start_month = "";
      $start_date = "";
      $start_hour = "";
      $start_minute = "";
    }
    if (!empty($plan["endDate"])) {
      $end_year = date("Y", strtotime($plan["endDate"]));
      $end_month = idate("m", strtotime($plan["endDate"]));
      $end_date = idate("d", strtotime($plan["endDate"]));
      $end_hour = idate("H", strtotime($plan["endTime"]));
      $end_minute = date("i", strtotime($plan["endTime"]));
    } else {
      $end_year = "";
      $end_month = "";
      $end_date = "";
      $end_hour = "";
      $end_minute = "";
    }
    if (!empty($plan["deadlineDate"])) {
      $deadline_year = date("Y", strtotime($plan["deadlineDate"]));
      $deadline_month = idate("m", strtotime($plan["deadlineDate"]));
      $deadline_date = idate("d", strtotime($plan["deadlineDate"]));
      $deadline_hour = idate("H", strtotime($plan["deadlineTime"]));
      $deadline_minute = date("i", strtotime($plan["deadlineTime"]));
    } else {
      $deadline_year = "";
      $deadline_month = "";
      $deadline_date = "";
      $deadline_hour = "";
      $deadline_minute = "";
    }
    if (!empty($plan["completed"])) {
      $completed = "1";
    } else {
      $completed = "0";
    }
    ?>
    //set_eventで使う変数の宣言
    var event = '<?php echo $plan["event"]; ?>';
    // console.log(event);
    var start_year = '<?php echo $start_year; ?>';
    var start_month = '<?php echo $start_month; ?>';
    var start_date = '<?php echo $start_date; ?>';
    var start_hour = '<?php echo $start_hour; ?>';
    var start_minute = '<?php echo $start_minute; ?>';
    var end_year = '<?php echo $end_year; ?>';
    var end_month = '<?php echo $end_month; ?>';
    var end_date = '<?php echo $end_date; ?>';
    var end_hour = '<?php echo $end_hour; ?>';
    var end_minute = '<?php echo $end_minute; ?>';
    var deadline_year = '<?php echo $deadline_year; ?>';
    var deadline_month = '<?php echo $deadline_month; ?>';
    var deadline_date = '<?php echo $deadline_date; ?>';
    var deadline_hour = '<?php echo $deadline_hour; ?>';
    var deadline_minute = '<?php echo $deadline_minute; ?>';
    var completed = '<?php echo $completed; ?>';
    //ページを開いたとき、予定をフォームに記入した状態にする
    set_event(event, start_year, start_month, start_date, start_hour, start_minute, end_year, end_month, end_date, end_hour, end_minute, deadline_year, deadline_month, deadline_date, deadline_hour, deadline_minute, completed);

    <?php endforeach; ?>
  }

</script>

</head>
<body class="jumbotron jumbotron-extend">
  <header>
    <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
      <div class="col-8">
        <a class="text-white h3" href="../../">就活管理アプリ Rak</a>
      </div>
      <div class="col-4">
        <a href="../../logout" class="btn btn-danger float-right">ログアウト</a>
      </div>
    </nav>
  </header>

  <h1>企業詳細</h1>

  <p>
    <?php echo $message; ?>
  </p>

  <a href="../" class="btn btn-secondary">企業一覧画面へ戻る</a>

  <div id="create_company_form">

    <form class="" action="" method="post">

      <p>
        <div>会社名</div>
        <input class="form-control" type="text" name="name" value="<?php echo $details["name"]; ?>" required>
      </p>

      <p>
        職種
        <input class="form-control" type="text" name="occupation" value="<?php echo $details["occupation"]; ?>" placeholder="総合職・技術職など">
      </p>

      <p>
        マイページURL
        <input class="form-control" type="text" name="URL" value="<?php echo $details["URL"]; ?>">
      </p>

      <p>
        詳細
        <textarea class="form-control" type="text" name="detail" rows="4" placeholder="ES・面接対策のメモなど"><?php echo $details["detail"]; ?></textarea>
      </p>

      <p>イベントの追加（10個まで）</p>

      <div id="event_form">

      </div>

      <div class="one_event_form bg-light" id="event_form_dummy">

        <div class="m-2">
          イベント
          <input class="form-control" type="text" name="event[0]" value="" placeholder="イベント名">
        </div>

        <div class="m-2">
          開始日時
          <select class="" name="start_year[0]">
            <?php
            for ($year_index = $year-2; $year_index < $year+10; $year_index++) {
              echo "<option value=".$year_index;
              if ($year_index == $year) {
                echo " selected";
              }
              echo ">";
              echo $year_index;
              echo "</option>";
            }
            ?>
          </select>
          年
          <select class="" name="start_month[0]">
            <?php
            for ($month_index = 1; $month_index <= 12; $month_index++) {
              echo "<option value=".$month_index;
              if ($month_index == $month) {
                echo " selected";
              }
              echo ">";
              echo $month_index;
              echo "</option>";
            }
            ?>
          </select>
          月
          <select class="" name="start_date[0]">
            <?php
            for ($date_index = 1; $date_index <= 31; $date_index++) {
              echo "<option value=".$date_index;
              if ($date_index == $date) {
                echo " selected";
              }
              echo ">";
              echo $date_index;
              echo "</option>";
            }
            ?>
          </select>
          日
          <select class="" name="start_hour[0]">
            <?php
            for ($hour_index = 0; $hour_index <= 23; $hour_index++) {
              echo "<option value=".$hour_index.">";
              echo $hour_index;
              echo "</option>";
            }
            ?>
          </select>
          時
          <select class="" name="start_minute[0]">
            <?php
            foreach ($minutes_list as $value) {
              echo "<option value=".$value.">";
              echo $value;
              echo "</option>";
            }
            ?>
          </select>
          分
        </div>

        <div class="m-2">
          終了日時
          <select class="" name="end_year[0]">
            <?php
            for ($year_index = $year-2; $year_index < $year+10; $year_index++) {
              echo "<option value=".$year_index;
              if ($year_index == $year) {
                echo " selected";
              }
              echo ">";
              echo $year_index;
              echo "</option>";
            }
            ?>
          </select>
          年
          <select class="" name="end_month[0]">
            <?php
            for ($month_index = 1; $month_index <= 12; $month_index++) {
              echo "<option value=".$month_index;
              if ($month_index == $month) {
                echo " selected";
              }
              echo ">";
              echo $month_index;
              echo "</option>";
            }
            ?>
          </select>
          月
          <select class="" name="end_date[0]">
            <?php
            for ($date_index = 1; $date_index <= 31; $date_index++) {
              echo "<option value=".$date_index;
              if ($date_index == $date) {
                echo " selected";
              }
              echo ">";
              echo $date_index;
              echo "</option>";
            }
            ?>
          </select>
          日
          <select class="" name="end_hour[0]">
            <?php
            for ($hour_index = 0; $hour_index <= 23; $hour_index++) {
              echo "<option value=".$hour_index.">";
              echo $hour_index;
              echo "</option>";
            }
            ?>
          </select>
          時
          <select class="" name="end_minute[0]">
            <?php
            foreach ($minutes_list as $value) {
              echo "<option value=".$value.">";
              echo $value;
              echo "</option>";
            }
            ?>
          </select>
          分
        </div>

        <div class="m-2">
          <label>日程が未定（締切を設定する） <input type="checkbox" name="deadline" data-index="0" value="1" onchange="deadlineCheck(event)"></label>
        </div>

        <div class="m-2">
          締切日時
          <select class="" name="deadline_year[0]" disabled>
            <?php
            echo "<option value=''></option>";
            for ($year_index = $year - 2; $year_index <= $year + 10; $year_index++) {
              echo "<option value='{$year_index}'>{$year_index}</option>";
            }
            ?>
          </select>
          年
          <select class="" name="deadline_month[0]" disabled>
            <?php
            echo "<option value=''></option>";
            for ($month_index = 1; $month_index <= 12; $month_index++) {
              echo "<option value='{$month_index}'>{$month_index}</option>";
            }
            ?>
          </select>
          月
          <select class="" name="deadline_date[0]" disabled>
            <?php
            echo "<option value=''></option>";
            for ($day_index = 1; $day_index <= 31 ; $day_index++) {
              echo "<option value='{$day_index}'>{$day_index}</option>";
            }
            ?>
          </select>
          日
          <select class="" name="deadline_hour[0]" disabled>
            <?php
            echo "<option value=''></option>";
            for ($hour_index = 0; $hour_index < 24 ; $hour_index++) {
              echo "<option value='{$hour_index}'>{$hour_index}</option>";
            }
            ?>
          </select>
          時
          <select class="" name="deadline_minute[0]" disabled>
            <?php
            echo "<option value=''></option>";
            foreach ($minutes_list as $value) {
              echo "<option value='{$value}'>{$value}</option>";
            }
            ?>
          </select>
          分
        </div>

        <div class="m-2">
          <label>締め切りが未定 <input type="checkbox" name="undecided" data-index="0" value="1" disabled onchange="undecidedCheck(event)"></label>
        </div>

        <div class="m-2">
          <label>イベントの順番（小さい方が先） <input type="text" name="order[0]" value="0"></label>
        </div>

        <div class="m-2">
          <label>予定を完了 <input type="checkbox" name="completed[0]" value="1"></label>
        </div>

        <div class="m-2">
          <button type="button" class="btn btn-danger" name="remove" data-index="0" onclick="remove_event(event)">イベントを削除</button>
        </div>

      </div>

      <p>
        <button type="button" class="btn btn-dark" name="button" id="add_event_form_btn">イベントを追加</button>
      </p>

      <p>
        <input type="hidden" id="index" name="max_index" value="0">
        <input type="hidden" name="companies_id" value="<?php echo $_POST["companies_id"]; ?>">
        <input type="submit" class="btn btn-primary" name="add_company_btn" value="企業情報の更新" onclick="return update_company()">
      </p>

      <p>
        <a href="../" class="btn btn-secondary">企業一覧画面へ戻る</a>
      </p>

    </form>

  </div>

  <script type="text/javascript">

  let button = document.getElementById("add_event_form_btn");
  //console.log(document.getElementById("event_form").firstElementChild);
  var i = 1;
  button.addEventListener("click", add_event_form);

  function add_event_form() { //イベントを追加ボタン
    let forms = document.getElementById("event_form");
    let dummy = document.getElementById("event_form_dummy");
    const clone = dummy.cloneNode(true);
    const form_num = forms.childElementCount;
    const max = 10;
    if (form_num < max) {
      //予定の数は1社につき10個まで
      clone.id = "event_form_dummy" + i;
      clone.style.display = "block";

      eventName = clone.children[0];
      start = clone.children[1];
      end = clone.children[2];
      check = clone.children[3];
      deadline = clone.children[4];
      undecided = clone.children[5];
      orderNum = clone.children[6];
      completedCheck = clone.children[7];
      remove = clone.children[8];

      eventName.children["event[0]"].name = "event["+ i +"]";

      start.children["start_year[0]"].name = "start_year["+ i +"]";
      start.children["start_month[0]"].name = "start_month["+ i +"]";
      start.children["start_date[0]"].name = "start_date["+ i +"]";
      start.children["start_hour[0]"].name = "start_hour["+ i +"]";
      start.children["start_minute[0]"].name = "start_minute["+ i +"]";

      end.children["end_year[0]"].name = "end_year["+ i +"]";
      end.children["end_month[0]"].name = "end_month["+ i +"]";
      end.children["end_date[0]"].name = "end_date["+ i +"]";
      end.children["end_hour[0]"].name = "end_hour["+ i +"]";
      end.children["end_minute[0]"].name = "end_minute["+ i +"]";

      // clone.children[3].getElementsByTagName("input")[0].name = "deadline["+ i +"]";
      check.getElementsByTagName("input")[0].dataset.index = i;

      deadline.children["deadline_year[0]"].name = "deadline_year["+ i +"]";
      deadline.children["deadline_month[0]"].name = "deadline_month["+ i +"]";
      deadline.children["deadline_date[0]"].name = "deadline_date["+ i +"]";
      deadline.children["deadline_hour[0]"].name = "deadline_hour["+ i +"]";
      deadline.children["deadline_minute[0]"].name = "deadline_minute["+ i +"]";

      // clone.children[5].getElementsByTagName("input")[0].name = "undecided["+ i +"]";
      undecided.getElementsByTagName("input")[0].dataset.index = i;

      orderNum.getElementsByTagName("input")[0].value = i;
      orderNum.getElementsByTagName("input")[0].name = "order["+ i +"]";

      completedCheck.getElementsByTagName("input")[0].name = "completed["+ i +"]";

      remove.children["remove"].dataset.index = i;

      child = forms.appendChild(clone);
      //console.log(child);
      const index = document.getElementById("index");
      index.value = i;
      i++;
    } else {
      alert("登録できるイベントの数は1社につき" + max + "個までです");
    }

  }

  function set_event(event, start_year, start_month, start_date, start_hour, start_minute, end_year, end_month, end_date, end_hour, end_minute, deadline_year, deadline_month, deadline_date, deadline_hour, deadline_minute, completed) { //ページを開いたときにイベントフォームが読み込まれる
    // console.log(completed);
    let forms = document.getElementById("event_form");
    let dummy = document.getElementById("event_form_dummy");
    const clone = dummy.cloneNode(true);
    clone.id = "event_form_dummy" + i;
    clone.style.display = "block";

    eventName = clone.children[0];
    start = clone.children[1];
    end = clone.children[2];
    check = clone.children[3];
    deadline = clone.children[4];
    undecided = clone.children[5];
    orderNum = clone.children[6];
    completedCheck = clone.children[7];
    remove = clone.children[8];

    eventName.children["event[0]"].value = event;
    eventName.children["event[0]"].name = "event["+ i +"]";

    if (!start_year) {
      start.children["start_year[0]"].disabled = true;
      start.children["start_month[0]"].disabled = true;
      start.children["start_date[0]"].disabled = true;
      start.children["start_hour[0]"].disabled = true;
      start.children["start_minute[0]"].disabled = true;
      check.getElementsByTagName("input")[0].checked = true;
      undecided.getElementsByTagName("input")[0].disabled = false;
    }

    start.children["start_year[0]"].value = start_year;
    start.children["start_year[0]"].name = "start_year["+ i +"]";
    start.children["start_month[0]"].value = start_month;
    start.children["start_month[0]"].name = "start_month["+ i +"]";
    start.children["start_date[0]"].value = start_date;
    start.children["start_date[0]"].name = "start_date["+ i +"]";
    start.children["start_hour[0]"].value = start_hour;
    start.children["start_hour[0]"].name = "start_hour["+ i +"]";
    start.children["start_minute[0]"].value = start_minute;
    start.children["start_minute[0]"].name = "start_minute["+ i +"]";

    if (!end_year) {
      end.children["end_year[0]"].disabled = true;
      end.children["end_month[0]"].disabled = true;
      end.children["end_date[0]"].disabled = true;
      end.children["end_hour[0]"].disabled = true;
      end.children["end_minute[0]"].disabled = true;
    }

    end.children["end_year[0]"].value = end_year;
    end.children["end_year[0]"].name = "end_year["+ i +"]";
    end.children["end_month[0]"].value = end_month;
    end.children["end_month[0]"].name = "end_month["+ i +"]";
    end.children["end_date[0]"].value = end_date;
    end.children["end_date[0]"].name = "end_date["+ i +"]";
    end.children["end_hour[0]"].value = end_hour;
    end.children["end_hour[0]"].name = "end_hour["+ i +"]";
    end.children["end_minute[0]"].value = end_minute;
    end.children["end_minute[0]"].name = "end_minute["+ i +"]";

    check.getElementsByTagName("input")[0].dataset.index = i;

    if (!deadline_year) {
      deadline.children["deadline_year[0]"].disabled = true;
      deadline.children["deadline_month[0]"].disabled = true;
      deadline.children["deadline_date[0]"].disabled = true;
      deadline.children["deadline_hour[0]"].disabled = true;
      deadline.children["deadline_minute[0]"].disabled = true;
      if (!start_year) {
        undecided.getElementsByTagName("input")[0].checked = true;
      }
    } else {
      deadline.children["deadline_year[0]"].disabled = false;
      deadline.children["deadline_month[0]"].disabled = false;
      deadline.children["deadline_date[0]"].disabled = false;
      deadline.children["deadline_hour[0]"].disabled = false;
      deadline.children["deadline_minute[0]"].disabled = false;
    }

    deadline.children["deadline_year[0]"].value = deadline_year;
    deadline.children["deadline_year[0]"].name = "deadline_year["+ i +"]";
    deadline.children["deadline_month[0]"].value = deadline_month;
    deadline.children["deadline_month[0]"].name = "deadline_month["+ i +"]";
    deadline.children["deadline_date[0]"].value = deadline_date;
    deadline.children["deadline_date[0]"].name = "deadline_date["+ i +"]";
    deadline.children["deadline_hour[0]"].value = deadline_hour;
    deadline.children["deadline_hour[0]"].name = "deadline_hour["+ i +"]";
    deadline.children["deadline_minute[0]"].value = deadline_minute;
    deadline.children["deadline_minute[0]"].name = "deadline_minute["+ i +"]";

    undecided.getElementsByTagName("input")[0].dataset.index = i;

    orderNum.getElementsByTagName("input")[0].value = i;
    orderNum.getElementsByTagName("input")[0].name = "order["+ i +"]";

    if (completed === "1") {
      completedCheck.getElementsByTagName("input")[0].checked = true;
    }

    completedCheck.getElementsByTagName("input")[0].name = "completed["+ i +"]";

    remove.children["remove"].dataset.index = i;

    child = forms.appendChild(clone);
    //console.log(child);
    const index = document.getElementById("index");
    index.value = i;
    i++;
  }

  function remove_event(e) {
    let forms = document.getElementById("event_form");
    console.log(e);
    let remove_index = e.currentTarget.dataset["index"];
    let child = document.getElementById("event_form_dummy" + remove_index);
    console.log(child);
    forms.removeChild(child);
    return true;
  }

  function update_company() {
    if(confirm("企業情報を更新してもよろしいですか？")) {
      return true;
    } else {
      return false;
    }
  }

  function deadlineCheck(e) {
    let deadline = e.currentTarget;
    let changedIndex = deadline.dataset.index;
    let form = document.getElementById("event_form_dummy" + changedIndex);
    let undecided = form.children[5].getElementsByTagName("input")[0];

    if (deadline.checked) {
      //開始日時・終了日時をdisabledにし、締切日時を操作可能に
      document.getElementsByName("start_year[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("start_month[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("start_date[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("start_hour[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("start_minute[" + changedIndex + "]")[0].disabled = true;

      document.getElementsByName("end_year[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("end_month[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("end_date[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("end_hour[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("end_minute[" + changedIndex + "]")[0].disabled = true;

      document.getElementsByName("deadline_year[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("deadline_month[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("deadline_date[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("deadline_hour[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("deadline_minute[" + changedIndex + "]")[0].disabled = false;

      undecided.disabled = false;
    } else {
      document.getElementsByName("start_year[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("start_month[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("start_date[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("start_hour[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("start_minute[" + changedIndex + "]")[0].disabled = false;

      document.getElementsByName("end_year[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("end_month[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("end_date[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("end_hour[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("end_minute[" + changedIndex + "]")[0].disabled = false;

      document.getElementsByName("deadline_year[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("deadline_month[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("deadline_date[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("deadline_hour[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("deadline_minute[" + changedIndex + "]")[0].disabled = true;

      undecided.disabled = false;
    }

  }

  function undecidedCheck(e) {
    let undecided = e.currentTarget;
    let changedIndex = undecided.dataset.index;
    // console.log(changedIndex);
    let form = document.getElementById("event_form_dummy" + changedIndex);
    let deadline = form.children[3].getElementsByTagName("input")[0];

    if (undecided.checked) {
      document.getElementsByName("deadline_year[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("deadline_month[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("deadline_date[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("deadline_hour[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("deadline_minute[" + changedIndex + "]")[0].disabled = true;
    } else if (deadline.checked) {
      document.getElementsByName("deadline_year[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("deadline_month[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("deadline_date[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("deadline_hour[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("deadline_minute[" + changedIndex + "]")[0].disabled = false;
    }

  }


  </script>

</body>
</html>
