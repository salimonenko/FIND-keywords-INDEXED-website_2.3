<?php
/* Функция делает логические операции с массивами. Может объединять массивы или находить их пересечение (через eval).
    Для Arr1() || Arr2()  ->     array_merge(Arr1,Arr2)
    Для Arr1() && Arr2()  ->     array_intersect(Arr1,Arr2)
И т.д.
*/
// Процедурная реализация (без ООП)

$a = array();
$a[1] = array(1, 2, 3, 10);
$a[2] = array(3, 4, 5, 2);
$a[3] = array(7, 8, 9, 10);
$a[4] = array(10, 11, 2);
$a[5] = array(14, 15, 16);

// Тестовое выражение
//$bool_string = '$a[1] && $a[2]';
$bool_string = '$a[1] && ($a[2] || ($a[3] && $a[4] || ($a[2] && $a[5] && $a[4] || $a[1] && $a[3])))';
$bool_string = '$a['. (1). ']' .' && '. '($a['. (2).']'.' || '.'($a['.(3). ']'.' && '. '$a['. (4) . ']'.' || '.'($a['. (2). ']'.' && '.'$a['. (5). ']'.' && '.'$a['. (4). ']'.' || '. '$a['. (1). ']'.' && '.'$a['. (3). '])))';
//print_r(array_intersect($a[1],array_merge($a[2],array_merge(array_intersect($a[3],$a[4]),array_merge(array_intersect(array_intersect($a[2],$a[5]),$a[4]),array_intersect($a[1],$a[3]))))));



// Пример тестового запуска:
//print_r(array_logical_converter($a, $bool_string));



/******    ГЛАВНАЯ ФУНКЦИЯ    *******************/
function array_logical_converter($a, $bool_string){
    $logical_rezult_Arr = array_logical_expression($bool_string, 0);

    if($logical_rezult_Arr[0] === -1){
        return $logical_rezult_Arr;
    }

    $rez_Arr = eval_logical_Arr_operations($logical_rezult_Arr[1], $a);
return $rez_Arr;
}

function array_logical_expression($logical_rezult, $i){

// 1. Вначале надо бы оценить строку, содержащую выражение, при помощи eval (на корректность). Результат оценки пока неважен
    $rez_Arr = eval_logical_Arr_operations((bool)$logical_rezult);

    if($rez_Arr[0] === -1){
        return $rez_Arr;
    }

    $i_max = 20;
    if($i++ > $i_max){
        return array(-1, 'Error|В функции '. __FUNCTION__  .'() превышено число итераций рекурсии, равное '. $i_max.'.');
    }

// 2. Определяем самые вложенные (внутренние) скобки в строке логического выражения (быть может, те, что еще остались)
    preg_match("~(\([^()]*?\))~", $logical_rezult, $matches);

    if(sizeof($matches) > 0){
        $parenth = parenthesis_operators($matches[0]);
        $logical_rezult = str_replace($matches[0], $parenth, $logical_rezult);

        $logical_rezult_Arr = array_logical_expression($logical_rezult, $i);

        if($logical_rezult_Arr[0] === -1){
            return $logical_rezult_Arr;
        }

        $logical_rezult = $logical_rezult_Arr[1];

    }else{ // Если скобок (уже) нет
        $logical_rezult = parenthesis_operators($logical_rezult);
        $logical_rezult = str_replace(array('{', '}'), array('(', ')'), $logical_rezult);
    }

    return array(null, $logical_rezult);
}

function parenthesis_operators($parenth){
    $parenth = str_replace(array('(', ')'), ' ', $parenth);
    $parenth_Arr = change_logical_operator_to_F($parenth, '&&', 0);

    if($parenth_Arr[0] === -1){
        return $parenth_Arr;
    }

    $parenth_Arr = change_logical_operator_to_F($parenth_Arr[1], '||', 0);

return strval($parenth_Arr[1]);
}

// Функция преобразовывает строку, содержащую логические операции для массивов, в строку с соответствующими массивными функциями
function change_logical_operator_to_F($parenth, $operator, $i){
/* Для Arr1() && Arr2() :     array_merge(Arr1,Arr2)
   Для Arr1() || Arr2() :     array_intersect(Arr1,Arr2)
*/

$i_max = 20;
if($i++ > $i_max){
    return array(-1, 'Error|В функции '. __FUNCTION__  .'() превышено число итераций рекурсии, равное '. $i_max.'.');
}
    $operators_Arr = array('&&' => 'array_intersect', "||" => 'array_merge');

    $funct = $operators_Arr[$operator];

    if(!isset($funct)){
        return array(-1, 'Error|Задан неверный логический оператор. Допускается только: '. "&&, &mid;&mid;");
    }

    if(strpos($parenth, $operator) !== false){
        $replacement_amp = " ". $funct ."{". '$1,$2'."} ";
        $parenth = preg_replace('~\s+([^&\|]+?)'. preg_quote($operator, '|') .'(\s*[^&\|]+?)\s+~', $replacement_amp, $parenth);
        $parenth = preg_replace('~\s+~', '', $parenth);
        $parenth = preg_replace('~&&~', ' && ', $parenth);
        $parenth = ' '. preg_replace('~\|\|~', ' || ', $parenth). ' ';

        $parenth_Arr = change_logical_operator_to_F($parenth, $operator, $i);
    }else{
        $parenth_Arr = array(-1, $parenth);
    }

return array(null, strval($parenth_Arr[1]));
}


function eval_logical_Arr_operations($bool_string, $a = null){
// В массиве:
// true, если есть совпадение с выражением для искомых искомых слов; false - если нет.

    $bool_string_REZ = 0;

    $str_code = "\$bool_string_REZ = ". $bool_string;
    @eval($str_code. "|| 1". ";"); // Для проверки корректности выражения $str_code. Если оно верно, результат eval() даст заведомо 1 (true)

    if($bool_string == ''){
        return array(-1, 'Error:empty|Пустое логическое выражение.');
    }

    if(!!$bool_string_REZ){
        eval($str_code. ";"); // Если ошибки не было, получаем фактическое значение

        return array(null, $bool_string_REZ);

    }else{ // Значит, возникла ошибка в выражении для eval()
        $message_to_user = 'Ошибка при оценке логического выражения с массивами при помощи функции eval. Вот оно, это выражение: '. $bool_string.'';
        return array(-1, $message_to_user);
    }
}
