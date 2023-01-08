echo "Zapocinje kreiranje baze..."
psql -U postgres postgres < baza.sql
echo "Kreiranje baze zavrsilo!"
pause