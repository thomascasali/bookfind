-- Schema del database per il progetto Bookfind

-- Creazione del database
CREATE DATABASE IF NOT EXISTS bookfind;
USE bookfind;

-- Tabella degli edifici
CREATE TABLE IF NOT EXISTS `edifici` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `indirizzo` varchar(255) DEFAULT NULL,
  `citta` varchar(100) DEFAULT NULL,
  `cap` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabella dei libri
CREATE TABLE IF NOT EXISTS `libri` (
  `inventario` varchar(20) NOT NULL,
  `id_edificio` int(11) DEFAULT NULL,
  `sezione` varchar(50) NOT NULL,
  `collocazione` varchar(50) NOT NULL,
  `sequenza` varchar(50) NOT NULL,
  `specificazione` varchar(255) DEFAULT NULL,
  `stanza` varchar(20) NOT NULL,
  `scaffale` varchar(20) NOT NULL,
  `stato` enum('disponibile','prestito','manutenzione') NOT NULL DEFAULT 'disponibile',
  PRIMARY KEY (`inventario`),
  KEY `id_edificio` (`id_edificio`),
  KEY `idx_sezione_collocazione_sequenza` (`sezione`,`collocazione`,`sequenza`),
  KEY `idx_stanza_scaffale` (`stanza`,`scaffale`),
  CONSTRAINT `libri_ibfk_1` FOREIGN KEY (`id_edificio`) REFERENCES `edifici` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabella degli utenti (da implementare per un sistema di autenticazione completo)
CREATE TABLE IF NOT EXISTS `utenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `cognome` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ruolo` enum('admin','operatore') NOT NULL DEFAULT 'operatore',
  `data_creazione` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserimento di un edificio predefinito
INSERT INTO `edifici` (`nome`, `indirizzo`, `citta`, `cap`) VALUES
('Sede Principale', 'Via Roma 1', 'Modena', '41121');

-- Inserimento di un utente amministratore predefinito (password: admin)
-- In un sistema reale, usare password_hash() per generare una password sicura
INSERT INTO `utenti` (`username`, `password`, `nome`, `cognome`, `email`, `ruolo`) VALUES
('admin', '$2y$10$rJhcCgUHcGP1CpNfpGlTU.R3AQNGgRXLEWB9AQHas3Lsl8jcCw0Ki', 'Amministratore', 'Sistema', 'admin@esempio.it', 'admin');