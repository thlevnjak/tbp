<?php

if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
    header("Location: http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], true, 301);
    exit;
}

require 'sesija.class.php';

if(isset($_COOKIE['prijava_sesija'])){
    unset($_COOKIE['prijava_sesija']);
    setcookie('prijava_sesija', null, -1, '/');
}

Sesija::obrisiSesiju();
header("Location: obrasci/prijava.php");
exit();