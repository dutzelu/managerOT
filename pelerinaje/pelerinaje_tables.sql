-- Tabele pentru modulul Pelerinaje
-- Execută acest script în phpMyAdmin sau în MySQL pentru a crea tabelele

-- Tabelul pentru pelerinaje
CREATE TABLE IF NOT EXISTS `pelerinaje` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `denumire` varchar(255) NOT NULL,
  `locatie` varchar(255) NOT NULL,
  `zi_start` date NOT NULL,
  `zi_sfarsit` date NOT NULL,
  `descriere` text,
  `link_ot` varchar(500),
  `cost_euro` decimal(10,2) DEFAULT 0.00,
  `cost_dolari` decimal(10,2) DEFAULT 0.00,
  `status` enum('activ','finalizat','anulat') NOT NULL DEFAULT 'activ',
  `data_adaugare` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabelul pentru pelerini
CREATE TABLE IF NOT EXISTS `pelerini` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pelerinaj_id` int(11) NOT NULL,
  `nume` varchar(100) NOT NULL,
  `prenume` varchar(100) NOT NULL,
  `nume_mama` varchar(100),
  `prenume_mama` varchar(100),
  `nume_tata` varchar(100),
  `prenume_tata` varchar(100),
  `data_nasterii` date NOT NULL,
  `telefon` varchar(20) NOT NULL,
  `email` varchar(150),
  `tara_domiciliu` varchar(100),
  `oras_domiciliu` varchar(100),
  `ocupatie` varchar(150),
  `nume_angajator` varchar(150),
  `telefon_angajator` varchar(20),
  `ultima_vizita_israel` int(4),
  `aplicat_viza` enum('Da','Nu') DEFAULT 'Nu',
  `stare_civila` enum('casatorit','necasatorit','divortat','vaduv'),
  `upload_pasaport` varchar(500),
  `plata_dolari` decimal(10,2) DEFAULT 0.00,
  `plata_euro` decimal(10,2) DEFAULT 0.00,
  `cu_sau_fara_avion` enum('cu avion','fara avion') DEFAULT 'cu avion',
  `afectiuni_medicale` text,
  `telefon_persoana_apropiata` varchar(20),
  `data_inscriere` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pelerinaj_id` (`pelerinaj_id`),
  CONSTRAINT `fk_pelerinaj` FOREIGN KEY (`pelerinaj_id`) REFERENCES `pelerinaje` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
