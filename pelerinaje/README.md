# Modul Pelerinaje - Ghid de Utilizare

## 📋 Instalare

### Pasul 1: Crearea Tabelelor în Baza de Date

1. Deschide **phpMyAdmin** în browser (de obicei la `http://localhost/phpmyadmin`)
2. Selectează baza de date **managerot**
3. Dă click pe tab-ul **SQL**
4. Deschide fișierul `pelerinaje/pelerinaje_tables.sql` din directorul rădăcină
5. Copiază întreg conținutul și lipește-l în zona SQL din phpMyAdmin
6. Apasă butonul **Execută**

Tabelele `pelerinaje` și `pelerini` vor fi create automat.

### Pasul 2: Crearea Directorului pentru Pașapoarte

Directorul pentru pașapoarte se va crea automat când primul pelerin își va încărca pașaportul. 
Dacă dorești să îl creezi manual, rulează următoarea comandă în PowerShell din directorul aplicației:

```powershell
New-Item -ItemType Directory -Path "pelerini_pasapoarte" -Force
```

## 🎯 Cum să Folosești Modulul

### 1. Adăugarea unui Pelerinaj

1. Navighează în meniul lateral la **"Formulare și Adăugare"** → **"Adaugă Pelerinaj"**
2. Completează formularul cu următoarele informații:
   - **Denumire**: Numele pelerinajului (ex: "Pelerinaj Israel - Aprilie 2026")
   - **Locație**: Destinația (ex: "Israel, Ierusalim, Betleem")
   - **Data Start și Data Sfârșit**: Perioada pelerinajului
   - **Descriere**: Detalii despre itinerar, ce include, etc. (suportă formatare HTML)
   - **Link OT**: Link către pagina pelerinajului (opțional)
   - **Cost Euro/Dolari**: Prețul pelerinajului
   - **Status**: Selectează "Activ" pentru pelerinaje deschise pentru înscrieri
3. Apasă **"Salvează Pelerinajul"**

### 2. Vizualizarea Pelerinajelor

1. Navighează la **"Liste și Vizualizări"** → **"Pelerinaje"**
2. Vei vedea toate pelerinajele organizate pe tab-uri:
   - **Active**: Pelerinaje în curs de înscriere
   - **Finalizate**: Pelerinaje încheiate
   - **Anulate**: Pelerinaje anulate
   - **Toate**: Lista completă

### 3. Distribuirea Formularului de Înscriere

1. Deschide un pelerinaj activ
2. În partea dreaptă vei găsi un card albastru **"Link Formular Înscriere"**
3. Copiază linkul apăsând pe butonul cu clipboard
4. Distribuie acest link pelerinilor pentru a se putea înscrie
   - Linkul arată astfel: `http://localhost/managerot/formular_pelerin.php?pelerinaj=1`
5. Pelerini pot completa formularul fără a se autentifica în aplicație

### 4. Gestionarea Înscrierilor

**Vizualizare Pelerini:**
1. Din lista de pelerinaje, apasă pe butonul cu ochiul (👁️) pentru a vedea detaliile pelerinajului
2. Vei vedea:
   - Statistici: Total pelerini, câți cu/fără avion, total încasări
   - Lista completă a pelerinilor înscriși
   - Tabel cu toate informațiile importante

**Editare Date Pelerin:**
1. În tabelul cu pelerini, apasă pe butonul cu ochiul (👁️) lângă numele pelerinului
2. Vei putea edita toate informațiile:
   - Date personale (nume, prenume, date părinți)
   - Date de contact (telefon, email, adresă)
   - Informații profesionale
   - Informații despre Israel și vize
   - Plăți efectuate (Euro/Dolari)
   - Documente (încarcă pașaport nou dacă e necesar)
   - Afecțiuni medicale

### 5. Editarea unui Pelerinaj

1. Din pagina de detalii a pelerinajului, apasă pe **"Editează"**
2. Modifică informațiile necesare
3. Poți schimba status-ul pelerinajului:
   - **Activ**: Formularul de înscriere este disponibil public
   - **Finalizat**: Pelerinajul s-a încheiat
   - **Anulat**: Pelerinajul a fost anulat

## 📊 Statistici și Rapoarte

În pagina fiecărui pelerinaj vei vedea automat:
- **Total Pelerini**: Numărul total de înscriși
- **Cu/Fără Avion**: Defalcare pe tipul de călătorie
- **Total Încasări**: Suma totală în Euro și Dolari

## 🔒 Securitate

- Formularul extern (formular_pelerin.php) este accesibil fără autentificare
- Toate celelalte pagini (gestionare pelerinaje, editare pelerini) necesită autentificare
- Pașapoartele se salvează într-un director separat cu nume unice
- Validări pentru tipuri de fișiere și dimensiuni maxime

## 📁 Structura Fișierelor Create

```
managerot/
├── pelerinaje/
│   ├── pelerinaje_tables.sql          # Script SQL pentru crearea tabelelor
│   ├── add_pelerinaj.php              # Formular adăugare pelerinaj
│   ├── edit_pelerinaj.php             # Formular editare pelerinaj
│   ├── pelerinaje.php                 # Lista tuturor pelerinajelor
│   ├── pelerinaj.php                  # Detalii pelerinaj + lista pelerini
│   ├── pelerinaj_card.php             # Template pentru afișare card pelerinaj
│   ├── pelerin.php                    # Vizualizare/Editare date pelerin
│   └── README.md                      # Ghid de utilizare
├── formular_pelerin.php           # Formular EXTERN pentru înscrieri
└── pelerini_pasapoarte/           # Director pentru pașapoarte (creat automat)
```

## 🆘 Rezolvare Probleme

**Tabelele nu se creează:**
- Verifică că ești conectat la baza de date corectă în phpMyAdmin
- Asigură-te că utilizatorul MySQL are permisiuni CREATE TABLE

**Pașapoartele nu se încarcă:**
- Verifică permisiunile directorului (trebuie să fie 777 sau 755)
- Verifică dimensiunea max de upload în php.ini (upload_max_filesize)

**Formularul extern nu apare:**
- Verifică că ai STATUS = 'activ' pentru pelerinaj
- Verifică că URL-ul este corect și include parametrul ?pelerinaj=ID

## 📞 Note Importante

1. **Când schimbi un pelerinaj în "finalizat" sau "anulat"**, formularul de înscriere nu va mai fi activ
2. **Pașapoartele** pot fi în format PDF, JPEG sau PNG, maxim 10MB
3. **Link-ul OT** este opțional și poate fi folosit pentru a trimite către pagina oficială a pelerinajului
4. **Plățile** în Euro și Dolari sunt independente - poți folosi una sau ambele
5. **DataTables** este activat pe tabelul cu pelerini pentru sortare și căutare ușoară

---

✅ Modulul este gata de utilizare! Succes!
