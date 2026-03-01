<?php

mb_internal_encoding("utf-8");
$internal_enc = mb_internal_encoding();
mb_regex_encoding($internal_enc);


// 0. Задаем значения опасных параметров по умолчанию (для начала)
$max_keywords_LEN = 0; $reg_keywords = ''; $message_to_user = ''; $ru_dic_FILE_NAME_sorted= ''; $ru_aff_FILE_NAME = '3'; $ru_aff_Arr = ''; $ALL_words = ''; $predlogi_final_PATH = ''; $ru_alfabet = ''; $ru_dic_marks_levels_Arr = ''; $enc_Arr = array();

// 1. Задаваемые параметры/функции
require __DIR__ . '/parametrs.php'; // Здесь параметрам даются целевые значения
require __DIR__ . '/common_functions.php';
require __DIR__ . '/array_logical.php';


$t0 = microtime(true);

header('Content-type: text/html; charset=utf-8');

// Кнопка запроса очередной порции ссылок
$INPUT_next_links = '<input src="/LOCAL_only/REDACTOR/img/arrow_right.png" style="background-image: none; margin: 10px 0 0 25px; display: block; vertical-align: top; width: 30px;" onclick="show_links_in_POPUP(\'links\')" class="buttons_REDACTOR next_links" title="Показать еще '. $links_NUM_max_output .' ссылок" type="image" />';

