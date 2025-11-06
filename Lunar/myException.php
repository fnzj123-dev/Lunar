<?php
// Stub class for legacy compatibility.
// Lunar.php expects myException::myErrorHandler() to exist.
if (!class_exists('myException')) {
    class myException extends Exception
    {
        public static function myErrorHandler($errno, $errstr, $errfile, $errline)
        {
            // 최소한의 에러 처리. 로그를 남기거나 무시.
            // 원래 진짜만세력은 내부적으로 오류를 무시하도록 설계됨.
            // 여기선 PHP 기본 핸들러로 넘기지 않음으로써 안전하게 처리.
            return false; // false를 반환하면 PHP 기본 에러 핸들러가 이어서 처리
        }
    }
}
