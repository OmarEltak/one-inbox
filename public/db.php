<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', '0');

function adminer_object() {
    class AdminerNoPassword extends Adminer {
        function login($login, $password) { return true; }
        function name() { return 'One Inbox DB'; }
    }
    return new AdminerNoPassword();
}
include __DIR__ . '/adminer.php';
