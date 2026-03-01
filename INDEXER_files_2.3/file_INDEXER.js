// Функция запускается после получения ответа сервера (по AJAX), обрабатывает ответы сервера
function Function_after_server(arg_Arr, responseTEXT, responseSTATUS) {

// 1. Задаем общие переменные
//    var idd;
    var idd_ol = 'links'; // id списка, в к-рый записываются приходящие с сервера ссылки на файлы сайта, соответствующие логич. выраж. с ключевыми словами
    var id_num_showed_links = 'num_showed_links'; // id тега, к-рый показывает общее число таких ссылок на странице

// 2. Выполняем ПОСЛЕ-СЕРВЕРНЫЕ действия, в зависимости от запросов на сервер
switch (arg_Arr[0]) {
    case 'Notepad_PP': // Если был запрос на запуск Notepad_PP
    Function_after_server_Notepad_PP(responseTEXT);
        break;
    case 'ask_string_number': // Если был запрос к серверу для определения номера строки в файле files.txt
    Function_after_server_ask_string_number(responseTEXT);
        break;
    case 'save_string_number':
    Function_after_server_ask_save_string_number(responseTEXT);
        break;
    case 'ask_string_numbers_NOT_indexed':
    Function_after_server_ask_string_numbers_NOT_indexed(responseTEXT);
        break;
    case 'DO_working':
    Function_after_server_DO_working(responseTEXT);
        break;
    case 'find_keywords': // Если был запрос на поиск ключевых слов (логич. выражения) в индексированных файлах
    Function_after_server_find_keywords(responseTEXT, arg_Arr, idd_ol, id_num_showed_links);
        break;
    case 'file_FINDER': // Если был запрос на создание файла files.txt. Это - файл со строками вида:  \filename|560
    Function_after_server_file_FINDER(responseTEXT);
        break;
    case 'Notepad_PP_error_log': // Если был запрос на открытие файла лога ошибок
    Function_after_server_Notepad_PP_error_log(responseTEXT);
        break;
    case 'ask_keyword_links': // Если был запрос на получение еще скольких-то ссылок на файлы, содержащих искомые слова
    Function_after_server_ask_keyword_links(responseTEXT, idd_ol, id_num_showed_links);
        break;
    case 'link_description':  // Если был запрос на получение описания страницы (например, тега description)
    Function_after_server_link_description(responseTEXT, arg_Arr);
        break;
    case 'sort_ru_dic': // Если был запрос на сортировку файла-словаря ru.dic
    Function_after_server_sort_ru_dic(responseTEXT, arg_Arr);
        break;
        default:
                alert( "Нет таких действий. Похоже - ошибка в файле file_INDEXER.js" );
        }

    }

// Функция показывает описание файла (например, взятое из тега description), имя которого содержится в href ссылки
function show_description(text, idd, tag) {
    var div = document.getElementById(idd);
    var offsets = getOffsetRect(tag);
    var x = offsets.left;
    var y = offsets.top;

    div.style.left = (x - parseInt(getComputedStyle(div).width) - 15) + 'px';
    div.style.top = y + 'px';
    div.style.display = 'block';

    div.innerHTML = text;
// Записываем ответ сервера в атрибут (чтобы при повторном обращении брать эту информацию оттуда, не делая повторный запрос на сервер)
    tag.setAttribute('data-description', text);
}

// Функция запускает Notepad_PP
function Function_after_server_Notepad_PP(responseTEXT) {
    document.getElementById('responser').innerHTML += responseTEXT;
}
// Функция запрашивает с сервера номера строки в файле files.txt, на котором в предыдущий раз окончалось индексирование
function Function_after_server_ask_string_number(responseTEXT) {
    var path_file_names_all_files = '/unknown/';
    if(document.querySelector('[data-path_file_names_all_files]')){ // Читаем атрибут из тега ЭТОГО скрипта
        path_file_names_all_files = document.querySelector('[data-path_file_names_all_files]').getAttribute('data-path_file_names_all_files');
    }

    if(responseTEXT === '-1'){
        document.getElementById('responser').innerHTML += 'Похоже, произошла ошибка сервера: невозможно открыть файл со списком проиндексированных имен файлов.<br/>';
    }else{
        document.getElementById('last_managed_string').value = responseTEXT;

        if(responseTEXT === document.getElementById('last_index').textContent){
            var path_file_names_error = '/unknown/';
            if(document.querySelector('[data-path_file_names_error]')){ // Читаем атрибут из тега ЭТОГО скрипта
                path_file_names_error = document.querySelector('[data-path_file_names_error]').getAttribute('data-path_file_names_error');
            }

            alert('Это число равняется номеру последней строки в файле '+ path_file_names_all_files + '. Похоже, все файлы проиндексированы. Чтобы убедиться в этом, посмотрите также файл '+ path_file_names_error + ', в котором могут содержаться относительные пути и индексы файлов, индексирование которых не получилось.\n\nЧтобы убедиться дополнительно, выполните операцию "Показать номера НЕпроиндексированных строк"');
        }else {
            document.getElementById('responser').innerHTML += 'Установлен номер строки, равный '+ responseTEXT+ '. После обновления страницы индексирование файлов будет продолжено, начиная с этого номера строки.<br/>';
        }
    }

}

