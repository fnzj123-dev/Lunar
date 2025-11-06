<?php
// index.php - Lunar API (Render용)
declare(strict_types=1);

// 항상 JSON으로 응답
header('Content-Type: application/json; charset=utf-8');

// -------------------------------------------------------------
// 0) include_path 세팅 (Lunar 내부 상대경로 include 에러 방지)
// -------------------------------------------------------------
set_include_path(
  get_include_path()
  . PATH_SEPARATOR . __DIR__
  . PATH_SEPARATOR . __DIR__ . '/Lunar'
);

// -------------------------------------------------------------
// 1) myException 클래스가 없으면 간단히 만들어줌
// (Lunar.php에서 set_error_handler('myException::myErrorHandler') 호출함)
// -------------------------------------------------------------
if (!class_exists('myException')) {
  class myException extends Exception {
    public static function myErrorHandler($errno, $errstr, $errfile, $errline) {
      // 기본 에러 핸들러로 넘기도록 false 반환
      return false;
    }
  }
}

// -------------------------------------------------------------
// 2) Lunar.php 불러오기
// -------------------------------------------------------------
require_once __DIR__ . '/Lunar.php';

// -------------------------------------------------------------
// 3) 클래스 자동 탐색 (namespace 포함)
// -------------------------------------------------------------
$lunar = null;

if (class_exists('\\oops\\Lunar')) {
  $lunar = new \oops\Lunar();             // ✅ 실제 선언된 클래스
} elseif (class_exists('\\oops\\Lunar_API')) {
  $lunar = new \oops\Lunar_API();         // 예비 (다른 버전 호환)
} elseif (class_exists('Lunar')) {
  $lunar = new Lunar();                   // 혹시 전역 클래스라면
} else {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => 'Lunar class not found. Tried: \\oops\\Lunar, \\oops\\Lunar_API, Lunar',
    'declared_classes' => array_values(array_filter(get_declared_classes(), function($c){
      return stripos($c, 'lunar') !== false || stripos($c, 'oops') !== false;
    })),
  ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
  exit;
}

// -------------------------------------------------------------
// 4) 입력값 처리
// -------------------------------------------------------------
$in = json_decode(file_get_contents('php://input'), true) ?: $_GET;
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

// -------------------------------------------------------------
// 5) 함수 이름이 달라도 자동 시도 (유연 호출)
// -------------------------------------------------------------
function callIf($obj, array $cands, ...$args) {
  foreach ($cands as $fn) {
    if (method_exists($obj, $fn)) {
      try { return $obj->$fn(...$args); } catch (Throwable $e) { /* 무시 */ }
    }
  }
  return null;
}

// 후보 메서드 이름 (실제 레포 구조에 맞게 유연 호출)
$lunarInfo = callIf($lunar, ['solar_to_lunar','solar2lunar'], $y,$m,$d,$hh,$mi);
$ganji     = callIf($lunar, ['ganji','sexagenary','getGanji'], $y,$m,$d,$hh,$mi);
$terms     = callIf($lunar, ['getTerm','terms','nearest_term'], $y,$m,$d,$hh,$mi);
$jd        = callIf($lunar, ['jd','getJD'], $y,$m,$d,$hh,$mi);

// -------------------------------------------------------------
// 6) 결과 출력
// -------------------------------------------------------------
echo json_encode([
  'ok'        => true,
  'input'     => ['year'=>$y,'month'=>$m,'day'=>$d,'hour'=>$hh,'minute'=>$mi],
  'lunar'     => $lunarInfo,
  'ganji'     => $ganji,
  'terms'     => $terms,
  'julianDay' => $jd,
], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
