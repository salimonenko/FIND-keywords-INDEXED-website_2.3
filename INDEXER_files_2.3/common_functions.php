<?php
/* 1. Получаем метафоны */
function do_metaphone1($word, $metaphone_len){

    if(strlen($word) < $metaphone_len || preg_match('/[\d\+]/', $word)){ // Если слово короткое или содержит цифру или +, то будем искать точное соответствие (в транслите)
        return $word;
    }

    $word_metaphone = metaphone($word, $metaphone_len);

    if(strlen($word_metaphone) < ($metaphone_len-1)){ // Если функция metaphone дала слишком короткую строку-код
        return strtolower($word);
    }else{
        return strtolower($word_metaphone);
    }

}


/* 2. Транслит (обычный, НЕ псевдо) */
function translit1($value)
{
    $converter = array(
        'а' => 'a',    'б' => 'b',    'в' => 'v',    'г' => 'g',    'д' => 'd',
        'е' => 'e',    'ё' => 'e',    'ж' => 'zh',   'з' => 'z',    'и' => 'i',
        'й' => 'y',    'к' => 'k',    'л' => 'l',    'м' => 'm',    'н' => 'n',
        'о' => 'o',    'п' => 'p',    'р' => 'r',    'с' => 's',    'т' => 't',
        'у' => 'u',    'ф' => 'f',    'х' => 'h',    'ц' => 'c',    'ч' => 'ch',
        'ш' => 'sh',   'щ' => 'sch',  'ь' => '',     'ы' => 'y',    'ъ' => '',
        'э' => 'e',    'ю' => 'yu',   'я' => 'ya',

        'А' => 'A',    'Б' => 'B',    'В' => 'V',    'Г' => 'G',    'Д' => 'D',
        'Е' => 'E',    'Ё' => 'E',    'Ж' => 'Zh',   'З' => 'Z',    'И' => 'I',
        'Й' => 'Y',    'К' => 'K',    'Л' => 'L',    'М' => 'M',    'Н' => 'N',
        'О' => 'O',    'П' => 'P',    'Р' => 'R',    'С' => 'S',    'Т' => 'T',
        'У' => 'U',    'Ф' => 'F',    'Х' => 'H',    'Ц' => 'C',    'Ч' => 'Ch',
        'Ш' => 'Sh',   'Щ' => 'Sch',  'Ь' => '',     'Ы' => 'Y',    'Ъ' => '',
        'Э' => 'E',    'Ю' => 'Yu',   'Я' => 'Ya',
    );

    $value = strtr($value, $converter);
    return $value;
}

/* 3. Определение кодировки */
function check_enc($text_html, $enc_Arr){ // В РНР 5.3 работает отлично. А в РНР 8.0 могут быть сбои, если входная строка будет в иной кодировке
// В массиве кодировок $enc_Arr ПЕРВОЙ должна идти utf-8
    $true_encoding = '';

    foreach ($enc_Arr as $encoding){
        if(mb_check_encoding($text_html, $encoding)){
            $true_encoding =  $encoding;
            break;
        }
    }

    return $true_encoding;
}

/* 4. Итоговое сообщение об ошибках */
// Функция окончательно проверяет ошибки
function check_ERRORS($mess){
// На всякий случай, окончательно делаем контроль ошибок
// *************    КОНТРОЛЬ ОШИБОК    (Начало)*****************************************
         if((error_get_last() != '') || (is_array(error_get_last()) && (error_get_last() != array()) )){
             print_r(error_get_last());
             file_put_contents(PATH_FILE_NAMES_ERROR, $mess. PHP_EOL. implode(', ', error_get_last()) .' '. date("d.m.Y"). PHP_EOL , FILE_APPEND);
             die('<p class="error_mes">'. $mess. '</p>');
         }
// *************    /КОНТРОЛЬ ОШИБОК    (Конец)*****************************************
}


