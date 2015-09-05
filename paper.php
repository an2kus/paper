<?php /* paper.php Project paper (c) Alex Nemch an2k@mail.ru */

error_reporting ( E_ERROR | E_WARNING | E_PARSE );

$dir1 = "/home/an2k/www/er/sites/default/files" ; // для упаковки
$dir2 = "../sites/default/files/" ; // для закачки
$filename = "list_students" ; // без расширения
$hierarchy = "hierarchy" ;  // файл шаблона иерархии одной записи студента

?>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf8">
</head>
<body>
<center><br><br>
<?php

$key = $_COOKIE['key'] ;

if ( $_POST['subm'] == "OK" ) {
    setcookie ( "key", $_POST['key'] ) ;
    header ( "Location: paper.php" ) ;
}

if ( ! $key ) {
?>
  <h2>Вход</h2>
  <form action=paper.php method="POST">
    <p><input size=12 type=password name=key>
    <input type=submit name=subm value="OK">
  </form>
<?php
  exit() ;
}

if ( "paper1q2w3e" != $key ) die ( "<hr><hr><font color=red>Пароль неверный. Закройте браузер и начните все сначала.</font><hr><hr>" ) ;

?>

<table align=center bgcolor=#dddccc cellpadding=20 cellspacing=0 border=0><tr><td align=center>

<h2><font color=blue>Списки студентов МГИМО на получение льгот для проезда по г.Москве</font></h2>
<hr>
<a href=help.html><h4>Правила формирования CSV-файла (HELP)</h4></a>
<hr>
<h4>Копирование файла с вашего копмпьютера на сервер<br>
Если файл уже есть на сервере, он будет стерт и заменен новым.</h4>
<br>
<form enctype=multipart/form-data action=paper.php method=POST>
  <input size=57 type=file name=url>
  <input type=submit name=add_apl value=Закачать>
</form>

<?php

if ( $_POST['add_apl'] ) { // нажата кнопка Закачать
  $url = $_FILES['url']['tmp_name'] ;
  $url_name = $_FILES['url']['name'] ;

  writer_file  ( $dir2, $filename, $hierarchy, $url, $url_name ) ; // функция закачки

  $path = $dir2 . $filename ; // csv-файл сохраняется с эти путем
  $body_xml = create_body_XML_file  ( $dir2, $filename, $hierarchy ) ; // функция создания XML-файла из $filename.csv

  $body_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<tns:file xmlns:tns=\"http://university.sm.msr.com/schemas/incoming\">
<fileInfo>
 <fileSender>186</fileSender>
 <version>1.1.3</version>
 <recordCount>" .

 $GLOBALS["num_rec"] .

"</recordCount>
</fileInfo>
<recordList>" .
  $body_xml .
"</recordList>
</tns:file>" ;

$xml = $path . ".xml" ;
if ( ! $h = fopen ( $xml, 'wt' ) ) die ( "<font color=red>Не могу открыть файл <b>$xml</b></font><hr><hr>" ) ;
if ( fwrite ( $h, $body_xml ) === FALSE ) die ( "<font color=red>Не могу произвести запись в файл <b>$xml</b></font><hr><hr>" ) ;
fclose ( $h ) ;

  echo "<font color=green><b>XML-файл готов</b>. Количество записей: <big>" . $GLOBALS["num_rec"] . "</big></font><hr><hr>" ;

  // для скачивания упаковать файлы xml и csv RAR`ом и записать это в лог
  system ( "cd $dir1; echo '\n=====\n' >> paper.log; date >> paper.log; rar u $filename.csv.rar $filename.csv >> paper.log; rar u $filename.xml.rar $filename.xml >> paper.log" ) ;
}

?>
<td><tr>
<tr><td align=center>
<hr>
<h4>Файлы для скачивания</h4>
<a href=<?php echo $dir2 . $filename . ".xml.rar" ?>><big><b>Готовый XML-файл</b></big></a><br><br>
<a href=<?php echo $dir2 . $filename . ".csv.rar" ?>>Закачанный CSV-файл</a>&nbsp;&nbsp;&nbsp;
<a href=<?php echo $dir2 . $hierarchy ?> target=_blank>Файл иерархии</a>&nbsp;&nbsp;&nbsp;
<a href=<?php echo $dir2 . "all_paper.rar" ?>>Исходники проекта</a>

<td><tr>
</table>

Версия 1.1 beta, 2015 (c) <a target=_blank href=http://mgimo.ru>МГИМО</a>. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Разработка и техподдержка <a target=_blank href=http://nemchenko.ru>А.Немченко</a>

</center>
</body>
</html>
<?php

function writer_file  ( $dir2, $filename, $hierarchy, $url, $url_name ) { // функция закачки
  $filename .= ".csv" ;
  if ( ! $url ) die ( "<hr><hr><font color=red>Файл не выбран!</font><hr><hr>" ) ;
  elseif ( $filename != $url_name and $hierarchy != $url_name ) die ( "<hr><hr><font color=red>Разрешено закачивать только файл с именем <b>$filename</b></font><hr><hr>" ) ;
  if ( $filename == $url_name ) $path = $dir2 . $filename ; // csv-файл сохраняется с эти путем
  else $path = $dir2 . $hierarchy ; // иерархия с этим

  if ( copy ( $url, $path ) ) echo "<hr><hr><font color=green>Файл <b>$url_name</b> успешно скопирован на сервер.</font><br>" ;
  else die ( "<hr><hr><font color=red>Не удалось скопировать файл $url в <b>$path</b></font><hr><hr>" ) ;

  return 1 ;
}

