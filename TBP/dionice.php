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

$ispis = null;

if (isset($_GET['oznaka']) && !isset($_GET['obilazak'])) {
    $oznaka = $_GET['oznaka'];

    $veza = new Baza();

    $korisnik = $_SESSION[Sesija::KORISNIK];

    $upit1 = "SELECT korisnik_id FROM korisnik WHERE korisnicko_ime = '{$korisnik}'";

    $rezultat1 = $veza->upit($upit1);
    $redak1 = pg_fetch_array($rezultat1);
    $ID_korisnika = $redak1["korisnik_id"];

    $upit = "SELECT c.cesta_id, c.stanje, kc.naziv "
            . "FROM cesta c "
            . "INNER JOIN kategorija_ceste kc ON c.kategorija_ceste_id = kc.kategorija_ceste_id "
            . "WHERE c.oznaka = '{$oznaka}'";
    $rezultat = $veza->upit($upit);

    $redak = pg_fetch_array($rezultat);
    $IDcesta = $redak['cesta_id'];

    $upit2 = "SELECT DISTINCT kc.naziv "
            . "FROM kategorija_ceste kc "
            . "INNER JOIN upravljanje u ON u.kategorija_ceste_id = kc.kategorija_ceste_id "
            . "WHERE u.korisnik_id = {$ID_korisnika}";
    $rezultat2 = $veza->upit($upit2);

    $veza->zatvoriVezu();

    $kategorije = array();
    $i = 0;
    while ($redak2 = pg_fetch_array($rezultat2)) {
        $kategorije[$i] = $redak2['naziv'];
        $i++;
    }

    $ispis = "<form name='odabir' id='odabir' method='get' action='dionice.php'>"
            . "<label>Odabrali ste dionicu <b>{$oznaka}</b></label>"
            . "<input name='oznaka' id='oznaka' type='hidden' value='{$oznaka}'/>";

    if (isset($_SESSION["uloga"]) && $_SESSION["uloga"] < 3 && !in_array($redak['naziv'], $kategorije)) {
        $ispis .= "<label>, nemate pravo za ažuriranje dionice pod kategorijom '<b>{$redak['naziv']}</b>'</label>";
    }
    if (isset($_SESSION["uloga"]) && $_SESSION["uloga"] < 3 && $redak['stanje'] === 'zatvorena') {
        $ispis .= "<label>, koja je zatvorena.</label><br>";
        if ($_SESSION["uloga"] < 3) $ispis .= "<input id='submit1' name='dodaj' type='submit' value='Dodaj novu dionicu' /><br>";
    }
    if (isset($_SESSION["uloga"]) && $_SESSION["uloga"] < 4 && $redak['stanje'] === 'otvorena') {
        $ispis .= "<br><input id='submit1' name='obilazak' type='submit' value='Dodaj novi obilazak' /><br>";
        $ispis .= "<input id='submit1' name='problem' type='submit' value='Prijavi problem' /><br>";
        if ($_SESSION["uloga"] < 3) $ispis .= "<input id='submit1' name='dodaj' type='submit' value='Dodaj novu dionicu' /><br>";
    }
    if (isset($_SESSION["uloga"]) && $_SESSION["uloga"] < 3 && in_array($redak['naziv'], $kategorije)) {
        $ispis .= "<input id='submit1' name='azuriraj' type='submit' value='Ažuriraj dionicu' /><br>";
        $ispis .= "<input name='cesta_id' id='cesta_id' type='hidden' value='{$IDcesta}'/>";
        $ispis .= "<input id='submit1' name='obrisi' type='submit' value='Izbriši dionicu' />";
    }
    $ispis .= "</form>";
}

if (!isset($_GET['oznaka']) && isset($_SESSION["uloga"]) && $_SESSION["uloga"] < 3) {
    $ispis = "<form novalidate name='odabir' id='odabir' method='get' action='dionice.php'>"
            . "<input id='submit1' name='dodaj' type='submit' value='Dodaj dionicu' /></form>";
}

if (isset($_GET['problem'])) {
    $oznaka = $_GET['oznaka'];
    $ispis = "<form novalidate name='odabir' id='odabir' method='get' action='dionice.php'>"
            . "<label>Odabrali ste dionicu <b>{$oznaka}</b></label>"
            . "<input name='oznaka' id='oznaka' type='hidden' value='{$oznaka}'/>"
            . "<label><br><br>Naziv problema: </label>"
            . "<input name='naziv' id='naziv' type='text'/><br>"
            . "<label>Opis problema: </label>"
            . "<input name='opis' id='opis' type='text'/><br>"
            . "<input id='submit1' name='prijaviproblem' type='submit' value='Prijavi problem' /></form>";
}

