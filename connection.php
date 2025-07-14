<?php

date_default_timezone_set('Asia/Kathmandu'); // Set local time zone
header('Content-Type: application/json'); // Set JSON response header

$city = $_GET['q'] ?? 'Ilam'; // Get city from query parameter or use default city

$serverName = "localhost";
$userName = "root";
$password = "";
$databaseName = "prototype2";

// Connect to MySQL server
$conn = mysqli_connect($serverName, $userName, $password);
if (!$conn) {
    echo json_encode(['error' => 'Failed to connect to database', 'details' => mysqli_connect_error()]);
    exit;
}

// Create database if it doesn't exist
$createDatabase = "CREATE DATABASE IF NOT EXISTS $databaseName";
if (!mysqli_query($conn, $createDatabase)) {
    echo json_encode(['error' => 'Failed to create database', 'details' => mysqli_error($conn)]);
    exit;
}

// Select the database
mysqli_select_db($conn, $databaseName);

// Create the weather table if it does not exist
$createTable = "CREATE TABLE IF NOT EXISTS weather(
    id INT AUTO_INCREMENT PRIMARY KEY,
    city VARCHAR(255) NOT NULL,
    day VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    temperature FLOAT NOT NULL,
    description VARCHAR(255) NOT NULL,
    humidity INT NOT NULL,
    wind_speed FLOAT NOT NULL,
    wind_direction INT NOT NULL,
    pressure INT NOT NULL,
    icon VARCHAR(255) NOT NULL
);"; 

if (!mysqli_query($conn, $createTable)) {
    echo json_encode(['error' => 'Failed to create table', 'details' => mysqli_error($conn)]);
    exit;
}

// Fetch the latest weather data for the requested city
$selectLatestData = "SELECT * FROM weather WHERE city = '$city' ORDER BY timestamp DESC LIMIT 1";
$result = mysqli_query($conn, $selectLatestData);
$rows = [];

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $lastUpdated = strtotime($row['timestamp']);
    $currentTime = time();

    // If the data is less than 2 hours old, return it
    if (($currentTime - $lastUpdated) < 7200) {
        echo json_encode([$row]);
        exit;
    }
}

// Fetch fresh weather data from OpenWeather API
$apiKey = 'e2c67abeb2c214a8dc3bff414c7c0f48';
$url = "https://api.openweathermap.org/data/2.5/weather?q=$city&appid=$apiKey&units=metric";

$response = @file_get_contents($url);
if ($response === FALSE) {
    echo json_encode(['error' => 'Failed to fetch weather data from API']);
    exit;
}

$data = json_decode($response, true);

if (!isset($data['main'])) {
    echo json_encode(['error' => 'Invalid response from weather API', 'details' => $data]);
    exit;
}

$city = mysqli_real_escape_string($conn, $data['name']);
$day = date('l');
$date = date('Y-m-d');
$temperature = $data['main']['temp'];
$description = $data['weather'][0]['description'];
$humidity = $data['main']['humidity'];
$wind_speed = $data['wind']['speed'];
$wind_direction = $data['wind']['deg'];
$pressure = $data['main']['pressure'];
$icon = $data['weather'][0]['icon'];

// Insert the new data
$insertData = "INSERT INTO weather (city, day, date, temperature, description, humidity, wind_speed, wind_direction, pressure, icon) 
               VALUES ('$city', '$day', '$date', '$temperature', '$description', '$humidity', '$wind_speed', '$wind_direction', '$pressure', '$icon')";

if (!mysqli_query($conn, $insertData)) {
    echo json_encode(['error' => 'Failed to insert data', 'details' => mysqli_error($conn)]);
    exit;
}

$deleteOldData = "DELETE FROM weather WHERE city = '$city' AND id NOT IN (SELECT MAX(id) FROM weather WHERE city = '$city')";
if (!mysqli_query($conn, $deleteOldData)) {
    echo json_encode(['error' => 'Failed to delete old data', 'details' => mysqli_error($conn)]);
    exit;
}

// Fetch and return the latest data
$result = mysqli_query($conn, $selectLatestData);
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}

if (empty($rows)) {
    echo json_encode(['error' => 'No data found for the requested city']);
} else {
    echo json_encode($rows);
}

?>
