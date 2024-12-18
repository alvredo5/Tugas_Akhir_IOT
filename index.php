<?php
// Koneksi database MySQL
$host_db = "localhost";
$user_db = "root";
$pass_db = "";
$db_name = "mqtt_monitoring";

$conn = new mysqli($host_db, $user_db, $pass_db, $db_name);
if ($conn->connect_error) {
  die("Koneksi Database Gagal: " . $conn->connect_error);
}

// Mengambil data suhu dan kelembapan terakhir dari database
$result = $conn->query("SELECT * FROM sensor_data ORDER BY id DESC LIMIT 1");
$row = $result->fetch_assoc();
$temperature = $row['temperature'] ?? '--';
$humidity = $row['humidity'] ?? '--';

// Fungsi untuk menampilkan grafik suhu dan kelembapan
function generateChartData($conn)
{
  $data = [];
  $result = $conn->query("SELECT temperature, humidity, timestamp FROM sensor_data ORDER BY timestamp DESC LIMIT 10");
  while ($row = $result->fetch_assoc()) {
    $data[] = ['time' => $row['timestamp'], 'temperature' => $row['temperature'], 'humidity' => $row['humidity']];
  }
  return $data;
}

$chartData = generateChartData($conn);
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Monitoring dan Kontrol Ruangan</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 20px;
      background-color: #f4f4f4;
    }

    h1 {
      color: #333;
      text-align: center;
    }

    .box {
      padding: 20px;
      margin: 20px auto;
      border-radius: 8px;
      background: #fff;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      max-width: 600px;
    }

    .status {
      font-size: 1.5em;
      color: #007BFF;
      text-align: center;
    }

    .led-container {
      display: flex;
      justify-content: space-around;
      margin-top: 20px;
      text-align: center;
    }

    .led-indicator {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background-color: #ccc;
      /* Default abu-abu */
    }

    .led-red {
      background-color: #dc3545;
    }

    .led-blue {
      background-color: #007bff;
    }

    .led-green {
      background-color: #28a745;
    }

    /* Tombol Dinamis */
    .button {
      padding: 10px 20px;
      font-size: 1em;
      border: none;
      border-radius: 5px;
      color: white;
      cursor: pointer;
      margin-top: 10px;
      transition: background-color 0.3s ease;
    }

    .button-off {
      background-color: #6c757d;
    }

    .button-red {
      background-color: #dc3545;
    }

    .button-blue {
      background-color: #007bff;
    }

    .button-green {
      background-color: #28a745;
    }
  </style>
</head>

<body>
  <h1>Sistem Monitoring dan Kontrol Ruangan</h1>

  <div class="box">
    <h2>Data Sensor</h2>
    <p>Suhu: <span class="status"><?php echo $temperature; ?> &deg;C</span></p>
    <p>Kelembapan: <span class="status"><?php echo $humidity; ?> %</span></p>

    <!-- Grafik Suhu dan Kelembapan -->
    <h3>Grafik Suhu dan Kelembapan Terakhir</h3>
    <canvas id="temperatureChart"></canvas>
    <script>
      const data = <?php echo json_encode($chartData); ?>;
      const labels = data.map(item => item.time);
      const temperatureData = data.map(item => item.temperature);
      const humidityData = data.map(item => item.humidity);

      const ctx = document.getElementById('temperatureChart').getContext('2d');
      const chart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Suhu (°C)',
            data: temperatureData,
            borderColor: 'rgba(255, 99, 132, 1)',
            fill: false,
          }, {
            label: 'Kelembapan (%)',
            data: humidityData,
            borderColor: 'rgba(54, 162, 235, 1)',
            fill: false,
          }]
        }
      });
    </script>
  </div>

  <!-- Indikator LED dan Tombol -->
  <div class="box">
    <h2>Status LED</h2>
    <div class="led-container">
      <!-- LED Merah -->
      <div>
        <?php
        if ($temperature > 30) {
          $led_red_status = 'led-red';
          $button_red_status = 'button-red';
          $red_text = 'Menyala';
          $red_button_text = 'ON';
        } else {
          $led_red_status = '';
          $button_red_status = 'button-off';
          $red_text = 'Mati';
          $red_button_text = 'OFF';
        }
        ?>
        <div class="led-indicator <?php echo $led_red_status; ?>"></div>
        <p>LED Merah: <?php echo $red_text; ?></p>
        <button class="button <?php echo $button_red_status; ?>"><?php echo $red_button_text; ?></button>
      </div>

      <!-- LED Biru -->
      <div>
        <?php
        if ($temperature < 29) {
          $led_blue_status = 'led-blue';
          $button_blue_status = 'button-blue';
          $blue_text = 'Menyala';
          $blue_button_text = 'ON';
        } else {
          $led_blue_status = '';
          $button_blue_status = 'button-off';
          $blue_text = 'Mati';
          $blue_button_text = 'OFF';
        }
        ?>
        <div class="led-indicator <?php echo $led_blue_status; ?>"></div>
        <p>LED Biru: <?php echo $blue_text; ?></p>
        <button class="button <?php echo $button_blue_status; ?>"><?php echo $blue_button_text; ?></button>
      </div>

      <!-- LED Hijau -->
      <div>
        <?php
        if ($humidity > 50) {
          $led_green_status = 'led-green';
          $button_green_status = 'button-green';
          $green_text = 'Menyala';
          $green_button_text = 'ON';
        } else {
          $led_green_status = '';
          $button_green_status = 'button-off';
          $green_text = 'Mati';
          $green_button_text = 'OFF';
        }
        ?>
        <div class="led-indicator <?php echo $led_green_status; ?>"></div>
        <p>LED Hijau: <?php echo $green_text; ?></p>
        <button class="button <?php echo $button_green_status; ?>"><?php echo $green_button_text; ?></button>
      </div>
    </div>
  </div>
</body>

</html>