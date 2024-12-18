#include <ESP8266WiFi.h>
#include <PubSubClient.h>
#include <DHT.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

// Konfigurasi WiFi dan MQTT
const char* ssid = "Orangdalam";
const char* password = "09876543";
const char* mqtt_server = "x2.revolusi-it.com";
const char* mqtt_user = "usm";
const char* mqtt_password = "usmjaya001";
const char* topik = "g231220071"; // Menambahkan topik

WiFiClient espClient;
PubSubClient client(espClient);

// Pin Konfigurasi
#define DHTPIN D5
#define DHTTYPE DHT11
DHT dht(DHTPIN, DHTTYPE);

#define LED_RED D6   // LED merah
#define LED_BLUE D7  // LED biru
#define LED_GREEN D8 // LED hijau

// Konfigurasi LCD
LiquidCrystal_I2C lcd(0x27, 16, 2);

// Variabel
float suhu, kelembapan;

void setup() {
  Serial.begin(115200);
  dht.begin();
  pinMode(LED_RED, OUTPUT);
  pinMode(LED_BLUE, OUTPUT);
  pinMode(LED_GREEN, OUTPUT);
  setup_wifi();
  client.setServer(mqtt_server, 1883);
  lcd.begin(16, 2); 
  lcd.backlight();
  lcd.setCursor(0, 0);
  lcd.print("Monitoring...");
}

void loop() {
  if (!client.connected()) {
    reconnect();
  }
  client.loop();

  static unsigned long lastTime = 0;
  if (millis() - lastTime > 10000) {
    lastTime = millis();
    suhu = dht.readTemperature();
    kelembapan = dht.readHumidity();

    if (!isnan(suhu) && !isnan(kelembapan)) {
      // Kirim data ke MQTT server
      client.publish("g231220071/temperature", String(suhu).c_str());
      client.publish("g231220071/humidity", String(kelembapan).c_str());

      // Tampilkan di LCD
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("Suhu: " + String(suhu) + " C");
      lcd.setCursor(0, 1);
      lcd.print("Hum: " + String(kelembapan) + " %");

      // Tampilkan di Serial Monitor
      Serial.println("=== Pembacaan Data DHT11 ===");
      Serial.printf("Pesan dari MQTT [%s] === Pembacaan Data DHT11 ===\n", topik);
      Serial.printf("Pesan dari MQTT [%s] Suhu: %.2f °C\n", topik, suhu);
      Serial.printf("Pesan dari MQTT [%s] Kelembapan: %.2f %%\n", topik, kelembapan);
      Serial.println("===========================");

      controlLED(suhu, kelembapan);
    } else {
      Serial.println("Gagal membaca data dari DHT11");
    }
  }
}

void controlLED(float suhu, float kelembapan) {
  if (suhu > 30) {
    digitalWrite(LED_RED, HIGH);  // LED Merah menyala
    digitalWrite(LED_BLUE, LOW);  // LED Biru mati
    digitalWrite(LED_GREEN, LOW); // LED Hijau mati
  } else if (suhu < 29) {
    digitalWrite(LED_BLUE, HIGH); // LED Biru menyala
    digitalWrite(LED_RED, LOW);   // LED Merah mati
    digitalWrite(LED_GREEN, LOW); // LED Hijau mati
  } else {
    // Kondisi untuk LED Hijau berdasarkan kelembapan
    if (kelembapan > 50) {
      digitalWrite(LED_GREEN, HIGH); // LED Hijau menyala jika kelembapan > 70
    } else {
      digitalWrite(LED_GREEN, LOW);  // LED Hijau mati jika kelembapan <= 70
    }
    digitalWrite(LED_RED, LOW);    // LED Merah mati
    digitalWrite(LED_BLUE, LOW);   // LED Biru mati
  }
}

void setup_wifi() {
  delay(10);
  Serial.println();
  Serial.print("Menghubungkan ke ");
  Serial.println(ssid);

  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();
  Serial.println("WiFi terhubung!");
  Serial.print("Alamat IP: ");
  Serial.println(WiFi.localIP());
}

void reconnect() {
  while (!client.connected()) {
    Serial.print("Menghubungkan ke MQTT...");
    if (client.connect("ESP8266Client", mqtt_user, mqtt_password)) {
      Serial.println("Terhubung!");
      client.subscribe("g231220071/control");
    } else {
      Serial.print("Gagal, rc=");
      Serial.print(client.state());
      Serial.println(" coba lagi dalam 5 detik");
      delay(5000);
    }
  }
}
