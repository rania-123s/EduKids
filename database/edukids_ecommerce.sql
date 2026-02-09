-- E-commerce schema updates for EduKids
-- Apply on database: edukids

ALTER TABLE `produit`
  ADD COLUMN `age_min` int(11) DEFAULT NULL,
  ADD COLUMN `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN `statut` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'actif',
  ADD COLUMN `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `commande`
  ADD COLUMN `parent_id` int(11) DEFAULT NULL,
  ADD COLUMN `date_commande` datetime DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `ligne_commande` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commande_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_LIGNE_COMMANDE_COMMANDE` (`commande_id`),
  KEY `IDX_LIGNE_COMMANDE_PRODUIT` (`produit_id`),
  CONSTRAINT `FK_LIGNE_COMMANDE_COMMANDE` FOREIGN KEY (`commande_id`) REFERENCES `commande` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_LIGNE_COMMANDE_PRODUIT` FOREIGN KEY (`produit_id`) REFERENCES `produit` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `paiement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commande_id` int(11) NOT NULL,
  `mode_paiement` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_paiement` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_PAIEMENT_COMMANDE` (`commande_id`),
  CONSTRAINT `FK_PAIEMENT_COMMANDE` FOREIGN KEY (`commande_id`) REFERENCES `commande` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
