<?php
// PHPMailer Autoload file for simple integration
// Source: https://github.com/PHPMailer/PHPMailer
// This is a minimal loader for PHPMailer
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
}
