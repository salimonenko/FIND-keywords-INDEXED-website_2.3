<?php
// Программа сортирует файл-словарь ru.dic. Также создает файл с маркерами, ускоряющими поиск в файле-словаре

mb_internal_encoding("utf-8");
$internal_enc = mb_internal_encoding();
mb_regex_encoding($internal_enc);


if(!defined('flag_perfom_working') || (flag_perfom_working != '1')){
    die('Эту программу нельзя запускать непосредственно. Access forbidden.');
}

// Функция сортирует словарь и создает маркеры для ускорения доступа
function DIC_sort($ru_dic_FILE_NAME_saved, $ru_dic_FILE_NAME, $path_DIR_name, $min_WORD_len, $ru_dic_FILE_marks, $ru_dic_marks_levels_Arr, $ru_alfabet, $metaphone_len, $DO_working_flag_FILE, $ru_dic_indexing_info_file, $internal_enc, $JS_manage_mes){

// 1. Сортировка словаря
    $dic_Arr = file($ru_dic_FILE_NAME_saved); // Массив всех слов из русского словаря


$dic_Arr = array_map(function ($el){
    $el_Arr = explode('/', $el);
    if(sizeof($el_Arr) > 1){
        return mb_strtolower($el_Arr[0]) . '/'. $el_Arr[1];
    }else{
        return mb_strtolower($el);
    }
}, $dic_Arr);


sort($dic_Arr, SORT_REGULAR);


file_put_contents($ru_dic_FILE_NAME, implode("", $dic_Arr));

check_ERRORS('Error|Произошла ошибка при сортировке файла '. $ru_dic_FILE_NAME_saved. '. ');

// 2. Если ошибок при сортировке не было
echo '<p>1. Файл-словарь '. $ru_dic_FILE_NAME . ' успешно отсортирован...</p>';

// 3. Добавляем в файлы 1.txt (из каталога metaphones) признаки присутствия в файле-словаре (ru.dic)

// 3.1. Создаем каталог для подкаталогов-символов - частей метафонов
    if(!is_dir($path_DIR_name)){
        mkdir($path_DIR_name);
    }

 file_put_contents($DO_working_flag_FILE, ''); // Создаем файл-флаг. Если он присутствует, то итерации цикла перебора файлов (содержащихся в файле files.txt) будут продолжаться. Если нет - то цикл будет остановлен. Сессии пока не используем, т.к. они не наглядны.

$t = microtime(true);


// 3.2. Слова, присутствующие в файле-словаре, метафонизируем и к последним двум символам их метафонов добавляем:
// 1. Признак присутствия "1", если у конкретного слова суффикса (вида /AS) НЕТ. Получится что-то типа: ag:1|;
// 2. Сам суффикс, если он ЕСТЬ.                                                 Получится что-то типа: ag:/AS|;
//
for($i=1; $i < sizeof($dic_Arr); $i++){ // Первый элемент этого массива - цифра - общее число слов словаря. Ее не индексируем

        /* Проверяем, присутствует ли флаговый файл. Если да, то делаем следующую итерацию. Если нет - прекращаем цикл */
        if(!file_exists($DO_working_flag_FILE)){
            echo '<b>Индексирование файла-словаря остановлено.</b>';
            die();
        }

    $dic_word = trim(translit1($dic_Arr[$i])); // Для метафонизации

    $dic_word_Arr = explode('/', $dic_word);
    $dic_word_WORD = $dic_word_Arr[0];
    $dic_word_SUFF = isset($dic_word_Arr[1]) ? '/'. $dic_word_Arr[1] : '1';

    $keyword_metaph = do_metaphone1($dic_word_WORD, $metaphone_len); // Превращаем в metaphone


set_time_limit(40); // С этого момента скрипт будет выполняться не более указанного количества секунд (каждая итерация цикла). Точнее, будет выбрано минимальное время из указанного количества секунд и установленного в настройках (файл php.ini)

    if(strlen($keyword_metaph) < $min_WORD_len){ // Слишком короткие слова из словаря не индексируем, не берем
        continue;
    }

$rez = LAST_met_2_index($keyword_metaph, $dic_word_SUFF, true, $path_DIR_name, '', array(), $JS_manage_mes); // Та же самая функция, к-рая используется для индексации (содержимого, т.е. слов) файлов сайта

    if(microtime(true) - $t > 1){ // Запасаем номер текущей проиндексированной строки из файла-словаря для последующего извлечения и отправки его клиенту (через событие сервера)
        $t = microtime(true);
        file_put_contents($ru_dic_indexing_info_file, 'Индексируется (из словаря '. basename(realpath($ru_dic_FILE_NAME)) .') слово номер: <span class="waiting">'.$i.'</span> из '.sizeof($dic_Arr));
        flush();
    }

}


check_ERRORS('Error|Произошла ошибка при индексации слов из файла-словаря.');

echo '<p>2. Файл-словарь '. $ru_dic_FILE_NAME . ' успешно проиндексирован.</p>';



die();


// 4. Создаем маркеры, ускоряющие поиск в файле-словаре ru.dic
$markers_Arr = array();
$markers_Arr[0] = 'ДваПервыеСимволаСловаИзФайла_ru.dic : НомерНачальнойСтроки - НомерКонечнойстроки : ЧислоСтрокВ_Диапазоне : НачальнаяПозиция - КонечнаяПозиция';
$len_end = 0; $len_begin = 0; $marker = ''; $i0 = 0;

// Букв-разделители русского алфавита на 3 части. Они - для того, что бы снизить число слов из словаря, среди которых будет производиться поиск искомых слов
$mark_letter1 = $ru_dic_marks_levels_Arr[0]; // Например, "к"
$mark_letter2 = mb_substr($ru_alfabet, mb_strpos($ru_alfabet, $mark_letter1)+1, 1, $internal_enc); // Например, "л" (СЛЕДУЮЩАЯ буква)
$mark_letter3 = $ru_dic_marks_levels_Arr[1]; // Например, "т"

for($i=0; $i < sizeof($dic_Arr); $i++){
    $dic_str = mb_strtolower($dic_Arr[$i], $internal_enc);

    $letter_1 = mb_substr($dic_str, 0, 1, $internal_enc); // Первая буква
    $letter_2 = mb_substr($dic_str, 1, 1, $internal_enc);

    if($letter_2 <= $mark_letter1){
        if(!key_exists($letter_1. $mark_letter1, $markers_Arr)  ){
            $i0 = $i;
            $len_begin = $len_end;
            $marker = $letter_1. $mark_letter1;
        }
    }elseif ($letter_2 > $mark_letter1 && $letter_2 <= $mark_letter3){
        if(!key_exists($letter_1. $mark_letter2, $markers_Arr)  ){
            $i0 = $i;
            $len_begin = $len_end;
            $marker = $letter_1. $mark_letter2;
        }
    }else{
        if(!key_exists($letter_1. $mark_letter3, $markers_Arr)  ){
            $i0 = $i;
            $len_begin = $len_end;
            $marker = $letter_1. $mark_letter3;
        }
    }

    $len_end += strlen($dic_str);

    $markers_Arr[$marker] = $marker. ':'. $i0. '-'. $i. ':'. ($i-$i0+1). ':'. $len_begin.'-'. $len_end;

}
file_put_contents($ru_dic_FILE_marks, implode("\n", $markers_Arr));

// *************    КОНТРОЛЬ ОШИБОК    (Начало)*****************************************
         if((error_get_last() != '') || (is_array(error_get_last()) && (error_get_last() != array()) )){
             print_r(error_get_last());
             $mess = 'Error|Произошла ошибка при сортировке файла '. $ru_dic_FILE_NAME_saved. '. ';
             file_put_contents(PATH_FILE_NAMES_ERROR, $mess. PHP_EOL. implode(', ', error_get_last()) .' '. date("d.m.Y"). PHP_EOL , FILE_APPEND);
             die('<p class="error_mes">'. $mess .'</p>'. $JS_manage_mes);
         }
// *************    /КОНТРОЛЬ ОШИБОК    (Конец)*****************************************

// Если ошибок при создании маркеров не было
echo '<p>2. Файл с маркерами, ускоряющими поиск в файле-словаре, успешно создан.</p>';

}









