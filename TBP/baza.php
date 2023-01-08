<?php

class Baza {
    private $veza;

    public function __construct() {
        $this->veza = pg_connect("host=localhost port=5432 dbname=postgres user=postgres password=root")
            or die('Nije se moguće spojiti: ' . pg_last_error());
    }

    public function upit($upit) {
        return pg_query($this->veza, $upit);
    }

    public function zatvoriVezu() {
        pg_close($this->veza);
    }
}