/* 2. **********************    POST  (Начало)  ***************************/
// 2.1 find_keywords
if(isset($_POST['find_keywords'])){ // Поиск по искомым словам (быть может, с учетом логического выражения)
    // 2.1.1. Проверяем входные данные
    if(mb_strlen($_POST['find_keywords'], $internal_enc) > $max_keywords_LEN){
        die('<p class="error_mes">Слишком длинный список (логическое выражение) искомых слов ('. mb_strlen($_POST['find_keywords'], $internal_enc).' символов). А допускается не более '. $max_keywords_LEN. ' символов.</p>');
    }

    if($_POST['find_keywords'] == ''){
        die('<p class="error_mes">искомые слова не заданы. Для поиска по искомым словам следует задать их. Можно использовать логическое выражение на основе  искомых слов.</p>');
    }

    $keywords = trim($_POST['find_keywords']); // искомые слова (логическое выражение с кл. словами)
    $keywords = mb_strtolower($keywords, $internal_enc);

// 2.1.2. Проверяем, нет ли среди искомых слов предлогов, местоимений и пр. Если есть - удаляем их и сообщаем клиенту
    $keywords_Arr = preg_split('|\s+|', $keywords);
// 2.1.3. Берем практически все известные предлоги, частицы, союзы, междометия, местоимения русского языка из ОНОНЧАТЕЛЬНОГО файла
    $predlogi_Arr = file($predlogi_final_PATH);

    $i_ili_Arr = array('и', 'или');

    $keywords_predlogi_Arr = array_filter($predlogi_Arr, function ($el) use ($keywords_Arr, $i_ili_Arr){ // Эти слова есть в файле предлогов, местоимений и пр.
        $el = trim($el);
        return in_array($el, $keywords_Arr) && !in_array($el, $i_ili_Arr);
    });

    $keywords_predlogi_Arr = array_map('trim', $keywords_predlogi_Arr);

    if(sizeof($keywords_predlogi_Arr) > 0){
        $output = '<p>Многие предлоги, местоимения, союзы, частицы русского языка исключаются из состава искомых слов и не участвуют в поиске. Эти слова НЕ были включены в состав поисковых искомых слов: <span style="font-weight: bold; background-color: #FBE2B4">'. implode(', ', $keywords_predlogi_Arr) .'</span>.</p>';
        echo $output;
    }

    $keywords_Arr = array_diff($keywords_Arr, $keywords_predlogi_Arr); // Массив кл. слов без предлогов, местоимений и т.д.
    $keywords_Arr = array_map('trim', $keywords_Arr);

    $keywords = implode(' ', $keywords_Arr);

// 2.1.4. Запускаем поиск по искомым (ключевым) словам
    find_keywords($keywords, $internal_enc, $reg_keywords, $message_to_user, $min_WORD_len, $metaphone_len, $path_DIR_name, $file_finded_FILES_name, $links_NUM_max_output, $INPUT_next_links, $ru_aff_FILE_NAME, $ru_dic_FILE_NAME, $ru_dic_FILE_NAME_sorted, $ru_aff_Arr, $ALL_words, $t0, $ru_dic_FILE_marks, $ru_alfabet, $ru_dic_marks_levels_Arr, $enc_Arr);

check_ERRORS('Error|Произошла ошибка поиска файлов сайта по искомым выбранным словам.');
die();
}
// 2.2. ask_keyword_links
if(isset($_POST['ask_keyword_links'])){ // Поиск по искомым словам (быть может, с учетом логического выражения)

    // 2.2.1. Проверяем входные данные
    if(!is_numeric($_POST['ask_keyword_links'])){
        die('<p class="error_mes">Запрос на показ дополнительных ссылок на файлы сайта, содержащие заданные искомые слова, содержит недопустимые символы.'. '</p>');
    }

    $link_last_num = $_POST['ask_keyword_links']; // Будем брать из файла ссылки, начиная с этого номера

    $file_names_Arr = ask_keyword_links($file_finded_FILES_name, $link_last_num, $links_NUM_max_output);

    $file_names_Arr = array_map(function ($el){
        $href = str_replace('\\', '/', $el);
        return '<li>'. '<a class="files" href="'. $href. '" title="Открыть в новой вкладке" target="_blank">'. $href. '</a></li>';
    }, $file_names_Arr); // Превращаем массив имен файлов в массив тегов <li>...</li> с соответствующими ссылками внутри них

    $str =  implode('', $file_names_Arr);

    if($str){
        echo $str;
    }else{
        echo '<p class="error_mes">Больше нет файлов, соответствующих указанным искомым словам.</p>';
    }
check_ERRORS('Error|Произошла ошибка поиска файлов сайта по искомым выбранным словам.');
die();
}
// 2.3. link_description
if(isset($_POST['link_description'])){
// Получение описания (части содержимого) файла по искомым словам (быть может, с учетом логического выражения) - для подсказок

    $file_name_REL = preg_replace('|[^'. $ru_alfabet .'a-z0-9!~\?_/\\\.-]|i', '', $_POST['link_description']); // В utf-8

// 2.4. Определяем кодировку файла с перечнем файлов сайта и получаем массив, состоящий из имен этих файлов
    $rez_Arr = get_files_Arr($enc_Arr);

    $ENC_FILE_names_all_files = $rez_Arr[1];

    // Актуально, когда в имени файла содержится, например, кириллица
    $file_name_REL_encoded = ($ENC_FILE_names_all_files === $internal_enc) ? $file_name_REL : mb_convert_encoding($file_name_REL, $ENC_FILE_names_all_files, $internal_enc);


    $file_name_ABS = realpath($_SERVER['DOCUMENT_ROOT']. $file_name_REL_encoded);

// 2.5. Пытаемся читать содержимое тега <description />. Если такого тега нет, то берем начальную часть файла, за исключением тегов
    if($file_name_ABS){ // Если такой файл существует
        $file_str = file_get_contents($file_name_ABS, null, null, 0, 10000);

        $true_encoding = check_enc($file_str, $enc_Arr);
        if(!$true_encoding){
            die('Не удалось определить кодировку этого файла');
        }elseif(strtolower($true_encoding) !== strtolower($internal_enc)){
            $file_str = mb_convert_encoding($file_str, $internal_enc, $true_encoding);
        }

        preg_match('~<meta\s+name\s*=\s*(?:\'|")(?:description|DESCRIPTION)(?:\'|")([^>]*)/?>~', $file_str, $matches); // символы ? > могут восприниматься, как конец тега РНР. Доделать +++
// Доделать, с учетом возможного разного порядка description и content todo  +++
        $description = sizeof($matches) > 1 ? $matches[1] : null;

        if($description){
            $content = preg_match('~content\s*=\s*(?:\'|")([^\'"]*)(?:\'|")~', $description, $matches);
            $description = sizeof($matches) > 1 ? $matches[1] : null;
        }

        if($description){
            echo $description;
        }else{ // Если нет метатега <description ... /> или он - пустой (без контента), тогда
            $file_str = preg_replace('~<\?[\s\S]*?\?>|<\?[\s\S]*~', ' ', $file_str); // Удаляем все теги PHP.
            $file_str = preg_replace('~<head[^>]*>(?:[\s\S]*?)</head>~', '', $file_str); // берем начальную часть файла, за исключением раздела <head>
            $file_str = preg_replace('~<[^>]*?>~', ' ', $file_str); // Вырезаем все теги html, DOCTYPE и комментарии
            $file_str = preg_replace('~\s+~', ' ', $file_str);
            echo substr($file_str, 0, 3000);
        }

    }else{
        echo 'Ошибка! Похоже, файл <span style="font-weight: bold">'. $file_name_REL . '</span> отсутствует. Поэтому невозможно получить его описание.';
    }
check_ERRORS('Error|Произошла ошибка поиска файлов сайта по искомым выбранным словам.');
die();
}


