<?php
session_start();
require("../../db.php");

date_default_timezone_set("Asia/Tokyo");

$pdo = connectDB();

//var_dump($_POST);

$year = date("Y");
$month = date("n");
$date = date("d");

//企業の追加ボタンを押したとき、登録する
if (!empty($_POST["add_company_btn"])) {

  $sql = $pdo -> prepare("INSERT INTO companies (users_id, name, occupation, URL, detail) VALUES (:users_id, :name, :occupation, :URL, :detail)");
  $sql -> bindParam(":users_id", $_SESSION["id"], PDO::PARAM_STR);
  $sql -> bindParam(":name", $_POST["name"], PDO::PARAM_STR);
  $sql -> bindParam(":occupation", $_POST["occupation"], PDO::PARAM_STR);
  $sql -> bindParam(":URL", $_POST["URL"], PDO::PARAM_STR);
  $sql -> bindParam(":detail", $_POST["detail"], PDO::PARAM_STR);
  $sql -> execute();
  $companies_id = $pdo -> lastInsertId();

  for ($i=0; $i <= $_POST["max_index"]; $i++) {
    if (!empty($_POST["event"][$i])) {
      //イベント情報を1つずつ登録

      $startDate = null;
      $startTime = null;
      $endDate = null;
      $endTime = null;
      $deadlineDate = null;
      $deadlineTime = null;
      $completed = "0";

      if (!empty($_POST["startDate"][$i])) {
        $startDate = $_POST["startDate"][$i];
        $startTime = $_POST["startTime"][$i];
      }
      if (!empty($_POST["endDate"][$i])) {
        $endDate = $_POST["endDate"][$i];
        $endTime = $_POST["endTime"][$i];
      }
      if (!empty($_POST["deadlineDate"][$i])) {
        $deadlineDate = $_POST["deadlineDate"][$i];
        $deadlineTime = $_POST["deadlineTime"][$i];
      }
      if (!empty($_POST["completed"][$i])) {
        $completed = "1";
      }
      $sql = $pdo -> prepare("INSERT INTO plans (users_id, companies_id, event, startDate, startTime, endDate, endTime, deadlineDate, deadlineTime, orderNum, completed) VALUES (:users_id, :companies_id, :event, :startDate, :startTime, :endDate, :endTime, :deadlineDate, :deadlineTime, :orderNum, :completed)");
      $sql -> bindParam(":users_id", $_SESSION["id"], PDO::PARAM_STR);
      $sql -> bindParam(":companies_id", $companies_id, PDO::PARAM_STR);
      $sql -> bindParam(":event", $_POST["event"][$i], PDO::PARAM_STR);
      //$sql -> bindParam(":detail", $_POST["detail"][$i], PDO::PARAM_STR);
      $sql -> bindParam(":startDate", $startDate, PDO::PARAM_STR);
      $sql -> bindParam(":startTime", $startTime, PDO::PARAM_STR);
      $sql -> bindParam(":endDate", $endDate, PDO::PARAM_STR);
      $sql -> bindParam(":endTime", $endTime, PDO::PARAM_STR);
      $sql -> bindParam(":deadlineDate", $deadlineDate, PDO::PARAM_STR);
      $sql -> bindParam(":deadlineTime", $deadlineTime, PDO::PARAM_STR);
      // $sql -> bindParam(":orderNum", $i, PDO::PARAM_STR);
      $sql -> bindParam(":orderNum", $_POST["order"][$index], PDO::PARAM_STR);
      $sql -> bindParam(":completed", $completed, PDO::PARAM_STR);
      $sql -> execute();
    }
  }

  header("Location: ./"); //多重読み込み防止のため
}

//削除ボタンを押したとき、企業情報と予定情報を削除する
if (!empty($_POST["delete_company_btn"])) {
  $sql = $pdo -> prepare("DELETE FROM companies WHERE id = :id");
  $sql -> bindParam(":id", $_POST["companies_id"], PDO::PARAM_STR);
  $sql -> execute();

  $sql = $pdo -> prepare("DELETE FROM plans WHERE companies_id = :companies_id");
  $sql -> bindParam(":companies_id", $_POST["companies_id"], PDO::PARAM_STR);
  $sql -> execute();

  header("Location: ./");
}

//会社情報・予定情報を取得
$companies = array();
$sql = $pdo -> prepare("SELECT * FROM companies LEFT JOIN (SELECT event, companies_id, startDate, startTime, endDate, endTime, deadlineDate, deadlineTime FROM plans WHERE completed = '0' ORDER BY companies_id, orderNum) AS list ON companies.id = list.companies_id WHERE companies.users_id = :users_id");
$sql -> bindParam(":users_id", $_SESSION["id"], PDO::PARAM_STR);
$sql -> execute();
$companiesa = $sql -> fetchAll();

$sql = $pdo -> prepare("SELECT * FROM companies LEFT JOIN (SELECT event, companies_id, startDate, startTime, endDate, endTime, deadlineDate, deadlineTime FROM plans WHERE completed = '0' ORDER BY companies_id, orderNum) AS list ON companies.id = list.companies_id WHERE companies.users_id = :users_id");
$sql -> bindParam(":users_id", $_SESSION["id"], PDO::PARAM_STR);
$sql -> execute();
$companiesb = $sql -> fetchAll();

$companies = array_merge($companiesa, $companiesb);

// var_dump($companies);

// $minutes_list =  array("00", "05", "10", "15", "20", "25", "30", "35", "40", "45", "50", "55");
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

