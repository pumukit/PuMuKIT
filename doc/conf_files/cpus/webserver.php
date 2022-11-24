<?php

define('USER', 'pumukit');
define('PASSWORD', 'PUMUKIT');

function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ('.' != $object && '..' != $object) {
                if ('dir' == filetype($dir.'/'.$object)) {
                    rrmdir($dir.'/'.$object);
                } else {
                    unlink($dir.'/'.$object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

function showLogin()
{
    header('WWW-Authenticate: Basic realm="Demo System"');
    header('HTTP/1.0 401 Unauthorized');
    echo "You don't have permissions to enter.\n";

    exit;
}

$username = $_SERVER['PHP_AUTH_USER'];
$userpass = $_SERVER['PHP_AUTH_PW'];
if (!isset($username)) {
    showLogin();
} else {
    if (USER == $username && PASSWORD == $userpass) {
        header('HTTP/1.0 200 OK');
        header('Content-Type: text/html');

        if (isset($_POST['command'])) {
            set_time_limit(0);
            ini_set('memory_set', '-1');
            echo "\n (webserver.php) ".stripslashes($_POST['command']);

            $tempDir = '/tmp/'.sha1(time()).'/';
            @mkdir($tempDir, 0777, true);

            $dcurrent = getcwd();
            chdir($tempDir);

            exec(stripslashes($_POST['command'].' 2>&1'), $salida);

            chdir($dcurrent);

            file_put_contents('../log/log_trans.log', stripslashes($_POST['command']).
                              "\n".implode("\n", $salida)."\n\n\n", FILE_APPEND);

            echo implode("\n", $salida);
        } else {
            echo "Welcome to Transco PuMuKIT \n";
        }

        rrmdir($tempDir);
    } else {
        showLogin();
    }
}
