<?php
// Программа (функция) для искомого (ключевого) слова дает всевозможные его формы (путем вариации окончаний), исходя из правил русского языка. На основе файла ru.aff
/* 1. Вначале пытаемся определить суффикс (типа /BKLM) слова, если оно содержится в словаре. Это возможно, если слово задано в начальной форме.
   2. Анализируем каждое окончание из файла суффиксов ru.aff: содержится ли оно в конце слова?
   3. Если содержится, то
 *
 * */

error_reporting(E_ALL);

mb_internal_encoding("UTF-8");
$internal_enc = mb_internal_encoding();
mb_regex_encoding("utf-8");

if(!defined('flag_perfom_working') || (flag_perfom_working != '1')) {
    header('Content-type: text/html; charset=utf-8');
    die('Эту программу нельзя запускать непосредственно. Access forbidden.');
}


// 6. Для каждого искомого слова из массива кл. слов запускаем процедуру поиска близких слов (с разными окончаниями)
/*$words_to_find_Arr = array();
for($j=0; $j < sizeof($str_Arr); $j++){
     $rez_Arr = check_WORD_in_DIC($ru_aff_Arr, $ALL_words, $internal_enc, $str_Arr[$j]);
     $words_to_find_Arr = array_merge($words_to_find_Arr, $rez_Arr);
}*/
//check_WORD_in_DIC($ru_aff_Arr, $ALL_words, $internal_enc, 'делали');
//  долю


/**********     ФУНКЦИИ PHP     **************************/

