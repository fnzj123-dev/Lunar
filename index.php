<?php
// index.php - Simple Manse API (Render)

// 항상 JSON으로 응답
header('Content-Type: application/json; charset=utf-8');

// 0) include_path 에 Lunar 서브폴더 추가 (레거시 include 대비)
set_include_path(
  get_include_path()
  . PATH_SEPARATOR . __DIR__
  . PATH_SEPARATOR . __DIR__ . '/Lunar'
);

// 1) 누락되었던 myException 호환 스텁 (혹시 파일이 없다면 대비)
if (!class_exists('myException')) {
  class myException extends Exception {
    public static function myErrorHandler($errno, $errstr, $errfile, $errline) {
      return false; // 기본 핸들러에 넘김
    }
  }
}

// 2) 라이브러리 로드
require_once __DIR__ . '/Lunar.php';   // 루트의 Lunar.php
// 혹시 내부에서 또 필요한 파일이 있다면 include_path로 찾아짐

// 3) 클래스 찾아서 인스턴스 생성(네임스페이스/클래스명 자동 대응)
$lunar = null;
if (class_exists('Lunar')) {
  $lunar = new Lunar();
} elseif (class_exists('\\oops\\lunar\\Lunar')) {
  $lunar = new \oops\lunar\Lunar();
} elseif (class_exists('Lunar_API')) {            // 혹시 옛 API 클래스가 있다면
  $lunar = new Lunar_API();
} else {
  // 디버깅용: 어떤 클래스들이 로드됐는지 보여주기
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => 'Lunar class not found. Tried: Lunar, oops\\lunar\\Lunar, Lunar_API',
    'declared_classes' => array_values(array_filter(get_declared_classes(), function($c){
      return stripos($c, 'lunar') !== false || stripos($c, 'oops') !== false;
    })),
  ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
  exit;
}

// 4) 입력 파싱
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

// 5) 메서드 이름이 달라도 돌아가게 후보 호출
function callIf($obj, array $cands, ...$args) {
  foreach ($cands as $fn) {
    if (method_exists($obj, $fn)) {
      try { return $obj->$fn(...$args); } catch (Throwable $e) { /* skip */ }
    }
  }
  return null;
}

// 후보: 실제 라이브러리 구현에 맞춰 자동으로 맞춤
$lunarInfo = callIf($lunar, ['solar_to_lunar','solar2lunar'], $y,$m,$d,$hh,$mi);
$ganji     = callIf($lunar, ['ganji','sexagenary','getGanji'], $y,$m,$d,$hh,$mi);
$terms     = callIf($lunar, ['getTerm','terms','nearest_term'], $y,$m,$d,$hh,$mi);
$jd        = callIf($lunar, ['jd','getJD'], $y,$m,$d,$hh,$mi);

// 6) 응답
echo json_encode([
  'ok'        => true,
  'input'     => ['year'=>$y,'month'=>$m,'day'=>$d,'hour'=>$hh,'minute'=>$mi],
  'lunar'     => $lunarInfo,
  'ganji'     => $ganji,
  'terms'     => $terms,
  'julianDay' => $jd,
], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
