<?php
require("autoload.php"); // Menggunakan autoload Composer untuk phpMQTT
use Bluerhinos\phpMQTT;

// Konfigurasi server MQTT
$host = "x2.revolusi-it.com";
$port = 1883;
$username = "usm";
$password = "usmjaya001";
$clientId = "phpMQTTClient";

// Topik kontrol LED
$topic_control = "g231220071/control";
// Topik data suhu dan kelembapan
$topic_temperature = "g231220071/temperature";
$topic_humidity = "g231220071/humidity";

// Koneksi ke database MySQL
$host_db = "localhost";
$user_db = "root";
$pass_db = "";
$db_name = "mqtt_monitoring";

$conn = new mysqli($host_db, $user_db, $pass_db, $db_name);
if ($conn->connect_error) {
  die("Koneksi Database Gagal: " . $conn->connect_error);
}

// Fungsi untuk mengirim pesan ke broker MQTT
function sendMQTTMessage($host, $port, $username, $password, $clientId, $topic, $message)
{
  $mqtt = new Bluerhinos\phpMQTT($host, $port, $clientId);
  if ($mqtt->connect(true, NULL, $username, $password)) {
    $mqtt->publish($topic, $message, 0);
    $mqtt->close();
    return true;
  }
  return false;
}

// Mengambil parameter suhu dan kelembapan
$temperature = isset($_GET['temperature']) ? floatval($_GET['temperature']) : null;
$humidity = isset($_GET['humidity']) ? floatval($_GET['humidity']) : null;

if ($temperature !== null && $humidity !== null) {
  // Simpan data suhu dan kelembapan ke database
  $stmt = $conn->prepare("INSERT INTO sensor_data (temperature, humidity) VALUES (?, ?)");
  $stmt->bind_param("dd", $temperature, $humidity);
  $stmt->execute();
  $stmt->close();

  // Kirim data ke topik MQTT
  sendMQTTMessage($host, $port, $username, $password, $clientId, $topic_temperature, $temperature);
  sendMQTTMessage($host, $port, $username, $password, $clientId, $topic_humidity, $humidity);

  // Kendali LED otomatis berdasarkan suhu
  $message_auto = "off";  // Default status LED
  if ($temperature > 30) {
    $message_auto = "on_red";  // Suhu > 30, hidupkan LED Merah
  } elseif ($temperature < 20) {
    $message_auto = "on_blue";  // Suhu < 20, hidupkan LED Biru
  } elseif ($humidity > 70) {
    $message_auto = "on_green";  // Kelembapan > 70%, hidupkan LED Hijau
  }

  // Kirim perintah otomatis ke MQTT
  sendMQTTMessage($host, $port, $username, $password, $clientId, $topic_control, $message_auto);
  echo "Data suhu dan kelembapan berhasil dikirim dan LED dikendalikan.";
} else {
  echo "Parameter suhu dan kelembapan tidak lengkap!";
}
?>