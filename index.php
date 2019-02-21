<?php
/**
 * Created by PhpStorm.
 * User: danii
 * Date: 20.02.2019
 * Time: 22:59
 */

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PWD',  '');
define('DB_NAME', 'test');

$errorMessage = '';

$db = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);

if ($db->connect_errno) {
    die('No database connection.');
}

if (isset($_POST['title']) && isset($_POST['body'])) {
    addMessage(addslashes(strip_tags($_POST['title'])), addslashes(strip_tags($_POST['body'])));
} else if (isset($_GET['delete_id'])) {
    deleteMessage(intval($_GET['delete_id']));
}

render();

function addMessage($title, $body) {
    global $db, $errorMessage;

    if (strlen($title) < 1 || strlen($title) > 255) {
        $errorMessage = 'Title must be from 1 to 255 symbols long';
        return;
    }

    if (strlen($body) < 1 || strlen($body) > 5000) {
        $errorMessage = 'Body must be from 1 to 5000 symbols long';
        return;
    }

    $db->query(sprintf("INSERT INTO `messages` (`title`, `body`) VALUES ('%s','%s')", $title, formatText($body)));
}

function deleteMessage($id) {
    global $db, $errorMessage;
    $db->query(sprintf("DELETE FROM `messages` WHERE `id`=%d", $id));
}

function render() {
    global $db, $errorMessage;
    $messages = $db->query("SELECT * FROM `messages` ORDER BY `id`")->fetch_all(MYSQLI_ASSOC);

    include('view.phtml');
}

function formatText($text) {
    // Простые стандартные тэги обработаем в цикле
    $simpleTags = [
      '**' => ['<b>',' </b>'],
      '__' => ['<u>',' </u>'],
    ];
    foreach ($simpleTags as $from => $to) {
        $pattern = '/' . preg_quote($from) . '([^' . preg_quote($from) . ']+)' . preg_quote($from) . '/';
        $text    = preg_replace($pattern, $to[0] . '$1' . $to[1], $text);
    }

    // Более сложный тег отдельно ((url|text))
    $matches = [];
    preg_match_all('/\(\((https?:\/\/)?([^\|]+)\|([^\|]+)\)\)/',$text,$matches);
    foreach ($matches[0] as $key => $match) {
        $text = str_replace($matches[0][$key], '<a href="' . ($matches[1][$key] ?: 'http://') . $matches[2][$key] . '">' . $matches[3][$key] . '</a>'  , $text);
    }

    return nl2br($text);
}