/* 5. Удаление НЕПУСТОГО каталога. РАБОТАЕТ ПЛОХО. Переделать. todo +++  */
function rrmdir($src) {

    $dir = opendir($src);

    while(false !== ($file = readdir($dir))) {
        if(($file != '.' ) && ( $file != '..' )) {

            $full = $src . '/' . $file;
            if(is_dir($full)) {
                $rez = @rrmdir($full);
                $mess = 'каталога ';
            }else{
                $rez = @unlink($full);
                $mess = 'файла ';
            }
            if(!$rez){
                echo 'Ошибка при удалении '. $mess. $full;
                print_r(error_get_last());
                die();
            }
        }
    }
    closedir($dir);
    rmdir($src);
return true;
}


/* 6. Функция определяет кодировку файла с перечнем файлов сайта и получает массив, состоящий из имен этих файлов */
function get_files_Arr($enc_Arr){
    $str = file_get_contents(PATH_FILE_NAMES_ALL_FILES);

$ENC_FILE_names_all_files = strtolower(check_enc($str, $enc_Arr));

if(!$ENC_FILE_names_all_files){
    $mess = ' Не удалось определить кодировку файла '. PATH_FILE_NAMES_ALL_FILES;
    echo '<p class="error_mes">'. $mess. '</p>';
    file_put_contents(PATH_FILE_NAMES_ERROR, $mess. ' '. date("d.m.Y"). PHP_EOL , FILE_APPEND);
die();
}

$ALL_files_Arr_tmp = explode(PHP_EOL, $str); // Вместо функции file(), т.к. она, вроде бы, работает медленнее
unset($str);

$ALL_files_Arr = array(); // Массив относительных имен файлов
for($i=0; $i < sizeof($ALL_files_Arr_tmp); $i++){
    $elem = trim($ALL_files_Arr_tmp[$i]);

    if($elem){
        $pos = strpos($elem, '|') + 1;
        $key = 1*substr($elem, $pos);
        $ALL_files_Arr[$key] = substr($elem, 0, $pos - 1); // Элемент массива вида: 3 => filename
    }
}
unset($ALL_files_Arr_tmp); // Для экономии памяти

return array($ALL_files_Arr, $ENC_FILE_names_all_files);
}



