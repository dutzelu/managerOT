CREATE TABLE IF NOT EXISTS `donatii_atasamente` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donatie_id` int(11) NOT NULL,
  `cale_fisier` text NOT NULL,
  `nume_original` varchar(255) NOT NULL,
  `tip_fisier` varchar(120) NOT NULL DEFAULT '',
  `dimensiune` int(11) NOT NULL DEFAULT 0,
  `data_incarcare` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_donatie_id` (`donatie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
