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

if (isset($_POST["submit"]) && !empty($_FILES['datoteka'])) {
    $datoteka = $_FILES['datoteka']['tmp_name'];
    $ime_datoteke = $_FILES['datoteka']['name'];
    $vrsta = pathinfo($ime_datoteke, PATHINFO_EXTENSION);
    $putanja = 'multimedija/' . $ime_datoteke;
    $cijelaPutanja = $direktorij .  '\\multimedija\\' . $ime_datoteke;

    if (is_uploaded_file($datoteka)) {
        if (!move_uploaded_file($datoteka, $cijelaPutanja)) {
            echo 'Problem: nije moguće prenijeti datoteku na odredište';
            exit;
        } else {
            $veza = new Baza();
            $korisnik = $_SESSION[Sesija::KORISNIK];
            
            $upit = "SELECT korisnik_id FROM korisnik WHERE korisnicko_ime = '{$korisnik}'";
            $rezultat = $veza->upit($upit);
            $redak = pg_fetch_array($rezultat);
            $IDkorisnika = $redak["korisnik_id"];

            $upit2 = "SELECT cesta_id FROM cesta WHERE oznaka = '{$_POST['oznaka']}'";
            $rezultat2 = $veza->upit($upit2);
            $redak2 = pg_fetch_array($rezultat2);
            
            $upit3 = "INSERT INTO dokumenti (dokument_id, ime, status, korisnik_id, cesta_id, vrsta, putanja, dokument) VALUES "
                    . "(DEFAULT,"
                    . "'{$ime_datoteke}',"
                    . "'nije potvrđen',"
                    . "{$IDkorisnika},"
                    . "{$redak2['cesta_id']},"
                    . "'{$vrsta}',"
                    . "'{$putanja}',"
                    . "lo_import('{$cijelaPutanja}'));";

            $rezultat3 = $veza->upit($upit3);
            
            $veza->zatvoriVezu();

            if ($rezultat != null) {
                $ispis = "Dokument {$ime_datoteke} uspješno uploadan!";
            }
            header("Location: dokumenti.php?poruka={$ispis}");
            exit();
        }
    }
}

$ispis = "";

if (isset($_GET['ime'])) {
    $ime = $_GET['ime'];
    $dokument = $_GET['dokument'];
    $IDdokument = $_GET['dokument_id'];
    $ispis = "<form name='status' id='status' method='get' action='dokumenti.php'>"
            . "<label>Ime dokumenta: {$ime}</label>"
            . "<input name='ime' id='ime' type='hidden' value='{$ime}'/>"
            . "<input name='dokument' id='dokument' type='hidden' value='{$dokument}'/>"
            . "<input name='dokument_id' id='dokument_id' type='hidden' value='{$IDdokument}'/>"
            . "<label><br><br>Status dokumenta: <br></label>"
            . "<select name='status' id='status'>"
            . "<option value='potvrđen' " . (($_GET['status'] === 'potvrđen') ? "selected" : '') . ">potvrđen</option>"
            . "<option value='odbijen' " . (($_GET['status'] === 'odbijen') ? 'selected' : '') . ">odbijen</option>"
            . "<option value='nije potvrđen' " . (($_GET['status'] === 'nije potvrđen') ? 'selected' : '') . ">nije potvrđen</option>"
            . "</select><br>";
    if ($_SESSION["uloga"] == 2) {
        $ispis .= "<input id='submit1' name='promijeni' type='submit' value='Promijeni status dokumenta' /></form>";
    }
    if ($_SESSION["uloga"] == 1) {
        $ispis .= "<input id='submit1' name='promijeni' type='submit' value='Promijeni status dokumenta' /><br>";
        $ispis .= "<input id='submit1' name='obrisi' type='submit' value='Obriši dokument' /></form>";
    }
}

if (isset($_GET['promijeni'])) {
    $ime = $_GET['ime'];
    $IDdokument = $_GET['dokument_id'];
    $status = $_GET['status'];

    $veza = new Baza();

    $upit = "UPDATE dokumenti SET status = '{$_GET["status"]}' WHERE dokument_id = '{$IDdokument}'";
    $rezultat = $veza->upit($upit);
    $veza->zatvoriVezu();
    
    if ($rezultat != null) {
        $ispis = "Status dokumenta " . $ime . " postavljen na " . $status;
    }
    header("Location: dokumenti.php?poruka={$ispis}");
    exit();
}

if (isset($_GET['obrisi'])) {
    $ime = $_GET['ime'];
    $IDdokument = $_GET['dokument_id'];
    $dokument = $_GET['dokument'];

    $veza = new Baza();

    $upit = "SELECT lo_unlink({$dokument}) FROM dokumenti";
    $rezultat = $veza->upit($upit);

    $upit2 = "DELETE FROM dokumenti WHERE dokument_id = {$IDdokument}";
    $rezultat2 = $veza->upit($upit2);
    $veza->zatvoriVezu();
    
    if ($rezultat2 != null) {
        $ispis = "Dokument " . $ime . " uspješno obrisan";
    }
    header("Location: dokumenti.php?poruka={$ispis}");
    exit();
}