function Function_after_server_ask_save_string_number(responseTEXT) {
    document.getElementById('responser').innerHTML += responseTEXT;
}

function Function_after_server_ask_string_numbers_NOT_indexed(responseTEXT) {
    document.getElementById('rezults').innerHTML = responseTEXT;
}

function Function_after_server_DO_working(responseTEXT) {
    if(responseTEXT === 'true'){ // Сигнал о том, что создан флаг-файл (при наличии которого может проводиться индексирование). Обновляем страницу для начала индексирования
        window.location.reload();
    }
    else if(responseTEXT === 'false'){ // Сигнал о том, что флаг-файл удален

        document.getElementById('responser').innerHTML += '<p class="info_mes">Флаг-файл успешно удален. Процесс индексирования остановлен.</p>';
    }else if(responseTEXT === 'Error_unlink_flag_FILE'){ // Сигнал о том, что не получлиось удалить флаг-файл
        document.getElementById('responser').innerHTML += '<p class="error_mes">НЕ получилось удалить флаг-файл. Чтобы остановить процесс индексирования, повторите операцию еще раз...</p>';
    }else {
        document.getElementById('responser').innerHTML += '<p class="info_mes">' + responseTEXT + '</p>';
    }
}
// Функция делает запрос на поиск искомых (ключевых) слов
function Function_after_server_find_keywords(responseTEXT, arg_Arr, idd_ol, id_num_showed_links) { // Если был запрос на поиск ключевых слов (логич. выражения) в индексированных файлах
    document.getElementById('popup1').innerHTML = responseTEXT; // Помещаем ответ сервера

// Назначаем обработчик на список ссылок, пришедший с сервера (показывает всп. окно с description)
    document.getElementById('links').onmouseover = function (e) {
        e = e || window.e;
        var target = e.target || e.srcElement;

        var link;
            if(target.tagName.toLowerCase() === 'a'){
                link = target;
            }else {
                return;
            }

// Если этот атрибут уже установлен, значит, в него уже было записано описание description (напрмер, для html страницы)
            if(link.hasAttribute('data-description')){
                var description = link.getAttribute('data-description');
                show_description(description,  arg_Arr[1], link); // Поэтому выводим информацию из него, чтобы не делать лишних запросов на сервер
// Иначе - делаем запрос на сервер
            }else {
                var href = encodeURIComponent(link.getAttribute('href'));
                var body = 'link_description='+ href + '&r=' + Math.random();
                var arg_Arr1 = ['link_description', arg_Arr[1], link];
                var url_php = 'keywords_FINDER.php';

                DO_send_data1(Function_after_server, arg_Arr1, url_php, body);
            }
    };


        if(document.getElementById(idd_ol)){
            document.getElementById(id_num_showed_links).textContent = document.getElementById(idd_ol).querySelectorAll('a.files').length; // Показываем, сколько фактически ссылок на файлы пришло с сервера
        }else{
            console.log('Вот ответ сервера: ' + responseTEXT);
//            alert('Похоже, возникла ошибка! Для уточнения проблемы см. также сообщение в консоли.');
        }
}

function Function_after_server_file_FINDER(responseTEXT) {
    // Если был запрос на создание файла files.txt. Это - файл со строками вида:  \filename|560
    if(document.getElementById('rezults')){
        document.getElementById('rezults').innerHTML = responseTEXT;
    }else{ // Если такого блока нет, то выводим ответ сервера хоть куда-нибудь
        document.body.innerHTML += responseTEXT;
        console.log(responseTEXT);
        alert('Т.к. на странице отсутствует блок для получения ответа сервера, ответ выведен в конец страницы, а также продублирован в консоли. Обновите эту страницу.');
    }
}

function Function_after_server_Notepad_PP_error_log(responseTEXT) { // Если был запрос на открытие файла лога ошибок
    document.getElementById('responser').innerHTML += responseTEXT;
}

