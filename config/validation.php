<?php

// ^ -> indica inizio stringa
// $ -> indica fine stringa
// /( e )/ sono obbligatori, sintassi del framework
return [
    'first_name' => "regex:/(^[A-Za-zÀ-ÖØ-öø-ÿ ',.]{3,30}$)/",
    'last_name' => "regex:/(^[A-Za-zÀ-ÖØ-öø-ÿ ',.]{3,30}$)/",
    'name' => "regex:/(^[A-Za-zÀ-ÖØ-öø-ÿ ',.]{6,60}$)/",
    'fiscal_code' => 'regex:/(^[A-Z]{6}\d{2}[A-Z]{1}\d{2}[A-Z]{1}\d{3}[A-Z]{1}$)/',
    'notes' => "regex:/(^[A-Za-zÀ-ÖØ-öø-ÿ0-9 ,.;?!\n]{0,255}$)/",
    'cap' => 'regex:/(^[0-9]{5}$)/',
    'mobile_phone' => 'regex:/(^[+]{,1}[0-9]{1,3}[ ]{0,1}[0-9]{1,3}[ ]{0,1}[0-9]{1,3}$)/',
    'batch_code' => 'regex:/(^[a-zA-Z0-9]{5}$)/',
    'reservation_code' => 'regex:/(^[a-zA-Z0-9]{32}$)/',
    'vaccine_name' => "regex:/(^[a-zA-Z0-9]{2}[a-zA-Z '-,&]{1,30}$)/",
];

?>
