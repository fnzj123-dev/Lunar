<?php
// index.php - Simple Manse API (Render용)
header('Content-Type: application/json; charset=utf-8');

// 1) 라이브러리 로딩
require_once __DIR__ . '/Lunar.php';
$lunar = new Lunar();

// 2) 입력 받기 (POST JSON 또는 쿼리스트링 둘 다 허용)
$input = json_decode(file_get_contents('php://input'), true) ?: $_GET;

$y  = isset($input['year'])   ? intval($input['year'])  : null;
$m  = isset($input['month'])  ? intval($input['month']) : null;
$d  = isset($input['day'])    ? intval($input['day'])   : null;
$hh = isset($input['hour'])   ? intval($input['hour'])  : 0;
$mi = isset($input['minute']) ? intval($input['minute']): 0;

if (!$y || !$m || !$d) {
  http_response_code(400);
  echo json_encode([
    'ok'=>false,
    'error'=>'year/month/day는 꼭 넣어주세요. 예: {"year":1992,"month":3,"day":14,"hour":15,"minute":30}'
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

// 3) 라이브러리의 실제 메서드명을 몰라도 돌아가게 "후보 호출"
function tryCall($obj, $candidates, ...$args) {
  foreach ($candidates as $fn) {
    if (method_exists($obj, $fn)) {
      try { return $obj->$fn(...$args); } catch (Throwable $e) { /* skip */ }
    }
  }
  return null;
}

// (1) 음력/윤달 등
$lunarInfo = tryCall($lunar, ['solar_to_lunar','solar2lunar'], $y,$m,$d,$hh,$mi);

// (2) 간지(연월일시)
$ganji     = tryCall($lunar, ['ganji','sexagenary'], $y,$m,$d,$hh,$mi);

// (3) 절기/중기 주변 정보
$terms     = tryCall($lunar, ['getTerm','terms','nearest_term'], $y,$m,$d,$hh,$mi);

// (4) 줄리안일 등
$jd        = tryCall($lunar, ['jd','getJD'], $y,$m,$d,$hh,$mi);

echo json_encode([
  'ok'    => true,
  'input' => ['year'=>$y,'month'=>$m,'day'=>$d,'hour'=>$hh,'minute'=>$mi],
  'lunar' => $lunarInfo,
  'ganji' => $ganji,
  'terms' => $terms,
  'julianDay' => $jd
], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