if (isset($_GET['prijaviproblem'])) {
    $veza = new Baza();
    
    $oznaka = $_GET['oznaka'];
    $upit = "SELECT cesta_id FROM cesta WHERE oznaka = '{$oznaka}'";
    $rezultat = $veza->upit($upit);
    $redak = pg_fetch_array($rezultat);
    $IDceste = $redak["cesta_id"];

    $korisnik = $_SESSION[Sesija::KORISNIK];

    $upit2 = "SELECT korisnik_id FROM korisnik WHERE korisnicko_ime = '{$korisnik}'";
    $rezultat2 = $veza->upit($upit2);
    $redak2 = pg_fetch_array($rezultat2);
    $IDkorisnika = $redak2["korisnik_id"];

    $upit3 = "INSERT INTO problem (naziv, opis, korisnik_id, cesta_id, datum_vrijeme) VALUES "
            . "('{$_GET["naziv"]}', "
            . "'{$_GET["opis"]}', "
            . "{$IDkorisnika}, "
            . "{$IDceste}, now());";
    $rezultat3 = $veza->upit($upit3);
    
    $veza->zatvoriVezu();
    
    if ($rezultat3 != null) {
        $ispis = "Prijava problema je uspješno poslana";
        header("Location: prijava_problema.php");
    }
    exit();
}

if (isset($_GET['azuriraj'])) {
    $ispis = "";
    $veza = new Baza();
    
    $oznaka = $_GET['oznaka'];

    $upit = "SELECT * FROM cesta WHERE oznaka = '{$oznaka}'";

    $rezultat = $veza->upit($upit);
    $redak = pg_fetch_array($rezultat);

    $korisnik = $_SESSION[Sesija::KORISNIK];

    $upit1 = "SELECT korisnik_id FROM korisnik WHERE korisnicko_ime = '{$korisnik}'";
    $rezultat1 = $veza->upit($upit1);

    $redak1 = pg_fetch_array($rezultat1);
    $IDkorisnika = $redak1["korisnik_id"];

    $upit2 = "SELECT DISTINCT kc.naziv, kc.kategorija_ceste_id "
            . "FROM kategorija_ceste kc "
            . "INNER JOIN upravljanje u ON u.kategorija_ceste_id = kc.kategorija_ceste_id "
            . "WHERE u.korisnik_id = {$IDkorisnika}";
    $rezultat2 = $veza->upit($upit2);

    $ispis = "<form name='odabir' id='odabir' method='get' action='dionice.php'>"
            . "<label>Odabrali ste dionicu <b>{$oznaka}</b><br><br></label>"
            . "<input name='oznaka' id='oznaka' type='hidden' value='{$oznaka}'/>"
            . "<label>Kategorija: </label>"
            . "<select name='kategorije' id='kategorije'>";
    while ($redak2 = pg_fetch_array($rezultat2)) {
        $ispis .= "<option value='{$redak2["naziv"]}' " . (($redak['kategorija_ceste_id'] === $redak2['kategorija_ceste_id']) ? "selected" : '') . ">{$redak2["naziv"]}</option>";
    }
    $ispis .= "</select><br>"
            . "<label>Naziv početka dionice: </label>"
            . "<input name='pocetak' id='pocetak' type='text' value='{$redak['pocetak_dionice']}'/><br>"
            . "<label>Naziv kraja dionice: </label>"
            . "<input name='kraj' id='kraj' type='text' value='{$redak['kraj_dionice']}'/><br>"
            . "<label>Duljina dionice: </label>"
            . "<input name='duljina' id='duljina' type='text' value='{$redak['duljina_dionice']}'/><br>"
            . "<label>Stanje odabrane dionice: <br></label>"
            . "<select name='stanje' id='stanje'>"
            . "<option value='otvorena' " . (($redak['stanje'] === 'otvorena') ? "selected" : '') . ">Otvorena</option>"
            . "<option value='zatvorena' " . (($redak['stanje'] === 'zatvorena') ? 'selected' : '') . ">Zatvorena</option>"
            . "</select><br>"
            . "<input id='submit1' name='azurirajkonacno' type='submit' value='Ažuriraj dionicu' /></form>";
    $veza->zatvoriVezu();
}

if (isset($_GET['azurirajkonacno'])) {
    $oznaka = $_GET['oznaka'];
    $veza = new Baza();
    
    $spoj = "SELECT kategorija_ceste_id FROM kategorija_ceste WHERE naziv = '{$_GET["kategorije"]}'";
    $rezultat = $veza->upit($spoj);
    var_dump($spoj);
    $redak = pg_fetch_array($rezultat);
    $upit = "UPDATE cesta SET "
            . "pocetak_dionice = '{$_GET["pocetak"]}', "
            . "kraj_dionice = '{$_GET["kraj"]}', "
            . "duljina_dionice = '{$_GET["duljina"]}', "
            . "stanje = '{$_GET["stanje"]}', "
            . "kategorija_ceste_id = {$redak['kategorija_ceste_id']} "
            . "WHERE oznaka = '{$oznaka}'";
    $rezultat = $veza->upit($upit);
    $veza->zatvoriVezu();

    if ($rezultat != null) {
        $ispis = "Dionica {$oznaka} uspješno ažurirana!";
    }
    header("Location: dionice.php?poruka={$ispis}");
    exit();
}

