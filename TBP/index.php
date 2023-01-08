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

if (isset($_GET["napravi"])) {
    header("Location: kopija.php?napravi");
}

?>

<!DOCTYPE html>
<html lang="hr">
    <head>
        <title>Početna stranica</title>
        <meta charset="UTF-8">
        <link href="css/thlevnjak.css" type="text/css" rel="stylesheet">
    </head>
    <body>
        <header>
            <a href="#sadrzaj"><h1>Promet</h1></a>
        </header>
		<?php
			include_once 'meni.php';
		?>
        <section id="sadrzaj">
            <table id="tablica">
                <caption>Problemi</caption>
                <thead>
                    <tr>
                        <th>Broj evidentiranih problema</th>
                        <th>Kategorija ceste</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $veza = new Baza();

                    $brojKategorija = "SELECT COUNT(*) AS broj FROM kategorija_ceste";
                    $rez = $veza->upit($brojKategorija);
					$red = pg_fetch_array($rez);

                    for ($i = 1; $i <= $red['broj']; $i++) {
                        $upit = "SELECT k.naziv, COUNT(p.problem_id) AS broj_evidentiranih_problema FROM problem p "
                                . "INNER JOIN cesta c ON p.cesta_id = c.cesta_id "
                                . "INNER JOIN kategorija_ceste k ON c.kategorija_ceste_id = k.kategorija_ceste_id "
                                . "WHERE k.kategorija_ceste_id = {$i} "
								. "GROUP BY 1";

                        $rezultat = $veza->upit($upit);
						$redak = pg_fetch_array($rezultat);
						
                        if ($redak['broj_evidentiranih_problema']??='0' != 0) {
                            echo "<tr>";
                            echo "<td>" . $redak['broj_evidentiranih_problema'] . "</td>";
                            echo "<td>" . $redak['naziv'] . "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
            <div>
                <form name="kopija" id="kopija" method="get" action="index.php">
                    <?php
                    if (isset($_SESSION["uloga"]) && $_SESSION["uloga"] === '1') {
                        echo "<input type='submit' id='submit' name='napravi' value='Napravi sigurnosnu kopiju' style='margin: 0; position: absolute; left: 40%;'/>";
                    }
                    ?>
                </form>
            </div>
        </section>
        <br>
    </body>
</html>