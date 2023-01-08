<?php

echo "<nav><ul>";
if (!isset($_SESSION["uloga"]) || $_SESSION["uloga"] === '4') {
    echo "<li><a href=\"$putanja/index.php\">Početna</a></li>
          <li><a href=\"$putanja/dionice.php\">Dionice</a></li>
          <li><a href=\"$putanja/obrasci/prijava.php\">Prijava</a></li>
          <li><a href=\"$putanja/obrasci/registracija.php\">Registracija</a></li>";
}
if (isset($_SESSION["uloga"]) && $_SESSION["uloga"] < 4) {
    echo "<li><a href=\"$putanja/index.php\">Početna</a></li>
          <li><a href=\"$putanja/dionice.php\">Dionice</a></li>
          <li><a href=\"$putanja/dokumenti.php\">Dokumenti</a></li>
          <li><a href=\"$putanja/obilazak.php\">Obilasci</a></li>";
}

if (isset($_SESSION["uloga"]) && $_SESSION["uloga"] < 3) {
    echo "<li><a href=\"$putanja/prijava_problema.php\">Problemi</a></li>";
    echo "<li><a href=\"$putanja/privatno/korisnici.php\">Korisnici</a></li>";
}

if (isset($_SESSION["uloga"]) && $_SESSION["uloga"] < 2) {
    echo "<li><a href=\"$putanja/kategorije_cesta.php\">Kategorije</a></li>";
}
if (isset($_SESSION["uloga"]) && $_SESSION["uloga"] < 4) {
    echo "<li><a href=\"$putanja/odjava.php\">Odjava</a></li>";
}
echo "</ul></nav>";
