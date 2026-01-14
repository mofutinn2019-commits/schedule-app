<?php
require 'db.php';
session_start();

/* ログインチェック */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* 削除処理 */
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare(
        "DELETE FROM schedules WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$_GET['delete'], $user_id]);
    header("Location: index.php");
    exit;
}

/* 追加・編集処理 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['edit_id'])) {
        // 編集
        $stmt = $pdo->prepare(
            "UPDATE schedules SET title=?, schedule_date=?
             SET title=?, schedule_date=?, color=?
             WHERE id=? AND user_id=?"
        );
        $stmt->execute([
            $_POST['title'],
            $_POST['schedule_date'],
            $_POST['color'],
            $_POST['edit_id'],
            $user_id
        ]);
    } else {
        // 追加
        $stmt = $pdo->prepare(
            "INSERT INTO schedules (user_id, title, schedule_date, start_time, color)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
          $user_id,
          $_POST['title'],
          $_POST['schedule_date'],
          $_POST['start_time'],
          $_POST['color']
        ]);
        
    }
}

/* ===== カレンダー用変数 ===== */
$year  = (int)($_GET['year']  ?? date('Y'));
$month = (int)($_GET['month'] ?? date('n'));

/* 前月・次月 */
$prev = strtotime("$year-$month-01 -1 month");
$next = strtotime("$year-$month-01 +1 month");

$prevYear  = date('Y', $prev);
$prevMonth = date('n', $prev);
$nextYear  = date('Y', $next);
$nextMonth = date('n', $next);

$firstDay   = strtotime("$year-$month-01");
$lastDay    = date('t', $firstDay);
$startWeek  = date('w', $firstDay);
$today      = date('Y-m-d');

/* 予定取得 */
$stmt = $pdo->prepare(
    "SELECT * FROM schedules
     WHERE user_id = ?
     AND schedule_date BETWEEN ? AND ?"
);

$startDate = "$year-$month-01";
$endDate   = "$year-$month-$lastDay";

$stmt->execute([$user_id, $startDate, $endDate]);

$events = [];
foreach ($stmt as $row) {
    $events[$row['schedule_date']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- ▼ ここに入れる ▼ -->
<link rel="manifest" href="/schedule_app/manifest.json">

<!-- iOS用（超重要） -->
<link rel="apple-touch-icon" href="/schedule_app/icons/icon-192.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="予定管理">
<!-- ▲ ここまで ▲ -->
<title>予定管理</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-text text-white">
        <?= htmlspecialchars($_SESSION['email']) ?>
    </span>
    <a href="logout.php" class="btn btn-outline-light btn-sm">ログアウト</a>
</nav>

<div class="container mt-4">

<!-- ▼ 月切り替え -->
<h4 class="text-center mb-3 d-flex justify-content-between align-items-center">
  <a class="btn btn-outline-secondary btn-sm"
     href="?year=<?= $prevYear ?>&month=<?= $prevMonth ?>">＜ 前</a>

  <span><?= $year ?>年 <?= $month ?>月</span>

  <a class="btn btn-outline-secondary btn-sm"
     href="?year=<?= $nextYear ?>&month=<?= $nextMonth ?>">次 ＞</a>
</h4>

<table class="table table-bordered bg-white shadow-sm text-center">
<thead class="table-light">
<tr>
<th class="text-danger">日</th>
<th>月</th><th>火</th><th>水</th><th>木</th><th>金</th>
<th class="text-primary">土</th>
</tr>
</thead>
<tbody>
<tr>
<?php
for ($i = 0; $i < $startWeek; $i++) {
    echo "<td></td>";
}

for ($day = 1; $day <= $lastDay; $day++) {
    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $bg = ($date === $today) ? 'table-warning' : '';

    echo "<td class='align-top calendar-cell $bg'
          data-date='$date'
          style='height:120px; cursor:pointer;'>";

    echo "<strong>$day</strong><br>";

    if (isset($events[$date])) {
        foreach ($events[$date] as $e) {
            $time = isset($e['start_time']) ? substr($e['start_time'], 0, 5) : '';

    echo "<div class='badge bg-{$e['color']} d-block mt-1'>
            {$time} ".htmlspecialchars($e['title'])."
                    <a href='?delete={$e['id']}'
                       onclick='return confirm(\"削除しますか？\")'
                       class='text-white ms-1'>×</a>
                  </div>";
        }
    }

    echo "</td>";

    if ((($day + $startWeek) % 7) == 0) {
        echo "</tr><tr>";
    }
}
?>
</tr>
</tbody>
</table>
</div>

<!-- モーダル -->
<div class="modal fade" id="addModal">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">予定を追加</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="schedule_date" id="modalDate">
        <input type="hidden" name="edit_id" id="editId">

        <div class="mb-3">
            <label class="form-label">内容</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">開始時刻</label>
            <input type="time" name="start_time" class="form-control" required>
        </div>

        <div class="mb-3">
  <label class="form-label">色</label>
  <select name="color" class="form-select">
    <option value="primary">青</option>
    <option value="success">緑</option>
    <option value="danger">赤</option>
    <option value="warning">黄</option>
    <option value="info">水色</option>
  </select>
</div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">保存</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.calendar-cell').forEach(cell => {
    cell.addEventListener('click', () => {
        document.getElementById('modalDate').value = cell.dataset.date;
        document.getElementById('editId').value = '';
        new bootstrap.Modal(document.getElementById('addModal')).show();
    });
});
</script>
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/schedule_app/sw.js')
    .then(() => console.log('Service Worker registered'));
}
</script>

</body>
</html>