// функция создания основного тела XML-файла из $filename.csv
function create_body_XML_file  ( $dir2, $filename, $hierarchy ) {
  $csv = $dir2 . $filename . ".csv" ; // csv-файл брать здесь
  $hierarchy = $dir2 . $hierarchy ;  // иерархию - здесь
  $arr_s = file ( $csv ) ; // s - строка
  foreach ( $arr_s as $i => $s ) {
    $s = trim ( $s ) ;
    if ( ! $s ) continue ;
    if ( ! $i ) {
      $arr_top = pars_str ( $s ) ; // парсим шапку
      $n1 = count ( $arr_top ) ;
      if ( $n1 < 3 ) {
        echo "<pre>" ;
        print_r ( $arr_top ) ;
        die ( "<font color=red>Слишком мало полей в шапке:<br>$n1 :: `$s`</font><hr><hr>" ) ;
      }
    }
    else {
      $arr_str[$i] = pars_str ( $s ) ; // парсим текущую строку и формируем 2-х мерный массив - фактически таблицу
      $n2 = count ( $arr_str[$i] ) ;
      if ( $n1 != $n2 ) die ( "<font color=red>Количество полей в шапке не равно количеству полей в строке:<br>$n1 != $n2 :: #$i :: `$s`</font><hr><hr>" ) ;
    }
  }

  // шаблон иерархии одной записи студента
  $template = file_get_contents ( $hierarchy ) ; // считать файл в одну строку
  $arr_tag = explode ( "\n", $template ) ;  // рассыпать строку в массив
  // каждый тег в массиве нужно затримить - вдруг какой-то идиот в файле иерархии пробелов понаставит
  foreach ( $arr_tag as $ti => $tt ) $atg[$ti] = trim ( $tt ) ;
  $arr_tag = $atg ;

  $body_xml = create_body_xml ( $arr_tag, $arr_top, $arr_str ) ;

  return $body_xml ;
}

// создание тела XML-файла из массива тегов шаблона, массива шапки (real tag) и массива строк
function create_body_xml ( $arr_tag, $arr_top, $arr_str ) {
  $arr_top_bak = $arr_top ; // резерв
  $GLOBALS["num_rec"] = 0 ; // счетчик строк
  // $i - номер строки, $j - номер колонки
  foreach ( $arr_str as $i => $arr_s ) { // перебираем строки содержащие реальные данные о студентах
    $body_xml .= "<record>\n" ;
    $arr_top = $arr_top_bak ; // востановить из резерва
    foreach ( $arr_tag as $t => $ttag ) { // перебираем теги шаблона
      $flag_write = 0 ; // флаг поднят если записан тег с реальными данными
      $ttag = trim ( $ttag ) ; // template tag - тег из шаблона
      if ( strstr ( $ttag, " " ) ) die ( "<font color=red>В названии тега не должно быть пробелов:<br>$t :: `$ttag`</font><hr><hr>" ) ;
      foreach ( $arr_s as $j => $value ) { // $value - значение поля (ячейки)
        $rtag = trim ($arr_top[$j]) ; // real tag - тег из шапки реальной таблицы
        if ( ! $rtag ) continue ;
        if ( strstr ( $rtag, " " ) ) die ( "<font color=red>В названии поля не должно быть пробелов:<br>$j :: `$rtag`</font><hr><hr>" ) ;
        elseif ( ! in_array ( $rtag, $arr_tag ) )  die ( "<font color=red>В массиве тегов шаблона (ttag) нет rtag=<b>$rtag</b> из шапки таблицы</font><hr><hr>" ) ;

        if ( $rtag == $ttag ) { // если реальный тег = текущему шаблону
          $body_xml .= " <" . $rtag . ">" . trim ( $value ) ;
          $ttag = "" ; // забить использованный тег, чтоб не мешал - может найтись еще такой же из другой секции
          $arr_top[$j] = "" ; // забить использованный тег, чтоб не мешал - может найтись еще такой же из другой секции
          $flag_write = 1 ;  // флаг поднят если записан тег с реальными данными ...
        }
      }
      if ( ! $flag_write ) $body_xml .= "<" . $ttag . ">\n" ;  //...в противном случае пишется тег иерархии (или закрывается реальный тег)
    }
    $body_xml .= "</record>\n" ;
    $GLOBALS["num_rec"]++ ;
  }
  return $body_xml ;
}

function pars_str ( $s ) { // парсинг строки
  $arr_f = explode ( "\t", $s ) ; // f - поле (часть строки)
  if ( ! is_array ( $arr_f ) or ! count ( $arr_f ) ) return 0 ;
  return $arr_f ;
}

?>
