<?php
// Minimal stub for legacy code.
// Some old code expects a class named `myException`.
// Define it if missing so `require_once('myException.php')` works.
if (!class_exists('myException')) {
    class myException extends Exception {}
}
