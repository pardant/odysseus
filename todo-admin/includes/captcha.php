<?php
/**
 * Simple server-side math CAPTCHA.
 */

function generateCaptcha(): array {
    startSecureSession();
    $a = random_int(1, 20);
    $b = random_int(1, 20);
    $ops = ['+', '-', '*'];
    $op = $ops[array_rand($ops)];

    switch ($op) {
        case '+': $answer = $a + $b; break;
        case '-': $answer = $a - $b; break;
        case '*': $answer = $a * $b; break;
    }

    $_SESSION['captcha_answer'] = $answer;
    $_SESSION['captcha_time'] = time();

    return [
        'question' => "{$a} {$op} {$b} = ?",
        'a' => $a,
        'b' => $b,
        'op' => $op,
    ];
}

function verifyCaptcha(string $input): bool {
    startSecureSession();
    if (!isset($_SESSION['captcha_answer'], $_SESSION['captcha_time'])) {
        return false;
    }
    if (time() - $_SESSION['captcha_time'] > 300) {
        unset($_SESSION['captcha_answer'], $_SESSION['captcha_time']);
        return false;
    }
    $valid = (int)$input === (int)$_SESSION['captcha_answer'];
    unset($_SESSION['captcha_answer'], $_SESSION['captcha_time']);
    return $valid;
}
