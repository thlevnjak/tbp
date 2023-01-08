echo "Zapocinje kreiranje baze..."
psql -U postgres baza < baza.sql
echo "Kreiranje baze zavrsilo!"
pause