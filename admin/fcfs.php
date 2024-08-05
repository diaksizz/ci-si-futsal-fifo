<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "dbfutsal");
require "../session.php";
if ($role !== 'Admin') {
    header("location:../login.php");
}

function fetchSewaData($conn, $selectedDate = null) {
    $query = "
        SELECT s.*, l.212279_nama AS nama_lapangan, u.212279_nama_lengkap AS nama_user
        FROM sewa_212279 s
        JOIN lapangan_212279 l ON s.212279_id_lapangan = l.212279_id_lapangan
        JOIN user_212279 u ON s.212279_id_user = u.212279_id_user
    ";
    if ($selectedDate) {
        $query .= " WHERE s.212279_tanggal_pesan = '$selectedDate'";
    }
    $query .= " ORDER BY s.212279_tanggal_pesan, s.212279_jam_mulai";
    return mysqli_query($conn, $query);
}

function calculateFCFS($data) {
    $results = [];
    $currentTime = 0;

    while ($row = mysqli_fetch_assoc($data)) {
        $arrivalTime = strtotime($row['212279_tanggal_pesan'] . ' ' . $row['212279_jam_mulai']);
        $burstTime = strtotime($row['212279_jam_habis']) - strtotime($row['212279_jam_mulai']);

        $startTime = max($arrivalTime, $currentTime);
        $completionTime = $startTime + $burstTime;
        $turnaroundTime = $completionTime - $arrivalTime;
        $waitingTime = $turnaroundTime - $burstTime;

        $row['start_time'] = date("H:i:s", $startTime);
        $row['completion_time'] = date("H:i:s", $completionTime);
        $row['turnaround_time'] = $turnaroundTime / 60;
        $row['waiting_time'] = $waitingTime / 60;

        $results[] = $row;
        $currentTime = $completionTime;
    }
    return $results;
}

$selectedDate = isset($_POST['selected_date']) ? $_POST['selected_date'] : null;
$data = fetchSewaData($conn, $selectedDate);
$fcfsData = calculateFCFS($data);

// Calculate averages
$totalWaitingTime = 0;
$totalTurnaroundTime = 0;
$count = count($fcfsData);

foreach ($fcfsData as $row) {
    $totalWaitingTime += $row['waiting_time'];
    $totalTurnaroundTime += $row['turnaround_time'];
}

$averageWaitingTime = $count ? $totalWaitingTime / $count : 0;
$averageTurnaroundTime = $count ? $totalTurnaroundTime / $count : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <title>FCFS Statistik</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css">
    <style>
        .center {
            margin: 50px auto;
            width: 90%;
            background: #fff;
            border-radius: 10px;
            box-shadow: 10px 10px 15px rgba(0, 0, 0, 0.5);
            padding: 20px;
        }
        .center h1 {
            text-align: center;
            padding: 20px 0;
            color: #333;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row min-vh-100">
      <div class="sidebar col-2 bg-secondary">
        <!-- Sidebar -->
        <h5 class="mt-5 judul text-center"><?= $_SESSION["username"]; ?></h5>
        <ul class="list-group list-group-flush">
          <li class="list-group-item bg-transparent"><a href="home.php">Home</a></li>
          <li class="list-group-item bg-transparent"><a href="member.php">Data Member</a></li>
          <li class="list-group-item bg-transparent"><a href="lapangan.php">Data Lapangan</a></li>
          <li class="list-group-item bg-transparent"><a href="pesan.php">Data Pesanan</a></li>
          <li class="list-group-item bg-transparent"><a href="admin.php">Data Admin</a></li>
          <li class="list-group-item bg-transparent"><a href="fcfs.php">Statistik Antrian FCFS</a></li>
          <li class="list-group-item bg-transparent"></li>
        </ul>
        <a href="../logout.php" class="mt-5 btn btn-inti text-dark">Logout</a>
      </div>
        <div class="col-10 p-5 mt-5">
            <!-- <div class="center"> -->
                <h3 class="judul">Data FCFS</h3>
                <hr>
                <form method="post" class="form-inline mb-5">
                    <div class="form-group">
                        <label for="selected_date">Select Date:</label>
                        <input type="date" id="selected_date" name="selected_date" class="form-control" value="<?= htmlspecialchars($selectedDate) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
                <table id="fcfsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                    <tr>
                        <th>ID Sewa</th>
                        <!-- <th>ID User</th> -->
                        <th>Nama User</th>
                        <!-- <th>ID Lapangan</th> -->
                        <th>Nama Lapangan</th>
                        <th>Tanggal Pesan</th>
                        <th>Lama Sewa</th>
                        <th>Jam Mulai</th>
                        <th>Jam Habis</th>
                        <th>Harga</th>
                        <th>Total</th>
                        <th>Start Time</th>
                        <th>Completion Time</th>
                        <th>Turnaround Time (min)</th>
                        <th>Waiting Time (min)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($fcfsData as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['212279_id_sewa']) ?></td>
                            <td><?= htmlspecialchars($row['nama_user']) ?></td>
                            <td><?= htmlspecialchars($row['nama_lapangan']) ?></td>
                            <td><?= htmlspecialchars($row['212279_tanggal_pesan']) ?></td>
                            <td><?= htmlspecialchars($row['212279_lama_sewa']) ?></td>
                            <td><?= htmlspecialchars($row['212279_jam_mulai']) ?></td>
                            <td><?= htmlspecialchars($row['212279_jam_habis']) ?></td>
                            <td><?= htmlspecialchars($row['212279_harga']) ?></td>
                            <td><?= htmlspecialchars($row['212279_total']) ?></td>
                            <td><?= htmlspecialchars($row['start_time']) ?></td>
                            <td><?= htmlspecialchars($row['completion_time']) ?></td>
                            <td><?= htmlspecialchars($row['turnaround_time']) ?></td>
                            <td><?= htmlspecialchars($row['waiting_time']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="mt-4">
                    <p><strong>Average Waiting Time:</strong> <?= number_format($averageWaitingTime, 2) ?> minutes</p>
                    <p><strong>Average Turnaround Time:</strong> <?= number_format($averageTurnaroundTime, 2) ?> minutes</p>
                </div>
            <!-- </div> -->
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.flash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js"></script>
<script>
    $(document).ready(function() {
        $('#fcfsTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });
    });
</script>
</body>
</html>
