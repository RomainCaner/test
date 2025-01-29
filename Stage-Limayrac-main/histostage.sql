-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 18 oct. 2024 à 10:38
-- Version du serveur : 10.4.28-MariaDB
-- Version de PHP : 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `histostage`
--

-- --------------------------------------------------------

--
-- Structure de la table `anneescolaire`
--

CREATE TABLE `anneescolaire` (
  `idAnneeScolaire` int(11) NOT NULL,
  `libAnneeScolaire` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `anneescolaire`
--

INSERT INTO `anneescolaire` (`idAnneeScolaire`, `libAnneeScolaire`) VALUES
(1, '2018/2019'),
(2, '2019/2020'),
(3, '2020/2021'),
(4, '2021/2022'),
(5, '2022/2023'),
(6, '2023/2024');

-- --------------------------------------------------------

--
-- Structure de la table `classe`
--

CREATE TABLE `classe` (
  `idClasse` varchar(50) NOT NULL,
  `libClasse` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `classe`
--

INSERT INTO `classe` (`idClasse`, `libClasse`) VALUES
('1CCST', '1ère année BTS Conseil et Commercialisation de Solutions Techniques'),
('1CG', '1ère année BTS Comptabilité et Gestion'),
('1CIEL', '1ère année BTS CIEL'),
('1DCG', '1ère année Diplôme de Comptabilité et de Gestion'),
('1DIET', '1ère année BTS Diététique'),
('1DSCG ALT', '1ère année DSCG'),
('1ESFA', '1ère année BTS Economie Sociale Familiale'),
('1ESFB', '1ère année BTS Economie Sociale Familiale'),
('1GPME', '1ère année BTS Gestion de la PME'),
('1SIO', '1ère année BTS Services Informatiques aux Organisations'),
('1TOURA', '1ère année BTS Tourisme'),
('1TOURB', '1ère année BTS Tourisme'),
('2CCST', '2ème année BTS Conseil et Commercialisation de Solutions Techniques'),
('2CG', '2ème année BTS Comptabilité et Gestion'),
('2DCG', '2ème année Diplôme de Comptabilité et de Gestion'),
('2DCG ALT', '2ème année Diplôme de Comptabilité et de Gestion'),
('2DIET', '2ème année BTS Diététique'),
('2DSCG ALT', '2ème année DSCG'),
('2ESFA', '2ème année BTS Economie Sociale Familiale'),
('2ESFB', '2ème année BTS Economie Sociale Familiale'),
('2GPME', '2ème année BTS Gestion de la PME'),
('2SIO', '2ème année BTS Services Informatiques aux Organisations'),
('2SN', '2ème année BTS Systèmes Numériques'),
('2TOURA', '2ème année BTS Tourisme'),
('2TOURB', '2ème année BTS Tourisme'),
('3DCG', '3ème année Diplôme de Comptabilité et de Gestion'),
('3DCG ALT', '3ème année Diplôme de Comptabilité et de Gestion'),
('BCMN ALT', 'Bachelor Commerce Marketing Négociation'),
('BEVENT ALT', 'Bachelor Event Project Manager'),
('BRET ALT', 'Bachelor Resp. d\'établissement touristique'),
('BRPARH ALT', 'Bachelor Responsable Paie et Administration des ressources humaines'),
('BSI ALT', 'Bachelor Responsable de Projets Informatiques'),
('CMIV', 'Classe de mise à niveau'),
('DECESF', 'Diplôme d\'Etat de Conseiller en ESF'),
('DECESF REGION', 'Diplôme d\'Etat de Conseiller en ESF'),
('ESI1 ALT', 'Master 1 ESI'),
('ESI2 ALT', 'Master 2 ESI'),
('L3DIET', 'Licence Pro nutrition alim. santé : parcours diet.');

-- --------------------------------------------------------

--
-- Structure de la table `contact`
--

CREATE TABLE `contact` (
  `idContact` int(11) NOT NULL,
  `titreContact` varchar(50) DEFAULT NULL,
  `nomContact` varchar(50) DEFAULT NULL,
  `prenomContact` varchar(50) DEFAULT NULL,
  `mailContact` varchar(50) DEFAULT NULL,
  `mobileContact` varchar(50) DEFAULT NULL,
  `fixeContact` varchar(50) DEFAULT NULL,
  `estActifContact` varchar(50) DEFAULT NULL,
  `fonctionContact` varchar(50) DEFAULT NULL,
  `isGerantContact` varchar(50) DEFAULT '0',
  `SIREN` varchar(50) NOT NULL,
  `NIC` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `contact`
--

INSERT INTO `contact` (`idContact`, `titreContact`, `nomContact`, `prenomContact`, `mailContact`, `mobileContact`, `fixeContact`, `estActifContact`, `fonctionContact`, `isGerantContact`, `SIREN`, `NIC`) VALUES
(14, 'Mme', 'Martin', 'Claire', 'claire.martin@example.com', '0698765432', '0156789432', '1', 'Responsable RH', NULL, '451678973', '00830'),
(15, 'Mme', 'Dubois', 'Marie', 'marie.dubois@entreprise.com', '0612345679', '0145678911', '1', 'Directrice des Ressources Humaines', '0', '482918547', '00050'),
(16, 'M', 'Khitaridze', 'George', 'george.khitaridze@gmail.com', '0761242525', '0145678911', '0', 'Directeur', '1', '482918547', '00050'),
(17, 'M', 'Durand', 'Alexandre', 'alexandre.durand@example.com', '0612345678', '0145678910', '1', 'Directeur', '1', '434188942', '00016'),
(18, 'Mme', 'Lefevre', 'Sophie', 'sophie.lefevre@example.com', '0698765432', '0156789432', '1', 'Responsable RH', '0', '434188942', '00016');

-- --------------------------------------------------------

--
-- Structure de la table `enseignant`
--

CREATE TABLE `enseignant` (
  `idEnseignant` int(11) NOT NULL,
  `statutEns` varchar(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `enseignant`
--

INSERT INTO `enseignant` (`idEnseignant`, `statutEns`) VALUES
(26, 'SCO'),
(27, 'SCO'),
(28, 'SCO'),
(29, 'SCO'),
(30, 'SCO'),
(31, 'SCO'),
(32, 'SCO'),
(33, 'SCO');

-- --------------------------------------------------------

--
-- Structure de la table `enseigner`
--

CREATE TABLE `enseigner` (
  `idEnseignant` int(11) NOT NULL,
  `idAnneeScolaire` int(11) NOT NULL,
  `idClasse` varchar(50) NOT NULL,
  `estRsEnseignant` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `enseigner`
--

INSERT INTO `enseigner` (`idEnseignant`, `idAnneeScolaire`, `idClasse`, `estRsEnseignant`) VALUES
(26, 6, '2TOURA', 1),
(27, 6, '2CG', 0),
(28, 6, '2SIO', 1),
(29, 6, '2GPME', 1),
(30, 6, '2ESFA', 0),
(31, 6, '1SIO', 0),
(32, 6, '1CG', 1),
(33, 6, '1TOURA', 0);

-- --------------------------------------------------------

--
-- Structure de la table `etablissement`
--

CREATE TABLE `etablissement` (
  `SIREN` varchar(50) NOT NULL,
  `NIC` varchar(50) NOT NULL,
  `denominationEtab` varchar(50) DEFAULT NULL,
  `numAdrEtab` varchar(50) DEFAULT NULL,
  `voieAdrEtab` varchar(50) DEFAULT NULL,
  `libAdrEtab` varchar(50) DEFAULT NULL,
  `cpAdrEtab` varchar(50) DEFAULT NULL,
  `villeAdrEtab` varchar(50) DEFAULT NULL,
  `missionEtab` text DEFAULT NULL,
  `fixeEtab` varchar(50) DEFAULT NULL,
  `mailEtab` varchar(50) DEFAULT NULL,
  `estSiegeSocialEtab` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `etablissement`
--

INSERT INTO `etablissement` (`SIREN`, `NIC`, `denominationEtab`, `numAdrEtab`, `voieAdrEtab`, `libAdrEtab`, `cpAdrEtab`, `villeAdrEtab`, `missionEtab`, `fixeEtab`, `mailEtab`, `estSiegeSocialEtab`) VALUES
('327733184', '00516', 'MICROSOFT FRANCE', '37', 'QUAI', 'DU PRESIDENT ROOSEVELT', '92130', 'ISSY-LES-MOULINEAUX', '62.02A', NULL, NULL, '1'),
('343262622', '17952', 'LIDL', '10', 'RUE', 'CLAUDE GONIN', '31400', 'TOULOUSE', '47.11D', '07688552336', 'toulouse@gmail.fr', '0'),
('349289058', '00050', 'SARL ENTREPRISE LECLERC', '5', 'RUE', 'JEAN MONNET', '31240', 'SAINT-JEAN', '43.34Z', NULL, NULL, '1'),
('408266245', '00021', 'SOCIETE D\'ECONOMIE MIXTE D\'EXPLOITATION DE CENTRES', '95', 'AVENUE', 'JEAN GONORD', '31400', 'TOULOUSEE', '93.21Z', '0684754825', 'CiteSpace@gmail.fr', '1'),
('412329575', '00085', 'BA FRANCE', '41', 'AVENUE', 'JEAN MONNET', '31770', 'COLOMIERS', '71.12B', NULL, NULL, '1'),
('434188942', '00016', 'I.B.M. FRANCE', '5', 'AVENUE', 'DU GENERAL DE GAULLE', '92160', 'ANTONY', '26.20Z', '', '', '1'),
('451678973', '00830', 'CASTORAMA FRANCE', '3', 'AVENUEEEE', 'DE TOULOUSE', '31240', 'L\'UNION', '47.52B', '07684848484', '', NULL),
('452504632', '00020', 'CORTEX-INFORMATIQUE', '7', 'PLACE', 'COMMERCIALE DE JOLIMONT', '31500', 'TOULOUSE', '95.11Z', '0768523635', 'CORTEX-INFORMATIQUE@gmail.com', '1'),
('477965883', '00028', 'OLIPHEN', '2', 'BOULEVARD', 'DEODAT DE SEVERAC', '31300', 'TOULOUSE', '47.11D', '0768855898', 'OLIPHEN@lomeet.fr', '1'),
('482918547', '00050', 'NOVA PAGE 82', '251', 'RUE', 'DE COPENHAGUE', '82000', 'MONTAUBAN', '46.51Z', '0765152464', 'exemple.limayrac@gmail.com', '0'),
('505235192', '00042', 'TIRIA', '36', 'AVENUE', 'CHARLES DE GAULLE', '32600', 'L\'ISLE-JOURDAIN', '62.01Z', '0768521476', 'tiria@gmail.fr', '1'),
('776944860', '00019', 'ASSOCIATION INSTITUT LIMAYRAC', '50', 'RUE', 'DE LIMAYRAC', '31500', 'TOULOUSE', '85.42Z', NULL, NULL, '1'),
('778127613', '00124', 'AIRBUS ATLANTIC', '13', 'RUE', 'MARIE LOUISE DISSARD', '31300', 'TOULOUSE', '30.30Z', NULL, NULL, '0'),
('804156925', '00021', 'BMA', '10', 'RUE', 'DU PONT MONTAUDRAN', '31000', 'TOULOUSE', '47.21Z', NULL, NULL, '1'),
('812496602', '00028', 'ALTITUDE AEROSPACE FRANCE', '2', 'RUE', 'DU PROFESSEUR PIERRE VELLAS', '31300', 'TOULOUSE', '71.12B', NULL, NULL, '1'),
('922132576', '00021', 'TERIALI', '46', 'BOULEVARD', 'JEAN BRUNHES', '31300', 'TOULOUSE', '47.11B', '0115236587', 'TERIALI@gmail.com', '1'),
('987654321', '00035', 'Tech Innovations Toulouse', '50', 'Rue', 'limayrac', '31000', 'Toulouse', 'Les missions d\'une entreprise consise croissance et de développement personnel à ses employés. En parallèle, elle vise à maximiser la rentabilité et la croissance durable, en adoptant des pratiques éthiques et responsables, notamment en matière de respect de l\'environnement et de soutien aux communautés locales. La mission de l\'entreprise est également de rester à la pointe de l\'innovation technologique et de l\'excellence opérationnelle, afin de garantir sa compétitivité e', '0123456789', 'tech.innovatioToulouse@gmail.com', '1'),
('987654321', '00036', 'Tech Innovations Montaudran', '60', 'Rue', 'George Sande', '31400', 'Montaudran', 'Les missions d\'une entreprise consistent à créer d', '0123456788', 'tech.innovatioMontaudran@gmail.com', '0');

-- --------------------------------------------------------

--
-- Structure de la table `etudiant`
--

CREATE TABLE `etudiant` (
  `idEtudiant` int(11) NOT NULL,
  `numAdrEtudiant` varchar(50) DEFAULT NULL,
  `voieAdrEtudiant` varchar(50) DEFAULT NULL,
  `libAdrEtudiant` varchar(50) DEFAULT NULL,
  `cpAdrEtudiant` varchar(50) DEFAULT NULL,
  `villeAdrEtudiant` varchar(50) DEFAULT NULL,
  `dateNaissanceEtudiant` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `etudiant`
--

INSERT INTO `etudiant` (`idEtudiant`, `numAdrEtudiant`, `voieAdrEtudiant`, `libAdrEtudiant`, `cpAdrEtudiant`, `villeAdrEtudiant`, `dateNaissanceEtudiant`) VALUES
(1, '53', 'Avenue', 'jean dupont', '31000', 'Toulouse', '2002-10-12'),
(2, NULL, NULL, NULL, NULL, NULL, '2002-10-13'),
(3, NULL, NULL, NULL, NULL, NULL, '2002-10-14'),
(4, NULL, NULL, NULL, NULL, NULL, '2002-10-15'),
(5, NULL, NULL, NULL, NULL, NULL, '2002-10-16'),
(6, NULL, NULL, NULL, NULL, NULL, '2002-10-17'),
(7, NULL, NULL, NULL, NULL, NULL, '2002-10-18'),
(8, NULL, NULL, NULL, NULL, NULL, '2002-10-19'),
(9, NULL, NULL, NULL, NULL, NULL, '2002-10-20'),
(10, NULL, NULL, NULL, NULL, NULL, '2002-10-21'),
(11, NULL, NULL, NULL, NULL, NULL, '2002-10-22'),
(12, NULL, NULL, NULL, NULL, NULL, '2002-10-23'),
(13, NULL, NULL, NULL, NULL, NULL, '2002-10-24'),
(14, NULL, NULL, NULL, NULL, NULL, '2002-10-25'),
(15, NULL, NULL, NULL, NULL, NULL, '2002-10-26'),
(16, NULL, NULL, NULL, NULL, NULL, '2002-10-27'),
(17, NULL, NULL, NULL, NULL, NULL, '2002-10-28'),
(18, NULL, NULL, NULL, NULL, NULL, '2002-10-29'),
(19, NULL, NULL, NULL, NULL, NULL, '2002-10-30'),
(20, NULL, NULL, NULL, NULL, NULL, '2002-10-31'),
(21, NULL, NULL, NULL, NULL, NULL, '2002-11-01'),
(22, NULL, NULL, NULL, NULL, NULL, '2002-11-02'),
(23, NULL, NULL, NULL, NULL, NULL, '2002-11-03'),
(24, NULL, NULL, NULL, NULL, NULL, '2002-11-04'),
(25, NULL, NULL, NULL, NULL, NULL, '2002-11-05'),
(35, NULL, NULL, NULL, NULL, NULL, '2004-05-20');

-- --------------------------------------------------------

--
-- Structure de la table `inscription`
--

CREATE TABLE `inscription` (
  `idClasse` varchar(50) NOT NULL,
  `idAnneeScolaire` int(11) NOT NULL,
  `idEtudiant` int(11) NOT NULL,
  `estRedoublant` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `inscription`
--

INSERT INTO `inscription` (`idClasse`, `idAnneeScolaire`, `idEtudiant`, `estRedoublant`) VALUES
('1CG', 6, 35, 0),
('2SIO', 6, 1, 0),
('2SIO', 6, 2, 0),
('2SIO', 6, 3, 0),
('2SIO', 6, 10, 0);

-- --------------------------------------------------------

--
-- Structure de la table `organisation`
--

CREATE TABLE `organisation` (
  `SIREN` varchar(50) NOT NULL,
  `denominationOrg` varchar(50) DEFAULT NULL,
  `formeJuridiqueOrg` varchar(50) DEFAULT NULL,
  `codeApeOrg` varchar(50) DEFAULT NULL,
  `dateCreationOrg` varchar(50) DEFAULT NULL,
  `trancheEffectifsOrg` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `organisation`
--

INSERT INTO `organisation` (`SIREN`, `denominationOrg`, `formeJuridiqueOrg`, `codeApeOrg`, `dateCreationOrg`, `trancheEffectifsOrg`) VALUES
('327733184', 'MICROSOFT FRANCE', '5710', '62.02A', '1983-04-01', '42'),
('343262622', 'LIDL', '5202', '47.11D', '1987-12-17', '53'),
('349289058', 'SARL ENTREPRISE LECLERC', '5499', '43.34Z', '1989-01-01', '12'),
('408266245', 'SOCIETE D\'ECONOMIE MIXTE D\'EXPLOITATION DE CENTRES', '5515', '93.21Z', '1996-07-01', '22'),
('412329575', 'BA FRANCE', '5710', '71.12B', '1997-05-31', '12'),
('434188942', 'I.B.M. FRANCE', '5499', '26.20Z', '2000-11-04', NULL),
('451678973', 'CASTORAMA FRANCE', '5710', '47.52B', '2004-01-20', '53'),
('452504632', NULL, '1000', '95.11Z', '2004-03-11', NULL),
('477965883', 'OLIPHEN', '5499', '47.11D', '2004-10-28', '11'),
('482918547', 'NOVA PAGE 82', '5499', '46.51Z', '2005-06-08', '03'),
('505235192', 'TIRIA', '5499', '62.01Z', '2008-07-07', '02'),
('776944860', 'ASSOCIATION INSTITUT LIMAYRAC', '9220', '85.42Z', '1900-01-01', '22'),
('778127613', 'AIRBUS ATLANTIC', '5710', '30.30Z', '1970-01-01', '51'),
('804156925', 'BMA', '5710', '47.21Z', '2014-07-25', '02'),
('812496602', 'ALTITUDE AEROSPACE FRANCE', '5710', '71.12B', '2015-07-01', '11'),
('922132576', 'TERIALI', '5710', '47.11B', '2022-12-08', NULL),
('987654321', 'Tech Innovations', 'SAS', '6201Z', '2023-06-15', '100-199');

-- --------------------------------------------------------

--
-- Structure de la table `stage`
--

CREATE TABLE `stage` (
  `idStage` int(11) NOT NULL,
  `dateDebutStage` date DEFAULT NULL,
  `dateFinStage` date DEFAULT NULL,
  `dureeHebdoStage` int(11) DEFAULT NULL,
  `activitesStage` text DEFAULT NULL,
  `adrEtudiantStage` varchar(50) DEFAULT NULL,
  `nomLieuStage` varchar(50) DEFAULT NULL,
  `contactLieuStage` varchar(50) DEFAULT NULL,
  `adrLieuStage` varchar(50) DEFAULT NULL,
  `idContact` int(11) NOT NULL,
  `idClasse` varchar(50) NOT NULL,
  `idAnneeScolaire` int(11) NOT NULL,
  `idEtudiant` int(11) NOT NULL,
  `SIREN` varchar(50) NOT NULL,
  `NIC` varchar(50) NOT NULL,
  `idEnseignant` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `stage`
--

INSERT INTO `stage` (`idStage`, `dateDebutStage`, `dateFinStage`, `dureeHebdoStage`, `activitesStage`, `adrEtudiantStage`, `nomLieuStage`, `contactLieuStage`, `adrLieuStage`, `idContact`, `idClasse`, `idAnneeScolaire`, `idEtudiant`, `SIREN`, `NIC`, `idEnseignant`) VALUES
(27, '2024-06-30', '2024-07-30', 35, 'Développement Web :\r\n\r\nConception et développement de sites web.\r\nCréation de modules et fonctionnalités web.\r\nMaintenance et mise à jour des sites existants.\r\nTests et débogage des applications web', '', '', '', '', 14, '2SIO', 6, 1, '451678973', '00830', 0),
(28, '2024-06-30', '2024-07-30', 35, 'Dev', '', '', '', '', 15, '2SIO', 6, 3, '482918547', '00050', 0),
(29, '2000-02-10', '2222-01-17', 35, 'rrheherhesZ,kgognizgheiugnrojfnro', '', '', '', '', 18, '2SIO', 6, 3, '434188942', '00016', 0),
(30, '0000-00-00', '0000-00-00', 35, '', '', '', '', '', 18, '2SIO', 6, 3, '434188942', '00016', 0),
(31, '2555-02-10', '1444-05-05', 35, 'ililili', '', '', '', '', 18, '2SIO', 6, 3, '434188942', '00016', 0),
(32, '2024-07-02', '2024-08-02', 35, 'Dev', '', '', '', '', 18, '2SIO', 6, 3, '434188942', '00016', 0),
(33, '2024-07-02', '2024-08-02', 35, 'dev', '', '', '', '', 18, '2SIO', 6, 3, '434188942', '00016', 0),
(34, '2024-07-02', '2024-08-02', 35, 'dev', '', '', '', '', 18, '2SIO', 6, 3, '434188942', '00016', 0),
(35, '2000-02-10', '1000-01-01', 35, 'iuiuiui', '', '', '', '', 18, '2SIO', 6, 3, '434188942', '00016', 0),
(36, '2024-06-10', '2024-08-10', 35, 'DEV', '', '', '', '', 18, '2SIO', 6, 3, '434188942', '00016', 0);

-- --------------------------------------------------------

--
-- Structure de la table `statut`
--

CREATE TABLE `statut` (
  `idStatut` int(11) NOT NULL,
  `libStatut` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `statut`
--

INSERT INTO `statut` (`idStatut`, `libStatut`) VALUES
(1, 'Fiche de renseignements en cours de saisie'),
(2, 'Fiche de renseignements validée'),
(3, 'Fiche de renseignements approuvée'),
(4, 'Convention générée'),
(5, 'Convention signée'),
(6, 'Stage annulé'),
(7, 'Stage terminé');

-- --------------------------------------------------------

--
-- Structure de la table `suivi`
--

CREATE TABLE `suivi` (
  `idStage` int(11) NOT NULL,
  `idStatut` int(11) NOT NULL,
  `dateSuivi` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `suivi`
--

INSERT INTO `suivi` (`idStage`, `idStatut`, `dateSuivi`) VALUES
(27, 6, '2024-06-24'),
(28, 6, '2024-06-24'),
(29, 6, '2024-07-01'),
(30, 6, '2024-07-01'),
(31, 6, '2024-07-01'),
(32, 6, '2024-07-02'),
(33, 6, '2024-07-02'),
(34, 6, '2024-07-02'),
(35, 6, '2024-07-12'),
(36, 6, '2024-07-15');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `idUser` int(11) NOT NULL,
  `loginUser` varchar(255) DEFAULT NULL,
  `mdpUser` varchar(500) DEFAULT NULL,
  `titreUser` varchar(50) DEFAULT NULL,
  `nomUser` varchar(500) DEFAULT NULL,
  `prenomUser` varchar(500) DEFAULT NULL,
  `mailUser` varchar(255) DEFAULT NULL,
  `fixeUser` varchar(50) DEFAULT NULL,
  `mobileUser` varchar(50) DEFAULT NULL,
  `roleUser` varchar(1) DEFAULT NULL,
  `estActifUser` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`idUser`, `loginUser`, `mdpUser`, `titreUser`, `nomUser`, `prenomUser`, `mailUser`, `fixeUser`, `mobileUser`, `roleUser`, `estActifUser`) VALUES
(1, 'IADDOU', '$2y$10$WYAepRF7u25vBFMV.4xOcuhjestOCdley2w8wM.Lw7ph5Il.mRRUK', 'Mr', 'ADDOU', 'Ilyess', 'Ilyess.iaddou@limayrac.fr', '0905033565', '0784477596', 'E', 1),
(2, 'GBARRILLON', '$2y$10$/EbicW7WpAdH/10vJRoqdOlLA4wvn.TCgLSH2CS2hAiA7jGy8D5KS', 'Mr', 'BARRILLON', 'Guillaume', NULL, NULL, NULL, 'E', 1),
(3, 'CBAS', '$2y$10$ZHw6hzKiag2uDPSZIQyIZuQbIefQYAwMjuU3E7amef3WCQzo7PMgu', 'Mr', 'BAS', 'Clément', 'clement.bas@limayrac.fr', '0784956132', '0578944562', 'E', 1),
(4, 'LBOUZAC', '$2y$10$CTYPxWJUA65SQeMbg89JsOAeIebSBAI1eM7iDqYRrAY/r0yMv03L2', 'Mr', 'BOUZAC', 'Ludovic', NULL, NULL, NULL, 'E', 1),
(5, 'HCAILLEBOTTEVINCE', '$2y$10$zBc6xxyEF0eHZzaERDl71uFRGy5PGtgeyayIlNP78S1KYi8T5kB1i', 'Mr', 'CAILLEBOTTE VINCE', 'Hector', NULL, NULL, NULL, 'E', 1),
(6, 'NCALDERAN', '$2y$10$f5pU5HU6WM0WQ2Bn4.ZBUeZFCh23A4ZrJVtQoeCxU1xsTAFetVGNu', 'Mr', 'CALDERAN', 'Nicolas', NULL, NULL, NULL, 'E', 1),
(7, 'PCHAVAT', '$2y$10$/D1DRPJT6xVBiuPfBy8dHekHN5CCnNgaQQ6urnY4gdDnHxto7mw1u', 'Mr', 'CHAVAT', 'Paul', NULL, NULL, NULL, 'E', 1),
(8, 'TCOUDERC', '$2y$10$UUe58AWBGJYTT.YMltdNqODVraxWSkS70NANIGX.lnd0EBBa897.e', 'Mr', 'COUDERC', 'Thomas', NULL, NULL, NULL, 'E', 1),
(9, 'KFERRARY', '$2y$10$c900wldYB68aegmITxaMoeED19UbvJEYDCJUzpxFwumzRSHPZxhzG', 'Mr', 'FERRARY', 'Killian', NULL, NULL, NULL, 'E', 1),
(10, 'WFONTA', '$2y$10$XuSxadncYV2MzLwkgTVWWOBiwQVzDoPBVd84ShPL7kTPzCSJjitl6', 'Mr', 'FONTA', 'William', NULL, NULL, NULL, 'E', 1),
(11, 'CGARAT', '$2y$10$o3/4FKoSOVEqhCfvPl9o5OWKXfowiQbpT.Zevzkb8YyNiHZaLeJ8u', 'Mr', 'GARAT', 'Clément', NULL, NULL, NULL, 'E', 1),
(12, 'LKLONECKI', '$2y$10$v7MndLD58nQAmnTvInVfQ.BKuFXY4Zb4h5njRgMS40EIUH7s7f79S', 'Mr', 'KLONECKI', 'Lucas', NULL, NULL, NULL, 'E', 1),
(13, 'GLE BOHEC', '$2y$10$bdvRFt1IcJohzPl0bYzR3O4rovtav29TN0M.dkXNdCRLe0ytI6.C6', 'Mr', 'LE BOHEC', 'Galadriel', NULL, NULL, NULL, 'E', 1),
(14, 'MMAGUEUR', '$2y$10$7aFD0L2akKibWss61Ehy5eZF6F1Pqab5H2DXWF7XAO.Qhy6rAVAF.', 'Mr', 'MAGUEUR', 'Marc', NULL, NULL, NULL, 'E', 1),
(15, 'MMAURY', '$2y$10$B.RRXPjzfCjclz1LdPv8A.PfjD9P5I8Pj6XiwqZ4oh07bpHc77GWy', 'Mr', 'MAURY', 'Mathis', NULL, NULL, NULL, 'E', 1),
(16, 'HNOURTHICHARI NORTI', '$2y$10$9IrqD6lQOXyM0z1aCBQj3eLGcn56Qihz9u6RF48r9w3QsY.PaYf56', 'Mr', 'NOURTHI CHARIF NORTI', 'Haitam', NULL, NULL, NULL, 'E', 1),
(17, 'NPELISSIER', '$2y$10$O120LHLN40WLXKsixiRarewlZz7TtoAvI3BhNE3JwHVEB7D1kAhYe', 'Mr', 'PELISSIER', 'Nicolas', NULL, NULL, NULL, 'E', 1),
(18, 'GPONCIN', '$2y$10$TuMpNEUr/e48iTNp25be..PasYbSKFrNRppsZ04edltCxastGsbEu', 'Mr', 'PONCIN', 'Gabriel', NULL, NULL, NULL, 'E', 1),
(19, 'MPRINCE', '$2y$10$yB0ZprtqwHn0iUUYgN6oE.6ow0LPZKenqSda0qm/jCRaPBXEB6WN2', 'Mr', 'PRINCE', 'Maxime', NULL, NULL, NULL, 'E', 1),
(20, 'SROUSSELOT', '$2y$10$ubuGGGvfBe.xTaDS4yvZp.Lu2oj6HIE.3cSGnoVBB6y3yBEI9IMPW', 'Mr', 'ROUSSELOT', 'Simon', NULL, NULL, NULL, 'E', 1),
(21, 'MSANTOS', '$2y$10$VV1ndYGhyNiLDKEUPd25Uuc8ht7TkLej/flVA8bGA/7.XM78P9Z.u', 'Mr', 'SANTOS', 'Maxime', NULL, NULL, NULL, 'E', 1),
(22, 'MSARRBOUR', '$2y$10$SsDkCiQiR8qn6Cq.3RPH.OBfhzZA8mLXIhXZ3DfPrXREXbS3qR7we', 'Mme', 'SARR-BOUR', 'Mahalia', NULL, NULL, NULL, 'E', 1),
(23, 'KTAMBOURA', '$2y$10$LO4XkBnDaPbje3X5HhXq9exM46OyLTzyiYnjZj.qBovY9I4ts1wGG', 'Mme', 'TAMBOURA', 'Kérima', NULL, NULL, NULL, 'E', 1),
(24, 'SVERCHERE', '$2y$10$b21OEZVIklkghBHLY1pJiuh/XqfhVZ0tFPyrUnnjOknyzTZ9yCWrO', 'Mr', 'VERCHERE', 'Sébastien', NULL, NULL, NULL, 'E', 1),
(25, 'RYANALLAH', '$2y$10$DUTJeKi0YA7m4IrFqeYywuiWsyKcEhUcRIcoqXtzTefLSIOtTI/1C', 'Mr', 'YANALLAH', 'Ryan', NULL, NULL, NULL, 'E', 1),
(26, 'DDELPECH', '$2y$10$DNa/QtcmOX2EHQQN29Fcl.f1dwq55KhG/AJtzNy9L8nTh2nRFUaju', 'Mme', 'DELPECH', 'Aure', NULL, NULL, NULL, 'P', 1),
(27, 'MMEDA', '$2y$10$lsmSKDMhpxgLLvuQhOHd1eC5xtCxAw/axs9snZuU1/J0C7L4KA5ay', 'Mme', 'MEDA', 'Chantal', NULL, NULL, NULL, 'P', 1),
(28, 'PPUEL', '$2y$10$y2kVdAgoHdj2DGmuMjQgiO5F0uSfj.C7AQuJXlELI9dD0AQzqikUO', 'Mr', 'PUEL', 'Christophe', 'Christophe.puel@limayrac.fr', '0789456123', '0578944561', 'P', 1),
(29, 'OORIOLA', '$2y$10$Xv.lqiJKYHSXJOscHDtle.Sbg6iaCSA1CMYvpLQEu2iM8cdTNDKky', 'Mme', 'ORIOLA', 'Conception', NULL, NULL, NULL, 'P', 1),
(30, 'RRODRIGUEZ', '$2y$10$SYW.4.OrfaV.DNAqB18vdO0fE5v9abP.N3pvVJdryzJpMaQnzZJau', 'Mme', 'RODRIGUEZ', 'Delphine', NULL, NULL, NULL, 'P', 1),
(31, 'RROMAN', '$2y$10$evK1VbWFA91v/l5vsJnLDO9D54MlI.u7vdquKELRYAoQsJODduKx.', 'Mr', 'ROMAN', 'Frédéric', NULL, NULL, NULL, 'P', 1),
(32, 'RRAMIARA', '$2y$10$PfJ16pz9VEsWZQGccKiVdOJ8RwVX4AeQIdKW547fc8pcJFPs6Qlw2', 'Mr', 'RAMIARA', 'Jean-François', NULL, NULL, NULL, 'P', 1),
(33, 'HHULLIN', '$2y$10$uOHDeTWM/30eBx4jriS64uk8v.cJyVK0ezULxHcFoszL/H5m.wOpa', 'Mr', 'HULLIN', 'Pierre', NULL, NULL, NULL, 'P', 1),
(34, 'admin', '$2y$10$XYgYzPmsDOV6ZDrgjjrTVOWI5FtBlC2Bs0g0v6w4XIgVmsCeONhJy', 'Mr', 'ADMIN', 'Super', NULL, NULL, NULL, 'A', 1),
(35, 'PDUPONT', '$2y$10$2PPqCSKVpRd974fbvDuVsuhjGmQwNUUYYE6f7qFQNoTDHMZMnK3hi', 'Mr', 'DUPONT', 'Pierre', NULL, NULL, NULL, 'E', 1);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `anneescolaire`
--
ALTER TABLE `anneescolaire`
  ADD PRIMARY KEY (`idAnneeScolaire`);

--
-- Index pour la table `classe`
--
ALTER TABLE `classe`
  ADD PRIMARY KEY (`idClasse`);

--
-- Index pour la table `contact`
--
ALTER TABLE `contact`
  ADD PRIMARY KEY (`idContact`),
  ADD KEY `SIREN` (`SIREN`,`NIC`);

--
-- Index pour la table `enseignant`
--
ALTER TABLE `enseignant`
  ADD PRIMARY KEY (`idEnseignant`);

--
-- Index pour la table `enseigner`
--
ALTER TABLE `enseigner`
  ADD PRIMARY KEY (`idEnseignant`,`idAnneeScolaire`,`idClasse`),
  ADD KEY `idAnneeScolaire` (`idAnneeScolaire`),
  ADD KEY `idClasse` (`idClasse`);

--
-- Index pour la table `etablissement`
--
ALTER TABLE `etablissement`
  ADD PRIMARY KEY (`SIREN`,`NIC`);

--
-- Index pour la table `etudiant`
--
ALTER TABLE `etudiant`
  ADD PRIMARY KEY (`idEtudiant`);

--
-- Index pour la table `inscription`
--
ALTER TABLE `inscription`
  ADD PRIMARY KEY (`idClasse`,`idAnneeScolaire`,`idEtudiant`),
  ADD KEY `idAnneeScolaire` (`idAnneeScolaire`),
  ADD KEY `idUser` (`idEtudiant`);

--
-- Index pour la table `organisation`
--
ALTER TABLE `organisation`
  ADD PRIMARY KEY (`SIREN`);

--
-- Index pour la table `stage`
--
ALTER TABLE `stage`
  ADD PRIMARY KEY (`idStage`),
  ADD KEY `idContact` (`idContact`),
  ADD KEY `idClasse` (`idClasse`,`idAnneeScolaire`,`idEtudiant`),
  ADD KEY `SIREN` (`SIREN`,`NIC`);

--
-- Index pour la table `statut`
--
ALTER TABLE `statut`
  ADD PRIMARY KEY (`idStatut`);

--
-- Index pour la table `suivi`
--
ALTER TABLE `suivi`
  ADD PRIMARY KEY (`idStage`,`idStatut`),
  ADD KEY `idStatut` (`idStatut`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`idUser`),
  ADD UNIQUE KEY `loginUser` (`loginUser`),
  ADD UNIQUE KEY `mailUser` (`mailUser`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `anneescolaire`
--
ALTER TABLE `anneescolaire`
  MODIFY `idAnneeScolaire` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `contact`
--
ALTER TABLE `contact`
  MODIFY `idContact` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `stage`
--
ALTER TABLE `stage`
  MODIFY `idStage` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT pour la table `statut`
--
ALTER TABLE `statut`
  MODIFY `idStatut` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `idUser` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `contact`
--
ALTER TABLE `contact`
  ADD CONSTRAINT `contact_ibfk_1` FOREIGN KEY (`SIREN`,`NIC`) REFERENCES `etablissement` (`SIREN`, `NIC`);

--
-- Contraintes pour la table `enseignant`
--
ALTER TABLE `enseignant`
  ADD CONSTRAINT `enseignant_ibfk_1` FOREIGN KEY (`idEnseignant`) REFERENCES `utilisateur` (`idUser`);

--
-- Contraintes pour la table `enseigner`
--
ALTER TABLE `enseigner`
  ADD CONSTRAINT `enseigner_ibfk_1` FOREIGN KEY (`idEnseignant`) REFERENCES `enseignant` (`idEnseignant`),
  ADD CONSTRAINT `enseigner_ibfk_2` FOREIGN KEY (`idAnneeScolaire`) REFERENCES `anneescolaire` (`idAnneeScolaire`),
  ADD CONSTRAINT `enseigner_ibfk_3` FOREIGN KEY (`idClasse`) REFERENCES `classe` (`idClasse`);

--
-- Contraintes pour la table `etablissement`
--
ALTER TABLE `etablissement`
  ADD CONSTRAINT `etablissement_ibfk_1` FOREIGN KEY (`SIREN`) REFERENCES `organisation` (`SIREN`);

--
-- Contraintes pour la table `etudiant`
--
ALTER TABLE `etudiant`
  ADD CONSTRAINT `etudiant_ibfk_1` FOREIGN KEY (`idEtudiant`) REFERENCES `utilisateur` (`idUser`);

--
-- Contraintes pour la table `inscription`
--
ALTER TABLE `inscription`
  ADD CONSTRAINT `inscription_ibfk_1` FOREIGN KEY (`idClasse`) REFERENCES `classe` (`idClasse`),
  ADD CONSTRAINT `inscription_ibfk_2` FOREIGN KEY (`idAnneeScolaire`) REFERENCES `anneescolaire` (`idAnneeScolaire`),
  ADD CONSTRAINT `inscription_ibfk_3` FOREIGN KEY (`idEtudiant`) REFERENCES `etudiant` (`idEtudiant`);

--
-- Contraintes pour la table `stage`
--
ALTER TABLE `stage`
  ADD CONSTRAINT `stage_ibfk_1` FOREIGN KEY (`idContact`) REFERENCES `contact` (`idContact`),
  ADD CONSTRAINT `stage_ibfk_2` FOREIGN KEY (`idClasse`,`idAnneeScolaire`,`idEtudiant`) REFERENCES `inscription` (`idClasse`, `idAnneeScolaire`, `idEtudiant`),
  ADD CONSTRAINT `stage_ibfk_3` FOREIGN KEY (`SIREN`,`NIC`) REFERENCES `etablissement` (`SIREN`, `NIC`);

--
-- Contraintes pour la table `suivi`
--
ALTER TABLE `suivi`
  ADD CONSTRAINT `suivi_ibfk_1` FOREIGN KEY (`idStage`) REFERENCES `stage` (`idStage`),
  ADD CONSTRAINT `suivi_ibfk_2` FOREIGN KEY (`idStatut`) REFERENCES `statut` (`idStatut`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