if (isset($_GET['poruka'])) {
    $ispis = $_GET['poruka'];
}
?>

<!DOCTYPE html>
<html lang="hr">
    <head>
        <title>Dokumenti</title>
        <meta charset="UTF-8">
        <link href="css/thlevnjak.css" type="text/css" rel="stylesheet">
    </head>
    <body>
        <header>
            <a href="#sadrzaj"><h1>Dokumenti</h1></a>
        </header>
        <?php
            include_once 'meni.php';
        ?>
        <section id="sadrzaj">
            <table>
                <caption>Popis potvrđenih dokumenata</caption>
                <thead>
                    <tr>
                        <th>Naziv dokumenta</th>
                        <th>Sadržaj</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $veza = new Baza();

                        $preuzimanja = "Preuzimanja/";
                        $direktorij .=  "/" . $preuzimanja;

                        $upit = "SELECT ime, vrsta, lo_export (dokument, '{$direktorij}' || ime) FROM dokumenti WHERE status = 'potvrđen' ORDER BY vrsta";
                        $rezultat = $veza->upit($upit);
                        while ($redak = pg_fetch_array($rezultat)) {
                            $vrsta = $redak['vrsta'];
                            echo "<tr>";
                            echo "<td>{$redak['ime']}</td>";
                            if ($vrsta == 'png' || $vrsta == 'jpeg' || $vrsta == 'jpg' || $vrsta == 'gif' || $vrsta == 'tiff') {
                                echo "<td><img src='{$preuzimanja}{$redak['ime']}' alt='greska' style='width:20%'></td>";
                            }
                            else {
                                echo "<td>Nije moguće vidjeti!</td>";
                            }
                            echo"</tr>";
                        }
                        $veza->zatvoriVezu();
                    ?>
                </tbody>
            </table>
            <form name="obrazac" method="post" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <label>Odaberite dionicu za koju želite staviti dokument: <br></label>
                <select name='oznaka' id='oznaka'>
                    <?php
                    $veza = new Baza();
    
                    $upit = "SELECT oznaka FROM cesta ORDER BY kategorija_ceste_id, oznaka";
                    $rezultat = $veza->upit($upit);
                    while ($redak = pg_fetch_array($rezultat)) {
                        echo "<option value='{$redak["oznaka"]}'>{$redak["oznaka"]}</option>";
                    }
                    ?>
                </select>
                <br>
                <label for="datoteka">Odaberite datoteku: </label>
                <input type="file" name="datoteka" /><br>
                <input id="submit1" name="submit" type="submit" value="Prenesi datoteku"/>
            </form>
        </section>
        <br>
        <?php
            if (isset($_SESSION["uloga"]) && $_SESSION["uloga"] < 3) {
                echo "<table>
                    <caption>Popis svih dokumenata</caption>
                    <thead>
                        <tr>
                            <th>Oznaka dionice</th>
                            <th>Ime dokumenta</th>
                            <th>Status dokumenta</th>
                        </tr>
                    </thead>
                    <tbody>";

                $veza = new Baza();
                $upit = "SELECT c.oznaka, d.dokument_id, d.ime, d.status, d.dokument FROM dokumenti d INNER JOIN cesta c ON d.cesta_id = c.cesta_id ORDER BY d.status DESC, c.oznaka";
                $rezultat = $veza->upit($upit);
                while ($redak = pg_fetch_array($rezultat)) {
                    $oznaci = "";
                    if ($redak['status'] === 'odbijen') {
                        $oznaci = "style='color: red;'";
                    } elseif ($redak['status'] === 'nije potvrđen') {
                        $oznaci = "style='color: purple;'";
                    } else {
                        $oznaci = "style='color: green;'";
                    }
                    echo "<tr>";
                    echo "<td><a {$oznaci} href='dokumenti.php?dokument_id={$redak['dokument_id']}&status={$redak['status']}&ime={$redak['ime']}&dokument={$redak['dokument']}'>{$redak['oznaka']}</a></td>";
                    echo "<td><a {$oznaci} href='dokumenti.php?dokument_id={$redak['dokument_id']}&status={$redak['status']}&ime={$redak['ime']}&dokument={$redak['dokument']}'>{$redak['ime']}</a></td>";
                    echo "<td><a {$oznaci} href='dokumenti.php?dokument_id={$redak['dokument_id']}&status={$redak['status']}&ime={$redak['ime']}&dokument={$redak['dokument']}'>{$redak['status']}</a></td>";
                    echo"</tr>";
                }
            }
        ?>
    </tbody>
</table>
<br>
<?php
    echo $ispis;
?>
</body>
</html>