<?php

if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
    header("Location: http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], true, 301);
    exit;
}

$putanja = dirname($_SERVER['REQUEST_URI']);
$direktorij = getcwd();

require "$direktorij/baza.php";
require "$direktorij/sesija.class.php";
require "$direktorij/kopija.class.php";

Sesija::kreirajSesiju();

$kopija = new Kopija(); 

if(isset($_GET['napravi'])) {
    $rez = $kopija->napraviKopiju();
    if ($rez == 0) {
        header("Location: index.php?napravljena=true");
    }
    else {
        header("Location: index.php?napravljena=false");
    }
    exit(0);
}