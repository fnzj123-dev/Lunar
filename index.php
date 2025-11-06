<?php
// index.php - Lunar API (Render용, 디버그 포함)
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// ---------------------- 공통 설정 ----------------------
set_include_path(
  get_include_path()
  . PATH_SEPARATOR . __DIR__
  . PATH_SEPARATOR . __DIR__ . '/Lunar'
);

// set_error_handler에서 요구하는 스텁
if (!class_exists('myException')) {
  class myException extends Exception {
    public static function myErrorHandler($errno, $errstr, $errfile, $errline) {
      return false; // 기본 핸들러로 넘김
    }
  }
}

// 라이브러리 로드
require_once __DIR__ . '/Lunar.php';

// 실제 클래스 선택 (API 우선 -> 코어)
$lunar = null;
$usedClass = null;
if (class_exists('\\oops\\Lunar_API')) {
  $lunar = new \oops\Lunar_API();  // <- 우선 사용
  $usedClass = '\\oops\\Lunar_API';
} elseif (class_exists('\\oops\\Lunar')) {
  $lunar = new \oops\Lunar();
  $usedClass = '\\oops\\Lunar';
} elseif (class_exists('Lunar')) {
  $lunar = new Lunar();
  $usedClass = 'Lunar';
} else {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => 'Lunar class not found',
    'declared_classes' => array_values(array_filter(get_declared_classes(), fn($c)=> stripos($c,'lunar')!==false || stripos($c,'oops')!==false)),
  ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
  exit;
}

// ---------------------- 디버그 모드 ----------------------
// ?debug=1 붙여 호출하면, 가용 메서드 목록을 그대로 보여줍니다.
$in = json_decode(file_get_contents('php://input'), true) ?: $_GET;
if (isset($in['debug'])) {
  echo json_encode([
    'ok' => true,
    'mode' => 'debug',
    'used_class' => $usedClass,
    'methods' => get_class_methods($lunar),
  ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
  exit;
}

// ---------------------- 입력 파라미터 ----------------------
$y  = isset($in['year'])   ? intval($in['year'])  : null;
$m  = isset($in['month'])  ? intval($in['month']) : null;
$d  = isset($in['day'])    ? intval($in['day'])   : null;
$hh = isset($in['hour'])   ? intval($in['hour'])  : 0;
$mi = isset($in['minute']) ? intval($in['minute']): 0;

if (!$y || !$m || !$d) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'year/month/day는 필수입니다. 예: {"year":1992,"month":3,"day":14,"hour":15,"minute":30}'], JSON_UNESCAPED_UNICODE);
  exit;
}

// ---------------------- 유연 호출 헬퍼 ----------------------
function callIf($obj, array $cands, ...$args) {
  foreach ($cands as $fn) {
    if (method_exists($obj, $fn)) {
      try { return $obj->$fn(...$args); } catch (Throwable $e) { /* skip */ }
    }
  }
  return null;
}

// ---------------------- 후보 메서드(넓게 시도) ----------------------
// 음력/윤달
$lunarInfo = callIf($lunar, [
  'solar_to_lunar', 'solar2lunar', 'solarToLunar', 'toLunar',
  'getLunar', 'getLunarDate', 's2l', 'solar_to_Lunar'
], $y,$m,$d,$hh,$mi);

// 간지(연/월/일/시)
$ganji = callIf($lunar, [
  'ganji', 'sexagenary', 'getGanji', 'get_ganji',
  'getSexagenary', 'date2ganji', 'calcGanji'
], $y,$m,$d,$hh,$mi);

// 절기/중기
$terms = callIf($lunar, [
  'getTerm', 'terms', 'nearest_term', 'seasondate', 'getSeasonDate'
], $y,$m,$d,$hh,$mi);

// 줄리안일
$jd = callIf($lunar, [
  'jd', 'getJD', 'julian', 'getJulianDay'
], $y,$m,$d,$hh,$mi);

// ---------------------- 응답 ----------------------
echo json_encode([
  'ok'        => true,
  'usedClass' => $usedClass,
  'input'     => ['year'=>$y,'month'=>$m,'day'=>$d,'hour'=>$hh,'minute'=>$mi],
  'lunar'     => $lunarInfo,
  'ganji'     => $ganji,
  'terms'     => $terms,
  'julianDay' => $jd,
], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