if (isset($_GET['dodaj'])) {
    $veza = new Baza();

    $korisnik = $_SESSION[Sesija::KORISNIK];
    
    $upit1 = "SELECT korisnik_id, uloga_id FROM korisnik WHERE korisnicko_ime = '{$korisnik}'";
    $rezultat1 = $veza->upit($upit1);
    $redak1 = pg_fetch_array($rezultat1);
    $IDkorisnika = $redak1["korisnik_id"];
    $uloga = $redak1["uloga_id"];

    if ($uloga == 1) {
        $upit2 = "SELECT naziv FROM kategorija_ceste";
    }
    else {
        $upit2 = "SELECT DISTINCT kc.naziv, k.uloga_id FROM korisnik k "
                . "INNER JOIN upravljanje u ON u.korisnik_id = k.korisnik_id "
                . "INNER JOIN kategorija_ceste kc ON u.kategorija_ceste_id = kc.kategorija_ceste_id "
                . "WHERE k.korisnik_id = {$IDkorisnika} ORDER BY 1";
    }
    $rezultat2 = $veza->upit($upit2);
    $ispis = "<form novalidate name='odabir' id='odabir' method='get' action='dionice.php'>"
            . "<label>Kategorija: </label>"
            . "<select name='kategorije' id='kategorije'>";
    while ($redak2 = pg_fetch_array($rezultat2)) {
        $ispis .= "<option value='{$redak2["naziv"]}'>{$redak2["naziv"]}</option>";
    }
    $ispis .= "</select>"
            . "<label><br>Oznaka dionice: </label>"
            . "<input name='oznaka' id='oznaka' type='text'/><br>"
            . "<label>Naziv početka dionice: </label>"
            . "<input name='pocetak' id='pocetak' type='text'/><br>"
            . "<label>Naziv kraja dionice: </label>"
            . "<input name='kraj' id='kraj' type='text'/><br>"
            . "<label>Broj kilometara: </label>"
            . "<input name='duljina' id='duljina' type='text'/><br>"
            . "<input id='submit1' name='dodajdionicu' type='submit' value='Dodaj dionicu' /></form>";
}

if (isset($_GET['dodajdionicu'])) {
    $oznaka = $_GET['oznaka'];
    $veza = new Baza();
    
    $upit = "SELECT kategorija_ceste_id FROM kategorija_ceste "
            . "WHERE naziv = '{$_GET["kategorije"]}'";
    
    $rezultat = $veza->upit($upit);
    $redak = pg_fetch_array($rezultat);

    $upit2 = "INSERT INTO cesta (cesta_id, oznaka, pocetak_dionice, kraj_dionice, duljina_dionice, stanje, kategorija_ceste_id) VALUES "
            . "(DEFAULT, "
            . "'{$_GET["oznaka"]}', "
            . "'{$_GET["pocetak"]}', "
            . "'{$_GET["kraj"]}', "
            . "{$_GET["duljina"]}, "
            . "'otvorena', "
            . "{$redak['kategorija_ceste_id']});";
    $rezultat2 = $veza->upit($upit2);
    $veza->zatvoriVezu();

    if ($rezultat2 != null) {
        $por = "Nova dionica uspješno dodana!";
    }
    else {
        $por = "Greška! Niste dodali novu dionicu!";
    }
    header("Location: dionice.php?poruka={$por}");
    exit();
}

if(isset($_GET['obrisi'])) {
    $IDcesta = $_GET['cesta_id'];

    $veza = new Baza();

    $upit = "DELETE FROM cesta WHERE cesta_id = {$IDcesta}";
    $rezultat = $veza->upit($upit);

    $veza->zatvoriVezu();
    if ($rezultat != null) {
        $por = "Dionica uspješno obrisana!";
    }
    header("Location: dionice.php?poruka={$por}");
    exit();
}

if (isset($_GET['poruka'])) {
    $ispis = $_GET['poruka'];
}

