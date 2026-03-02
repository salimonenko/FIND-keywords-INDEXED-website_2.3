<?php
/* Программа индексирования содержимого файлов сайта. Потом в индексированном содержимом можно очень быстро сделать НЕчеткий поиск даже при (очень) большом числе файлов.
Работает с использованием (пока) двух кодировок: Windows-1251 и utf-8. В этих кодировках могут содержаться как имена файлов сайта, так и их содержимое.
ВАЖНО: В этой программе почти каждому метафонному символу соответствует ОТДЕЛЬНЫЙ каталог с таким же именем. Последние 2 символа метафона - начало строки в текстовом файле (1.txt)
*/
mb_internal_encoding("utf-8");
$internal_enc = mb_internal_encoding();
mb_regex_encoding($internal_enc);

/* Список недопустимых имен каталогов в Windows:
CON, PRN, AUX, NUL, COM0, COM1, COM2, COM3, COM4, COM5, COM6, COM7, COM8, COM9, LPT0, LPT1, LPT2, LPT3, LPT4, LPT5, LPT6, LPT7, LPT8, LPT9.
CON: — console (input and output)
AUX: — an auxiliary device. In CP/M 1 and 2, PIP used PUN: (paper tape punch) and RDR: (paper tape reader) instead of AUX:
LST: — list output device, usually the printer
PRN: — as LST:, but lines were numbered, tabs expanded and form feeds added every 60 lines
NUL: — null device, akin to /dev/null
EOF: — input device that produced end-of-file characters, ASCII 0x1A
INP: — custom input device, by default the same as EOF:
OUT: — custom output device, by default the same as NUL:
*/

// <script>manage_message(document.currentScript.previousSibling)</script>;


$t0 = microtime(true);
header('Content-type: text/html; charset=utf-8');

// 0. Устанавливаем значения опасных переменных по умолчанию:
$begins = array(); $ends = array(); $begin = ''; $end = ''; $total_size = 0; $ru_dic_FILE_marks = ''; // Для начала
$ru_dic_marks_levels_Arr = '';  $ru_alfabet = ''; $min_WORD_len = 0; $metaphone_len = 0; $path_DIR_name = ''; $predlogi_PATH = '';
$path_FILE_name_STRING = ''; $enc_Arr = array(); $DO_working_flag_FILE = ''; $JS_manage_mes = ''; $reg_keywords = ''; // Они задаются в файле parametrs.php


// 1. Задаваемые параметры/функции (ПРИ ИЗМЕНЕНИИ ЭТИХ ПАРАМЕТРОВ ПОТРЕБУЕТСЯ ИНДЕКСАЦИЯ ВСЕХ ФАЙЛОВ ЗАНОВО!):
require __DIR__ . '/parametrs.php';
require_once __DIR__ . '/common_functions.php';