/* 7. Функция индексирует файл сайта ИЛИ добавляет признак присутствия слова в словаре в индексный файл */
function LAST_met_2_index($keyword_metaph, $dic_word_SUFF, $file_name, $path_DIR_name_TMP, $number, $ALL_files_Arr, $JS_manage_mes){

    $LAST_met_path_Arr = create_path_index_file($keyword_metaph, $path_DIR_name_TMP);
    $index_FILE = $LAST_met_path_Arr[0];
    $LAST_met_2 = $LAST_met_path_Arr[1];

            if($file_name === true && $number === ''){ // Если вставляется признак присутствия этого слова в файле-словаре ru.dic ("1" или суффикс, при его наличии)

                $delim = ':'. $dic_word_SUFF. '|';
            }else{ // Если индексируется содержимое файлов сайта
                $delim = '|;';
            }

            $to_SAVE = $LAST_met_2. $delim. $number.';'; // Строка вида ag|;560; или вида ag:1|; (если проводилось индексирование файла-СЛОВАРЯ)

        if(file_exists($index_FILE)){ // Если файл существует,
            if(is_writable($index_FILE)){ // Если файл доступен для записи, тогда проверяем, есть ли там такое индекс-число

                $index_FILE_Arr = explode("\n", file_get_contents($index_FILE));
                $flag_SAVE = false; // Флаг, нужно ли сохранять этот массив

                for($z=0; $z < sizeof($index_FILE_Arr); $z++){ // По каждой строчке индексного файла
                    $elem = trim($index_FILE_Arr[$z]);

                    $index_FILE_Arr = array_filter($index_FILE_Arr, function ($el){
                        return $el != '';
                    });

                    if(substr($elem, 0, 2) === $LAST_met_2){ // Если в начале элемента массива есть 2 символа типа ag

                        if($file_name === true && $number === ''){ // Если вставляется признак "1" - присутствия этого слова в файле-словаре ru.dic
                            if(substr($elem, 0, 5) !== $LAST_met_2. $delim){ // Если конец метафона есть, но признака присутствия еще нет
                                $index_FILE_Arr[$z] = trim(preg_replace('/^'. $LAST_met_2. '[^\\\n;]*/', $LAST_met_2. $delim, $index_FILE_Arr[$z]));

                                $to_SAVE = implode("\n", $index_FILE_Arr);
                                file_put_contents($index_FILE, $to_SAVE . "\n");
                            }
                            break; // Только в случае добавления признака "1" присутствия слова в файле-словаре (с учетом метафонизации)

                        }else{
                            if(strstr($elem, ';'. $number. ';') !== false){ // Если в том же элементе есть подстрока вида ;560;
                                break; //Если такая подстрока уже есть, значит, ее уже не нужно вставлять (если делается ИНДЕКСИРОВАНИЕ, а не вставка признака "1")
                            }else{ // Если еще нет, то добавляем. Будет что-то типа ag|;560;586;779; ... (или ag:1|;560;586;779; )
                                $index_FILE_Arr[$z] = $elem. $number. ';';
                                $flag_SAVE = true;
                                    break;
                            }
                        }
                    }
                }

                if($z === sizeof($index_FILE_Arr)){
                // Если дошли досюда, т.е. НЕТ искомой подстроки типа ag
                    $index_FILE_Arr[] = $to_SAVE; // то добавляем в массив новый элемент
                        $flag_SAVE = true;
                }

                if($flag_SAVE){
/*                    $index_FILE_Arr = array_filter($index_FILE_Arr, function ($el){
                        return $el != '';
                    });*/
                    $to_SAVE = implode("\n", $index_FILE_Arr);
                }
            }else{ // Если файл существует, но НЕ доступен для записи (значит, что-то пошло не так)

                $mess = 'Ошибка: не получилось записать число-индекс в файл '.$index_FILE. '. Т.к. этот файл недоступен для записи.';
                echo '<p class="error_mes">'. $mess. '</p>'. $JS_manage_mes;

                file_put_contents(PATH_FILE_NAMES_ERROR, $file_name. '|'. array_search($file_name, $ALL_files_Arr). '|'. $mess. ' '. date("d.m.Y"). PHP_EOL , FILE_APPEND);

return 'continue';
            }
        }else{ // Если файл не существует, то создаем его
            $flag_SAVE = true;
        }

        if($flag_SAVE){
            file_put_contents($index_FILE, $to_SAVE . "\n");
        }
// В итоге относительный путь к этому файлу будет примерно таким:  /metaphones/s/h/1.txt (для метафона shag). Буквы ag будут содержатьсяв одной из строк файла
// В этом файле будут содержаться индексные номера тех файлов (из files.txt), в которых метафон данного слова содержится хотя бы 1 раз

return true;
}

/* 8. Функция создает (или получает, если есть) путь к индексному файлу с именем вида $path_DIR_name_TMP/1.txt  */
function create_path_index_file($keyword_metaph, $path_DIR_name_TMP){
    for($j=0; $j < strlen($keyword_metaph)-2; $j++){ // По каждому отдельному символу данного слова, кроме предпоследнего и последнего символов
        $DIR_name_1 = substr($keyword_metaph, $j, 1); // Имена создаваемых каталогов будут состоять из 1 символа (a, b, c, d или т.п.)

        $path_DIR_name_TMP = $path_DIR_name_TMP. '/'. $DIR_name_1;
        if(!is_dir($path_DIR_name_TMP)){
            mkdir($path_DIR_name_TMP);
        }
    }
    $index_FILE = $path_DIR_name_TMP.'/1.txt';
    $LAST_met_2 = substr($keyword_metaph, $j, 2); // Последние 2 символа метафона

return array($index_FILE, $LAST_met_2);
}

/* 9. Функция проверяет корректность полученного логического выражения (перед последующей оценкой при помощи eval)  */
function check_keywords($keywords_Arr, $special_symb_Arr, $message_to_user, $bool_val){
    $bool_expression = implode('', array_map(function ($el) use ($special_symb_Arr, $bool_val){
    if(!in_array($el, $special_symb_Arr, true)){
        return $bool_val;
    }else{
        return $el;
    }
}, $keywords_Arr));

$rez_Arr = eval_keywords($bool_expression, $message_to_user);
return $rez_Arr;
}

