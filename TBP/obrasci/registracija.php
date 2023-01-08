<?php

if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
    header("Location: http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], true, 301);
    exit;
}

$putanja = dirname($_SERVER['REQUEST_URI'], 2);
$direktorij = dirname(getcwd());

require "$direktorij/baza.php";
require "$direktorij/sesija.class.php";

$greska = "";

if (isset($_GET['registracija'])) 
{
    $GLOBALS['greska'] = "";

    if ($_GET['lozinka'] != $_GET['ponovljenalozinka']) {
        $greska.= "Lozinka i ponovljena lozinka se ne podudaraju!<br>";
    }
    
    if(isset($_GET['korisnickoime']))
    {
        $poruka = "";
        $veza = new Baza();
        $username  = $_GET["korisnickoime"];
        $upit = "SELECT * FROM korisnik WHERE korisnicko_ime  = '$username'";
        $rezultat = $veza->upit($upit);
        
        if(pg_num_rows($rezultat) > 0) 
        {
            $greska.= "Upisano korisničko ime je već zauzeto!<br>";
        }         
        else 
        {
            $poruka = "Možete se registrirati s tim korisničkim imenom!<br>";
        }
        
        $veza->zatvoriVezu();
    }
    
    if (empty($GLOBALS['greska'])) 
    {
        $veza = new Baza();

        $din_sol = "{$_GET['korisnickoime']}";

        $ime = $_GET['ime'];
        $prezime = $_GET['prezime'];
        $korisnickoime = $_GET['korisnickoime'];
        $lozinka_sha256 = hash("sha256", $din_sol . "." . $_GET['lozinka']);
        $lozinka = $_GET['lozinka'];
        $adresa = array();
        $adresa[0] = $_GET['ulica'];
        $adresa[1] = $_GET['kucni_br'];
        $adresa[2] = $_GET['post_br'];
        $adresa[3] = $_GET['grad'];
        $adresa[4] = $_GET['drzava'];

        $upit = "INSERT INTO korisnik (ime, prezime, korisnicko_ime, lozinka, lozinka_sha256, uloga_id, adresa, status) VALUES ('{$ime}', '{$prezime}', '{$korisnickoime}', '{$lozinka}', '{$lozinka_sha256}', '3', ROW('{$adresa[0]}', '{$adresa[1]}', '{$adresa[2]}', '{$adresa[3]}', '{$adresa[4]}'),'aktivan')";

        $rezultat = $veza->upit($upit);

        Sesija::kreirajKorisnika($_GET['korisnickoime'], '3');
        header("Location: ../index.php");

        $veza->zatvoriVezu();
    }
}
?>
<!DOCTYPE html>
<html lang="hr">
    <head>
        <title>Registracija</title>
        <meta charset="UTF-8">
        <link href="../css/thlevnjak.css" type="text/css" rel="stylesheet">
    </head>
    <body>
        <header>
            <a href="#sadrzaj"><h1>Registracija</h1></a>
        </header>
        <?php
            include_once '../meni.php';
        ?>
        <section id="sadrzaj">
            <div id="provjeri"></div>
            <form id="forma" method="get" action="">
                <h2 style="text-align: center;padding-bottom: 2%">Registrirajte se</h2>
                <div style="margin-left: 36%">
                    <label for="ime"><b>Ime</b></label>
                    <input type="text" placeholder="Ime..." name="ime" id="ime" minlength="2" maxlength="30" autofocus><br>
                    <label for="prezime"><b>Prezime</b></label>
                    <input type="text" placeholder="Prezime..." name="prezime" minlength="2" maxlength="30" id="prezime"><br>
                    <label for="korisnickoime"><b>Korisničko ime</b></label>
                    <input type="text" placeholder="Korisničko ime..." name="korisnickoime" id="korisnickoime" required><br>
                    <label for="adresa"><b>Adresa</b></label>
                    <input type="text" placeholder="Ulica..." name="ulica" id="ulica">
                    <input type="text" placeholder="Kućni broj..." name="kucni_br" id="kucni_br"><br>
                    <input type="text" placeholder="Poštanski broj..." name="post_br" id="post_br">
                    <input type="text" placeholder="Grad..." name="grad" id="grad">
                    <input type="text" placeholder="Država..." name="drzava" id="drzava"><br>
                    <label for="lozinka"><b>Lozinka</b></label>
                    <input type="password" placeholder="Vaša lozinka..." name="lozinka" id="lozinka" minlength="8" maxlength="30"><br>
                    <label for="plozinka"><b>Ponovite lozinku</b></label>
                    <input type="password" placeholder="Ponovite lozinku..." name="ponovljenalozinka" id="ponovljenalozinka" required><br>

                    <button type="submit" name="registracija">Registriraj se</button>
                    <button type="reset" value="Inicijaliziraj" style="background-color: red">Inicijaliziraj</button>
                </div>
            </form>
            <br>
            <div id="greske" style="color:red;">
                <?php
                if(isset($greska)) 
                {
                    echo $greska;
                }
                ?>
            </div>
        </section>
    </body>
</html>