//*************   2. Проверяем, не было ли POST-запросов   *************************
if(isset($_POST['Notepad_PP']) && $_POST['Notepad_PP'] === 'Notepad_PP'){
    if(isset($_POST['n'])){
        if(!preg_match('|^[\d]{1,10}$|', $_POST['n'])){ // Если есть что-то, помимо цифр
            die('Неверный запрос браузера: число n может содержать только цифры, не более 10 символов<br/>');
        }else{
            $n = $_POST['n'];
        }
    }else{
        $n = 1;
    }
    if(isset($_POST['c'])){
        if(!preg_match('|^[\d]{1,10}$|', $_POST['c'])){ // Если есть что-то, помимо цифр
            die('Неверный запрос браузера: число c может содержать только цифры, не более 10 символов<br/>');
        }else{
            $n = $_POST['c'];
            $c = 0;
        }
    }else{
        $c = 0;
    }
// Значит, запрос на открытие файла files.txt
$file_to_open = PATH_FILE_NAMES_ALL_FILES;

start_NotepadPP_working1($file_to_open, $internal_enc, $n, $c);

die();
}
// Если был запрос на открытие файла-лога ошибок
if(isset($_POST['Notepad_PP']) && $_POST['Notepad_PP'] === 'Notepad_PP_error_log'){
    if(isset($_POST['n'])){
        if(!preg_match('|^[\d]{1,10}$|', $_POST['n'])){ // Если есть что-то, помимо цифр
            die('Неверный запрос браузера: число n может содержать только цифры, не более 10 символов<br/>');
        }else{
            $n = $_POST['n'];
        }
    }else{
        $n = 1;
    }
    if(isset($_POST['c'])){
        if(!preg_match('|^[\d]{1,10}$|', $_POST['c'])){ // Если есть что-то, помимо цифр
            die('Неверный запрос браузера: число c может содержать только цифры, не более 10 символов<br/>');
        }else{
            $c = $_POST['c'];
        }
    }else{
        $c = 0;
    }

    $file_to_open = PATH_FILE_NAMES_ERROR;

    if(!file_exists(PATH_FILE_NAMES_ERROR)){
        die('Похоже, файл-лог ошибок индексирования еще не создавался (значит, ошибок не было) или он был удален.');
    }

    start_NotepadPP_working1($file_to_open, $internal_enc, $n, $c);

die();
}
// Запрос на определение номера строки в файле files.txt
if(isset($_POST['ask_string_number']) && $_POST['ask_string_number'] === 'null'){
    $str_num = file_get_contents($path_FILE_name_STRING);

    if($str_num === false){
        echo -1;
    }elseif(trim($str_num) === ''){
        echo 0;
    }else{
        echo trim($str_num);
    }

die();
}
// запрос на сохранение номера строки, начиная с которой будут просматриваться строки в файле files.txt для последующей индексации
if(isset($_POST['save_string_number'])){
    if(!preg_match('|^[\d]{1,10}$|', $_POST['save_string_number'])){ // Если есть что-то, кроме цифр
        die('Неверный номер строки. Он может содержать только положительное целое число, не более 10 символов<br/>');
    }elseif($_POST['save_string_number'] < 1){
        die('Неверный номер строки. Он может быть только положительным, не более 10 символов<br/>');
    }else{
        $string_number = $_POST['save_string_number'];
        if(file_put_contents($path_FILE_name_STRING, $string_number)){
          echo '<p class="info_mes">Номер строки установлен. Для индексирования файлов сайта, начиная с номера строки, равного '. $string_number. ', обновите эту страницу...</p>';
        }else{
            $mess = 'Не получилось записать номер строки '. $string_number .' в файл '. $path_FILE_name_STRING;
            file_put_contents(PATH_FILE_NAMES_ERROR, $mess. ' '. date("d.m.Y"). PHP_EOL , FILE_APPEND);
            echo '<p class="error_mes">'. $mess . '</p>';
        }

    }
die();
}
// Запрос на поиск и показ номеров строк из файла files.txt, которые (точнее, файлы с содержащимися там именами) еще не были проиндексированы
if(isset($_POST['ask_string_numbers_NOT_indexed']) && $_POST['ask_string_numbers_NOT_indexed'] === 'null'){

    if(!file_exists(PATH_FILE_NAME_INDEXED_SUCCESS)){
        die('Похоже, индексирование файлов сайта еще не проводилось. Дело в том, что отсутствует файл-лог с индексами проиндексированных файлов. Попробуйте запустить операцию индексирования.');
    }

    $numbers_Arr = explode(PHP_EOL, file_get_contents(PATH_FILE_NAME_INDEXED_SUCCESS));
    $numbers_Arr = array_unique($numbers_Arr);

    $ALL_str_Arr = explode(PHP_EOL, file_get_contents(PATH_FILE_NAMES_ALL_FILES));

    $ALL_str_numbers_Arr = array();
    $max_size_to_SHOW = 10000;

    $size = min($max_size_to_SHOW, sizeof($ALL_str_Arr)); // Показываем только первые 10000 номеров

    if($size === $max_size_to_SHOW){
        echo '<p class="info_mes">(Показаны только '.$max_size_to_SHOW. ' первых номеров строк)</p>';
    }

    for ($i=1; $i < $size; $i++){ // Создаем массив номеров строк, от 1 до максимального (равного числы строк в файле files.txt)
        $ALL_str_numbers_Arr[$i] = $i;
    }

    $numbers_Arr = array_diff($ALL_str_numbers_Arr, $numbers_Arr);

    if(sizeof($numbers_Arr) > 0){
        echo '<h2>Вот номера строк из файла files.txt. В этих строках содержатся имена файлов, которые еще НЕ были поиндексированы:</h2>';
        echo implode(' ', $numbers_Arr);
    }else{
        echo '<h2>Все строки (с именами файлов сайта) успешно проиндексированы. Непроиндексированных файлов нет (среди разрешенных и/или не запрещенных).</h2>';
    }

die();
}
// Запрос на запуск/остановку индексирования
if(isset($_POST['DO_working'])){ // 1. Запуск индексирования
    if($_POST['DO_working'] === 'true'){

        if(!isset($_POST['last_managed_string']) || !preg_match('/^\d{1,10}$/', $_POST['last_managed_string'])){
            echo '<p class="error_mes">Неверное значение номера строки: допустимо только положительное целое число, не более 10 цифр.</p>';
            die();
        }

        $number = $_POST['last_managed_string'];
        file_put_contents($path_FILE_name_STRING, $number); // Сохраняем номер строки, начиная с которого пойдет поцесс индексирования


        if($number === '1'){ // Если начинаем индексирование с первой строчки из файла files.txt, то вначале нужно полностью УДАЛИТЬ каталог metaphones (!)
            if(is_dir($path_DIR_name)){
                if(!@rrmdir(realpath($path_DIR_name))){ // В случае ошибки
                    echo 'Ошибка: не получилось удалить каталог '.$path_DIR_name. '<br/><br/>';
                    check_ERRORS('');
                }
            }
//На всякий случай проверяем, удален ли каталог metaphones (были случаи, когда функция удаления непустого каталога удаляла НЕ ВСЕ вложенные каталоги)
            if(is_dir($path_DIR_name)){
                die('Каталог '. $path_DIR_name. ' <b>НЕ удален!</b> Повторите Запуск индексирования.');
            }
        }


    file_put_contents($DO_working_flag_FILE, ''); // Создаем файл-флаг. Если он присутствует, то итерации цикла перебора файлов (содержащихся в файле files.txt) будут продолжаться. Если нет - то цикл будет остановлен
// Определяем кодировку файла с перечнем файлов сайта и получаем массив, состоящий из имен этих файлов
    $rez_Arr = get_files_Arr($enc_Arr);
    $ALL_files_Arr = $rez_Arr[0];
    $ENC_FILE_names_all_files = $rez_Arr[1];

    echo 'true'; // Ответ клиенту, что можно обновлять страницу для начала индексирования. После этого клиент должен обновить страницу

    }elseif($_POST['DO_working'] === 'false'){ // 2. Останов

        if(!file_exists($DO_working_flag_FILE)){
            echo 'Файл уже был удален ранее. Его больше нет. Индексирование уже было остановлено.';
            die();
        }

        @unlink($DO_working_flag_FILE);

        if(!file_exists($DO_working_flag_FILE)){
            echo 'false'; // Сообщаем клиенту, что флаговый файл удален и индексирование остановлено
        }else{ // Флаговый файл почему-то не получилось удалить
            $mess = 'Error_unlink_flag_FILE: Флаговый файл '. $DO_working_flag_FILE. ' почему-то не получилось удалить';
            file_put_contents(PATH_FILE_NAMES_ERROR, $mess. ' '. date("d.m.Y"). PHP_EOL , FILE_APPEND);
            echo 'Error_unlink_flag_FILE';
        }
    }else{
        // ...
    }

die();
}
// Запрос на сортировку файла-словаря ru.dic
if(isset($_POST['sort_ru_dic']) && $_POST['sort_ru_dic'] === 'null'){
    define('flag_perfom_working', 1);
    require __DIR__ . '/dic_sorting.php';

    DIC_sort($ru_dic_FILE_NAME_saved, $ru_dic_FILE_NAME, $path_DIR_name, $min_WORD_len, $ru_dic_FILE_marks, $ru_dic_marks_levels_Arr, $ru_alfabet, $metaphone_len, $DO_working_flag_FILE, $ru_dic_indexing_info_file, $internal_enc, '');

    die();
}



//***************************************************************************************
?>

<script src="file_INDEXER.js"
        data-path_file_names_error = "<?php echo basename(PATH_FILE_NAMES_ERROR); ?>"
        data-path_file_names_all_files = "<?php echo basename(PATH_FILE_NAMES_ALL_FILES); ?>">
</script>

<?php
/* 3. Проверяем наличие файла files.txt и, если его нет, выдаем сообщение и кнопку для индексации ИМЕН файлов сайта(за исключением запрещенных)
      Файл files.txt формируется при помощи file_FINDER.php    */
if(!file_exists(PATH_FILE_NAMES_ALL_FILES)){
echo '<input src="/imgs/indexing-files.png" style="background-image: none; vertical-align: middle; margin-left: 15px; width: 41px;" onclick="file_FINDER()" class="buttons_REDACTOR" title="Запустить индексирование ИМЕН всех файлов сайта" alt="Индексировать" type="image"><br/>';
    die('Ошибка: не найден файл с перечнем всех файлов сайта (за исключением запрещенных) <b>'. PATH_FILE_NAMES_ALL_FILES. '</b>. Возможно, требуется сделать индексирование ИМЕН всех файлов сайта. Для этого - нажмите на кнопку.<br/>');
}