/* 10. Функция оценивает логическое выражение и выдает результат: true или false  */
function eval_keywords($bool_expression, $message_to_user){
    $bool_expression_REZ = 0; // true, если есть совпадение с выражением для искомых искомых слов; false - если нет.

    $str_code = "\$bool_expression_REZ = ". $bool_expression;
    @eval($str_code. "|| 1". ";"); // Для проверки корректности выражения $str_code. Если оно верно, результат eval() даст заведомо 1 (true)

    if(!!$bool_expression_REZ){
        eval($str_code. ";"); // Если ошибки не было, получаем фактическое значение

        return array(null, !!$bool_expression_REZ);

    }else{ // Значит, возникла ошибка в выражении для eval()
        return array(-1, $message_to_user);
    }
}

/* 11. Функция получает массив индексов (из соответствующих индексных файлов), для каждого искомого (ключевого) слова из массива $keywords_Arr  */
function get_indexes_Arr($keywords_Arr, $special_symb_Arr, $path_DIR_name, $keywords_FALSE_Arr, $message_to_user){

    $not_unique = array(); // Массив (метафонов) слов, к-рые уже искались
    $indexes_Arr = array();

    for($i=0; $i < sizeof($keywords_Arr); $i++){
        if(in_array($keywords_Arr[$i], $special_symb_Arr, true)){
            continue; // символы типа && || (  ), а также 1 - не ищем
        }

        if(in_array($keywords_Arr[$i], $not_unique, true)){
            continue; // Пропускаем слова, к-рые уже искались
        }else{ // Если еще не добавлено такое слово, то добавляем (чтобы на следующей итерации его не искать)
            $not_unique[] = $keywords_Arr[$i];
        }

    // 6.1. Создаем путь к файлу 1.txt из каталога metaphones
        $str_to_DIRS = substr($keywords_Arr[$i], 0, -2);
        $str_to_FILE = substr($keywords_Arr[$i], -2);

        $path = realpath($path_DIR_name. '/' .implode('/', str_split($str_to_DIRS)). '/1.txt');

        if(!file_exists($path)){ // Если такого файла нет, значит, такого метафона нет в индексных файлах
            $keywords_FALSE_Arr[$i] = 0;
            continue;
        }else{ // Если такой файл есть
            $file_Arr = explode("\n", file_get_contents($path));

    // 6.2. Берем только тот элемент массива, который совпадает с 2-мя последними символами метафона
            $elem_Arr = array_filter($file_Arr, function ($el) use ($str_to_FILE){
                return substr($el, 0, 2) === $str_to_FILE;
            });

            if(sizeof($elem_Arr) > 1){
                die('<p class="error_mes">Похоже, ранее произошла ошибка индексирования: в файле '. $path. ' присутствуе БОЛЕЕ ОДНОЙ строки, начинающейся на "'. $str_to_FILE.'. А должно быть НЕ БОЛЕЕ одной строки. Следует исправить программу, при помощи которой ранее производилось индексирование файлов сайта.</p>');
            }elseif(sizeof($elem_Arr) === 0){ // Значит, нет индексов, соответствующих данному метафону
                $keywords_FALSE_Arr[$i] = 0;
            }else{ // Если в индексном файле ровно 1 такая строчка
                $elem_Arr = array_values($elem_Arr); // Чтобы начальный индекс массива стал равным 0
                $indexes_Arr[$keywords_Arr[$i]] = explode(';', $elem_Arr[0]);
            }

    // 6.3. И сразу проверяем, а вдруг при других метафонах, (пока) равных 0, логическое выражение уже будет равно true (это значит, что оно удовлетворяется и дальше можно не искать). Актуально для сложных логических выражений
            $rez_Arr = check_keywords($keywords_FALSE_Arr, $special_symb_Arr, $message_to_user, 0);

            if($rez_Arr[0] === -1){
                die('<p class="error_mes">Ошибка: выражение с искомыми словами составлено некорректно, функция eval() не может его оценить. Проблема возникла на слове '. $keywords_Arr[$i]. '</p>');
            }
            if($rez_Arr[1]){ // Если логическое выраж. уже равно true, значит, оно удовлетворяется и дальнейший поиск можно не делать (чтобы снизить время поиска)
                // Доделать todo +++
            }
        }

     }
return array($indexes_Arr, $keywords_FALSE_Arr);
}