function check_WORD_in_DIC($ru_aff_Arr, $ru_alfabet, $ru_dic_marks_levels_Arr, $internal_enc, $word, $metaphone_len, $path_DIR_name, $ru_dic_FILE_marks){

$t0 = microtime(true);

set_time_limit(40); // С этого момента скрипт будет выполняться не более указанного количества секунд (каждая итерация цикла)


$word = mb_strtolower($word, $internal_enc);

$finded_Arr = array(); // Массив строчек из файла ru.aff, содержащих окончания, соответствующие слову.


// Получаем метафон
    $keyword_metaph = do_metaphone1(translit1($word), $metaphone_len); // Превращаем в metaphone;

// На основе метафона получаем путь к возможному индексному файлу
    $LAST_met_path_Arr = create_path_index_file($keyword_metaph, $path_DIR_name);
    $index_FILE = $LAST_met_path_Arr[0];
    $LAST_met_2 = $LAST_met_path_Arr[1];

/*$suff = ''; // Для начала (суффикс, содержащийся в словаре ru.dic и ранее помещенный оттуда в файл $index_FILE)
    if(file_exists($index_FILE)){ // Если такой файл есть, значит, искомое слово есть или в словаре, и/или в файлах сайта

        $index_FILE_Arr = file($index_FILE);
        $LAST_met_2_Arr = array_values(array_filter($index_FILE_Arr, function ($el) use ($LAST_met_2){
            return strstr($el, $LAST_met_2);
        }));

        if(sizeof($LAST_met_2_Arr) > 1){
            echo '<p class="error_mes">Ошибка: в файле '. $index_FILE .' почему-то присутствует '. sizeof($LAST_met_2_Arr) . ' подстроки (последних символа метафона) '. $LAST_met_2. '. Тогда как их должно быть не более 1. Видимо, индексирование файлов сайта ранее было произведено с ошибками.</p>';
        }elseif (sizeof($LAST_met_2_Arr) === 1){

            $begin_str_Arr = explode('|', $LAST_met_2_Arr[0]);
            $begin_str = $begin_str_Arr[0]; // Что-то типа it:/BLW, it:1 (или просто it)

            $begin_str_Arr = explode('/', $begin_str);
// Определяем суффикс. Что-то типа  BLW
            $suff = isset($begin_str_Arr[1]) ? $begin_str_Arr[1] : ''; // Суффикс, содержащийся в словаре ru.dic и ранее помещенный оттуда в файл $index_FILE
        }
    }*/



//echo $index_FILE.' '. $LAST_met_2.' !'. $suff. '!';


// 1. Находим начальные формы слова. Анализируем КАЖДОЕ окончание из файла суффиксов ru.aff: содержится ли оно в конце слова?
for($i=0; $i < sizeof($ru_aff_Arr); $i++){

    if(isset($ru_aff_Arr[$i][3])){
        if(preg_match('|'. $ru_aff_Arr[$i][3]. '$|' , $word) != false){
            // Если окончание содержится в слове, то убираем это окончание и делаем соотв. замену на окончание из столбца [2]

            $word_replaced = preg_replace('|'. $ru_aff_Arr[$i][3] .'$|', $ru_aff_Arr[$i][2], $word);
// А теперь проверяем соответствие слобцу [4]
            if(preg_match('|'. $ru_aff_Arr[$i][4]. '$|' , $word_replaced) != false){
                $finded_Arr[$i] = $ru_aff_Arr[$i];
//                $finded_Arr[$i][5] = mb_strlen($ru_aff_Arr[$i][3], $internal_enc);
            }

        }elseif(isset($ru_aff_Arr[$i][4])){ // Если не содержится в искомом слове, тогда, может, окончание слова соответствует окончанию из следующего столбца?
            if(preg_match('|'. $ru_aff_Arr[$i][4]. '$|' , $word) != false){
                $finded_Arr[$i] = $ru_aff_Arr[$i];
//                $finded_Arr[$i][5] = mb_strlen($ru_aff_Arr[$i][4], $internal_enc);
            }
        }
    }
}
$finded_Arr = array_values($finded_Arr); // Получен массив (пока непроверенных) начальных форм, типа:
/* Array
(
    [0] => делая
    [1] => делать
    [2] => делаять
    [3] => делаить
    [4] => делаь
    [5] => делай
    [6] => делае
    [7] => делаё
    [8] => делый
    [9] => делой
) */


$finded_Arr1 = array($word); // Массив начальных форм слова (именит. падеж, неопределенная фома глагола и т.д.). Они м.б. ошибочными, их следует проверить

// 2. По массиву всех окончаний, к-рые могут содержаться в слове
for($i=0; $i < sizeof($finded_Arr); $i++){
    if($finded_Arr[$i][2] === '0'){
        $replacement = '';
    }else{
        $replacement = $finded_Arr[$i][2];
    }



    $tmp = preg_replace('|'. $finded_Arr[$i][3]. '$|', $replacement, $word); // Заменяем фактическое окончание на окончание начальной формы

    if(!in_array($tmp, $finded_Arr1) &&  preg_match('|'. $finded_Arr[$i][4]. '$|' , $tmp) != false){ // Проверяем, соответствует ли полученная форма слова критерию из столбца [4]
        $finded_Arr1[] = $tmp; // Если да, то добавляем ее в массив

        if($finded_Arr[$i][2] === '0'){
            $finded_Arr1[] = $tmp. $finded_Arr[$i][3]; // Добавляем фактическое окончание
        }
    }

}
$finded_Arr1 = array_values(array_unique($finded_Arr1));

$word_DIC_suff_Arr = array(); // Массив искомых слов (слово-суффиксов), к-рые в начальной форме. Типа  сумма/I


// 3. Проверяем каждое из найденных слов: содержится ли оно в словаре ru.dic ? Т.е. проверяем, в начальной ли оно форме (если содержится).
// Ранее проверка делалась через маркеры-позиции. Теперь - путем поиска, содержится ли признак присутствия ("1" или суффикс) слова в соответствующем файле 1.txt
for($i=0; $i < sizeof($finded_Arr1); $i++){ // По каждой сконструированной форме слова

    $path_DIR_name_TMP = $path_DIR_name; // Для начала

    $keyword = do_metaphone1((translit1($finded_Arr1[$i])), $metaphone_len);

    for($j=0; $j < strlen($keyword)-2; $j++){ // По каждому отдельному символу данного слова, кроме предпоследнего и последнего символов
        $DIR_name_1 = substr($keyword, $j, 1); // Имена создаваемых каталогов будут состоять из 1 символа (a, b, c, d или т.п.)

        $path_DIR_name_TMP = $path_DIR_name_TMP. '/'. $DIR_name_1;
    }

    $LAST_met_2 = substr($keyword, $j, 2); // Последние 2 символа метафона
    $index_FILE = $path_DIR_name_TMP.'/1.txt';

    if(file_exists($index_FILE)){
        $index_FILE_Arr = explode("\n", file_get_contents($index_FILE));

        for($z=0; $z < sizeof($index_FILE_Arr); $z++){ // По каждой строчке индексного файла
            $substr1 = $LAST_met_2. ':1|'; // Без суффикса
            $substr2 = $LAST_met_2. ':/'; // С суффиксом

            if(strstr($index_FILE_Arr[$z], $substr1) !== false || strstr($index_FILE_Arr[$z], $substr2) !== false){ // Значит, признак присутствия имеется в этой строчке. Т.е. данное слово ЕСТЬ в файле-словаре
                preg_match('~\:/([^\|]*)\|~', $index_FILE_Arr[$z], $matches);

                if(sizeof($matches) > 0){ // Если начало строки имеет примерный вид:  tg:/KL|
                    $suff = '/'. $matches[1];
                }else{
                    $suff = ''; // Если начало строки имеет примерный вид:  tg:1|
                }
                $word_DIC_suff_Arr[] = $finded_Arr1[$i]. $suff; // Слово-суффикс
                    break;
            }

        }

    }else{ // Если нет такого файла, значит, нет и метафона, значит, нет такого файла в словаре
        continue;
    }

}

// 4. Теперь каждое слово в начальной форме нужно просклонять по разным окончаниям, в зависимости от суффикса из файла ru.dic
$words_to_find_Arr = array(); // Выходной массив слов для последующего поиска среди метафонов (с учетом разных окончаний)
/*   Там будет что-то вроде:
        Array
        (
            [0] => сумма
            [1] => суммы
            [2] => сумму
            [3] => суммой
            [4] => суммою
            [5] => сумме
            [6] => сумм
            [7] => суммами
            [8] => суммам
            [9] => суммах
        )
*/
// 5. По каждому слово-суффиксу (например: сумма/I )
    for($i=0; $i < sizeof($word_DIC_suff_Arr); $i++){

        $pos = strpos($word_DIC_suff_Arr[$i], '/');
        if($pos !== false){
            $DIS_suf = trim(substr($word_DIC_suff_Arr[$i], strpos($word_DIC_suff_Arr[$i], '/') + 1)); // Суффикс БЕЗ слова из файла ru.dic
            $DIC_word = trim(substr($word_DIC_suff_Arr[$i], 0, strpos($word_DIC_suff_Arr[$i], '/'))); // Слово БЕЗ суффикса из файла ru.dic
        }else{
            $DIS_suf = '';
            $DIC_word = trim($word_DIC_suff_Arr[$i]);
        }


// Вначале в этот массив добавляем само слово в начальной форме (именительный падеж существительного, неопределенная форма глагола и т.п.)
$words_to_find_Arr[] = $DIC_word;

        $suf_Arr = array_filter($ru_aff_Arr, function ($el) use ($DIS_suf) { // Для конкретного слова берем все строки из файла ru.aff, только с суффиксом $DIS_suf
            if(isset($el[1]) && sizeof($el) > 4){
// Если в совокупности суффиксов из файла ru.dic (например, BLMP) есть хотя бы один суффикс из файла ru.aff (например, B)
                return strpos($DIS_suf, $el[1]) !== false;
            }else{
                return null;
            }
        });
        $suf_Arr = array_values($suf_Arr);

        for($j=0; $j < sizeof($suf_Arr); $j++){ // По каждой строчке из файла ru.aff, содержащей суффикс $DIS_suf

            if(preg_match('|'. $suf_Arr[$j][4]. '$|', $DIC_word)){
                if($suf_Arr[$j][2] === '0'){
                    $removed = '';
                }else{
                    $removed = $suf_Arr[$j][2];
                }

                if($suf_Arr[$j][3] === '0'){
                    $replacement = '';
                }else{
                    $replacement = $suf_Arr[$j][3];
                }

                $tmp = preg_replace('|'. $removed. '$|', $replacement, $DIC_word);
                $words_to_find_Arr[] = $tmp;
            }
        }
    }


// 4. Бывают искомые слова, которых нет в словаре. Например, слово "иван". Такие слова НЕ вошли в массив $word_DIC_suff_Arr. Поэтому, для надежности, нужно добавить искомое слово (то самое, к-рое пришло от клиента) СНОВА
$words_to_find_Arr[] = $word; // Если оно в массиве уже есть, что ниже оно останется только в единственном числе (его добавить надо В КОНЦЕ!) +++

$words_to_find_Arr = array_values($words_to_find_Arr);
$words_to_find_Arr = array_unique($words_to_find_Arr);

return $words_to_find_Arr;
}
