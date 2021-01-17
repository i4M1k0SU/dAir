<?php

$config = require_once __DIR__ . '/config.php';
$sqlConfig = $config['dAir']['SQL'];

$seat = $_GET['seat'] ?? 'all';
$range = $_GET['range'] ?? 'latest';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($seat === null) {
    http_response_code(400);
    $result = [
        'status' => 400,
        'message' => 'invalid seat.'
    ];

    echo json_encode($result);
    return;
}

$pdo = new PDO("mysql:host=${sqlConfig['Host']};port=${sqlConfig['Port']};dbname=${sqlConfig['Database']};charset=${sqlConfig['Charset']};",
    $sqlConfig['User'],
    $sqlConfig['Password'],
    [
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

$rangeQuery = getRangeQuery($range);

$query = "SELECT 
    mb.name AS building_name,
    floor,
    id_str AS seat, co2,
    temperature,
    humidity,
    pressure,
    illuminance,
    noise,
    uv,
    discomfort_index,
    heatstroke_risk_indicator,
    datetime FROM t_air
    INNER JOIN m_seat ms on t_air.seat_id = ms.id
    INNER JOIN m_building mb on ms.building_id = mb.id
      WHERE datetime >= ${rangeQuery}";

$seatCount = 0;
$seatArray = [];

if ($seat !== 'all') {
    $seatArray = explode(',', $seat);
    $seatCount = count($seatArray);

    $inClause = substr(str_repeat(',?', $seatCount), 1);
    $query .= " AND ms.id_str IN (${inClause}) ";
}

$query .= ' ORDER BY datetime DESC';

$query .= $range === 'latest' ? ' LIMIT 1' : '';

$stmt = $pdo->prepare($query);
for ($i = 1; $i <= $seatCount; $i++) {
    $stmt->bindValue($i, $seatArray[$i - 1], PDO::PARAM_STR);
}
$stmt->execute();
$values = $stmt->fetchAll();
$data = json_encode($values);

echo $data;


function getRangeQuery(string $range): string
{
    switch ($range) {
        case 'latest':
        case 'hour':
            return '(NOW() - INTERVAL 1 HOUR)';
        case 'half-day':
            return '(NOW() - INTERVAL 12 HOUR)';
        case 'day':
            return '(NOW() - INTERVAL 1 DAY)';
        case 'week':
            return '(NOW() - INTERVAL 1 WEEK)';
        case 'month':
        default:
            return '(NOW() - INTERVAL 1 MONTH)';
    }
}