// 4. Определяем кодировку файла с перечнем файлов сайта и получаем массив, состоящий из имен этих файлов
$rez_Arr = get_files_Arr($enc_Arr);
$ALL_files_Arr = $rez_Arr[0];
$ENC_FILE_names_all_files = $rez_Arr[1];


// *********************************************************************************
// 5. Выдаем HTML-содержимое (в частности, панель управления)
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title>ПОИСК, Индексирование имен файлов сайта и их содержимого для реализации быстрого нечеткого поиска по искомым словам. Поиск по словам.</title>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes"/>
<style type="text/css">

* { font-size: 13px; box-sizing: border-box; font-family: Arial}
/*  Перенос слов  */
.hyphens{ -webkit-hyphens: auto; -moz-hyphens: auto; ms-hyphens: auto; hyphens: auto; word-wrap: break-word;}

#panel { display: table-cell; position: relative; max-width: 500px; vertical-align: top; margin-right: 15px; }
#rezults { display: table-cell; padding-left: 10px}

#tmpID { z-index: 1; width: 500px; margin-left: 0px; position: relative; background-color: #FFF3D1; text-align: left; border: medium solid;  }

#tmpID .buttons_REDACTOR {position: relative; display: inline-block; width: auto; padding: 0px 1px 1px 0px; height: auto; vertical-align: middle; text-align: left; border-width: 1px; border-style: solid;
    -moz-border-top-colors: none; -moz-border-right-colors: none; -moz-border-bottom-colors: none; -moz-border-left-colors: none;
    border-image: none; background-color: #F3F3F3; background-repeat: no-repeat; margin: 0px 1px 1px 0px;
    border-color: #FFF #505050 #505050 #FFF;
    box-shadow: 1px 1px 4px 2px rgba(185, 185, 185, 0.94);  }

#tmpID .buttons_REDACTOR:hover { background-color: #CEB867;}

#tmpID .buttons_REDACTOR:active { padding: 1px 0 0 1px; background-color: #b9b9b9;
    border-color: rgb(80,80,80) rgb(255,255,255) rgb(255,255,255) rgb(80,80,80);
    box-shadow: -2px -2px 7px 5px rgba(161, 161, 161, 0.59) inset; }

#last_index { background-color: rgb(145, 251, 255); display: inline-block; padding: 2px; font-weight: bold; }

.info_mes { display: inline-block; margin: 0; padding: 0; line-height: 100% }
.error_mes { display: block; margin: 0; padding: 0; color: red }
#responser .error_mes { font-weight: bold; color: red; font-size: 110%; margin: 5px }
#responser .info_mes { font-weight: bold; font-size: 110%; margin: 5px}

#closeBtn__ { cursor: pointer; float: right; background-color: #FF5C5C; padding: 5px; margin: 0px; width: 30px; text-align: center; font-size: 24px; }

#popup0 { width: auto; height: auto; border: solid 2px; position: absolute; left: 30px; top: 30px; background-color: #FFF3D1; box-shadow: -40px 0px 85px 73px #8C8C8C; display: none; z-index: 1;}

#popup1 { font-size: 14px; line-height: 150%; position: absolute; min-width: 500px; max-width: 700px; top: 30px; left: 500px; background-color: white; box-shadow: 50px 0px 185px 83px #8C8C8C; min-height: 300px; border: solid; display: none}
#popup1 p { margin: 3px; padding: 0; line-height: 120% }
#popup1 a.files { display: block; text-decoration: none}
#popup1 a.files:hover { color: red; background-color: wheat; text-decoration: underline}
#popup1 a.files:active { color: green}

#popup1 a.files { position: relative}

#popup3 { position: absolute; width: 400px; min-height: 50px; border-radius: 5px; border: solid 2px; background-color: lightyellow; padding: 2px; font-size: 12px; z-index: 1; display: none}

#popup3 * {font-size: 12px}

#poisk_spravka, #poisk_spravka_addition { max-width: 390px; margin: 5px 5px; font-size: 90%; }

ol { margin: 0; padding: 5px 30px;}

</style>

</head>
<body>


<div style="display: table">
<!-- Панель управления -->
<div id="panel">

    <div id="tmpID">
        <div style="display: inline-block; margin: 0; background-color: rgb(174, 255, 174); width: 100%">
            <div style="min-height: 40px; padding-left: 2px;">
                <input src="/imgs/indexing-files.png" style="background-image: none; vertical-align: middle; margin: 0 0 0 4px; width: 41px; float: right" onclick="file_FINDER()" class="buttons_REDACTOR" title="Запустить индексирование ИМЕН всех файлов сайта. Будет создан сводный файл со списком индексируемых файлов:
<?php echo realpath(PATH_FILE_NAMES_ALL_FILES); ?>)" alt="Индексировать" type="image"/>
                <input src="/imgs/sort1.png" style="background-image: none; vertical-align: middle; margin: 0 0 0 4px; width: 41px; float: right" onclick="ru_dic_SORTER('')" class="buttons_REDACTOR" title="Сортировка словаря. Будет заново создан и ПРОИНДЕКСИРОВАН словарь, отсортированный по алфавиту от А до Я. Старый файл сохранен под именем
<?php echo str_replace('\\', '/', $ru_dic_FILE_NAME_saved); ?>" alt="Сортировать" type="image"/>
                Файл: <span id="file_name" style="font-weight: bold; ">Файл не определен! Возможно, все файлы проиндексированы.</span></div>
            <div style="padding: 2px;">
                Этот файл содержится в <span><?php echo basename(realpath(PATH_FILE_NAMES_ALL_FILES)); ?></span> в строке номер: <span id="index" style="font-weight: bold">--</span></div>
        
        </div>

        <div style="display: inline-block; padding-top: 17px; margin-bottom: 10px; vertical-align: top;">
            <div style="display: inline-block">
                <span>Задать номер строки в файле <span><?php echo basename(realpath(PATH_FILE_NAMES_ALL_FILES)); ?></span>, с которого нужно начать<br/> (продолжить) индексирование:</span>

            <div style="display: inline-block; position: relative">
                <input id="last_managed_string" placeholder="1..." title="Вставьте индекс-номер файла, если нужно продолжить индексирование именно с этого файла" style="width: 150px;" type="text">
                <!--<input id="save" src="/imgs/save_as2.png" style="background-image: none; width: 25px; vertical-align: middle; margin-left: 15px;" class="buttons_REDACTOR" title="Сохранить заданный номер строки (в файле files.txt): с этого номера будет начато/продолжено индексирование файлов сайта" alt="SAVE->" type="image"/>-->
                <!--<div style="display: inline-block; font-size: 90%; font-style: italic; "> </div>-->
            </div>
            <div>
                <button style="margin: 5px 20px; cursor: pointer; padding: 3px" onclick="ask_string_number(); return false;" title="Если предыдущий процесс индексирования файлов был прерван браузером или в результате ошибки, то сервер мог сохранить, но НЕ УСПЕТЬ отправить браузеру последний номер строки">Показать номер строки, сохраненный на сервере  <br/>(чтобы с этого номера начать <br/>последующее индексирование)<span></span></button>
            </div>
            </div>

            <div style="display: inline-block; position: relative; vertical-align: top; max-width: 100px; text-align: right;">
            <div style="display: inline-block"><input id="DO_working_stop" src="/imgs/Close-Cancel.png" onclick="DO_working(false)" style="background-image: none; vertical-align: middle; position: relative; top: -5px; max-width: 41px" class="buttons_REDACTOR" title="Остановить индексирование файлов" alt="Стоп" type="image"/></div>

             <div style="display: inline-block"><input id="DO_working" src="/imgs/go.png" onclick="DO_working(true)" style="background-image: none; vertical-align: middle; position: relative; top: -5px; max-width: 41px" class="buttons_REDACTOR" title="Запуск индексирования СОДЕРЖИМОГО файлов (из списка, содержащегося в файле