/***************************    /POST  (Конец)  ***************************/


/***************      ФУНКЦИИ      **************************/


function ask_keyword_links($file_finded_FILES_name, $link_last_num, $links_NUM_max_output){

    $file_names_Arr = array();

    $input = fopen($file_finded_FILES_name, 'r');
    if($input === false){
        die('<p class="error_mes">Невозможно открыть файл с перечнем имен файлов сайта и их индексами ('. $file_finded_FILES_name. ')</p>');
    }

$i = 0;
    while (($buffer = fgets($input)) !== false){
        if($i++ < $link_last_num){
            continue;
        }

        if($i <= $link_last_num + $links_NUM_max_output){
            $file_names_Arr[] = trim($buffer);
        }else{
            break;
        }

    }
    fclose($input);
return $file_names_Arr;
}


// Функция делает поиск по искомым словам, в т.ч., с учетом искомого логического выражения (для этого файлы сайта ДОЛЖНЫ БЫТЬ заранее проиндексированы)
function find_keywords($keywords, $internal_enc, $reg_keywords, $message_to_user, $min_WORD_len, $metaphone_len, $path_DIR_name, $file_finded_FILES_name, $links_NUM_max_output, $INPUT_next_links, $ru_aff_FILE_NAME, $ru_dic_FILE_NAME, $ru_dic_FILE_NAME_sorted, $ru_aff_Arr, $ALL_words, $t0, $ru_dic_FILE_marks, $ru_alfabet, $ru_dic_marks_levels_Arr, $enc_Arr){


// 0. Проверяем искомые слова (кл. выражение)
if(preg_match($reg_keywords, $keywords, $matches)){ // Если обнаружится недопустимый символ
    die('<p class="error_mes">В искомых словах присутствует недопустимый символ: '. implode(' ', $matches).'</p>');
}

if(substr_count($keywords, '(', 0) !== substr_count($keywords, ')', 0)){
    die('<p class="error_mes">К искомых словах число открывающих скобок "(" равно '. substr_count($keywords, '(', 0). '; это не совпадает с числом закрывающих скобок ")", равным '.substr_count($keywords, ')', 0).'.</p>');
}


// 1. Нормализуем искомые слова (логическое выражение)
$keywords = preg_replace('/\(\s+/', '(', $keywords); // ( слово  ->  (слово
$keywords = preg_replace('/\s+\)/', ')', $keywords); // слово )  ->   слово)

$keywords = preg_replace('/\s+и\s+/', '&&', $keywords); // и -> &&
$keywords = preg_replace('/\(и\s+/', '(&&', $keywords);
$keywords = preg_replace('/\s+и\)/', '&&)', $keywords);

$keywords = preg_replace('/\s+или\s+/', '||', $keywords); // или -> ||
$keywords = preg_replace('/\(или\s+/', '(||', $keywords);
$keywords = preg_replace('/\s+или\)/', '||)', $keywords);


    $keywords1 = preg_replace('/\s+/', '', $keywords); // Временно удаляем все пробелы
    preg_match_all('/([&\|]{3,})/', $keywords1, $matches); // Нельзя &||  ||&&  и т.д.
    if(sizeof($matches[0]) > 0){ // Нельзя &&&, &&&&, ||| и т.д.
        die('<p class="error_mes">искомые слова содержат недопустимые последовательности символов: <b>'. implode(', ', $matches[0]). '</b>. Вместо "&&" в искомых словах может фигурировать "и", а вместо "||" - "или".</p>');
    }
    preg_match_all('/([^&]&[^&])|([^\|]\|[^\|])/', $keywords1, $matches);
    if(sizeof($matches[0]) > 0){ // Нельзя & или | одиночно. Допускаются только двойные: && или ||
        die('<p class="error_mes">искомые слова содержат одиночные символы: <b>&</b> или <b>|</b>. Это недопустимо.</p>');
    }

$keywords = preg_replace('/\s*&+\s*&+\s*/', '&&', $keywords);
$keywords = preg_replace('/\s*\|+\s*\|+\s*/', '||', $keywords);
$keywords = preg_replace('/\s+/', '&&', $keywords); // Все пробелы заменяем на &&

preg_match_all('/(\([&\|]+)|([&\|]+\))|(&\|)|(\|&)|(&{3,})|(\|{3,})/', $keywords, $matches, PREG_PATTERN_ORDER);
if(sizeof($matches[0]) > 0){ // Нельзя (&&  ||)  &&)  ||)

    die('<p class="error_mes">искомые слова содержат недопустимые последовательности символов: <b>'. implode(', ', $matches[0]). '</b>. Вместо "&&" в искомых словах может фигурировать "и", а вместо "||" - "или".</p>');
}
// Итак, получено логическое выражение вида слово1&&(слово2||слово3)

$special_symb_Arr = array('&&', '||', '(', ')', 1);

// Добавляем пробелы перед и после &&  ||  (  )
for($i=0; $i < sizeof($special_symb_Arr); $i++){
    $keywords = str_replace($special_symb_Arr[$i], ' '. $special_symb_Arr[$i]. ' ', $keywords);
}
$keywords = trim(preg_replace('/\s+/', ' ', $keywords)); // Оставляем только по одному пробелу

$keywords_Arr = explode(' ', $keywords);

if(!is_dir($path_DIR_name)){
    die('<p>Похоже, индексация СОДЕРЖИМОГО файлов сайта еще не была проведена. Т.к. отсутствует каталог '. str_replace('\\', '/', $path_DIR_name). '</p><p>Для реализации поиска следует сделать индексирование СОДЕРЖИМОГО файлов сайта.</p>');
}

echo '1: '. (microtime(true) - $t0). '<br>';

// 2. *************   НЕЧЕТКИЙ ПОИСК   *****************
$fuzzy = $_POST['fuzzy'];


// 2.1. Массив всевозможных окончаний русск. яз.
    $ru_aff_Arr = array_map(function ($elem){
        return preg_split('|\s+|', $elem, -1, PREG_SPLIT_NO_EMPTY);
    }, file($ru_aff_FILE_NAME));

// 2.2. Берем все слова русск. яз. из словаря (браузера Firefox 36)
//    $ALL_words = file_get_contents($ru_dic_FILE_NAME); // Все слова русск. яз. в начальной форме (в именит. падеже, неопред. форма глагола и т.п.)



$indexes_Arr = array();

if($fuzzy === '1'){ // Если включен НЕчеткий поиск

    define('flag_perfom_working', 1);
// К каждому искомому слову добавляем слова с другими окончаниями (при наличии), согласно словаря русск. языка. потом будет искаться КАЖДОЕ из этих слов
    require __DIR__ . '/FUZZY/keywords_FINDER_fuzzy.php';

}

// 2.3. Для каждого искомого слова из массива искомых (кл.) слов запускаем процедуру поиска близких слов (в том числе, с разными окончаниями, если поиск НЕчеткий)
    $words_to_find_Arr = array();

    for($j=0; $j < sizeof($keywords_Arr); $j++){ // По массиву, содерж. элементы логического выражения (например: слово1&&(слово2||слово3) )

        if(in_array($keywords_Arr[$j], $special_symb_Arr)){ // Пропускаем символы &&  ||  и т.д.
            $words_to_find_Arr[] = $keywords_Arr[$j];
            $indexes_Arr[] = $keywords_Arr[$j];
            continue;
        }
        $word = $keywords_Arr[$j];


        if($fuzzy === '1'){ // Для НЕчеткого поиска
            // Вызываем для каждого искомого слова, полученного от клиента
            $keyword_forms_Arr = check_WORD_in_DIC($ru_aff_Arr, $ru_alfabet, $ru_dic_marks_levels_Arr, $internal_enc, $word, $metaphone_len, $path_DIR_name, $ru_dic_FILE_marks);

        }else{ // Доля обычного поиска
            $keyword_forms_Arr =  array($word); // Будет массив лишь из одного слова (т.к. его формы НЕ ищем)
        }

// Получаем множества индексов для каждого ключевого слова
        $keyword_forms_Arr = array_map('translit1', $keyword_forms_Arr); // В транслит, чтобы можно было применить функцию metaphone()
        $keyword_forms_Arr = array_values(array_unique($keyword_forms_Arr));

// 2.4. Превращаем искомые слова в метафоны
// Иначе - заменяем слова на 1 (чтобы они не приводили логическое выражение в false). Символы && || (  )  1  оставляем, как есть
        $keyword_forms_Arr = array_map(function ($el) use ($min_WORD_len, $special_symb_Arr, $internal_enc, $metaphone_len) {
            if(in_array($el, $special_symb_Arr, true)){ // Для && || (  )  1
                return $el;
            }

            return (mb_strlen($el) >= $min_WORD_len ? do_metaphone1($el, $metaphone_len) : 1); // Заменяем короткие слова в логич. выражении на 1 (true)
        }, $keyword_forms_Arr);


// 2.5. Создаем массив, соответствующий искомому логическому выражению, но где ВСЕ оставш. кл. слова (временно) заменены на 0 (false)
        $keywords_FALSE_Arr = array_map(function ($el) use ($min_WORD_len, $special_symb_Arr, $internal_enc, $metaphone_len) {
            if(in_array($el, $special_symb_Arr, true)){ // Для && || (  )  1
                return $el;
            }
            return ($el === 1) ? 1 : 0; // Заменяем все оставшиеся слова на 0 (false). Кроме тех, к-рые установлены равными 1
        }, $keyword_forms_Arr);


        $rez_Arr = get_indexes_Arr($keyword_forms_Arr, $special_symb_Arr, $path_DIR_name, $keywords_FALSE_Arr, $message_to_user);
        $indexes_Arr_tmp = $rez_Arr[0]; // Массив индексов для разных форм искомых (ключевых) слов $keyword_forms_Arr


        $a = array_values($indexes_Arr_tmp);

        $bool_string = ''; // Будет что-то типа $a[0] || $a[1] || $a[2] || $a[3] || $a[4] || ...
        for($i=0; $i < sizeof($a); $i++){
            if($i > 0){
                $bool_string .= ' || ';
            }
            $bool_string .= '$a['. $i. ']';
        }
// 2.6. Объединяем множества индексов для каждого из искомых слов (сходных с изначальным словом) из $keyword_forms_Arr
        $rez_Arr = array_logical_converter($a, $bool_string);

        if($rez_Arr[0] === -1){ // Если ошибка в лог. выражении
            if($bool_string == ''){
                $rez_Arr1 = explode('|', $rez_Arr[1]);
                if($rez_Arr1[0] === 'Error:empty'){ // Если логич. выражение - пустое (значит, искомые ключевые слова НЕ соответствуют лог. выраж.)
                    die('<p class="error_mes">Данные искомые слова не обнаружены ни в одном из индексированных файлов.</p>');
                }

                die($rez_Arr[1]); // Если дошли досюда, значит, была ошибка
            }
        }

        $indexes_Arr_tmp = array_filter($rez_Arr[1], function ($el){
            return is_numeric($el);
        });

        $indexes_Arr_tmp = array_values(array_unique($indexes_Arr_tmp));
        sort($indexes_Arr_tmp); // Получен массив уникальных индексов для ВСЕвозможных форм искомого (кл.) слова

        $indexes_Arr[$j] = $indexes_Arr_tmp;
    }

    echo '2: '.  (microtime(true) - $t0). '<br>';

    $a = array_values($indexes_Arr);

    $bool_string = ''; // Будет что-то типа  $indexes_Arr[0] || $indexes_Arr[1] && $indexes_Arr[2] ...
    for($i=0; $i < sizeof($indexes_Arr); $i++){

        if(is_array($indexes_Arr[$i])){
            $bool_string .= '$a['. $i. ']';
        }else{
            $bool_string .= ' '. $indexes_Arr[$i]. ' ';
        }
    }
// 3. Оцениваем получившееся логическое выражение из индексов. Находим индексы, соответствующие логич. выражению
    $rez_Arr = array_logical_converter($a, $bool_string);

    if($rez_Arr[0] === -1){ // Если ошибка в лог. выражении
        die($rez_Arr[1]);
    }

    $indexes_Arr = $rez_Arr[1]; // Массив индексов файлов сайта из файла files.txt, которые удовлетворяют искомому логическому выражению


echo '3: '.  (microtime(true) - $t0). '<br>';


    $TRUE_indexes_Arr = array_values($indexes_Arr);

// 4. Теперь на основе полученных индексов нужно получить имена файлов (из files.txt)
$TRUE_indexes_Arr_SIZE = sizeof($TRUE_indexes_Arr);

    if(!$TRUE_indexes_Arr_SIZE){ // Если ни один индекс не соответствует (логическому) искомому выражению
        die('<p class="error_mes">Данные искомые слова не обнаружены ни в одном из индексированных файлов.</p>');
    }


$file_names_Arr = array(); // Массив имен целевых (искомых) файлов, соответствующие найденным индексам
// Читаем файл с перечнем  имен|индексов  всех файлов сайта
$ALL_files = file_get_contents(PATH_FILE_NAMES_ALL_FILES); // Для очень больших файлов - доделать обработку по частям todo +++
$ALL_files_txt_Arr = explode(PHP_EOL, $ALL_files); // Массив всех файлов сайта с индексами (строчки вида filename|index )
unset($ALL_files);

$ALL_files_Arr = array(); // Массив всех файлов сайта из files.txt. Вида:   Индекс => Имя
    for($i=0; $i < sizeof($ALL_files_txt_Arr); $i++){
        $x_Arr = explode('|', $ALL_files_txt_Arr[$i]);

        if($x_Arr[0]){
            $ALL_files_Arr[trim($x_Arr[1])] = $x_Arr[0];
        }else{
            continue;
        }
    }

    for($i=0; $i < sizeof($TRUE_indexes_Arr); $i++){
        $file_names_Arr[] = $ALL_files_Arr[$TRUE_indexes_Arr[$i]];
    }

if($TRUE_indexes_Arr_SIZE !== sizeof($file_names_Arr)){ // Значит, либо не для всех индексов были найдены файлы из списка в файле files.txt, либо какая-то иная ошибка
    die('<p class="error_mes">Почему-то размерности массива индексов ('. $TRUE_indexes_Arr_SIZE. ') и массива найденных файлов, соответствующих логическому выражению для искомых слов ('.  sizeof($file_names_Arr) .') НЕ совпадают. Произошла какая-то ошибка. См. Файл '. $_SERVER['PHP_SELF'] . ', стр.'. __LINE__ . '</p>');
}

echo '4: '. (microtime(true) - $t0). '<br>';

// 5. Сохраняем найденный перечень файлов в файл
file_put_contents($file_finded_FILES_name, implode(PHP_EOL, $file_names_Arr));

// 6. Оформляем полученные имена файлов для вывода на экран

$output = '<p>Затрачено времени: '. round(microtime(true) - $t0, 3). ' сек.</p>';
$output .= '<p class="">Всего найдено '. sizeof($file_names_Arr). ' файлов, соответствующим этим искомым словам. Из них показано <span id="num_showed_links">'. '</span>:';

if(sizeof($file_names_Arr) > $links_NUM_max_output){
    $output .= $INPUT_next_links;
}

echo '5-6: '. (microtime(true) - $t0). '<br>';

$output .= '</p>';
$output .= '<ol id="links">';

// Определяем кодировку файла с перечнем файлов сайта и получаем массив, состоящий из имен этих файлов
$rez_Arr = get_files_Arr($enc_Arr);

$ENC_FILE_names_all_files = $rez_Arr[1];

$max_links_output_len = min($links_NUM_max_output, sizeof($file_names_Arr));
    for($i=0; $i < $max_links_output_len; $i++){

        // Имя файла перекодированное (только для вывода в виде информации на экран). Актуально, когда в имени файла содержится, например, кириллица
        $file_names_Arr[$i] = ($ENC_FILE_names_all_files === $internal_enc) ? $file_names_Arr[$i] : mb_convert_encoding($file_names_Arr[$i], $internal_enc, $ENC_FILE_names_all_files);

        $href = str_replace('\\', '/', $file_names_Arr[$i]);
        $output .= '<li>'. '<a class="files" href="'. $href. '" title="Открыть в новой вкладке" target="_blank">'. $href. '</a></li>';
    }
$output .= '</ol>';
echo $output;

}



