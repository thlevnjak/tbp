<?php

if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
    header("Location: http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], true, 301);
    exit;
}

$putanja = dirname($_SERVER['REQUEST_URI'], 2);
$direktorij = dirname(getcwd());

require "$direktorij/baza.php";
require "$direktorij/sesija.class.php";

Sesija::kreirajSesiju();

$ispis = "";

if (isset($_GET['korisnicko_ime']) && $_GET['korisnicko_ime'] != $_SESSION[Sesija::KORISNIK] && !isset($_GET['spremi'])) {
    $veza = new Baza();
    
    $upit = "SELECT korisnicko_ime, status FROM korisnik WHERE korisnicko_ime='{$_GET['korisnicko_ime']}'";
    $rezultat = $veza->upit($upit);
    $redak = pg_fetch_array($rezultat);
    
    $ispis = "<form name='forma' id='forma' method='get' action='korisnici.php'>"
            . "<label>Odabran korisnik <b>{$redak['korisnicko_ime']}</b></label>"
            . "<input name='korisnicko_ime' id='korisnicko_ime' type='hidden' value='{$redak['korisnicko_ime']}'/>"
            . "<label><br><br>Novi status: <br></label>"
            . "<select name='status' id='status'>"
            . "<option value='aktivan' " . (($redak['status'] === 'blokiran') ? 'selected' : '') . ">Otključaj</option>"
            . "<option value='blokiran' " . (($redak['status'] === 'aktivan') ? "selected" : '') . ">Zaključaj</option>"
            . "</select><br>"
            . "<input id='submit1' name='spremi' type='submit' value='Spremi promjene' />"
            . "</form>";

    $veza->zatvoriVezu();
}

if (isset($_GET['spremi'])) {
    $veza = new Baza();
    
    $upit = "UPDATE korisnik SET status = '{$_GET["status"]}' WHERE korisnicko_ime = '{$_GET["korisnicko_ime"]}'";
    $rezultat = $veza->upit($upit);
    
    $veza->zatvoriVezu();
    
    if ($rezultat != null && $_GET['status'] === 'blokiran') {
        $ispis = "Korisnik {$_GET["korisnicko_ime"]} uspješno zaključan!";
    } 
    elseif ($rezultat != null && $_GET['status'] === 'aktivan') {
        $ispis = "Korisnik {$_GET["korisnicko_ime"]} uspješno otključan!";
    }
    header("Location: korisnici.php?poruka={$ispis}");
    exit();
}

if (isset($_GET['uloga_id']) && $_SESSION[Sesija::KORISNIK] != $_GET['korisnik'] && isset($_GET['korisnik']) && !isset($_GET['azuriraj'])) 
{
    $veza = new Baza();
    
    $upit = "SELECT uloga_id, korisnicko_ime FROM korisnik WHERE uloga_id='{$_GET['uloga_id']}' AND korisnicko_ime='{$_GET['korisnik']}'";
    $rezultat = $veza->upit($upit);
    $redak = pg_fetch_array($rezultat);
    
    $ispis = "<form name='forma2' id='forma2' method='get' action='korisnici.php'>"
            . "<label>Odabran korisnik <b>{$redak['korisnicko_ime']}</b></label>"
            . "<input name='uloga_id' id='uloga_id' type='hidden' value='{$redak['uloga_id']}'/>"
            . "<input name='korisnik' id='korisnik' type='hidden' value='{$redak['korisnicko_ime']}'/>"
            . "<label><br><br>Uloga: <br></label>"
            . "<select name='uloga' id='uloga'>"
            . "<option value='1' " . (($redak['uloga_id'] === '1') ? 'selected' : '') . ">Administrator</option>"
            . "<option value='2' " . (($redak['uloga_id'] === '2') ? "selected" : '') . ">Moderator</option>"
            . "<option value='3' " . (($redak['uloga_id'] === '3') ? "selected" : '') . ">Prometnik</option>"
            . "</select><br>"
            . "<input id='submit1' name='azuriraj' type='submit' value='Promijeni ulogu' />"
            . "</form>";
    
    $veza->zatvoriVezu();
}