if (isset($_GET['obilazak'])) {
    $veza = new Baza();
    
    $oznaka = $_GET['oznaka'];
    $upit = "SELECT cesta_id FROM cesta WHERE oznaka = '{$oznaka}'";
    $rezultat = $veza->upit($upit);
    $redak = pg_fetch_array($rezultat);
    $IDceste = $redak["cesta_id"];

    $korisnik = $_SESSION[Sesija::KORISNIK];

    $upit2 = "SELECT korisnik_id FROM korisnik WHERE korisnicko_ime = '{$korisnik}'";
    $rezultat2 = $veza->upit($upit2);
    $redak2 = pg_fetch_array($rezultat2);
    $IDkorisnika = $redak2["korisnik_id"];

    $upit3 = "INSERT INTO obilazak (korisnik_id, cesta_id) VALUES ({$IDkorisnika}, {$IDceste})";
    $rezultat = $veza->upit($upit3);
    $veza->zatvoriVezu();

    header("Location: obilazak.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="hr">
    <head>
        <title>Dionice</title>
        <meta charset="UTF-8">
        <link href="css/thlevnjak.css" type="text/css" rel="stylesheet">
    </head>
    <body>
        <header>
            <a href="#sadrzaj"><h1>Dionice</h1></a>
        </header>
        <?php
            include_once 'meni.php';
        ?>
        <section id="sadrzaj">
            <table>
                <caption>Popis dionica</caption>
                <thead>
                    <tr>
                        <th>Početak dionice</th>
                        <th>Kraj dionice</th>
                        <th>Oznaka</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $veza = new Baza();
                    
                    $upit = "SELECT COUNT(*) AS broj FROM cesta";
                    $rezultat = $veza->upit($upit);
                    $red = pg_fetch_array($rezultat);
                    $veza->zatvoriVezu();
                    for ($i = 1; $i < $red['broj']; $i++) {
                        $veza2 = new Baza();
                        
                        $upit2 = "SELECT c.cesta_id, c.oznaka, c.pocetak_dionice, c.kraj_dionice, kc.naziv, kc.kategorija_ceste_id, c.stanje FROM cesta c INNER JOIN kategorija_ceste kc ON kc.kategorija_ceste_id = c.kategorija_ceste_id"
                                . " WHERE kc.kategorija_ceste_id = {$i}"
                                . " ORDER BY 2";
                        $rezultat2 = $veza2->upit($upit2);

                        while ($redak = pg_fetch_array($rezultat2)) {
                            echo "<tr>";
                            echo "<td>" . $redak['pocetak_dionice'] . "</td>";
                            echo "<td>" . $redak['kraj_dionice'] . "</td>";
                            if ((isset($_SESSION["uloga"]) && $_SESSION["uloga"] < 3) || (isset($_SESSION["uloga"]) && $redak['stanje'] === 'otvorena')) {
                                echo "<td><b><a href='dionice.php?oznaka={$redak['oznaka']}'>" . $redak['oznaka'] . "</a></b></td>";
                            }
                            else {
                                echo "<td>" . $redak['oznaka'] . "</td>";
                            }
                            echo"</tr>";
                        }
                        $veza2->zatvoriVezu();
                    }
                    ?>
                </tbody>
            </table>
            <div>
                <?php
                    echo $ispis;
                ?>
            </div>
            <br>
            <form name="dionice" id="dionice" method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <label for="pocetak">Unesite dionicu: </label>
                <input name="pocetak" id="pocetak" type="text" placeholder="Mjesto početka"/>
                <input name="zavrsetak" id="zavrsetak" type="text" placeholder="Mjesto završetka"/>
                <input type="submit" id="submit1" name="submit" value="Pretraži"/>
            </form>
            <br>
            
                    <?php
                    if (isset($_GET['submit'])) {
                        $veza = new Baza();
                        
                        $pocetak = $_GET['pocetak'];
                        $zavrsetak = $_GET['zavrsetak'];
                        $upit = "SELECT oznaka, pocetak_dionice, kraj_dionice, duljina_dionice FROM cesta WHERE pocetak_dionice LIKE '%{$pocetak}%' AND kraj_dionice LIKE '%{$zavrsetak}%' ORDER BY 1";
                        $rezultat = $veza->upit($upit);
                        
                        echo "<table><thead><tr>
                                <th>Oznaka</th>
                                <th>Početak dionice</th>
                                <th>Kraj dionice</th>
                                <th>Duljina dionice (u km)</th>
                            </tr></thead><tbody>";

                        while ($redak = pg_fetch_array($rezultat)) {
                            echo "<tr>";
                            if (isset($_SESSION["uloga"]) && $_SESSION["uloga"] < 4) {
                                echo "<td><a href='dionice.php?oznaka={$redak['oznaka']}'>" . $redak['oznaka'] . "</a></td>";
                            } else {
                                echo "<td>" . $redak['oznaka'] . "</td>";
                            }
                            echo "<td>" . $redak['pocetak_dionice'] . "</td>";
                            echo "<td>" . $redak['kraj_dionice'] . "</td>";
                            echo "<td>" . $redak['duljina_dionice'] . "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </section>
    </body>
</html>