#create_company_form {
  display: none;
}
#event_form_dummy {
  display: none;
}
.one_event_form {
  border: 1px solid black;
  border-collapse: collapse;
  margin: 10px 0;
}
input[type=checkbox] {
  transform: scale(1.5);
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
    location.href = "#create_company_form";
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

  <h1>企業一覧</h1>

  <p>
    <button type="button" class="btn btn-light" id="create_company_btn" name="button" onclick="display_create_company_form()">企業を追加する</button>
  </p>

  <div class="">
    <div class="">
      <table class="p-0 container table table-bordered table-striped bg-light">
        <tr class="row m-0">
          <th class="col-lg-2 col-md-2 col-sm-4 col-6">会社名</th>
          <th class="col-lg-2 col-md-2 col-sm-4 col-6">次回選考</th>
          <th class="col-lg-2 col-md-2 col-sm-4 col-6">日時</th>
          <th class="col-lg-2 col-md-2 col-sm-4 col-6 table-mypage">マイページ</th>
          <th class="col-lg-2 col-md-2 col-sm-4 col-6 table-detail">詳細／編集</th>
          <th class="col-lg-2 col-md-2 col-sm-4 col-6 table-delete">削除</th>
        </tr>
        <?php
        $temp = array(); //既に表示された会社のidを保存
        foreach ($companies as $value) :
          if (empty($temp[$value["id"]])): //既に表示された会社は表示しない
        ?>
        <tr class="row m-0">
          <td class="col-lg-2 col-md-2 col-sm-4 col-6"><?php echo $value["name"]; ?></td>
          <td class="col-lg-2 col-md-2 col-sm-4 col-6"><?php echo $value["event"]; ?></td>
          <td class="col-lg-2 col-md-2 col-sm-4 col-6">
            <?php
            if (!empty($value["startDate"])) {
              echo date("m/d", strtotime($value["startDate"]))."<br>";
            }
            if (!empty($value["startTime"])) {
              echo date("H:i", strtotime($value["startTime"]))."～".date("H:i", strtotime($value["endTime"]));
            }
            if (!empty($value["deadlineDate"])) {
              echo "締切：<br>";
              echo date("m/d ", strtotime($value["deadlineDate"])).date("H:i", strtotime($value["deadlineTime"]));
            }
            ?>
          </td>
          <td class="col-lg-2 col-md-2 col-sm-4 col-6 table-mypage btn-content">
            <?php if (!empty($value["URL"])) : ?>
            <a class="btn btn-primary table-btn" href="<?php echo $value["URL"]; ?>" target="_blank" rel="noopener noreferrer">マイページ</a>
            <?php endif; ?>
          </td>
          <td class="col-lg-2 col-md-2 col-sm-4 col-6 table-detail btn-content">
            <form action="./detail/" method="post">
              <input type="hidden" name="companies_id" value="<?php echo $value["id"]; ?>">
              <button class="btn btn-dark table-btn" type="submit" name="button">詳細</button>
            </form>
          </td>
          <td class="col-lg-2 col-md-2 col-sm-4 col-6 table-delete btn-content">
            <form action="" method="post">
              <input type="hidden" name="companies_id" value="<?php echo $value["id"]; ?>">
              <input class="btn btn-danger table-btn" type="submit" name="delete_company_btn" value="削除" onclick="return delete_company('<?php echo $value["name"]; ?>')">
            </form>
          </td>
        </tr>
        <?php
          $temp[$value["id"]] = 1;
          endif;
        endforeach;
        ?>
      </table>

      <div class="container">
        <!-- <p>
          <button type="button" class="btn btn-light" id="create_company_btn" name="button" onclick="display_create_company_form()">企業を追加する</button>
        </p> -->

        <div id="create_company_form" class="bg-light">

          <form class="" action="" method="post">

            <p class="m-2 h3">
              企業の追加
            </p>

            <p class="m-2">
              会社名
              <input type="text" class="form-control" name="name" value="" required>
            </p>

            <p class="m-2">
              職種
              <input type="text" class="form-control" name="occupation" value="" placeholder="総合職・技術職など">
            </p>

            <p class="m-2">
              マイページURL
              <input type="text" class="form-control" name="URL" value="">
            </p>

            <p class="m-2">
              詳細
              <input type="text" class="form-control" name="detail" value="" placeholder="ES・面接対策のメモなど">
            </p>

            <p>イベントの追加</p>

            <div id="event_form">

            </div>

            <div class="one_event_form bg-light" id="event_form_dummy">

              <div class="m-2">
                イベント
                <input type="text" name="event[0]" value="" placeholder="イベント名">
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

            <p class="m-2">
              <button type="button" class="btn btn-dark" name="button" id="add_event_form_btn">イベントを追加</button>
            </p>

            <p class="m-2">
              <input type="hidden" id="index" name="max_index" value="0">
              <input type="submit" class="btn btn-primary" name="add_company_btn" value="企業の追加">
            </p>

          </form>

        </div>

      </div>
    </div>
  </div>

  <script type="text/javascript">
  let button = document.getElementById("add_event_form_btn");
  //console.log(document.getElementById("event_form").firstElementChild);
  var i = 1;
  button.addEventListener("click", function(){
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

      const index = document.getElementById("index");
      index.value = i;
      i++;

      location.href = "#add_event_form_btn";
    } else {
      alert("登録できるイベントの数は1社につき" + max + "個までです");
    }

  });

  function remove_event(e) {
    let forms = document.getElementById("event_form");
    let remove_index = e.currentTarget.dataset.index;
    let child = document.getElementById("event_form_dummy" + remove_index);
    forms.removeChild(child);
    return true;
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
