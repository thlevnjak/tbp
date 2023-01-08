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

if (isset($_SESSION["uloga"])) {
    Sesija::obrisiSesiju();
}

$greska = "";
$poruka = "";
$neuspjesnePrijave = 0;

if (isset($_GET['prijava'])) 
{
    $GLOBALS['greska'] = "";
    foreach ($_GET as $k => $v) 
    {
        if (empty($v) && $k !== "prijava") {
            $GLOBALS['greska'] .= "Nije popunjeno: " . $k . "<br>";
        }
    }
    if (empty($GLOBALS['greska'])) 
    {
        $veza = new Baza();

        $korisnickoime = $_GET['korisnickoime'];
        $lozinka = $_GET['lozinka'];
        
        $upit = "SELECT * FROM korisnik WHERE korisnicko_ime = '{$korisnickoime}'";
        $rezultat = $veza->upit($upit);
        $redak = pg_fetch_array($rezultat);

        if($redak == false) {
            $greska = "Greska";
            header("Location: prijava.php?greska={$greska}");
            echo $greska;
        }
        
        $GLOBALS['neuspjesnePrijave'] = $redak['br_neuspjesnih_prijava'];
        
        if ($korisnickoime === $redak['korisnicko_ime'] && $lozinka === $redak['lozinka'] && $redak['status'] === 'aktivan') 
        {
            $upitZaUspjesnePrijave = "UPDATE korisnik SET br_neuspjesnih_prijava = '0' WHERE korisnicko_ime = '{$korisnickoime}'";
            $rezUspjesne = $veza->upit($upitZaUspjesnePrijave);
            Sesija::kreirajKorisnika($korisnickoime, $redak['uloga_id']);
            header("Location: ../index.php");
        }         
        elseif ($korisnickoime === $redak['korisnicko_ime'] && $lozinka === $redak['lozinka'] && $redak['status'] === 'blokiran') 
        {
            $greska = "Vaš račun je blokiran!";
        }         
        elseif ($korisnickoime === $redak['korisnicko_ime'] && $lozinka !== $redak['lozinka'] && $redak['status'] === 'aktivan') 
        {
            $GLOBALS['neuspjesnePrijave'] = $GLOBALS['neuspjesnePrijave'] + 1;
            if (3 - $GLOBALS['neuspjesnePrijave'] == 0) 
            {
                $greska = "Kriva lozinka! Nemate više pokušaja i Vaš račun je blokiran!";
            }
            else 
            {
                $greska = "Kriva lozinka! Još imate " . (3 - $GLOBALS['neuspjesnePrijave']) . " pokušaja!";
            }

            $upitZaNeuspjesnePrijave = "UPDATE korisnik SET br_neuspjesnih_prijava = '{$GLOBALS['neuspjesnePrijave']}' WHERE korisnicko_ime = '{$korisnickoime}'";
            $rezNeuspjesne = $veza->upit($upitZaNeuspjesnePrijave);
            
            if($GLOBALS['neuspjesnePrijave'] == 3) 
            {
                $upitBlokiraj = "UPDATE korisnik SET status = 'blokiran', br_neuspjesnih_prijava = '0' WHERE korisnicko_ime = '{$korisnickoime}'";
                $rezultatZakljucaj = $veza->upit($upitBlokiraj);
            }
        }         
        elseif ($korisnickoime === $redak['korisnicko_ime'] && $lozinka !== $redak['lozinka'] && $redak['status'] === 'blokiran') 
        {
            $greska = "Vaš račun je blokiran!";
        }
        elseif ($korisnickoime !== $redak['korisnicko_ime']) 
        {
            $greska = "Ne postoji korisnik s tim korisničkim imenom!";
        }

        $veza->zatvoriVezu();
    }
}

if(isset($_GET['greska'])) {
    $greska = "Ne postoji taj korisnik!";
}

?>

<!DOCTYPE html>
<html lang="hr">
    <head>
        <title>Prijava</title>
        <meta charset="UTF-8">
        <link href="../css/thlevnjak.css" type="text/css" rel="stylesheet">
    </head>
    <body>
        <header>
            <a href="#sadrzaj"><h1>Prijava</h1></a>
        </header>
        <?php
            include_once '../meni.php';
        ?>
        <section id="sadrzaj">
            <form action="">
                <h2 style="text-align: center;padding-bottom: 2%">Prijavite se</h2>
                <div style="padding-left: 37%">
                    <label for="korisnickoime"><b>Korisničko ime</b></label>
                    <input type="text" placeholder="Vaše korisničko ime..." name="korisnickoime" id="korisnickoime" autofocus><br>
                    <label for="lozinka"><b>Lozinka</b></label>
                    <input type="password" placeholder="Vaša lozinka" name="lozinka" id="lozinka"><br>
                    <button type="submit" name="prijava">Prijava</button>
                    <button type="reset" style="background-color: red">Inicijaliziraj</button><br><br>
                </div>
            </form>
            <div id="greske" style="color:red;">
                <?php
                    echo $greska;
                ?>
            </div>
        </section>
    </body>
</html>
