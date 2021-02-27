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

      if (!empty($_POST["startDate"][$index])) {
        $startDate = $_POST["startDate"][$index];
        $startTime = $_POST["startTime"][$index];
      }
      if (!empty($_POST["endDate"][$index])) {
        $endDate = $_POST["endDate"][$index];
        $endTime = $_POST["endTime"][$index];
      }
      if (!empty($_POST["deadlineDate"][$index])) {
        $deadlineDate = $_POST["deadlineDate"][$index];
        $deadlineTime = $_POST["deadlineTime"][$index];
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

// $minutes_list =  array("00", "05", "10", "15", "20", "25", "30", "35", "40", "45", "50", "55");

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
input[type=checkbox] {
  transform: scale(1.5);
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
      $startDate = $plan["startDate"];
      $startTime = $plan["startTime"];
    } else {
      $startDate = "";
      $startTime = "";
    }
    if (!empty($plan["endDate"])) {
      $endDate = $plan["endDate"];
      $endTime = $plan["endTime"];
    } else {
      $endDate = "";
      $endTime = "";
    }
    if (!empty($plan["deadlineDate"])) {
      $deadlineDate = $plan["deadlineDate"];
      $deadlineTime = $plan["deadlineTime"];
    } else {
      $deadlineDate = "";
      $deadlineTime = "";
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
    var startDate = '<?php echo $startDate; ?>';
    var startTime = '<?php echo $startTime; ?>';
    var endDate = '<?php echo $endDate; ?>';
    var endTime = '<?php echo $endTime; ?>';
    var deadlineDate = '<?php echo $deadlineDate; ?>';
    var deadlineTime = '<?php echo $deadlineTime; ?>';
    var completed = '<?php echo $completed; ?>';
    //ページを開いたとき、予定をフォームに記入した状態にする
    set_event(event, startDate, startTime, endDate, endTime, deadlineDate, deadlineTime, completed);

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
          <input type="date" name="startDate[0]">
          <input type="time" name="startTime[0]">
        </div>

        <div class="m-2">
          終了日時
          <input type="date" name="endDate[0]">
          <input type="time" name="endTime[0]">
        </div>

        <div class="m-2">
          <label>日程が未定（締切を設定する） <input type="checkbox" name="deadline" data-index="0" value="1" onchange="deadlineCheck(event)"></label>
        </div>

        <div class="m-2">
          締切日時
          <input type="date" name="deadlineDate[0]" disabled>
          <input type="time" name="deadlineTime[0]" disabled>
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
    const max = 20;
    if (form_num < max) {
      //予定の数は1社につき20個まで
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

      eventName.children["event[0]"].required = true;
      eventName.children["event[0]"].name = "event["+ i +"]";

      start.getElementsByTagName("input")[0].name = "startDate["+ i +"]";
      start.getElementsByTagName("input")[1].name = "startTime["+ i +"]";

      end.getElementsByTagName("input")[0].name = "endDate["+ i +"]";
      end.getElementsByTagName("input")[1].name = "endTime["+ i +"]";

      // clone.children[3].getElementsByTagName("input")[0].name = "deadline["+ i +"]";
      check.getElementsByTagName("input")[0].dataset.index = i;

      deadline.getElementsByTagName("input")[0].name = "deadlineDate["+ i +"]";
      deadline.getElementsByTagName("input")[1].name = "deadlineTime["+ i +"]";

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
      location.href = "#add_event_form_btn";

    } else {
      alert("登録できるイベントの数は1社につき" + max + "個までです");
    }

  }

  function set_event(event, startDate, startTime, endDate, endTime, deadlineDate, deadlineTime, completed) { //ページを開いたときにイベントフォームが読み込まれる
    // console.log(completed);
    //関数の引数を変える
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
    eventName.children["event[0]"].required = true;
    eventName.children["event[0]"].name = "event["+ i +"]";

    if (!startDate) {
      start.getElementsByTagName("input")[0].disabled = true;
      start.getElementsByTagName("input")[1].disabled = true;
      check.getElementsByTagName("input")[0].checked = true;
      undecided.getElementsByTagName("input")[0].disabled = false;
    }

    start.getElementsByTagName("input")[0].value = startDate;
    start.getElementsByTagName("input")[0].name = "startDate["+ i +"]";
    start.getElementsByTagName("input")[1].value = startTime;
    start.getElementsByTagName("input")[1].name = "startTime["+ i +"]";

    if (!endDate) {
      end.getElementsByTagName("input")[0].disabled = true;
      end.getElementsByTagName("input")[1].disabled = true;
    }

    end.getElementsByTagName("input")[0].value = endDate;
    end.getElementsByTagName("input")[0].name = "endDate["+ i +"]";
    end.getElementsByTagName("input")[1].value = endTime;
    end.getElementsByTagName("input")[1].name = "endTime["+ i +"]";

    check.getElementsByTagName("input")[0].dataset.index = i;

    if (!deadlineDate) {
      deadline.getElementsByTagName("input")[0].disabled = true;
      deadline.getElementsByTagName("input")[1].disabled = true;
      if (!startDate) {
        undecided.getElementsByTagName("input")[0].checked = true;
      }
    } else {
      deadline.getElementsByTagName("input")[0].disabled = false;
      deadline.getElementsByTagName("input")[1].disabled = false;
    }

    deadline.getElementsByTagName("input")[0].value = deadlineDate;
    deadline.getElementsByTagName("input")[0].name = "deadlineDate["+ i +"]";
    deadline.getElementsByTagName("input")[1].value = deadlineTime;
    deadline.getElementsByTagName("input")[1].name = "deadlineTime["+ i +"]";

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
      document.getElementsByName("startDate[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("startTime[" + changedIndex + "]")[0].disabled = true;

      document.getElementsByName("endDate[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("endTime[" + changedIndex + "]")[0].disabled = true;

      document.getElementsByName("deadlineDate[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("deadlineTime[" + changedIndex + "]")[0].disabled = false;

      undecided.disabled = false;
    } else {
      document.getElementsByName("startDate[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("startTime[" + changedIndex + "]")[0].disabled = false;

      document.getElementsByName("endDate[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("endTime[" + changedIndex + "]")[0].disabled = false;

      document.getElementsByName("deadlineDate[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("deadlineTime[" + changedIndex + "]")[0].disabled = true;

      undecided.disabled = true;
      undecided.checked = false;
    }

  }

  function undecidedCheck(e) {
    let undecided = e.currentTarget;
    let changedIndex = undecided.dataset.index;
    // console.log(changedIndex);
    let form = document.getElementById("event_form_dummy" + changedIndex);
    let deadline = form.children[3].getElementsByTagName("input")[0];

    if (undecided.checked) {
      document.getElementsByName("deadlineDate[" + changedIndex + "]")[0].disabled = true;
      document.getElementsByName("deadlineTime[" + changedIndex + "]")[0].disabled = true;
    } else if (deadline.checked) {
      document.getElementsByName("deadlineDate[" + changedIndex + "]")[0].disabled = false;
      document.getElementsByName("deadlineTime[" + changedIndex + "]")[0].disabled = false;
    }

  }


  </script>

</body>
</html>
