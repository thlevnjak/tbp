<?php

if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
    header("Location: http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], true, 301);
    exit;
}

$putanja = dirname($_SERVER['REQUEST_URI']);
$direktorij = getcwd();

require "$direktorij/baza.php";
require "$direktorij/sesija.class.php";

Sesija::kreirajSesiju();

if (!isset($_SESSION["uloga"])) {
    header("Location: prijava.php");
    exit();
} elseif (isset($_SESSION["uloga"]) && $_SESSION["uloga"] === '3') {
    header("Location: index.php");
    exit();
}

$ispis = "";

if (isset($_SESSION["uloga"]) && $_SESSION["uloga"] == 1) {
    $ispis = "<form novalidate name='odabir' id='odabir' method='get' action='kategorije_cesta.php'>"
            . "<input id='submit1' name='dodaj' type='submit' value='Dodaj kategoriju ceste' /></form>";
}

if (isset($_GET['dodaj'])) {
    $ispis = "<form name='novakategorija' id='novakategorija' method='get' action='kategorije_cesta.php'>"
            . "<label>Naziv kategorije: </label>"
            . "<input name='naziv' id='naziv' type='text'/><br>"
            . "<label>Opis kategorije: </label>"
            . "<input name='opis' id='opis' type='text' size='75'/><br><br>"
            . "<input id='submit' name='dodajkategoriju' type='submit' value='Dodaj kategoriju' /><br>"
            . "</form>";
}

if (isset($_GET['dodajkategoriju'])) {
    $veza = new Baza();

    $upit = "INSERT INTO kategorija_ceste (naziv, opis) VALUES ('{$_GET['naziv']}', '{$_GET['opis']}');";
    $rezultat = $veza->upit($upit);
    $veza->zatvoriVezu();

    if ($rezultat != null) {
        $ispis = "Kategorija ceste uspješno dodana!";
    }
    header("Location: kategorije_cesta.php?poruka={$ispis}");
    exit();
}

if (isset($_GET['naziv']) && !isset($_GET['azurirajKategoriju'])) {
    $veza = new Baza();

    $upit = "SELECT kategorija_ceste_id, naziv, opis FROM kategorija_ceste WHERE naziv='{$_GET['naziv']}' ORDER BY 2";
    $rezultat = $veza->upit($upit);
    $redak = pg_fetch_array($rezultat);

    $upit2 = "SELECT korisnik_id, korisnicko_ime FROM korisnik WHERE uloga_id=2";
    $rezultat2 = $veza->upit($upit2);
    
    $upit3 = "SELECT DISTINCT k.korisnik_id, k.korisnicko_ime FROM upravljanje u "
            . "INNER JOIN korisnik k ON u.korisnik_id = k.korisnik_id "
            . "INNER JOIN kategorija_ceste kc ON u.kategorija_ceste_id = kc.kategorija_ceste_id "
            . "WHERE k.korisnik_id > 1 AND kc.naziv = '{$_GET['naziv']}'";
    $rezultat3 = $veza->upit($upit3);

    $duljina = strlen($redak['opis']);
    $ispis = "<form novalidate name='kategorije' id='kategorije' method='get' action='kategorije_cesta.php'>"
            . "<input name='kategorija_id' id='kategorija_id' type='hidden' value='{$redak['kategorija_ceste_id']}'/>"
            . "<label>Naziv kategorije: <br></label>"
            . "<input name='naziv' id='naziv' type='text' value='{$redak['naziv']}'/>"
            . "<label><br>Opis kategorije: <br></label>"
            . "<input name='opis' id='opis' type='text' size='{$duljina}' value='{$redak['opis']}'/><br>"
            . "<input id='submit' name='azurirajKategoriju' type='submit' value='Spremi promjene' /><br>"
            . "<input id='submit' name='dodajModeratore' type='submit' value='Upravljaj moderatorima' /><br>"
            . "<input id='submit' name='obrisi' type='submit' value='Obrisi kategoriju' /> <br>"
            . "</form>";
}

if (isset($_GET['azurirajKategoriju'])) {
    $IDkategorije = $_GET['kategorija_id'];
    $veza = new Baza();

    $upit = "UPDATE kategorija_ceste SET naziv = '{$_GET["naziv"]}', opis = '{$_GET["opis"]}' WHERE kategorija_ceste_id = '{$IDkategorije}'";
    $rezultat = $veza->upit($upit);
    $veza->zatvoriVezu();
    
    header("Location: kategorije_cesta.php");
    exit();
}

if (isset($_GET['obrisi'])) {
    $IDkategorije = $_GET['kategorija_id'];
    $veza = new Baza();

    $upit = "DELETE FROM kategorija_ceste WHERE kategorija_ceste_id = '{$IDkategorije}'";
    $rezultat = $veza->upit($upit);
    $veza->zatvoriVezu();
    
    header("Location: kategorije_cesta.php");
    exit();
}