<?php echo realpath(PATH_FILE_NAMES_ALL_FILES); ?>)" alt="Запуск" type="image"/></div>

            <div lang="ru" class="hyphens" style=" font-size: 90%; background-color: #AEFFF4; text-align: center">Показать номера НЕпроиндексированных строк:<input src="/imgs/find.png" style="background-image: none; width: 25px; vertical-align: middle; margin-left: 15px;" class="buttons_REDACTOR" title="Показать номера строк из файла files.txt, которые еще не были проиндексированы" onclick="find_NOT_INDEXED_strings()" alt="FIND->" type="image"/></div>
            </div>
        </div>
<p style="display: inline-block">Всего файлов, которые будут индексироваться: <span id="last_index"><?php echo sizeof($ALL_files_Arr); ?></span></p>
        <input id="Notepad" src="/imgs/notepad_pp-40.png" style="background-image: none; vertical-align: middle; margin-left: 15px;" class="buttons_REDACTOR" title="Открыть файл со списком индексируемых файлов Notepad++
<?php echo realpath(PATH_FILE_NAMES_ALL_FILES); ?>" alt="Открыть файл со списком индексируемых файлов Notepad++" type="image"/>

        <input src="/imgs/error_log.png" style="background-image: none; vertical-align: middle; margin-left: 15px; float: right; width: 41px" class="buttons_REDACTOR" title="Открыть лог-файл  журнал ошибок" onclick="open_error_log()" alt="Открыть файл ошибок" type="image"/>


<div style="overflow: auto;">
    <div style="float: right; background-color: #74E36D; padding: 15px 0px 0px 30px; margin-top: 20px">
        <input src="/imgs/find-files-indexed.png" style="background-image: none; float: right; z-index: 2; vertical-align: middle; width: 41px;" onclick="show_hide_POPUP(['popup0', 'popup1'], null)" class="buttons_REDACTOR" title="Найти файлы по искомым словам" type="image"/>
        <input id="fuzzy" src="/imgs/find-files-.png" style="background-image: none; float: right; z-index: 2; vertical-align: middle; height: 20px; width: 20px; margin-top: 21px;" onchange="fuzzy_finding('fuzzy', 'onchange')" class="buttons_REDACTOR" title="Поставьте галочку, чтобы использовать нечеткий поиск" type="checkbox"/>
    </div>
    <p style="margin-bottom: 0px;">Общий объем проиндексированных файлов: <span id="total"></span> кБ</p>
</div>

        <div id="tmpID_response"><div></div></div>

        <div style="position: absolute; right: -4px; bottom: -30px;"><div><input src="/imgs/delete-40_res.png" style="background-image: none; width: 25px" class="buttons_REDACTOR" title="Очистить журнал сообщений (ниже)" onclick="clean_responser('responser')" type="image"/></div></div>

        <div id="popup0">
            <p style=" margin: 0px; overflow: auto; background-color: rgba(0, 137, 254, 0.65); font-size: 14px; line-height: 20px; text-align: left;">
                <span style="padding: 4px; display: inline-block;">Нечеткий поиск по искомым словам:</span>
                <span id="closeBtn__" title="Закрыть" onclick="show_hide_POPUP(['popup0', 'popup1'], 'hide');"> × </span>
            </p>
            <textarea id="keywords" style="width: 400px; height: 150px; display: block" placeholder="Введите слова для поиска по индексированным файлам сайта...   Допускаются логические выражения, например: Слово1 && (Слово2 || слово3)...  Слово1 и (Слово2 или слово3)..."></textarea>
            <button style="padding: 6px; display: block; font-size: 120%; float: right; cursor: pointer; margin: 5px;" title="Выполнить нечеткий поиск (с использованием функции metaphone)" onclick="find_keywords()">Поиск</button>
            <p id="poisk_spravka">Поиск будет регистро-НЕзависимым.<br/>Допускается не более <span id="max_len"><?php echo $max_keywords_LEN; ?></span> символов, включая пробелы.<br/>Допустимы русские, английские буквы, пробелы <br/>(аналоги &amp;&amp;), скобки и логические операторы &amp;&amp; || (и или). </p><p id="poisk_spravka_addition" style="background-color: #C4FFC9"></p>
        </div>

        <div id="popup1"></div>

    </div>
    <div id="responser">
        <!--Фиктивный блок, нужен для обтекания (под кнопкой очистки)-->
        <div style="float: right; width: 30px; height: 30px"></div>
    </div>


</div>

<div id="popup3"></div>



<script type="text/javascript">
/* <!-- [CDATA [*/

// Скрываем всп. окно с description (или начальной частью файла)
document.getElementById('popup3').onmouseout = function () {
    var $this = this;
    var timer = setTimeout(function () {
        $this.style.display = 'none';
    }, 500);

document.getElementById('popup3').onmouseover = function () { // Если успели вернуть мышь обратно, то отключаем таймер и оставляем всп. окно
    clearTimeout(timer);
};
};



fuzzy_finding('fuzzy', null); // добавляем или убираем дополнительную информацию о нечетком поиске на панель

// Клавиши Escape,
document.onkeydown = function ( event ){

    if ( event.keyCode === 27 ) { // Escape
// 1. Закрываем ВСЕ (точнее, не более 100) всп. окна вида popup0, popup1, ...
//    var i = -1;
    var popups = [];
    var popups_max_num = 100; // Можно и больше, это с запасом

        for(var i=0; i < popups_max_num; i++){
            if(document.getElementById('popup' + i)){
                popups.push('popup' + i);
            }
        }
        show_hide_POPUP(popups, 'hide');
    }
};

