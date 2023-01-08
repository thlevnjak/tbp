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
    header("Location: obrasci/prijava.php");
    exit();
} elseif (isset($_SESSION["uloga"]) && $_SESSION["uloga"] === '3') {
    header("Location: index.php");
    exit();
}

$veza = new Baza();

$ispis = "";

$upit = "SELECT c.oznaka, p.problem_id, p.naziv, p.opis, p.datum_vrijeme "
        . "FROM problem p "
        . "INNER JOIN cesta c ON p.cesta_id = c.cesta_id "
        . "INNER JOIN korisnik k ON p.korisnik_id = k.korisnik_id "
        . "ORDER BY 2";
$rezultat = $veza->upit($upit);

if (isset($_GET['oznaka']) && !isset($_GET['spremi'])) {
    $oznaka = $_GET['oznaka'];

    $veza = new Baza();

    $upit2 = "SELECT stanje FROM cesta WHERE oznaka='{$oznaka}'";
    $rezultat2 = $veza->upit($upit2);
    $row = pg_fetch_array($rezultat2);
    $GLOBALS['ispis'] = "<form name='moderator' id='moderator' method='get' action='prijava_problema.php'>"
            . "<label>Odabrana dionica: <b>{$oznaka}</b></label>"
            . "<input name='oznaka' id='oznaka' type='hidden' value='{$oznaka}'/>"
            . "<label><br><br>Promjeni stanje dionice na: </label>"
            . "<select name='stanje' id='stanje'>"
            . "<option value='otvorena' " . (($row['stanje'] === 'zatvorena') ? "selected" : '') . ">otvorena</option>"
            . "<option value='zatvorena' " . (($row['stanje'] === 'otvorena') ? 'selected' : '') . ">zatvorena</option>"
            . "</select><br>"
            . "<input id='submit' name='spremi' type='submit' value='Spremi' />"
            . "</form>";
    $veza->zatvoriVezu();
}

if (isset($_GET['problem_id']) && !isset($_GET['obrisi'])) {
    $IDproblem = $_GET['problem_id'];
    $GLOBALS['ispis'] = "<form name='moderator' id='moderator' method='get' action='prijava_problema.php'>"
            . "<label>Odabrani problem: <b>{$IDproblem}</b></label><br>"
            . "<input name='problem_id' id='problem_id' type='hidden' value='{$IDproblem}'/>"
            . "<input id='obrisi' name='obrisi' type='submit' value='Obriši problem' />"
            . "</form>";
}

if(isset($_GET['obrisi'])) {
    $IDproblem = $_GET['problem_id'];

    $veza = new Baza();

    $upit = "DELETE FROM problem WHERE problem_id = {$IDproblem}";
    $rezultat = $veza->upit($upit);

    if ($rezultat != null) {
        $GLOBALS['ispis'] = "Problem obrisan!";
    }

    $veza->zatvoriVezu();
    header("Location: prijava_problema.php?poruka={$GLOBALS['ispis']}");
    exit();
}

if (isset($_GET['spremi'])) {
    $oznaka = $_GET['oznaka'];
    $stanje = $_GET['stanje'];
    $veza = new Baza();
    
    $row = pg_fetch_array($rezultat);
    $upit = "UPDATE cesta SET stanje = '{$stanje}' WHERE oznaka = '{$oznaka}'";
    $rezultat = $veza->upit($upit);
    
    if ($rezultat != null && $stanje == 'otvorena') {
        $GLOBALS['ispis'] = "Cesta {$oznaka} je otvorena!";
    }
    elseif ($rezultat != null && $stanje == 'zatvorena') {
        $GLOBALS['ispis'] = "Cesta {$oznaka} je zatvorena!";
    }

    $veza->zatvoriVezu();
    
    header("Location: prijava_problema.php?poruka={$GLOBALS['ispis']}");
    exit();
}

if (isset($_GET['poruka'])) {
    $GLOBALS['ispis'] = $_GET['poruka'];
}
?>

<!DOCTYPE html>
<html lang="hr">
    <head>
        <title>Problemi</title>
        <meta charset="UTF-8">
        <link href="css/thlevnjak.css" type="text/css" rel="stylesheet">
    </head>
    <body>
        <header>
            <a href="#sadrzaj"><h1>Prijava problema</h1></a>
        </header>
        <?php
            include_once 'meni.php';
        ?>
        <section id="sadrzaj">
            <table>
                <caption>Popis svih prijavljenih problema</caption>
                <thead>
                    <tr>
                        <th>Broj</th>
                        <th>Oznaka dionice</th>
                        <th>Naziv problema</th>
                        <th>Opis problema</th>
                        <th>Datum prijave</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = pg_fetch_array($rezultat)) {
                        echo "<tr>";
                        echo "<td><a href='prijava_problema.php?problem_id={$row['problem_id']}'>" . $row['problem_id'] . "</a></td>";
                        echo "<td><b><a href='prijava_problema.php?oznaka={$row['oznaka']}'>" . $row['oznaka'] . "</a></td>";                        
                        echo "<td><a href='prijava_problema.php?problem_id={$row['problem_id']}'>" . $row['naziv'] . "</a></td>";
                        echo "<td><a href='prijava_problema.php?problem_id={$row['problem_id']}'>" . $row['opis'] . "</a></td>";
                        echo "<td><a href='prijava_problema.php?problem_id={$row['problem_id']}'>" . $row['datum_vrijeme'] . "</a></td>";
                        echo"</tr>";
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                    </tr>
                </tfoot>
            </table>
            <br>
            <?php
                echo $GLOBALS['ispis'];
            ?>  
        </section>
    </body>
</html>