if (isset($_GET['dodajModeratore'])) {
    $veza = new Baza();

    $upit = "SELECT kategorija_ceste_id, naziv, opis FROM kategorija_ceste WHERE naziv='{$_GET['naziv']}' ORDER BY 2";
    $rezultat = $veza->upit($upit);
    $redak = pg_fetch_array($rezultat);

    $upit2 = "SELECT korisnik_id, korisnicko_ime FROM korisnik WHERE uloga_id=2";
    $rezultat2 = $veza->upit($upit2);
    
    $upit3 = "SELECT DISTINCT k.korisnik_id, k.korisnicko_ime FROM upravljanje u "
            . "INNER JOIN korisnik k ON u.korisnik_id = k.korisnik_id "
            . "INNER JOIN kategorija_ceste kc ON u.kategorija_ceste_id = kc.kategorija_ceste_id "
            . "WHERE k.korisnik_id > 1 AND kc.naziv = '{$_GET['naziv']}'";
    $rezultat3 = $veza->upit($upit3);

    $ispis = "<form novalidate name='kategorije' id='kategorije' method='get' action='kategorije_cesta.php'>"
            . "<input name='kategorija_id' id='kategorija_id' type='hidden' value='{$redak['kategorija_ceste_id']}'/>"
            . "<label><br>Dodijeli moderatora: <br></label>"
            . "<select name='slobodni' id='slobodni'>"
            . "<option selected></option>";
    while ($redak2 = pg_fetch_array($rezultat2)) {
        $ispis .= "<option value='{$redak2["korisnik_id"]}'>{$redak2["korisnicko_ime"]}</option>";
    }
    $ispis .= "</select>"
            . "<label><br>Oduzmi ulogu moderatora: <br></label>"
            . "<select name='zauzeti' id='zauzeti'>"
            . "<option selected></option>";
    while ($redak3 = pg_fetch_array($rezultat3)) {
        $ispis .= "<option value='{$redak3["korisnik_id"]}'>{$redak3["korisnicko_ime"]}</option>";
    }
    $ispis .= "</select><br>"
            . "<input id='submit1' name='moderatori' type='submit' value='Spremi moderatore' /><br>"
            . "</form>";
}

if (isset($_GET['moderatori']) && $_GET['slobodni'] == "" && $_GET['zauzeti'] != "") {
    $veza = new Baza();

    $upit = "DELETE FROM upravljanje WHERE korisnik_id = {$_GET['zauzeti']} AND kategorija_ceste_id = {$_GET['kategorija_id']};";
    $rezultat = $veza->upit($upit);
    $veza->zatvoriVezu();
    
    header("Location: kategorije_cesta.php");
    exit();
}

if (isset($_GET['moderatori']) && $_GET['slobodni'] != "" && $_GET['zauzeti'] == "") {
    $veza = new Baza();

    $upit = "INSERT INTO upravljanje (korisnik_id, kategorija_ceste_id) VALUES ({$_GET['slobodni']}, {$_GET['kategorija_id']});";
    $rezultat = $veza->upit($upit);
    $veza->zatvoriVezu();
    
    header("Location: kategorije_cesta.php");
    exit();
}

if (isset($_GET['moderatori']) && $_GET['slobodni'] != "" && $_GET['zauzeti'] != "") {
    $veza = new Baza();

    $upit = "INSERT INTO upravljanje (korisnik_id, kategorija_ceste_id) VALUES ({$_GET['slobodnimod']}, {$_GET['kategorija_id']});";
    $rezultat = $veza->upit($upit);
    
    if ($rezultat != null) {
        $ispis .= "Moderator uspješno dodijeljen!";
    }
    $upit2 = "DELETE FROM upravljanje WHERE korisnik_id = {$_GET['zauzeti']} AND kategorija_ceste_id = {$_GET['kategorija_id']};";
    $rezultat2 = $veza->upit($upit2);
    $veza->zatvoriVezu();

    header("Location: kategorije_cesta.php");
    exit();
}

if (isset($_GET['poruka'])) {
    $ispis = $_GET['poruka'];
}
?>

<!DOCTYPE html>
<html lang="hr">
    <head>
        <title>Kategorije</title>
        <meta charset="UTF-8">
        <link href="css/thlevnjak.css" type="text/css" rel="stylesheet">
    </head>
    <body>
        <header>
            <a href="#sadrzaj"><h1>Kategorije cesta</h1></a>
        </header>
        <?php
            include_once 'meni.php';
        ?>
        <section id="sadrzaj"> 
            <table>
                <caption>Kategorije cesta</caption>
                <thead>
                    <tr>
                        <th>Moderatori</th>
                        <th>Naziv kategorije</th>
                        <th>Opis kategorije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $veza = new Baza();
                
                    $upit = "SELECT naziv, opis FROM kategorija_ceste ORDER BY 1";
                    $rezultat = $veza->upit($upit);
                    while ($redak = pg_fetch_array($rezultat)) {
                        echo "<tr>";
                        echo "<td>";
                        $upit2 = "SELECT k.korisnicko_ime FROM upravljanje u "
                                . "INNER JOIN kategorija_ceste kc ON kc.kategorija_ceste_id = u.kategorija_ceste_id "
                                . "INNER JOIN korisnik k ON u.korisnik_id = k.korisnik_id "
                                . "WHERE kc.naziv = '{$redak['naziv']}' AND k.korisnik_id > 1 AND k.uloga_id = '2'";
                        $rezultat2 = $veza->upit($upit2);
                        while ($redak2 = pg_fetch_array($rezultat2)) {
                            echo "<b><a href='kategorije_cesta.php?korisnicko_ime={$redak2['korisnicko_ime']}&naziv={$redak['naziv']}'>" . $redak2['korisnicko_ime'] . "</a><br>";
                        }
                        echo "</td>";
                        echo "<td><a href='kategorije_cesta.php?naziv={$redak['naziv']}'>" . $redak['naziv'] . "</a></td>";
                        echo "<td><a href='kategorije_cesta.php?naziv={$redak['naziv']}'>" . $redak['opis'] . "</a></td>";
                        echo"</tr>";
                    }
                    ?>
                </tbody>
            </table>
            <?php
                echo $GLOBALS['ispis'];
            ?> 
        </section>
    </body>
</html>