//Функция выдает/убирает предупреждение и информацию о нечетком поиске
function fuzzy_finding(idd, par) {
    if(document.getElementById(idd).checked){
        if(par === 'onchange'){ // Если запуск функции произошел после события onchange мыши
            alert('НЕчеткий поиск позволяет найти те же слова, но с разными окончаниями. Использование НЕчеткого поиска - гораздо дольше, чем обычный поиск.');
        }
        document.getElementById('poisk_spravka_addition').innerHTML = 'При использовании нечеткого поиска, помимо самого искомого слова, будут искаться <span style="font-weight: bold; font-size: inherit">также близкие к нему слова</span>, т.е. имеющие разные (допустимые) окончания, если такие слова есть в русском языке.';

    }else{
        document.getElementById('poisk_spravka_addition').innerHTML = '';
    }
}


// Функция открывает программу Notepad++, а в ней - файл-лог ошибок
function open_error_log() {
    var focus_line = 1;
    var focus_offset = 0;
    var body = 'Notepad_PP=Notepad_PP_error_log&n=' + focus_line + '&c=' + focus_offset + '&r=' + Math.random();
    var arg_Arr = ['Notepad_PP_error_log'];
    var url_php = '<?php echo  str_replace('\\', '/', $_SERVER["PHP_SELF"]); ?>';

    DO_send_data1(Function_after_server, arg_Arr, url_php, body);
}

// Функция показывает/скрывает всп. окно для поиска по искомым словам
function show_hide_POPUP(popups, display) {

    if(display === 'hide'){
        change_display(popups, 'none')
    }else{
        if(getComputedStyle(document.getElementById(popups[0])).display === 'none'){
            change_display(popups, 'block')
        }else {
            change_display(popups, 'none')
        }
    }

function change_display(popups, x) {
    for(var i1=0; i1 < popups.length; i1++){
        document.getElementById(popups[i1]).style.display = x;
    }
 }

}

// Функция делает запрос на сервер для поиска по (нечетким) искомым словам
function find_keywords() {
    var text = document.getElementById('keywords').value.toLowerCase();
    var max_len = parseInt(document.getElementById('max_len').textContent);
    var logic_operands = ['&&', '||', 'и', 'или'];

    if(text.length > max_len){
        alert('Общая длина искомых слов, включая пробелы и логические символы, не может превышать ' + max_len + ' символов');
        return;
    }
    var reg = <?php echo $reg_keywords; ?>; /* /[^абвгдеёжзийклмнопрстуфхцчшщъыьэюя\sqwertyuiopasdfghjklzxcvbnm&\|\(\)]/  */
    if(text.match(reg)){ // Если обнаружится иной символ
        alert('В искомых словах допускаются только русские или английские буквы. \nТакже допустимы логические выражения с символами (   )  &&  ||');
        return;
    }

    if(!text){
        alert('искомые слова не заданы!');
        return;
    }

    var text_words_Arr = text.trim().replace(/\s+/g, ' ').split(' ');

    var min_len = <?php echo $min_WORD_len; ?>;

    var text_1_2_Arr = text_words_Arr.filter(function (el) {
        return (el.length < min_len) && !(logic_operands.indexOf(el) !== -1);
    });
    if(text_1_2_Arr.length > 0){
        var text_1_2 = text_1_2_Arr.join(', ');
        alert('Предупреждение: слишком короткие искомые слова (короче '+ min_len+ ' символов) НЕ будут отыскиваться. Это, в частности, слова:\n'+ text_1_2);
    }

    document.getElementById('popup1').innerHTML = ''; // Очищаем область для сообщений от сервера
    var fuzzy = document.getElementById('fuzzy').checked ? 1 : 0;
    var body = 'find_keywords='+ encodeURIComponent(text)+ '&fuzzy=' + fuzzy + '&r=' + Math.random();
    var arg_Arr = ['find_keywords', 'popup3'];
    var url_php = 'keywords_FINDER.php';

    DO_send_data1(Function_after_server, arg_Arr, url_php, body);

// Создаем заставку, пока ожидается ответ от сервера
    zastavka('popup1');
}


// Функция сортирует файл словаря ru.dic (взятого из Firefox-36) в алфавитном порядке
function ru_dic_SORTER(mess) {

    if(!confirm(mess + '\n\nНачать сортировку словаря <?php echo str_replace('\\', '/', $ru_dic_FILE_NAME); ?>? Это займет некоторое время...')){
        return;
    }

    var body = 'sort_ru_dic=null' + '&r=' + Math.random();
    var idd = 'rezults';
    var arg_Arr = ['sort_ru_dic', idd];
    var url_php = '<?php echo  str_replace('\\', '/', $_SERVER["PHP_SELF"]); ?>';

    document.getElementById(idd).innerHTML = '<div id="rezults_time"></div><div id="rezults_counter"></div>';

    DO_send_data1(Function_after_server, arg_Arr, url_php, body);

    zastavka('rezults_time');
    startListening(true, "Начинаем индексацию файла-словаря...", "<b>Индексация остановлена.</b>"); // Прослушиваем события сервера (будет сообщать периодически номер очередной проиндексированной строки в файле-словаре
}

// Функция делает очистку блока для сообщений (журнала), расположенного сразу ниже панели
function clean_responser(idd) {
    document.getElementById(idd).innerHTML = '<div style="float: right; width: 30px; height: 30px"></div>'; // Вставляет фиктивный блок для обтекания
}


// Функция делает запрос на индексирование или на прекращение его
function DO_working(flag_working) {

    if(!flag_working){
        stopListening("<b>Индексация остановлена.</b>"); // Прекращаем послушивать серверные события
    }


    var last_managed_string = document.getElementById('last_managed_string').value;

    var mess = '';
    if(last_managed_string === '1'){
        mess = 'Вы указали номер строки, равный '+ last_managed_string + '. Это означает, что индексация СОДЕРЖИМОГО файлов сайта пойдет ЗАНОВО. Перед этим каталог с индексированными файлами\n\n <?php echo str_replace('\\', '/', $path_DIR_name); ?> \n\nбудет УДАЛЕН!';
    }

    if(flag_working && !confirm(mess + '\n\nНачать/продолжить индексирование файлов сайта? Это займет некоторое время...')){
        return;
    }

    if(!last_managed_string && flag_working){
        alert('Введите номер строки из файла files.txt, с которого нужно начать/продолжить индексирование. Если не знаете, с какого номера начать, введите 1');
        return;
    }

    var body = 'DO_working='+ flag_working+ '&last_managed_string=' +last_managed_string + '&r=' + Math.random();
    var arg_Arr = ['DO_working'];
    var url_php = '<?php echo  str_replace('\\', '/', $_SERVER["PHP_SELF"]); ?>';

    DO_send_data1(Function_after_server, arg_Arr, url_php, body);
}


