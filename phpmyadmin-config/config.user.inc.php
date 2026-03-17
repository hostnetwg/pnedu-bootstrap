<?php
/**
 * Dodatkowa konfiguracja phpMyAdmin (w kontenerze Docker).
 * Plik ładowany przez obraz phpmyadmin/phpmyadmin jako /etc/phpmyadmin/config.user.inc.php
 */

// Brak limitu czasu po stronie phpMyAdmin (w sekundach)
$cfg['ExecTimeLimit'] = 0;

// Więcej pamięci dla dużych importów (php.ini nadal obowiązuje jako limit twardy)
$cfg['MemoryLimit'] = '1024M';

