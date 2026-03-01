<?php
// Программа ищет ВСЕ файлы на сайте и записывает их относительные пути в файл
// В файл будут записаны строки вида: Относительный путь|Номер. Номер - это уникальный индекс этого файла

mb_internal_encoding("utf-8");
$internal_enc = mb_internal_encoding();

// 1. Задаваемые параметры/функции
require __DIR__ . '/parametrs.php';

$forbidden_FILE_extensions = array('css', 'js', 'csv', 'png', 'pdf', 'bmp', 'jpg', 'jpeg', 'md', 'xls', 'log', 'inc', 'sh', 'json', 'yml', 'db', 'htaccess', 'xml', 'gif', 'doc', 'docx', 'svg', 'svgx', 'rar', 'zip', 'xcf', 'cdw', 'mp4', 'webp', 'ico', 'exe', 'mov', 'ini');  // Файлы с такими расширениями НЕ будут просматриваться
$forbidden_dirs = array('.idea', '.git', 'img', 'js', 'css', 'SSI', 'TEST', 'LOCAL_only', 'lib', 'metaphones');
$allowed_FILE_extensions = array('htm', 'html', 'php', 'txt'); // Будут просматриваться файлы только с такими расширениями (относит. пути к ним и будут включены в файл с путем $ALL_files_path)
$flag_allowed_FILE_extensions = true; // Применять (если - true) массив разрешенных расширений файлов

$dir_relative = '/'; // Относительный путь к каталогу, где будет производиться поиск файлов
$entry = '';


header('Content-type: text/html; charset=utf-8');


$ALL_files_path = PATH_FILE_NAMES_ALL_FILES; // Абсолютный путь к файлу с перечнем ВСЕХ (незапрещенных и/или разрешенных) файлов сайта

$path = $_SERVER['DOCUMENT_ROOT']. $dir_relative; // Абсолютный путь до начального каталога
file_put_contents($ALL_files_path, ''); // Очищаем файл

$i = 0; // Индекс-Номер файла (он потом будет записан в файл с именем $ALL_files_path). Запись будет иметь примерный вид: filename|4

if(!$flag_allowed_FILE_extensions){
    $allowed_FILE_extensions = null;
}

// 2. Записываем относительные пути к файлам (за исключением запрещенных) сайта в файл
$files = FILES_in_DIR($path, $forbidden_FILE_extensions, $allowed_FILE_extensions); // Число файлов в текущем каталоге
save_FILES_NAMES($files, $path, $ALL_files_path, $i); // Вначале выводим имена файлов в текущем каталоге
look_dir($path, $entry, $forbidden_FILE_extensions, $forbidden_dirs, $allowed_FILE_extensions, $ALL_files_path, $i); // В подкаталогах


echo 'Создан файл: <br/><br/><b>'. $ALL_files_path. '</b><br/><br/> с именами разрешенных/незапрещенных (для индексации) файлов сайта. <br/><br/>Теперь <b>НЕОБХОДИМО</b>  запустить индексацию этих файлов (иначе поиск будет происходить НЕПРАВИЛЬНО). В результате индексации будет создан/обновлен каталог <b>/'. basename($path_DIR_name). '</b> с содержащимися там каталогами, сообразно символам метафонов слов контента файлов сайта, а также файлами 1.txt.';

/*****************     ФУНКЦИИ     *************************/

// Используется рекурсия. Поэтому при достаточно большом числе файлов на сайте эта функция может дать сбой.
function look_dir($path, $entry, $forbidden_FILE_extensions, $forbidden_dirs, $allowed_FILE_extensions, $ALL_files_path, &$i){

    if(in_array(basename($path), $forbidden_dirs)){ // Если последняя часть пути содержится в массиве запрещенных каталогов
        return;
    }

    chdir($path);
    if($handle = opendir($path)){

        while (false !== ($entry = readdir($handle))) {

            if (is_dir($entry)) { // Если каталог
                if (($entry == ".") || ($entry == "..")) {
                    continue;
                }
                $entry = realpath($entry);

    if(in_array(basename($entry), $forbidden_dirs)){ // Если последняя часть пути содержится в массиве запрещенных каталогов
        continue;
    }

                $files = FILES_in_DIR($entry, $forbidden_FILE_extensions, $allowed_FILE_extensions); // Массив файлов в текущем каталоге (только файлы, без каталогов)
                save_FILES_NAMES($files, $entry, $ALL_files_path, $i); // Выводим (записываем в файл) имена файлов

                look_dir($entry, $entry, $forbidden_FILE_extensions, $forbidden_dirs, $allowed_FILE_extensions, $ALL_files_path, $i);
            }
        }
        closedir($handle);
        chdir('..');
    }else{
        echo 'Каталог '. realpath($entry). ' не может быть открыт.';
    }

}

//Функция записывает относительные пути файлов, имеющихся на сайте, в файл
function save_FILES_NAMES($files, $entry, $ALL_files_path, &$i){
    if(sizeof($files)){ // Если в текущем каталоге есть файлы

        for($j=0; $j < sizeof($files); $j++){

            $file_entry = realpath($entry. '/'. $files[$j]);

$pos = strlen($_SERVER['DOCUMENT_ROOT']);
$entry_rel = substr($file_entry, $pos);
    file_put_contents($ALL_files_path, $entry_rel. '|'. $i++ . PHP_EOL, FILE_APPEND); // Записываем относительные пути к файлам в файл

        }
    }

}


function FILES_in_DIR($path, $forbidden_FILE_extensions, $allowed_FILE_extensions){ // Возвращает:
    /* -1, если $path НЕ является каталогом,
     * false, если в каталоге $path НЕТ файлов,
     * true, если в каталоге $path ЕСТЬ файлы (точнее, не каталоги).
     */
    if(is_dir($path)){ // Требуется ПОЛНЫЙ путь
        $path_Arr = scandir($path);
    }else{
        return -1;
    }

    $rez_Arr = array_filter($path_Arr, function ($name) use ($path){
        return !is_dir(realpath($path. '/'. $name)); // Каталоги НЕ включаем в массив
    });

// Оставляем в массиве файлов только те, расширения которых НЕ содержатся в списке (массиве) запрещенных файлов. Т.е. за исключением рисунков, видео, pdf и пр.
// Или же оставляем те, которые СОДЕРЖАТСЯ в списке (массиве) разрешенных расширений
    $rez_Arr = array_filter($rez_Arr, function ($file_name) use ($forbidden_FILE_extensions, $allowed_FILE_extensions){



        if(!!$allowed_FILE_extensions){ // Если не null, т.е. НУЖНО использовать массив разрешенных расширений файлов
            return in_array(strtolower(pathinfo($file_name, PATHINFO_EXTENSION)), $allowed_FILE_extensions);
        }

        return !in_array(strtolower(pathinfo($file_name, PATHINFO_EXTENSION)), $forbidden_FILE_extensions);
    });
    $rez_Arr = array_values($rez_Arr);

    return array_values($rez_Arr);
}

