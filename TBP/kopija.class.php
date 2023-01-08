<?php

class Kopija {

    public function napraviKopiju() {
        $con = "pgsql:host=localhost dbname=baza";
        $user = "postgres";
        $password = "root";

        $veza = new PDO($con, $user, $password);

        exec('pg_dump --host=localhost --port=5432 --username=postgres --no-password --verbose --role "postgres" --format=p --create --clean --section=pre-data --section=data --section=post-data --inserts --column-inserts "baza"', $upis, $rez);

        $file_handle = fopen('kopija.sql', 'w') 
            or die("Ne može se otvoriti datoteka!");
        for ($i=0; $i < sizeof($upis); $i++)
        { 
            fwrite($file_handle, "$upis[$i]" . "\n");
        } 
        fclose($file_handle);
        header('Content-Description: File Transfer');
        header('Content-Type: text/sql');
        header('Content-Disposition: attachment; filename=' . basename("kopija.sql"));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize("kopija.sql"));
        ob_clean();
        flush();
        readfile("kopija.sql");
        unlink("kopija.sql");

        $veza = null;

        return $rez;
    }
}
