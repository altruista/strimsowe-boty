<?php

header("Content-type:text/plain; charset=utf-8");

// definicje + podstawowe funklcje
require_once "common.inc.php";

// ładowanie bazowych klas
require_dir_once(DIR_ROOT . "classes/");

Log::Add('****************');
Log::Add('Tworze strimy');

// ładowanie klas strimów
$strimy = Strimy\StrimFactory::CreateList( require_dir_once(DIR_MODULES . "strimy/") );

Log::Add('Zaczynamy zabawe');

set_time_limit(0);

// wypełniamy kolejkę (dodajemy linki do bazy)
$queue = new Queue();
$queue->populateListingsQueue($strimy);

Log::Add('Koniec');

Log::SaveToFile(DIR_LOGS . "populate_queue_" . date("Ymd") . ".log");