if (isset($_GET['azuriraj'])) 
{
    $veza = new Baza();

    $upit = "UPDATE korisnik SET uloga_id = '{$_GET['uloga']}' WHERE uloga_id = '{$_GET["uloga_id"]}' AND korisnicko_ime = '{$_GET["korisnik"]}'";
    $rezultat = $veza->upit($upit);
    
    $upit2 = "SELECT korisnik_id FROM korisnik WHERE korisnicko_ime = '{$_GET["korisnik"]}'";
    $rezultat2 = $veza->upit($upit2);
    $redak2 = pg_fetch_array($rezultat2);

    $upit3 = "SELECT COUNT(*) as broj FROM kategorija_ceste";
    $rezultat3 = $veza->upit($upit3);
    $redak3 = pg_fetch_array($rezultat3);

    if ($_GET['uloga'] == 1) {
        for ($i=1; $i <= $redak3['broj']; $i++) { 
            $upit3 = "INSERT INTO upravljanje (korisnik_id, kategorija_ceste_id) VALUES ({$redak2['korisnik_id']}, {$i})";
            $rezultat3 = $veza->upit($upit3);
        }        
    }
    if ($_GET['uloga'] > 1) {
        $upit3 = "DELETE FROM upravljanje WHERE korisnik_id = {$redak2['korisnik_id']}";
        $rezultat3 = $veza->upit($upit3);
    }
    
    $veza->zatvoriVezu();
    
    if ($rezultat != null && $_GET['uloga'] === '1') 
    {
        $ispis = "Korisniku {$_GET['korisnik']} dodijeljena uloga administratora!";
    } 
    elseif ($rezultat != null && $_GET['uloga'] === '2') 
    {
        $ispis = "Korisniku {$_GET['korisnik']} dodijeljena uloga moderatora!";
    } 
    elseif ($rezultat != null && $_GET['uloga'] === '3') 
    {
        $ispis = "Korisniku {$_GET['korisnik']} dodijeljena uloga prometnika!";
    }
    header("Location: korisnici.php?poruka={$ispis}");
    exit();
}

if (isset($_GET['poruka'])) 
{
    $ispis = $_GET['poruka'];
}
?>

<!DOCTYPE html>
<html lang="hr">
    <head>
        <title>Korisnici</title>
        <meta charset="UTF-8">
        <link href="../css/thlevnjak.css" type="text/css" rel="stylesheet">
    </head>
    <body>
        <header>
            <a href="#sadrzaj"><h1>Popis korisnika</h1></a>
        </header>
        <?php
            include_once '../meni.php';
        ?>
        <section id="sadrzaj">
            <table>
                <caption>Popis korisnika</caption>
                <thead>
                    <tr>
                        <th>Korisničko ime</th>
                        <th>Ime</th>
                        <th>Prezime</th>
                        <?php
                            if (isset($_SESSION["uloga"]) && $_SESSION["uloga"] === '1') {
                                echo "<th>Lozinka</th>";
                                echo "<th>Uloga</th>";
                                echo "</tr>";
                                echo "</thead>";
                                echo "</tbody>";
                            }
                        ?>
                    <?php
                    $veza = new Baza();
                    
                    $upit = "SELECT * FROM korisnik k INNER JOIN uloga u ON k.uloga_id = u.uloga_id";
                    $rezultat = $veza->upit($upit);
                    while ($redak = pg_fetch_array($rezultat)) 
                    {
                        echo "<tr>";
                        if (isset($_SESSION["uloga"]) && $_SESSION["uloga"] === '1') 
                        {                            
                            echo "<td><b><a href='korisnici.php?korisnicko_ime={$redak['korisnicko_ime']}'>" . $redak['korisnicko_ime'] . "</a></b></td>";
                            echo "<td><a href='korisnici.php?korisnicko_ime={$redak['korisnicko_ime']}'>" . $redak['ime'] . "</a></td>";
                            echo "<td><a href='korisnici.php?korisnicko_ime={$redak['korisnicko_ime']}'>" . $redak['prezime'] . "</a></td>";
                            echo "<td><a href='korisnici.php?korisnicko_ime={$redak['korisnicko_ime']}'>" . $redak['lozinka'] . "</a></td>";
                            echo "<td><u><a href='korisnici.php?uloga_id={$redak['uloga_id']}&korisnik={$redak['korisnicko_ime']}'>" . $redak['vrsta_uloge'] . "</a></u></td>";
                        } 
                        else 
                        {
                            echo "<td>" . $redak['korisnicko_ime'] . "</td>";
                            echo "<td>" . $redak['ime'] . "</td>";
                            echo "<td>" . $redak['prezime'] . "</td>";
                        }
                        echo"</tr>";
                    }
                    ?>
                </tbody>
            </table>
            <br>
        <div>
            <?php
                echo $ispis;
            ?>
        </div>
    </body>
</html>