document.getElementById('Notepad').onclick = function() {
    var focus_line = 1;
    var focus_offset = 0;
    var body = 'Notepad_PP=Notepad_PP&n=' + focus_line + '&c=' + focus_offset + '&r=' + Math.random();
    var arg_Arr = ['Notepad_PP'];
    var url_php = '<?php echo  str_replace('\\', '/', $_SERVER["PHP_SELF"]); ?>';

    DO_send_data1(Function_after_server, arg_Arr, url_php, body);
};

// Функция запрашивает номер строки в файле files.txt, который содержит относит. путь к файлу сайта, индексировавшемуся в предыдущий раз
function ask_string_number() {
    var body = 'ask_string_number=null' + '&r=' + Math.random();
    var arg_Arr = ['ask_string_number'];
    var url_php = '<?php echo  str_replace('\\', '/', $_SERVER["PHP_SELF"]); ?>';

    DO_send_data1(Function_after_server, arg_Arr, url_php, body);
}

// Функция делает запро на сервер для поиска еще непроиндексированных строк из файла files.txt (в каждой из них содержится относит. путь к файлу)
function find_NOT_INDEXED_strings() {
    var body = 'ask_string_numbers_NOT_indexed=null' + '&r=' + Math.random();
    var arg_Arr = ['ask_string_numbers_NOT_indexed'];
    var url_php = '<?php echo  str_replace('\\', '/', $_SERVER["PHP_SELF"]); ?>';

    DO_send_data1(Function_after_server, arg_Arr, url_php, body);
}

// Функция создает таймер-заставку (счет времени по секундам) в блоке, ожидающем ответа сервера
// Когда исчезает элемент с классом "waiting", таймер останавливается
function zastavka(idd, Function_name, funct_arg_Arr) {
    document.getElementById(idd).innerHTML = '<span class="waiting">'+ (0) + ' сек.</span>';
    var i=0;
    var timer = setInterval(function () {

        if(!document.getElementById(idd)){
            clearInterval(timer);
            return;
        }

        if(!document.getElementById(idd).querySelector('[class="waiting"]')){
            clearInterval(timer);
        }else {
            document.getElementById(idd).innerHTML = '<span class="waiting">'+ (i++) + ' сек.</span>';
//            Function_name(funct_arg_Arr); // Через промежуток время запускаем другую функцию
        }
    }, 1000);
}




// Функция запускает серверное событие
function startListening(flag, go, end) {
    if(flag){
    source = new EventSource("events.php");
    source.onmessage = receiveMessage;
    var rezults = document.getElementById('rezults_counter');
    rezults.innerHTML = "" + go;
    }

    if(!flag){
        stopListening(end);
    }
}
function stopListening(end) {

    try {
        source.close();
    }catch (er){
        console.log(er);
    }
    var rezults = document.getElementById('rezults');
    rezults.innerHTML += "<br>" + end;
}
function receiveMessage(event) {
    var rezults = document.getElementById('rezults_counter');
    rezults.innerHTML = event.data;
}




// Функция запускается скриптами, периодически приходящими с сервера
function show_index(number, file_name, total_size) {
// Помещает присланный номер строки (из файла files.txt), в которой содержится относит. путь к только что проиндексированному файлу, в блоки:
    document.getElementById('index').textContent = number;
    document.getElementById('file_name').textContent = file_name;
//    document.getElementById('last_index').textContent = number;
    document.getElementById('last_managed_string').value = number;
    document.getElementById('total').textContent = Math.round(total_size/1024 * 100)/100; // Общий размер проиндексированных файлов с момента последнего запуска индексирования
}

// Функция запускается скриптами, периодически приходящими с сервера: дублирует сообщения об ошибках в блок id="responser"
function manage_message() {
    document.getElementById('responser').innerHTML += document.currentScript.previousSibling.outerHTML;
}

// Функция делает запрос на сервер для получения еще  ссылок на файлы, содержащие искомые слова
function show_links_in_POPUP(idd) {

    var link_last_num = document.getElementById('num_showed_links').textContent; // Отправляем общее число ссылок на странице. чтобы сервер знал, с какого номера продолжить выдачу ссылок
    var body = 'ask_keyword_links='+link_last_num + '&r=' + Math.random();
    var arg_Arr = ['ask_keyword_links'];
    var url_php = 'keywords_FINDER.php';

    DO_send_data1(Function_after_server, arg_Arr, url_php, body);
}


/*]] --> */
</script>


<div id="rezults">

</div>



<?php

flush(); // Чтобы вывести предыдущий html (без задержек)


indexer($predlogi_PATH, $min_WORD_len, $internal_enc, $path_FILE_name_STRING, $ALL_files_Arr, $DO_working_flag_FILE, $ENC_FILE_names_all_files, $enc_Arr, $total_size, $begins, $ends, $path_DIR_name, $metaphone_len, $JS_manage_mes);


// 8. Контроль общего времени выполнения
$t1 = microtime(true);

echo '<br/><br/>Затрачено времени: '. ($t1 - $t0). ' секунд.';


die();

?>


</div>


</body>
</html>


<?php

/**********    ФУНКЦИИ    *************/