function Function_after_server_ask_keyword_links(responseTEXT, idd_ol, id_num_showed_links) { // Если был запрос на получение еще скольких-то ссылок на файлы, содержащих искомые слова

    if(document.getElementById(idd_ol)){
        document.getElementById(idd_ol).innerHTML += responseTEXT; // Добавляем ссылки в список

        document.getElementById(id_num_showed_links).textContent = document.getElementById(idd_ol).getElementsByTagName('li').length; // Обновляем общее число ссылок
// Удаляем почти все кнопки запроса для поиска следующей порции ссылок, оставляем только самую первую
        var next_links = document.getElementById('popup1').getElementsByClassName('next_links');
        for (var i=1; i < next_links.length; i++){
            var parent = next_links[i].parentNode;
            parent.removeChild(next_links[i]);
        }

        var div = document.createElement('div'); // Фиктивный блок
        div.innerHTML = responseTEXT;

        if(div.querySelector('a.files')){
// Если в ответе сервера есть хотя бы одна ссылка с классом files (т.е. ссылка на файл, удовл. логич. условию поиска кл. слов)
            var INPUT_next_links_new = next_links[0].cloneNode(true);
            document.getElementById('popup1').appendChild(INPUT_next_links_new); // Добавляем еще одну  кнопку сразу за списком
        }

    }else{
        alert('Ошибка: на странице отсутствует элемент с id="'+ idd_ol + '". Поэтому невозможно показать ссылки, полученные с сервера.');
    }
}

function Function_after_server_link_description(responseTEXT, arg_Arr){ // Если был запрос на получение описания страницы (например, тега description)
    show_description(responseTEXT, arg_Arr[1], arg_Arr[2]);
}

function Function_after_server_sort_ru_dic(responseTEXT, arg_Arr) {
    var idd = arg_Arr[1];
    if(document.getElementById(idd)){
        document.getElementById(idd).innerHTML = responseTEXT;
    }else{ // Если такого блока нет, то выводим ответ сервера хоть куда-нибудь
        document.body.innerHTML += responseTEXT;
        console.log(responseTEXT);
        alert('Т.к. на странице отсутствует блок для получения ответа сервера, ответ выведен в конец страницы, а также продублирован в консоли. Обновите эту страницу.');
    }
}


// Функция кроссбраузерно выдает координаты лев. верх угла элемента
function getOffsetRect(elem) {
    var box = elem.getBoundingClientRect();
    var body = document.body;
    var docElem = document.documentElement;

    var scrollTop = window.pageYOffset || docElem.scrollTop || body.scrollTop;
    var scrollLeft = window.pageXOffset || docElem.scrollLeft || body.scrollLeft;

    var clientTop = docElem.clientTop || body.clientTop || 0;
    var clientLeft = docElem.clientLeft || body.clientLeft || 0;

    var top  = box.top +  scrollTop - clientTop;
    var left = box.left + scrollLeft - clientLeft;

    return { top: Math.round(top), left: Math.round(left) }
}


// Функция непосредственно выполняет запрос на сервер
function DO_send_data1(Function_after1, arg_Arr, url_php, body) {
    /*  Function_after1 - функция, запускаемая после прихода ответа сервера
     arg_Arr - массив параметров, передаваемый при запуске функции Function_after1
     url_php - URL, куда направляется запрос (сообщение)
     body - тело сообщения на сервер
     */
    var xhr = new XMLHttpRequest();
    xhr.open("POST",  url_php, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function xhr_state() {
        if (xhr.readyState != 4) return;
        if (xhr.status >= 200 && xhr.status <= 400) {
            // Принят ответ сервера
            var responseTEXT = xhr.responseText;
            var responseSTATUS = xhr.status;

            if (responseTEXT == "") {
                alert("Ошибка сервера: получен пустой ответ.")
            }

            if(arg_Arr != '' && Function_after1 != ''){ // Выполняем функцию с именем Function_after1, если ее имя и массив параметров - не пустые
//                        var func = new Function('return ' + Function_after1)();
//                    func.call(window.document, request, scriptBODY);
                Function_after1(arg_Arr, responseTEXT, responseSTATUS); // Вызывается после получения ответа сервера
            }

        } else {alert('xhr error 2\nВозможно, сервер недоступен\n' + xhr.statusText +'\n\n Проверьте, есть ли у Вас в данный момент связь с сервером, на котором находится эта программа.\n Если (локальный) сервер точно запущен - подождите пару минут, пока обновится кэш IP-адресов браузера. Или - обновите страницу.' );}
    };

    xhr.send(body); // запрос
}


// Функция запускает индексирование ИМЕН ВСЕХ файлов сайта (за исключением запрещенных к индексации).
function file_FINDER() {

    if(document.getElementById('rezults')){
        document.getElementById('rezults').innerHTML = '';
    }

    var body = 'file_FINDER='+null + '&r=' + Math.random();
    var arg_Arr = ['file_FINDER'];
    var url_php = 'file_FINDER.php';

    DO_send_data1(Function_after_server, arg_Arr, url_php, body);
}


