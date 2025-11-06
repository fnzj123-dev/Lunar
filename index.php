<?php
// index.php - Simple Manse API (Render용)
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// 0) 라이브러리 상대경로 인클루드 경로에 추가 (에러 원인 해결)
set_include_path(
  get_include_path()
  . PATH_SEPARATOR . __DIR__           // 루트
  . PATH_SEPARATOR . __DIR__ . '/Lunar'// Lunar 서브폴더(여기에 myException.php 등 있음)
);

// 1) 라이브러리 로딩
require_once __DIR__ . '/Lunar.php';
$lunar = new Lunar();

// 2) 입력 받기 (POST JSON 또는 쿼리스트링 둘 다 허용)
$in = json_decode(file_get_contents('php://input'), true) ?: $_GET;
$y  = isset($in['year'])   ? intval($in['year'])  : null;
$m  = isset($in['month'])  ? intval($in['month']) : null;
$d  = isset($in['day'])    ? intval($in['day'])   : null;
$hh = isset($in['hour'])   ? intval($in['hour'])  : 0;
$mi = isset($in['minute']) ? intval($in['minute']): 0;

if (!$y || !$m || !$d) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'year/month/day는 꼭 넣어주세요 (예: 1992,3,14,15,30)'], JSON_UNESCAPED_UNICODE);
  exit;
}

// 3) 실제 메서드명을 몰라도 돌아가게 "후보 호출"
function callIf($obj, array $cands, ...$args) {
  foreach ($cands as $fn) {
    if (method_exists($obj, $fn)) {
      try { return $obj->$fn(...$args); } catch (Throwable $e) { /* skip */ }
    }
  }
  return null;
}

$lunarInfo = callIf($lunar, ['solar_to_lunar','solar2lunar'], $y,$m,$d,$hh,$mi);
$ganji     = callIf($lunar, ['ganji','sexagenary'], $y,$m,$d,$hh,$mi);
$terms     = callIf($lunar, ['getTerm','terms','nearest_term'], $y,$m,$d,$hh,$mi);
$jd        = callIf($lunar, ['jd','getJD'], $y,$m,$d,$hh,$mi);

echo json_encode([
  'ok'        => true,
  'input'     => ['year'=>$y,'month'=>$m,'day'=>$d,'hour'=>$hh,'minute'=>$mi],
  'lunar'     => $lunarInfo,
  'ganji'     => $ganji,
  'terms'     => $terms,
  'julianDay' => $jd,
], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