function indexer($predlogi_PATH, $min_WORD_len, $internal_enc, $path_FILE_name_STRING, $ALL_files_Arr, $DO_working_flag_FILE, $ENC_FILE_names_all_files, $enc_Arr, $total_size, $begins, $ends, $path_DIR_name, $metaphone_len, $JS_manage_mes){

// 6. Работаем с русскими предлогами (союзами и т.п.)
    $predlogi_Arr = file($predlogi_PATH); // Берем практически все известные предлоги, частицы, союзы, междометия, местоимения русского языка
    $predlogi_Arr = array_map('trim', $predlogi_Arr);

    $predlogi_Arr = array_filter($predlogi_Arr, function ($el) use ($min_WORD_len, $internal_enc){ // Берем только слова не короче $min_WORD_len символов
        return (mb_strlen($el, $internal_enc) >= $min_WORD_len);
    });

    $predlogi_Arr = array_unique($predlogi_Arr); // Убираем повторяющиеся слова из массива
    file_put_contents('predlogi_DATA.txt', implode(PHP_EOL, $predlogi_Arr));

    $i_begin = 0;
    if(is_readable($path_FILE_name_STRING)){ // Если ранее уже проводилось индексирование и был запасен индекс-номер последнего проиндексированного файла
        $i_begin = 1*(trim(file_get_contents($path_FILE_name_STRING))) - 1;
    }

    if($i_begin < 0){ // На всякий случай, во избежание ошибки
        $i_begin = 0;
    }

    $flag_was_indexing = false;

    /*****************************************************************************/
    /********      7. ИНДЕКСИРУЕМ КАЖДЫЙ ФАЙЛ ИЗ МАССИВА ФАЙЛОВ      *************/
    for($i=$i_begin; $i < sizeof($ALL_files_Arr); $i++){ // По массиву файлов сайта, разрешенных (или не запрещенных) к индексации

        /* Проверяем, присутствует ли флаговый файл. Если да, то делаем следующую итерацию. Если нет - прекращаем цикл */
        if(!file_exists($DO_working_flag_FILE)){

            if($flag_was_indexing){ // Если индексирование делалось, но было прервано
                echo 'Индексирование прекращено';
            }else{ // Если страница была просто обновлена
                echo 'Для начала/продолжения индексирования файлов сайта нужно произвести ЗАПУСК индексирования. Индексирование начнется с номера строки (в файле files.txt), который задан в панели слева.';
            }
            break;
        }

        $flag_was_indexing = true; // Если после запуска индексирования была хотя бы 1 итерация, т.е. индексировался хотя бы 1 файл

        set_time_limit(40); // С этого момента скрипт будет выполняться не более указанного количества секунд (каждая итерация цикла). Точнее, будет выбрано минимальное время из указанного количества секунд и установленного в настройках (файл php.ini)

// 7.1. Берем содержимое выбранного файла
        $file_name = $ALL_files_Arr[$i]; // Относительный путь к индексируемому файлу
        $file_name_ABS = realpath($_SERVER['DOCUMENT_ROOT']. $file_name); // Абсолютный путь

// Имя файла перекодированное (только для вывода в виде информации на экран). Актуально, когда в имени файла содержится, например, кириллица
        $file_name_encoded = ($ENC_FILE_names_all_files === $internal_enc) ? $file_name : mb_convert_encoding($file_name, $internal_enc, $ENC_FILE_names_all_files);

        if(!is_readable($file_name_ABS)){ // Если файл вдруг не существует (мало ли...) или заблокирован в данный момент
            $mess = ' Ошибка: файл '.$file_name_encoded. ' не удалось проиндексировать, т.к. он недоступен для чтения';
            echo '<p class="error_mes">'. $mess. '</p>'. $JS_manage_mes;
            file_put_contents(PATH_FILE_NAMES_ERROR, $file_name. '|'. array_search($file_name, $ALL_files_Arr). '|'. $mess.' '.date("d.m.Y") . PHP_EOL , FILE_APPEND);
            continue;
        }else{
            echo '<p class="info_mes">'. ($i+1).'. Индексируется файл: <b>'.$file_name_encoded. '... </b></p>';
        }

        flush(); // Чтобы выводить строчки постепенно, одну за другой, а не потом все сразу

        $body = file_get_contents($file_name_ABS);



// 7.2. Определяем кодировку содержимого индексируемого файла (пока допустимы только cp1251 и utf-8)
        $enc = check_enc($body, $enc_Arr);
        if(!$enc){
            $mess = ' Не удалось определить кодировку файла '. $file_name_encoded;
            echo '<p class="error_mes">'. $mess. '</p>'. $JS_manage_mes;
            file_put_contents(PATH_FILE_NAMES_ERROR, $file_name. '|'. array_search($file_name, $ALL_files_Arr). '|'. $mess. ' ' .date("d.m.Y"). PHP_EOL , FILE_APPEND);
        }

        if($enc !== strtolower($internal_enc)){
            $body = mb_convert_encoding($body, $internal_enc, $enc);
        }

        $total_size += mb_strlen($body, $internal_enc); // Добавляем объем этого файла

// 7.3. Ищем в содержимом файла теги <body>...</body>
        $body_num = @preg_match_all('|<body[^>]*>([\s\S]*)</body>|', $body, $matches, PREG_SET_ORDER);

        if($body_num === false){
            $mess = ' Ошибка в функции preg_match_all() при работе с файлом '. $file_name_encoded .'; см. Файл '. $_SERVER['PHP_SELF'] . ', стр.'. __LINE__ ;
            echo '<p class="error_mes">'. $mess . '</p>'. $JS_manage_mes;
            file_put_contents(PATH_FILE_NAMES_ERROR, $file_name. '|'. array_search($file_name, $ALL_files_Arr). '|'. $mess. ' '. date("d.m.Y"). PHP_EOL , FILE_APPEND);
        }

        if(sizeof($matches) === 1){ // Если этих тегов ровно 1 пара

            $body = $matches[0][1]; // Содержимое тегов <body>...</body>

// 7.4. Пробуем найти редактируемую область в содержимом файла (она должна быть внутри тегов <body>...</body>). Если она есть, то берем ее содержимое
            $flag_FIND_redact_area = true; // Искать ТОЛЬКО  в редактируемой области (при ее наличии)
// Если в файле задана редактируемая область и задан флаг true (ТОЛЬКО в редактируемой области), то берем только ЕЕ содержимое

            $path_redactorDATA = "/LOCAL_only/REDACTOR/redactorDATA.php";
            if(file_exists($_SERVER['DOCUMENT_ROOT']. $path_redactorDATA)){ // Если найден файл настроек редактора

                require_once $_SERVER['DOCUMENT_ROOT']. $path_redactorDATA;

                $domen = $_SERVER['SERVER_NAME'];
                $domen = preg_replace("'(^www\.)'", "", $domen); // Вырезаем www.  в самом начале, если есть

                $begin = $begins[$domen];
                $end = $ends[$domen];

                if(CHECK_begin_end_finder($domen, $begin, $end, $body)){ // Если в файле ЕСТЬ комментарии, ограничивающие редактируемую область

                    $pos_BEGIN = strpos($body, $begin);
                    $pos_END = strpos($body, $end);

                    if($pos_BEGIN !== false && $pos_END !== false){ // Если ограничивающие комментарии найдены в тексте, значит, ЕСТЬ редактируемая область
                        $body = substr($body, $pos_BEGIN, $pos_END - $pos_BEGIN); // Берем только содержимое, содержащееся между ограничивающими комментариями
                    }
                }
            }
        }else{
//    die('В файле '. $file_name_encoded. ' число пар тегов <body>...</body>'. ' НЕ равно 1.');
        }
        $body = preg_replace('~<\?[\s\S]*?\?>|<\?[\s\S]*~', ' ', $body); // Удаляем все теги PHP.
        $body = preg_replace('|<script[^>]*>([\s\S]*?)</script>|i', ' ', $body); // Вырезаем все JS-скрипты (т.к. их индексировать не будем)
        $body = preg_replace('|<[^>]*?>|', ' ', $body); // Вырезаем все теги html, DOCTYPE и комментарии
// Итак, получено текстовое содержимое (textContent) или полное, или, быть может, только между тегами <body>...</body> или, быть может, даже только между ограничивающими комментариями, ограничивающими редактируемую область


// 7.5. Дорабатываем полученную текстовую строку, превращая ее массив метафонов
        $body = preg_replace('|&[^;]*;|', '', $body); // Вырезаем HTML_сущности (т.к. их индексировать не будем)
        $reg = '[^абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM]'; // для кириллицы параметр i не работает, поэтому берем как строчные, так и заглавные буквы
//$body = preg_replace($reg, ' ', $body); // Работает, НО(!) проблемы с символом №, например

        $body = mb_ereg_replace($reg, ' ', $body); // Вырезаем все, кроме пробелов, русских и английских букв (обоих регистров)
        $body = preg_replace('|\s+|', ' ', $body); // Вырезаем лишние пробельные символы, оставляем только по одному пробелу

        $body_Arr = explode(' ', mb_strtolower($body, $internal_enc)); // Массив слов из (основного) содержимого файла

        $body_Arr = array_filter($body_Arr, function ($el) use ($min_WORD_len) {
            return (mb_strlen(($el)) >= $min_WORD_len); // Оставляем только слова, которые не короче $min_WORD_len
        });


        $body_Arr = array_unique($body_Arr);
        $body_Arr = array_diff($body_Arr, $predlogi_Arr); // Убираем (многие) предлоги, междометия, частицы, местоимения русского языка
        $body_Arr = array_map('translit1', $body_Arr); // В транслит, чтобы можно было применить функцию metaphone()


        $body_Arr = array_map(function ($word) use ($internal_enc, $metaphone_len){
            return do_metaphone1($word, $metaphone_len); // Превращаем в metaphone
        }, $body_Arr);

// Получаем массив уникальных метафонов
        $body_Arr = array_unique($body_Arr); // Оставляем только уникальные слова, т.е. повторы не учитываем (пока)
        $body_Arr = array_values($body_Arr);

        /**************************************************************************/

// 7.6. Создаем каталог для подкаталогов-символов - частей метафонов
        if(!is_dir($path_DIR_name)){
            mkdir($path_DIR_name);
        }

// 7.7. Создаем индекс-каталоги, в них создаем индекс-файлы, а в последние записываем индекс-номера путей к файлам сайта (если таких номеров еще не было записано)
        if(($number = array_search($file_name, $ALL_files_Arr)) !== false){ // Определяем число-индекс, присутствующий у имени файла (из содержимого которого выше были получены слова-метафоны), после |  (в файле files.txt)

            for($k=0; $k < sizeof($body_Arr); $k++){ // По каждому из слов
                $path_DIR_name_TMP = $path_DIR_name;
                if(isset($body_Arr[$k]) && $body_Arr[$k] != ''){

                    $rez = LAST_met_2_index($body_Arr[$k], '', $file_name, $path_DIR_name_TMP, $number, $ALL_files_Arr, $JS_manage_mes);
            if($rez === 'continue'){
                continue;
            }

                }
//die();
            }

// 7.8. На всякий случай, окончательно делаем контроль ошибок
check_ERRORS('Error|Произошла ошибка при индексировании файла '. $file_name_encoded. '. Номер строки '. ($i+1). $JS_manage_mes);

// 7.9. Если ошибок не было, сохраняем номер проиндексированного файла (это - номер строки, начиная с 0, в файле files.txt). Здесь будет содержаться перечень индексов тех файлов, которые успешно проиндексированы
            file_put_contents(PATH_FILE_NAME_INDEXED_SUCCESS, ($i+1). PHP_EOL, FILE_APPEND);
            file_put_contents($path_FILE_name_STRING, ($i+1). PHP_EOL);
        }

// 7.10. Если дошли до сюда (в каждой итерации цикла перебора индексируемых файлов), то выводим подтверждение об успехе. Также сообщаем номер строки в файле files.txt, содержащей имя только что проиндексированного файла и его индекс
        echo ' OK'. '<script>show_index('. ($i+1). ',"'. str_replace("\\", "\\\\", $file_name_encoded) .'",'. $total_size. ')</script>'. '<br/>'; // +1, т.к. номерация строк в файле (в Notepad++) начинается с 1, а не с 0
        flush();

    }

}

