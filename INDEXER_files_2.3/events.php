<?php

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

// 1. Задаваемые параметры/функции
require __DIR__ . '/parametrs.php'; // Здесь параметрам даются целевые значения


$starttime = time();
// Запускаем бесконечный цикл
while(true) {
    // Получаем текущее время
    $serverTime = time(); // уникальный id сообщения

    $mess = '<b>'. file_get_contents($ru_dic_indexing_info_file). '</b>';

    // Отправляем полученное время в сообщении
    sendMsg($serverTime, $mess);

    // Ожидаем 1 секунду перед тем, как создавать новое сообщение
    sleep(1);

    if ((time() - $starttime) > 10000) {
        die('Похоже, произошла ошибка клиента: он не передал сигнал об окончании операции. Превышено время ожидания сервера. Таймер остановлен.');
    }
}

// Функция отправки сообщения
function sendMsg($id, $mess) {
    echo "id: $id" . PHP_EOL;
    echo "data: $mess" . PHP_EOL;
    echo PHP_EOL;
    ob_flush();
    flush();
}
