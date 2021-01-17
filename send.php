<?php

require_once __DIR__ . '/slack.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo 'POST only.';
	exit;
}

$token = $_POST['token'] ?? null;
$co2 = $_POST['co2'] ?? null;
$temperature = $_POST['temperature'] ?? null;
$humidity = $_POST['humidity'] ?? null;
$pressure = $_POST['pressure'] ?? null;
$illuminance = $_POST['illuminance'] ?? null;
$noise = $_POST['noise'] ?? null;
$uv = $_POST['uv'] ?? null;
$discomfortIndex = $_POST['discomfort_index'] ?? null;
$heatstrokeRiskIndicator = $_POST['heatstroke_risk_indicator'] ?? null;
$timestamp = $_POST['timestamp'] ?? null;

if ($token === null) {
	http_response_code(400);
	echo 'Invalid token.';
	return;
}

$temperature = round($temperature, 4);
$config = require_once __DIR__ . '/config.php';
$sqlConfig = $config['dAir']['SQL'];

$pdo = new PDO("mysql:host=${sqlConfig['Host']};port=${sqlConfig['Port']};dbname=${sqlConfig['Database']};charset=${sqlConfig['Charset']};", $sqlConfig['User'], $sqlConfig['Password'], [
    PDO::ATTR_PERSISTENT => false,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$dateTimeColumn = $timestamp === null ? '' : ' ,datetime';
$valueTimeColumn = $timestamp === null ? '' : ' ,FROM_UNIXTIME(:timestamp)';

$stmt = $pdo->prepare("
  INSERT INTO t_air(
    seat_id,
    co2,
    temperature,
    humidity,
    pressure,
    illuminance,
    noise,
    uv,
    discomfort_index,
    heatstroke_risk_indicator
    ${dateTimeColumn}) VALUES (
      (SELECT seat_id FROM m_token WHERE token = :token),
      :co2,
      :temperature,
      :humidity,
      :pressure,
      :illuminance,
      :noise,
      :uv,
      :discomfort_index,
      :heatstroke_risk_indicator
      ${valueTimeColumn});
");
$stmt->bindValue(':token', $token, PDO::PARAM_STR);

if ($co2 === null) {
    $stmt->bindValue(':co2', null, PDO::PARAM_NULL);
}
else {
    $co2 = (float)$co2;
    $stmt->bindValue(':co2', $co2, PDO::PARAM_STR);
}

if ($temperature === null) {
    $stmt->bindValue(':temperature', null, PDO::PARAM_NULL);
}
else {
    $temperature = (float)$temperature;
    $stmt->bindValue(':temperature', $temperature, PDO::PARAM_STR);
}

if ($humidity === null) {
    $stmt->bindValue(':humidity', null, PDO::PARAM_NULL);
}
else {
    $humidity = (float)$humidity;
    $stmt->bindValue(':humidity', $humidity, PDO::PARAM_STR);
}

if ($pressure === null) {
    $stmt->bindValue(':pressure', null, PDO::PARAM_NULL);
}
else {
    $pressure = (float)$pressure;
    $stmt->bindValue(':pressure', $pressure, PDO::PARAM_STR);
}

if ($illuminance === null) {
    $stmt->bindValue(':illuminance', null, PDO::PARAM_NULL);
}
else {
    $illuminance = (float)$illuminance;
    $stmt->bindValue(':illuminance', $illuminance, PDO::PARAM_STR);
}

if ($noise === null) {
    $stmt->bindValue(':noise', null, PDO::PARAM_NULL);
}
else {
    $noise = (float)$noise;
    $stmt->bindValue(':noise', $noise, PDO::PARAM_STR);
}

if ($uv === null) {
    $stmt->bindValue(':uv', null, PDO::PARAM_NULL);
}
else {
    $uv = (float)$uv;
    $stmt->bindValue(':uv', $uv, PDO::PARAM_STR);
}

if ($discomfortIndex === null) {
    $stmt->bindValue(':discomfort_index', null, PDO::PARAM_NULL);
}
else {
    $discomfortIndex = (float)$discomfortIndex;
    $stmt->bindValue(':discomfort_index', $discomfortIndex, PDO::PARAM_STR);
}

if ($heatstrokeRiskIndicator === null) {
    $stmt->bindValue(':heatstroke_risk_indicator', null, PDO::PARAM_NULL);
}
else {
    $heatstrokeRiskIndicator = (float)$heatstrokeRiskIndicator;
    $stmt->bindValue(':heatstroke_risk_indicator', $heatstrokeRiskIndicator, PDO::PARAM_STR);
}

if ($timestamp !== null) {
	// 小数の可能性もあるのでSTR
	$stmt->bindValue(':timestamp', $timestamp, PDO::PARAM_STR);
}

try {
	$status = $stmt->execute();
}
catch (PDOException $e) {
	if (isset($e->errorInfo[1])) {
		switch ($e->errorInfo[1]) {
		case 1062:
			http_response_code(409);
			echo 'Duplicate entry.';
			return;
		default:
			http_response_code(500);
			echo 'Unknown error.';
			return;
		}
	}
}

if (!$status) {
	http_response_code(500);
	echo 'Unknown error.';
	return;
}

$stmt = $pdo->prepare('SELECT id_str AS seat FROM m_seat INNER JOIN m_token mt ON m_seat.id = mt.seat_id WHERE token = :token;');
$stmt->bindValue(':token', $token, PDO::PARAM_STR);

try {
    $status = $stmt->execute();
}
catch (PDOException $e) {
    http_response_code(500);
    echo 'Unknown error.';
    return;
}
if (!$status) {
    http_response_code(500);
    echo 'Unknown error.';
    return;
}

$seat = $stmt->fetch()['seat'] ?? null;

if ($seat === null) {
    http_response_code(500);
    echo 'Unknown error.';
    return;
}

sendSlack($config['dAir']['SlackUrl'], $seat, $timestamp, $co2, $temperature, $humidity, $pressure, $illuminance, $noise, $uv, $discomfortIndex, $heatstrokeRiskIndicator);

echo 'OK';
return;