// Функция проверяет наличие начального и конечного ограничивающих комментариев (как и в программе для редактирования)
function CHECK_begin_end_finder($domen, $begin, $end, $text_html){

// Проверяем наличие начальных комментариев, ограничивающих основной контент страницы. Их должно быть ровно по ОДНОМУ
    if($begin == ""){
return false;
//        die("Эта страница - не для наших сайтов, ее корректировка невозможна (отсутствуют начальные комментарии, ограничивающие начало редактируемого контента). Возможно, следует открыть эту страницу в браузере <span style='font-weight: bold'>БЕЗ www.</span>");
    }
    $beginCOMMS = preg_match_all("/".  preg_quote($begin, '/'). '/', $text_html, $matches);
    $endCOMMS = preg_match_all("/".  preg_quote($end, '/'). '/', $text_html,  $matches);

// Проверка на отсутствие начальных и конечных ограничивающих комментариев. Актуально для файлов, которые могут редактироваться при помощи редактора
    // Начальные
    if($beginCOMMS < 1)
return false;
    if($endCOMMS < 1)
return false;

    // Конечные
    if($beginCOMMS > 1)
return false;
    if($endCOMMS > 1)
return false;


return true;
 }



// Функция открывает программу notepad++, а в ней - файл с именем $file_to_open
function start_NotepadPP_working1($file_to_open, $internal_enc, $n, $c){

    $command = 'start notepad++ -n'. $n .' -c'. $c. ' ' .  $file_to_open;

    echo '<p class="info_mes">Команда открытия Notepad++ выполнена.</p>';

    exec($command, $exec_output, $exec_res_code);

    if($exec_res_code != 0){ // Ошибка открытия notepad++
        print_r($exec_output); // Вывод команды exec в случае ошибки
        $mess = 'В результате попытки открытия файла в программе notepad++ возникла ошибка. Вот ее код: \'. $exec_res_code';
        file_put_contents(PATH_FILE_NAMES_ERROR, $mess. ' '. date("d.m.Y"). PHP_EOL , FILE_APPEND);
        die('<p class="error_mes">В результате попытки открытия файла в программе notepad++ возникла ошибка. Вот ее код: '. $exec_res_code .'</p>>');
    }
 }










