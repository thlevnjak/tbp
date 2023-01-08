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

if (isset($_GET['oznaka']) && isset($_GET['obilazak_id']) && !isset($_GET['brisi'])) {
    $ispis = "<br>Odabrali ste obilazak za dionicu {$_GET['oznaka']}"
            . "<form name='obrisi' id='obrisi' method='get' action='obilazak.php'>"
            . "<input name='obilazak_id' id='obilazak_id' type='hidden' value='{$_GET["obilazak_id"]}'/>"
            . "<input type='submit' id='submit' name='brisi' value='Obrisi obilazak' />"
            . "</form>";
}

if (!isset($_GET['obilazak_id']) && !isset($_GET['brisi'])) {
    $ispis = "";
}

if (isset($_GET['brisi'])) {
    $obilazak_id = $_GET['obilazak_id'];
    $veza = new Baza();

    $upit = "DELETE FROM obilazak WHERE obilazak_id = {$obilazak_id}";

    $rezultat = $veza->upit($upit);

    $veza->zatvoriVezu();

    if ($rezultat != null) {
        $ispis = "Obilazak uspješno obrisan!";
    }
    header("Location: obilazak.php?poruka={$ispis}");
    exit();
}

if (isset($_GET['poruka'])) {
    $ispis = $_GET['poruka'];
}
?>

<!DOCTYPE html>
<html lang="hr">
    <head>
        <title>Obilazak</title>
        <meta charset="UTF-8">
        <link href="css/thlevnjak.css" type="text/css" rel="stylesheet">
    </head>
    <body>
        <header>
            <a href="#sadrzaj"><h1>Obilasci</h1></a>
        </header>
        <?php
            include_once 'meni.php';
        ?>  
        <section id="sadrzaj"> 
            <table>
                <caption>Lista obilazaka</caption>
                <thead>
                    <tr>
                        <th>Oznaka</th>
                        <th>Početak dionice</th>
                        <th>Kraj dionice</th>
                        <th>Duljina dionice</th>
                        <th>Obilazak dodan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $veza = new Baza();
                    
                    $upit = "SELECT o.obilazak_id, o.datum_vrijeme, c.oznaka, c.pocetak_dionice, c.kraj_dionice, c.duljina_dionice "
                            . "FROM cesta c "
                            . "INNER JOIN obilazak o ON c.cesta_id = o.cesta_id "
                            . "INNER JOIN korisnik k ON o.korisnik_id=k.korisnik_id "
                            . "WHERE k.korisnicko_ime = '{$_SESSION[Sesija::KORISNIK]}'";

                    $rezultat = $veza->upit($upit);

                    while ($row = pg_fetch_array($rezultat)) {
                        echo "<tr>";
                        echo "<td><b><a href='obilazak.php?obilazak_id={$row['obilazak_id']}&oznaka={$row['oznaka']}'>" . $row['oznaka'] . "</a></b></td>";
                        echo "<td>" . $row['pocetak_dionice'] . "</td>";
                        echo "<td>" . $row['kraj_dionice'] . "</td>";
                        echo "<td>" . $row['duljina_dionice'] . "</td>";
                        echo "<td>" . $row['datum_vrijeme'] . "</td>";
                        echo"</tr>";
                    }

                    $upit2 = "SELECT SUM(c.duljina_dionice) AS prijedjeno FROM "
                            . "cesta c "
                            . "INNER JOIN obilazak o ON c.cesta_id = o.cesta_id "
                            . "INNER JOIN korisnik k ON o.korisnik_id=k.korisnik_id "
                            . "WHERE k.korisnicko_ime = '{$_SESSION[Sesija::KORISNIK]}'";

                    $rezultat2 = $veza->upit($upit2);

                    $row = pg_fetch_array($rezultat2);
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <?php
                            echo"<td>Ukupno prijeđeno: " . $row['prijedjeno'] . " kilometara</td>";
                        ?>
                    </tr>
                </tfoot>
            </table>            
            <?php
                echo $ispis;
            ?>
        </section>
    </body>
</html>