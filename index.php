<?php
// index.php - Lunar API 최종 안정 버전
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// -------------------------------------------------------------
// include_path 설정 (레거시 include 대응)
// -------------------------------------------------------------
set_include_path(
  get_include_path()
  . PATH_SEPARATOR . __DIR__
  . PATH_SEPARATOR . __DIR__ . '/Lunar'
);

// -------------------------------------------------------------
// myException 스텁 (레거시용)
// -------------------------------------------------------------
if (!class_exists('myException')) {
  class myException extends Exception {
    public static function myErrorHandler($errno, $errstr, $errfile, $errline) {
      return false;
    }
  }
}

// -------------------------------------------------------------
// 라이브러리 로드
// -------------------------------------------------------------
require_once __DIR__ . '/Lunar.php';

// -------------------------------------------------------------
// 클래스 선택 (\oops\Lunar 고정)
// -------------------------------------------------------------
use oops\Lunar as LunarClass;

if (!class_exists('\\oops\\Lunar')) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>'Class \\oops\\Lunar not found'], JSON_UNESCAPED_UNICODE);
  exit;
}

$lunar = new \oops\Lunar();

// -------------------------------------------------------------
// 입력 파라미터
// -------------------------------------------------------------
$in = json_decode(file_get_contents('php://input'), true) ?: $_GET;
$y  = isset($in['year'])   ? intval($in['year'])  : null;
$m  = isset($in['month'])  ? intval($in['month']) : null;
$d  = isset($in['day'])    ? intval($in['day'])   : null;
$hh = isset($in['hour'])   ? intval($in['hour'])  : 0;
$mi = isset($in['minute']) ? intval($in['minute']): 0;

if (!$y || !$m || !$d) {
  http_response_code(400);
  echo json_encode(['ok'=>false, 'error'=>'year/month/day는 필수입니다.'], JSON_UNESCAPED_UNICODE);
  exit;
}

// -------------------------------------------------------------
// 실제 계산 수행
// -------------------------------------------------------------

// 1) 양력 → 음력 변환
$lunarInfo = null;
if (method_exists($lunar, 'tolunar')) {
  $lunarInfo = $lunar->tolunar($y, $m, $d, $hh, $mi);
}

// 2) 절기 정보
$terms = null;
if (method_exists($lunar, 'seasondate')) {
  $terms = $lunar->seasondate($y, $m, $d);
}

// 3) 간지 (필요하면 dayfortune / ganji_ref 등 이용 가능)
$ganji = null;
if (method_exists($lunar, 'dayfortune')) {
  $ganji = $lunar->dayfortune($y, $m, $d);
}

// 4) 줄리안일 (cal2jd 사용)
$jd = null;
if (method_exists($lunar, 'cal2jd')) {
  $jd = $lunar->cal2jd($y, $m, $d, $hh, $mi);
}

// -------------------------------------------------------------
// 응답 출력
// -------------------------------------------------------------
echo json_encode([
  'ok'        => true,
  'class'     => '\\oops\\Lunar',
  'input'     => ['year'=>$y,'month'=>$m,'day'=>$d,'hour'=>$hh,'minute'=>$mi],
  'lunar'     => $lunarInfo,
  'ganji'     => $ganji,
  'terms'     => $terms,
  'julianDay' => $jd
], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
