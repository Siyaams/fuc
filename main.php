<?php
header('Content-Type: application/json; charset=utf-8');

// === Date Range (UPDATED) ===
$from = "2025-09-14 00:13:00";
$to   = "2025-09-23 23:59:59";

// === API Endpoint and Params ===
$base_url = 'http://51.89.99.105/NumberPanel/agent/res/data_smscdr.php';
$timestamp = time();

$params = [
    'fdate1' => $from,
    'fdate2' => $to,
    'frange' => '',
    'fclient' => '',
    'fnum' => '',
    'fcli' => '',
    'fgdate' => '',
    'fgmonth' => '',
    'fgrange' => '',
    'fgclient' => '',
    'fgnumber' => '',
    'fgcli' => '',
    'fg' => 0,
    'sEcho' => 2,
    'iColumns' => 9,
    'sColumns' => ',,,,,,,,',
    'iDisplayStart' => 0,
    'iDisplayLength' => -1,
    'mDataProp_0' => 0,
    'sSearch_0' => '',
    'bRegex_0' => 'false',
    'bSearchable_0' => 'true',
    'bSortable_0' => 'true',
    'mDataProp_1' => 1,
    'sSearch_1' => '',
    'bRegex_1' => 'false',
    'bSearchable_1' => 'true',
    'bSortable_1' => 'true',
    'mDataProp_2' => 2,
    'sSearch_2' => '',
    'bRegex_2' => 'false',
    'bSearchable_2' => 'true',
    'bSortable_2' => 'true',
    'mDataProp_3' => 3,
    'sSearch_3' => '',
    'bRegex_3' => 'false',
    'bSearchable_3' => 'true',
    'bSortable_3' => 'true',
    'mDataProp_4' => 4,
    'sSearch_4' => '',
    'bRegex_4' => 'false',
    'bSearchable_4' => 'true',
    'bSortable_4' => 'true',
    'mDataProp_5' => 5,
    'sSearch_5' => '',
    'bRegex_5' => 'false',
    'bSearchable_5' => 'true',
    'bSortable_5' => 'true',
    'mDataProp_6' => 6,
    'sSearch_6' => '',
    'bRegex_6' => 'false',
    'bSearchable_6' => 'true',
    'bSortable_6' => 'true',
    'mDataProp_7' => 7,
    'sSearch_7' => '',
    'bRegex_7' => 'false',
    'bSearchable_7' => 'true',
    'bSortable_7' => 'true',
    'mDataProp_8' => 8,
    'sSearch_8' => '',
    'bRegex_8' => 'false',
    'bSearchable_8' => 'true',
    'bSortable_8' => 'false',
    'sSearch' => '',
    'bRegex' => 'false',
    'iSortCol_0' => 0,
    'sSortDir_0' => 'desc',
    'iSortingCols' => 1,
    '_' => $timestamp
];

$url = $base_url . '?' . http_build_query($params);

// === Execute cURL ===
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_ENCODING => '',
    CURLOPT_HTTPHEADER => [
        "User-Agent: Mozilla/5.0 (Linux; Android 11; WALPAD8G Build/RP1A.200720.011) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.7204.157 Safari/537.36",
        "Accept: application/json, text/javascript, */*; q=0.01",
        "Accept-Encoding: gzip, deflate",
        "X-Requested-With: XMLHttpRequest",
        "Referer: http://51.89.99.105/NumberPanel/agent/SMSCDRReports",
        "Accept-Language: en-US,en;q=0.9,ar-EG;q=0.8,ar;q=0.7,fr-DZ;q=0.6,fr;q=0.5,bn-BD;q=0.4,bn;q=0.3,fr-FR;q=0.2",
        "Cookie: PHPSESSID=n7qt2lsi0ge3pvlppqj431gn1m"
    ]
]);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)], JSON_PRETTY_PRINT);
    curl_close($ch);
    exit;
}
curl_close($ch);

// === Decode JSON Response ===
$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Invalid JSON response'], JSON_PRETTY_PRINT);
    exit;
}

// === Helpers ===
function fetchFlagData() {
    $flagApi = "https://siyamahmmed.shop/flag.php";
    $flagData = @file_get_contents($flagApi);
    if ($flagData === false) return [];
    $json = json_decode($flagData, true);
    return is_array($json) ? $json : [];
}

function cleanNumber($number) {
    return preg_replace('/\D+/', '', $number);
}

function getCountryFromNumber($number, $flagList) {
    $cleanNum = cleanNumber($number);
    usort($flagList, function ($a, $b) {
        return strlen(cleanNumber($b['code'] ?? '')) - strlen(cleanNumber($a['code'] ?? ''));
    });
    foreach ($flagList as $entry) {
        $code = cleanNumber($entry['code'] ?? '');
        if ($code && strpos($cleanNum, $code) === 0) {
            $flag = $entry['emoji'] ?? '🌍';
            $country = $entry['name'] ?? 'Unknown';
            return "{$flag} {$country}";
        }
    }
    return "🌍 Unknown";
}

function convertToBDTime($timeStr) {
    $utc = new DateTime($timeStr, new DateTimeZone('UTC'));
    $utc->setTimezone(new DateTimeZone('Asia/Dhaka'));
    return $utc->format('Y-m-d H:i:s');
}

function extractOTP($message) {
    if (preg_match('/\b\d{3}[-\s]?\d{3}\b|\b\d{4,8}\b/', $message, $matches)) {
        return trim($matches[0]);
    }
    return null;
}

// === Process and Output ===
$flagList = fetchFlagData();
$results = [];

foreach ($data['aaData'] ?? [] as $row) {
    $time     = $row[0] ?? '';
    $number   = $row[2] ?? '';
    $platform = $row[3] ?? '';
    $message  = $row[5] ?? '';

    if (!$number || !$platform || !$message) continue;

    $otp     = extractOTP($message);
    $country = getCountryFromNumber($number, $flagList);
    $bdTime  = convertToBDTime($time);

    $results[] = [
        'id'       => sha1($message),
        'number'   => $number,
        'platform' => $platform,
        'country'  => $country,
        'time'     => $bdTime,
        'otp'      => $otp,
        'message'  => $message
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
