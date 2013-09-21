<?php

header("Content-type:text/plain; charset=utf-8");

// definicje + podstawowe funklcje
require_once "common.inc.php";

// ładowanie bazowych klas
require_dir_once(DIR_ROOT . "classes/");

Log::Add('****************');
Log::Add('Laduje strimy');
require_dir_once(DIR_MODULES . "strimy/");

Log::Add('Wczytuje opcje');

$options = get_options(array(
    'limit'         => 45, // max 45 treści na 45 minut
    'time_limit'    => 60*45 // 45 minut
));

Log::Add('Opcje: ' . print_r($options, true));

Log::Add('Zaczynamy zabawe');

// 60s na dokończenie jakichkolwiek dodakowych zadań
set_time_limit($options['time_limit'] + 60);

$queue = new Queue();
$queue->postListings($options);

Log::Add('Koniec');

Log::SaveToFile(DIR_LOGS . "add_" . date("Ymd") . ".log");
