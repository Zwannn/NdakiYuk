-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: ndakiyuk2
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `detail_pendaki`
--

DROP TABLE IF EXISTS `detail_pendaki`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detail_pendaki` (
  `id_detail` int NOT NULL AUTO_INCREMENT,
  `id_pemesanan` int NOT NULL,
  `nama_pendaki` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nik` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `no_hp` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `umur` int DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_detail`),
  KEY `id_pemesanan` (`id_pemesanan`),
  CONSTRAINT `detail_pendaki_ibfk_1` FOREIGN KEY (`id_pemesanan`) REFERENCES `pemesanan` (`id_pemesanan`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detail_pendaki`
--

LOCK TABLES `detail_pendaki` WRITE;
/*!40000 ALTER TABLE `detail_pendaki` DISABLE KEYS */;
INSERT INTO `detail_pendaki` VALUES (2,2,'adi rabbani','352771839187002','081244868475',23,'Laki-laki'),(3,3,'susanto','352771839187002','081244868475',25,'Laki-laki'),(4,4,'adi rabbani','352771839187002','081244868475',23,'Laki-laki'),(5,5,'adi rabbani','352771839187002','081244868475',23,'Laki-laki'),(6,5,'aldo','23456789876456','081277392012',22,'Laki-laki'),(7,5,'nasrun','34567892098422','08129947802',24,'Laki-laki'),(12,7,'kusuam','352771839187002','081244868475',21,'Laki-laki'),(13,7,'farel','45678901241401','082123779409',20,'Laki-laki'),(14,8,'adi rabbani','352771839187002','081244868475',23,'Laki-laki'),(15,8,'ayang 1','2345678998','09817898',21,'Perempuan'),(16,8,'ayang 2','123456787656','098123719841',22,'Perempuan'),(20,12,'Farrel Setiawan','12345678910','123456789',20,'Laki-laki'),(21,13,'Farrel Setiawan','12345678910','082264468882',20,'Laki-laki'),(22,14,'Farrel Setiawan','12345678910','082264468882',20,'Laki-laki'),(23,15,'Farrel Setiawan','12345678910','082264468882',20,'Laki-laki'),(24,15,'tes','1234567890','123456789',20,'Laki-laki'),(25,15,'tes2','123456789','1234567890',20,'Laki-laki'),(26,16,'Farrel Setiawan','12345678910','082264468882',20,'Laki-laki'),(27,16,'bani','123456745','23456789',23,'Laki-laki'),(28,16,'haqi','123456789','12346785435',24,'Laki-laki'),(29,17,'Farrel Setiawan','12345678910','082264468882',20,'Laki-laki'),(30,18,'Farrel Setiawan','12345678910','082264468882',20,'Laki-laki'),(31,18,'bani','1234567890','23456789',23,'Laki-laki'),(32,18,'haqi','12345678','1234567890',24,'Laki-laki'),(35,21,'adi rabbani','352771839187002','6281218606687',23,'Laki-laki'),(37,22,'adi rabbani','352771839187002','6281218606687',22,'Laki-laki'),(38,22,'rilla amalia','3516026706050001','0812244890470',21,'Perempuan'),(39,23,'adi rabbani','352771839187002','6281218606687',22,'Laki-laki'),(40,23,'rilla amalia','3516026706050001','0812244890470',21,'Perempuan'),(41,24,'adi rabbani','352771839187002','6281218606687',22,'Perempuan'),(42,24,'rilla amalia','3516026706050001','0812244890470',21,'Perempuan'),(43,25,'adi rabbani','352771839187002','6281218606687',21,'Laki-laki'),(44,26,'Kusuma Adi','352771839187002','6281218606687',22,'Laki-laki'),(45,27,'Jarwo kwat','3527722333187001','6281218607789',19,'Laki-laki');
/*!40000 ALTER TABLE `detail_pendaki` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gunung`
--

DROP TABLE IF EXISTS `gunung`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gunung` (
  `id_gunung` int NOT NULL AUTO_INCREMENT,
  `id_kategori` int NOT NULL,
  `nama_gunung` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `lokasi` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tinggi` int DEFAULT NULL,
  `estimasi_pendakian` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `kuota_harian` int DEFAULT NULL,
  `harga_tiket` int DEFAULT NULL,
  `pos_perizinan` text COLLATE utf8mb4_general_ci,
  `status_gunung` enum('Buka','Tutup') COLLATE utf8mb4_general_ci DEFAULT 'Buka',
  `gambar` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `peta_gunung` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fasilitas_gunung` text COLLATE utf8mb4_general_ci,
  `info_terkini` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_gunung`),
  KEY `id_kategori` (`id_kategori`),
  CONSTRAINT `gunung_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori_gunung` (`id_kategori`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gunung`
--

LOCK TABLES `gunung` WRITE;
/*!40000 ALTER TABLE `gunung` DISABLE KEYS */;
INSERT INTO `gunung` VALUES (1,1,'Gunung Bromo','Probolinggo',2329,'2-4 Jam','Gunung wisata populer dengan pemandangan lautan pasir dan sunrise.',1000,35000,'Cemoro Lawang, Tosari, Ngadas','Buka','bromo.jpg','map bromo.pdf','2026-05-15 08:45:10','Basecamp Registrasi, Camping Ground, Toilet Umum, Warung Logistik, Tempat Parkir','Wajib booking online lewat situs resmi BBTNBTS.'),(2,1,'Gunung Penanggungan','Mojokerto',1653,'3-5 Jam','Gunung favorit pendaki pemula dengan jalur yang cukup ramah.',130,1000,'Tamiajeng, Jolotundo, Kedungudi','Buka','penanggungan.jpg','map penanggungan.pdf','2026-05-15 08:45:10','Pos Registrasi Simaksi, Camping Ground Puncak Bayangan, Toilet Basecamp, Warung Penjual Air, Pusat Informasi','Jalur Tamiajeng tetap jadi favorit pendakian malam minggu.'),(3,1,'Gunung Panderman','Batu',2045,'3-4 Jam','Gunung pendek dengan view Kota Batu yang indah.',250,10000,NULL,'Buka','panderman.jpg','map panderman & butak.pdf','2026-05-15 08:45:10','Pos Perizinan Latar Ombo, Camping Ground, Sumber Air Keris, Tempat Parkir Kendaraan, Shelter Istirahat','Pendaki diimbau membawa air mandiri dari basecamp.'),(4,1,'Gunung Kelud','Kediri',1731,'2-3 Jam','Gunung vulkanik dengan akses wisata yang mudah.',400,10000,NULL,'Buka','kelud.jpg','map kelud.pdf','2026-05-15 08:45:10','Jalur Wisata Beraspal, Tangga Puncak, Pos Pemantauan Vulkanik, Toilet Umum, Warung Kuliner Basecamp','Akses jalan aspal hingga area parkir atas lancar.'),(5,1,'Gunung Pundak','Mojokerto',1585,'3-5 Jam','Gunung cocok untuk latihan dan camping ringan.',200,15000,NULL,'Buka','pundak.jpg','map pundak.pdf','2026-05-15 08:45:10','Basecamp Perizinan, Camping Ground Keluarga, Akses Air Bersih Pipanisasi, Toilet Loket, Papan Petunjuk Jalur','Sangat ramai oleh pendaki keluarga saat akhir pekan.'),(6,2,'Gunung Arjuno','Malang',3339,'10-12 Jam','Gunung populer dengan trek panjang dan hutan lebat.',500,25000,NULL,'Buka','arjuno.jpg','map arjuno & welirang.pdf','2026-05-15 08:45:10','Pos Registrasi Izin, Sumber Air Alas Lali Jiwo, Lembah Kidang Camp Area, Shelter Darurat, Pos Ranger Evakuasi','Cuaca puncak tidak menentu, harap bawa perlengkapan badai.'),(7,2,'Gunung Welirang','Pasuruan',3156,'9-11 Jam','Gunung dengan jalur belerang dan trek cukup menantang.',400,25000,NULL,'Buka','welirang.jpg','map arjuno & welirang.pdf','2026-05-15 08:45:10','Pos Perizinan Utama, Pondokan Penambang Belerang, Sumber Air Kembar, Shelter Angin, Jalur Evakuasi Medis','Aktivitas penambangan belerang tradisional berjalan normal.'),(8,2,'Gunung Ijen','Banyuwangi',2799,'4-6 Jam','Terkenal dengan blue fire dan kawah asam terbesar.',600,15000,NULL,'Buka','ijen.jpg','map ijen.pdf','2026-05-15 08:45:10','Paltuding Perizinan, Toilet & Kamar Mandi, Warung Makan 24 Jam, Shelter Istirahat Jalur, Pusat Informasi Penyelamatan','Blue fire bisa dilihat jika berangkat mendaki jam 02:00 dini hari.'),(9,2,'Gunung Butak','Malang',2868,'7-9 Jam','Memiliki sabana luas dan cocok untuk camping.',300,20000,NULL,'Buka','butak.jpg','map panderman & butak.pdf','2026-05-15 08:45:10','Basecamp Simaksi resmi, Sabana Camping Ground Luas, Sumber Air Sendang, Papan Navigasi Jalur, Tempat Parkir','Mata air di area Sabana dilaporkan mengalir jernih.'),(10,2,'Gunung Kawi','Malang',2551,'5-7 Jam','Gunung dengan trek hutan dan suasana tenang.',250,15000,NULL,'Buka','kawi.jpg','map kawi.pdf','2026-05-15 08:45:10','Pos Registrasi Pendakian, Hutan Camp Area, Sumber Air Alami, Kraton Jalur Budaya, Shelter Istirahat','Jalur pendakian bersih dan suasana hutan sangat tenang.'),(11,3,'Gunung Semeru','Lumajang',3676,'12-16 Jam','Gunung tertinggi di Pulau Jawa dengan trek panjang.',700,45000,NULL,'Buka','semeru.jpg','map semeru.pdf','2026-05-15 08:45:10','Ranu Pani Registrasi, Shelter Kalimati, Ranu Kumbolo Camp Area, Sumber Mata Air, Jalur Evakuasi, Pos Ranger, Musholla, Shelter Darurat','Kuota harian dibatasi ketat, disarankan cek kuota jauh hari.'),(12,3,'Gunung Raung','Banyuwangi',3332,'14-18 Jam','Memiliki jalur ekstrem dengan kawah besar.',250,35000,NULL,'Buka','raung.jpg','map raung.pdf','2026-05-15 08:45:10','Basecamp Kalibaru, Pos 7 Camp Area, Rumah Singgah Jalur, Pusat Registrasi Alat Safety, Pos Ranger Rescue Tim','Wajib menggunakan jasa guide dan alat safety lengkap.'),(13,3,'Gunung Argopuro','Probolinggo',3088,'2-4 Hari','Pendakian terpanjang di Pulau Jawa.',200,35000,NULL,'Buka','argopuro.jpg','map argopuro.pdf','2026-05-15 08:45:10','Pos Perizinan Baderan, Pos Bremi, Cisentor Camp, Sabana Cikasur Camp, Sumber Mata Air Sungai Alami, Dermaga Cinta Danau Taman Hidup','Manajemen logistik harus matang untuk durasi minimal 3 hari.'),(14,3,'Gunung Lamongan','Lumajang',1651,'6-8 Jam','Gunung dengan trek hutan dan tanjakan curam.',150,15000,NULL,'Buka','lamongan.jpg','map lamongan.pdf','2026-05-15 08:45:10','Basecamp Mbah Guco, Camping Ground Watu Gede, Sumber Air Sumber Melati, Papan Markah Jalur, Shelter Istirahat','Sumber air Melati berfungsi dengan debit air normal.'),(15,3,'Gunung Wilis','Nganjuk',2563,'8-10 Jam','Gunung dengan jalur panjang dan minim sumber air.',200,20000,NULL,'Buka','wilis.jpg','map wilis.pdf','2026-05-15 08:45:10','Pos Registrasi Roro Wilis, Camp Area Watu Garuda, Sumber Air Sungai Kecil, Papan Markah Navigasi, Shelter Darurat','Jalur minim tanda arah, pastikan membawa navigasi offline.'),(16,4,'Gunung Piramid','Bondowoso',1521,'5-7 Jam','Gunung dengan jalur sempit dan ekstrem.',100,10000,NULL,'Buka','piramid.jpg',NULL,'2026-05-15 08:45:10','Pos Perizinan Warga, Camp Area Punggur, Papan Peringatan Bahaya Ekstrem, Tali Pengaman Jalur Punggung Naga, Pos Evakuasi Warga',NULL),(17,4,'Gunung Saeng','Bondowoso',1559,'6-8 Jam','Jalur pendakian tajam dan cukup berbahaya.',100,10000,NULL,'Buka','saeng.jpg',NULL,'2026-05-15 08:45:10','Pos Registrasi Warga Lokal, Shelter Istirahat Terbatas, Markah Jalur Alami, Tempat Parkir Swadaya',NULL),(18,4,'Gunung Bokong','Bondowoso',1150,'4-6 Jam','Gunung kecil dengan jalur vertikal dan curam.',80,10000,NULL,'Buka','bokong.jpg','map bokong.pdf','2026-05-15 08:45:10','Pos Perizinan Kampung, Markah Batas Hutan, Jalur Pendakian Vertikal Tali, Area Parkir Motor Rumah Warga',NULL),(19,4,'Gunung Argowayang','Jember',2815,'12-15 Jam','Pendakian berat dengan trek panjang dan sepi.',120,20000,NULL,'Buka','argowayang.jpg','map argowayang.pdf','2026-05-15 08:45:10','Pos Pendaftaran Swadaya, Area Camp Terbatas Hutan Belantara, Markah Navigasi Pohon, Shelter Darurat',NULL),(20,4,'Gunung Liman','Trenggalek',2563,'10-12 Jam','Gunung dengan trek liar dan minim petunjuk jalur.',100,15000,'via Kare, via Bujulan','Buka','gunung_liman_1780737969.jpg',NULL,'2026-05-15 08:45:10','Pos Perizinan Tradisional, Area Camp Terbatas Puncak, Markah Rute Alami Bantuan Komunitas, Pos Evakuasi Darurat',NULL);
/*!40000 ALTER TABLE `gunung` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gunung_logistik`
--

DROP TABLE IF EXISTS `gunung_logistik`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gunung_logistik` (
  `id_gunung_logistik` int NOT NULL AUTO_INCREMENT,
  `id_gunung` int NOT NULL,
  `id_logistik` int NOT NULL,
  `status_wajib` enum('Wajib','Disarankan') COLLATE utf8mb4_general_ci DEFAULT 'Disarankan',
  PRIMARY KEY (`id_gunung_logistik`),
  KEY `id_gunung` (`id_gunung`),
  KEY `id_logistik` (`id_logistik`),
  CONSTRAINT `gunung_logistik_ibfk_1` FOREIGN KEY (`id_gunung`) REFERENCES `gunung` (`id_gunung`) ON DELETE CASCADE,
  CONSTRAINT `gunung_logistik_ibfk_2` FOREIGN KEY (`id_logistik`) REFERENCES `logistik` (`id_logistik`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=262 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gunung_logistik`
--

LOCK TABLES `gunung_logistik` WRITE;
/*!40000 ALTER TABLE `gunung_logistik` DISABLE KEYS */;
INSERT INTO `gunung_logistik` VALUES (1,1,1,'Wajib'),(2,1,2,'Wajib'),(3,1,3,'Wajib'),(4,1,5,'Wajib'),(5,1,6,'Wajib'),(6,1,12,'Wajib'),(13,3,1,'Wajib'),(14,3,2,'Wajib'),(15,3,3,'Wajib'),(16,3,5,'Wajib'),(17,3,6,'Wajib'),(18,3,12,'Wajib'),(19,4,1,'Wajib'),(20,4,2,'Wajib'),(21,4,3,'Wajib'),(22,4,5,'Wajib'),(23,4,6,'Wajib'),(24,4,12,'Wajib'),(25,5,1,'Wajib'),(26,5,2,'Wajib'),(27,5,3,'Wajib'),(28,5,5,'Wajib'),(29,5,6,'Wajib'),(30,5,12,'Wajib'),(47,3,8,'Disarankan'),(48,3,9,'Disarankan'),(49,3,10,'Disarankan'),(50,3,11,'Disarankan'),(51,6,23,'Wajib'),(52,6,22,'Wajib'),(53,6,8,'Wajib'),(54,6,9,'Wajib'),(55,6,10,'Wajib'),(56,6,11,'Wajib'),(57,6,17,'Wajib'),(58,6,19,'Wajib'),(59,6,12,'Wajib'),(60,7,23,'Wajib'),(61,7,22,'Wajib'),(62,7,8,'Wajib'),(63,7,9,'Wajib'),(64,7,10,'Wajib'),(65,7,11,'Wajib'),(66,7,17,'Wajib'),(67,7,19,'Wajib'),(68,7,12,'Wajib'),(69,8,1,'Wajib'),(70,8,22,'Wajib'),(71,8,5,'Wajib'),(72,8,12,'Wajib'),(73,8,19,'Wajib'),(74,9,23,'Wajib'),(75,9,22,'Wajib'),(76,9,8,'Wajib'),(77,9,9,'Wajib'),(78,9,10,'Wajib'),(79,9,11,'Wajib'),(80,9,17,'Wajib'),(81,9,19,'Wajib'),(82,9,12,'Wajib'),(83,10,23,'Wajib'),(84,10,22,'Wajib'),(85,10,8,'Wajib'),(86,10,9,'Wajib'),(87,10,10,'Wajib'),(88,10,11,'Wajib'),(89,10,17,'Wajib'),(90,10,19,'Wajib'),(91,10,12,'Wajib'),(92,7,7,'Wajib'),(93,7,25,'Wajib'),(94,7,21,'Disarankan'),(95,8,7,'Wajib'),(96,8,25,'Wajib'),(97,8,16,'Disarankan'),(98,6,21,'Disarankan'),(99,6,15,'Disarankan'),(100,6,34,'Disarankan'),(101,9,21,'Disarankan'),(102,9,15,'Disarankan'),(103,9,4,'Disarankan'),(104,10,21,'Disarankan'),(105,10,14,'Disarankan'),(106,11,23,'Wajib'),(107,11,22,'Wajib'),(108,11,8,'Wajib'),(109,11,9,'Wajib'),(110,11,10,'Wajib'),(111,11,11,'Wajib'),(112,11,17,'Wajib'),(113,11,12,'Wajib'),(114,11,19,'Wajib'),(115,11,20,'Wajib'),(116,12,23,'Wajib'),(117,12,22,'Wajib'),(118,12,8,'Wajib'),(119,12,9,'Wajib'),(120,12,10,'Wajib'),(121,12,11,'Wajib'),(122,12,17,'Wajib'),(123,12,12,'Wajib'),(124,12,19,'Wajib'),(125,12,20,'Wajib'),(126,13,23,'Wajib'),(127,13,22,'Wajib'),(128,13,8,'Wajib'),(129,13,9,'Wajib'),(130,13,10,'Wajib'),(131,13,11,'Wajib'),(132,13,17,'Wajib'),(133,13,12,'Wajib'),(134,13,19,'Wajib'),(135,13,20,'Wajib'),(136,15,23,'Wajib'),(137,15,22,'Wajib'),(138,15,8,'Wajib'),(139,15,9,'Wajib'),(140,15,10,'Wajib'),(141,15,11,'Wajib'),(142,15,17,'Wajib'),(143,15,12,'Wajib'),(144,15,19,'Wajib'),(145,15,34,'Wajib'),(146,11,24,'Wajib'),(147,11,25,'Wajib'),(148,11,7,'Wajib'),(149,13,26,'Wajib'),(150,13,21,'Wajib'),(151,15,26,'Wajib'),(152,15,13,'Wajib'),(153,14,1,'Wajib'),(154,14,22,'Wajib'),(155,14,6,'Wajib'),(156,14,12,'Wajib'),(157,14,19,'Wajib'),(158,16,27,'Wajib'),(159,16,22,'Wajib'),(160,16,21,'Wajib'),(161,16,19,'Wajib'),(162,16,20,'Wajib'),(163,16,18,'Wajib'),(164,17,27,'Wajib'),(165,17,22,'Wajib'),(166,17,21,'Wajib'),(167,17,19,'Wajib'),(168,17,20,'Wajib'),(169,18,27,'Wajib'),(170,18,22,'Wajib'),(171,18,21,'Wajib'),(172,18,19,'Wajib'),(173,19,23,'Wajib'),(174,19,22,'Wajib'),(175,19,8,'Wajib'),(176,19,34,'Wajib'),(177,19,35,'Wajib'),(178,19,18,'Wajib'),(179,19,31,'Wajib'),(180,20,23,'Wajib'),(181,20,22,'Wajib'),(182,20,8,'Wajib'),(183,20,34,'Wajib'),(184,20,35,'Wajib'),(185,20,18,'Wajib'),(186,20,31,'Wajib'),(187,12,27,'Wajib'),(188,12,28,'Wajib'),(189,12,29,'Wajib'),(190,12,30,'Wajib'),(191,12,32,'Wajib'),(252,2,2,'Wajib'),(253,2,1,'Wajib'),(254,2,3,'Wajib'),(255,2,5,'Wajib'),(256,2,6,'Wajib'),(257,2,11,'Disarankan'),(258,2,10,'Disarankan'),(259,2,9,'Disarankan'),(260,2,8,'Disarankan'),(261,2,12,'Wajib');
/*!40000 ALTER TABLE `gunung_logistik` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gunung_mitra`
--

DROP TABLE IF EXISTS `gunung_mitra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gunung_mitra` (
  `id_gunung_mitra` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
  `id_gunung` int NOT NULL,
  PRIMARY KEY (`id_gunung_mitra`),
  KEY `id_user` (`id_user`),
  KEY `id_gunung` (`id_gunung`),
  CONSTRAINT `gunung_mitra_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `gunung_mitra_ibfk_2` FOREIGN KEY (`id_gunung`) REFERENCES `gunung` (`id_gunung`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gunung_mitra`
--

LOCK TABLES `gunung_mitra` WRITE;
/*!40000 ALTER TABLE `gunung_mitra` DISABLE KEYS */;
INSERT INTO `gunung_mitra` VALUES (4,15,11),(5,16,6),(6,17,1),(9,18,2);
/*!40000 ALTER TABLE `gunung_mitra` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kategori_gunung`
--

DROP TABLE IF EXISTS `kategori_gunung`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kategori_gunung` (
  `id_kategori` int NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kategori_gunung`
--

LOCK TABLES `kategori_gunung` WRITE;
/*!40000 ALTER TABLE `kategori_gunung` DISABLE KEYS */;
INSERT INTO `kategori_gunung` VALUES (1,'Mudah','Cocok untuk pendaki pemula dengan jalur relatif aman dan pendek'),(2,'Sedang','Memiliki jalur yang cukup menantang dengan durasi pendakian menengah'),(3,'Berat','Memiliki trek panjang, tanjakan curam, dan membutuhkan stamina kuat'),(4,'Extreme','Pendakian sangat berat dan membutuhkan pengalaman serta fisik yang sangat baik');
/*!40000 ALTER TABLE `kategori_gunung` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kategori_logistik`
--

DROP TABLE IF EXISTS `kategori_logistik`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kategori_logistik` (
  `id_kategori_logistik` int NOT NULL AUTO_INCREMENT,
  `id_kategori` int NOT NULL,
  `nama_kategori_logistik` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_kategori_logistik`),
  KEY `id_kategori` (`id_kategori`),
  CONSTRAINT `kategori_logistik_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori_gunung` (`id_kategori`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kategori_logistik`
--

LOCK TABLES `kategori_logistik` WRITE;
/*!40000 ALTER TABLE `kategori_logistik` DISABLE KEYS */;
INSERT INTO `kategori_logistik` VALUES (1,1,'Perlengkapan Dasar'),(2,1,'Perlengkapan Cuaca'),(3,2,'Perlengkapan Camping'),(4,2,'Perlengkapan Navigasi'),(5,2,'Perlengkapan Cuaca Dingin'),(6,3,'Perlengkapan Survival'),(7,3,'Perlengkapan Fisik'),(8,3,'Perlengkapan Gunung Tinggi'),(9,4,'Perlengkapan Extreme'),(10,4,'Perlengkapan Safety'),(11,4,'Perlengkapan Survival Extreme');
/*!40000 ALTER TABLE `kategori_logistik` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logistik`
--

DROP TABLE IF EXISTS `logistik`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logistik` (
  `id_logistik` int NOT NULL AUTO_INCREMENT,
  `id_kategori_logistik` int NOT NULL,
  `nama_logistik` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_logistik`),
  KEY `id_kategori_logistik` (`id_kategori_logistik`),
  CONSTRAINT `logistik_ibfk_1` FOREIGN KEY (`id_kategori_logistik`) REFERENCES `kategori_logistik` (`id_kategori_logistik`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logistik`
--

LOCK TABLES `logistik` WRITE;
/*!40000 ALTER TABLE `logistik` DISABLE KEYS */;
INSERT INTO `logistik` VALUES (1,1,'Daypack 20L','Tas ringan untuk pendakian singkat'),(2,1,'Botol Minum','Membawa cadangan air minum'),(3,1,'Snack Energi','Makanan ringan penambah energi'),(4,1,'Topi Gunung','Melindungi kepala dari panas'),(5,2,'Jaket Gunung','Melindungi tubuh dari angin dan dingin'),(6,2,'Jas Hujan','Digunakan saat hujan'),(7,2,'Masker','Melindungi dari debu dan pasir'),(8,3,'Tenda','Digunakan untuk camping'),(9,3,'Sleeping Bag','Menjaga suhu tubuh saat tidur'),(10,3,'Matras','Alas tidur di gunung'),(11,3,'Kompor Portable','Memasak makanan di gunung'),(12,4,'Headlamp','Penerangan saat malam'),(13,4,'Peluit','Alat darurat untuk memberi sinyal'),(14,4,'Peta Jalur','Navigasi jalur pendakian'),(15,5,'Sarung Tangan','Melindungi tangan dari dingin'),(16,5,'Buff','Melindungi wajah dari angin dingin'),(17,5,'Thermal Jacket','Jaket khusus suhu dingin'),(18,6,'Emergency Blanket','Selimut darurat untuk survival'),(19,6,'Kotak P3K','Peralatan pertolongan pertama'),(20,6,'Pisau Lipat','Peralatan survival darurat'),(21,7,'Tracking Pole','Membantu keseimbangan saat trekking'),(22,7,'Sepatu Gunung','Sepatu khusus pendakian berat'),(23,7,'Carrier 60L','Tas besar untuk ekspedisi'),(24,8,'Gaiter','Pelindung kaki dari pasir dan lumpur'),(25,8,'Kacamata Gunung','Melindungi mata dari UV'),(26,8,'Water Purifier','Menyaring air di gunung'),(27,9,'Helm Gunung','Melindungi kepala dari benturan'),(28,9,'Harness','Peralatan pengaman pendakian ekstrem'),(29,9,'Webbing','Digunakan dalam safety climbing'),(30,10,'Carabiner','Pengait pengaman climbing'),(31,10,'Senter Cadangan','Cadangan penerangan'),(32,10,'Sarung Tangan Tactical','Grip tambahan di jalur ekstrem'),(33,11,'Tenda Survival','Tenda untuk cuaca ekstrem'),(34,11,'Kompas','Navigasi manual di gunung'),(35,11,'Radio HT','Komunikasi darurat'),(36,1,'Tracking pole','Digunakan untuk bantuan keseimbangan saat mendaki');
/*!40000 ALTER TABLE `logistik` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pembayaran`
--

DROP TABLE IF EXISTS `pembayaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pembayaran` (
  `id_pembayaran` int NOT NULL AUTO_INCREMENT,
  `id_pemesanan` int NOT NULL,
  `metode_pembayaran` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_pengirim` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bukti_pembayaran` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_verifikasi` enum('Pending','Valid','Tidak Valid') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `tanggal_bayar` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pembayaran`),
  KEY `id_pemesanan` (`id_pemesanan`),
  CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_pemesanan`) REFERENCES `pemesanan` (`id_pemesanan`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pembayaran`
--

LOCK TABLES `pembayaran` WRITE;
/*!40000 ALTER TABLE `pembayaran` DISABLE KEYS */;
INSERT INTO `pembayaran` VALUES (1,2,'QRIS Otomatis','Sistem Gateway','assets/img/bukti/dummy_qris.jpg','Valid','2026-05-19 17:17:56'),(2,3,'QRIS Otomatis','Simulasi Gateway',NULL,'Valid','2026-05-19 17:22:40'),(3,4,'QRIS Otomatis','Simulasi Gateway',NULL,'Valid','2026-05-19 17:57:44'),(4,4,'QRIS Otomatis','Simulasi Gateway',NULL,'Valid','2026-05-19 17:59:02'),(5,4,'QRIS Otomatis','Simulasi Gateway',NULL,'Valid','2026-05-19 18:03:59'),(6,4,'QRIS Otomatis','Simulasi Gateway',NULL,'Valid','2026-05-19 18:08:06'),(7,5,'QRIS Otomatis','Simulasi Gateway',NULL,'Valid','2026-05-22 16:11:00'),(9,7,'QRIS Otomatis','Simulasi Gateway',NULL,'Valid','2026-05-25 03:41:02'),(10,8,'QRIS Otomatis','Simulasi Gateway',NULL,'Valid','2026-05-25 04:00:51'),(15,12,'QRIS Otomatis','Simulasi Gateway',NULL,'Valid','2026-05-31 15:32:41'),(16,13,'QRIS Otomatis','Simulasi Gateway',NULL,'Valid','2026-05-31 19:37:48'),(17,14,'QRIS Otomatis','Simulasi Gateway',NULL,'Valid','2026-06-01 05:06:08'),(18,15,'QRIS Otomatis','Simulasi Gateway',NULL,'Valid','2026-06-01 05:15:23'),(19,16,'QRIS Otomatis','Simulasi Gateway',NULL,'Valid','2026-06-01 11:28:29'),(20,17,'QRIS Otomatis','Simulasi Gateway',NULL,'Valid','2026-06-01 12:16:11'),(21,18,'QRIS Otomatis','Simulasi Gateway',NULL,'Valid','2026-06-02 02:08:50'),(22,21,'QRIS API KlikQRIS (Simulasi Lokal)','adi rabbani',NULL,'Valid','2026-06-03 15:03:21'),(23,23,'QRIS API KlikQRIS (Simulasi Lokal)','adi rabbani',NULL,'Valid','2026-06-06 10:03:23'),(24,24,'QRIS API KlikQRIS (Simulasi Lokal)','adi rabbani',NULL,'Valid','2026-06-06 10:06:02'),(25,25,'QRIS API KlikQRIS (Simulasi Lokal)','adi rabbani',NULL,'Valid','2026-06-06 10:45:46'),(26,26,'QRIS API KlikQRIS (Simulasi Lokal)','adi rabbani',NULL,'Valid','2026-06-06 14:41:36'),(27,27,'QRIS API KlikQRIS (Simulasi Lokal)','adi rabbani',NULL,'Valid','2026-06-06 15:15:21');
/*!40000 ALTER TABLE `pembayaran` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pemesanan`
--

DROP TABLE IF EXISTS `pemesanan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pemesanan` (
  `id_pemesanan` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
  `id_gunung` int NOT NULL,
  `pos_masuk` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pos_keluar` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kode_booking` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tanggal_naik` date NOT NULL,
  `tanggal_turun` date DEFAULT NULL,
  `jumlah_pendaki` int NOT NULL,
  `total_harga` int NOT NULL,
  `status_pemesanan` enum('Menunggu Pembayaran','Menunggu Verifikasi','Diterima','Ditolak','Selesai') COLLATE utf8mb4_general_ci DEFAULT 'Menunggu Pembayaran',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status_checkin` enum('Belum Check-in','Sudah Check-in') COLLATE utf8mb4_general_ci DEFAULT 'Belum Check-in',
  `status_checkout` enum('Belum Check-out','Sudah Check-out') COLLATE utf8mb4_general_ci DEFAULT 'Belum Check-out',
  `waktu_checkin` datetime DEFAULT NULL,
  `waktu_checkout` datetime DEFAULT NULL,
  PRIMARY KEY (`id_pemesanan`),
  UNIQUE KEY `kode_booking` (`kode_booking`),
  KEY `id_user` (`id_user`),
  KEY `id_gunung` (`id_gunung`),
  CONSTRAINT `pemesanan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `pemesanan_ibfk_2` FOREIGN KEY (`id_gunung`) REFERENCES `gunung` (`id_gunung`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pemesanan`
--

LOCK TABLES `pemesanan` WRITE;
/*!40000 ALTER TABLE `pemesanan` DISABLE KEYS */;
INSERT INTO `pemesanan` VALUES (2,2,2,'Tamiajeng','Tamiajeng','NY-20260519EB531C','2026-05-19','2026-05-20',1,22500,'Diterima','2026-05-19 17:17:47','Belum Check-in','Belum Check-out',NULL,NULL),(3,2,2,'Tamiajeng','Tamiajeng','NY-20260519795C87','2026-05-21','2026-05-22',1,22500,'Selesai','2026-05-19 17:21:15','Sudah Check-in','Sudah Check-out','2026-06-04 14:50:25','2026-06-04 14:51:24'),(4,2,1,'Cemoro Lawang','Cemoro Lawang','NY-2026051955821D','2026-05-20','2026-05-20',1,42500,'Diterima','2026-05-19 17:27:47','Belum Check-in','Belum Check-out',NULL,NULL),(5,2,1,'Cemoro Lawang','Cemoro Lawang','NY-2026052203CCA9','2026-05-23','2026-05-24',3,122500,'Diterima','2026-05-22 16:10:56','Belum Check-in','Belum Check-out',NULL,NULL),(7,2,8,'Jalur Utama','Jalur Utama','NY-20260525B9F730','2026-05-26','2026-05-27',2,42500,'Diterima','2026-05-25 03:40:54','Belum Check-in','Belum Check-out',NULL,NULL),(8,2,6,'Jalur Utama','Jalur Utama','NY-202605251830B5','2026-05-25','2026-05-25',3,92500,'Diterima','2026-05-25 03:58:43','Belum Check-in','Belum Check-out',NULL,NULL),(12,8,8,'Jalur Utama','Jalur Utama','NY-202605311FBD43','2026-06-05','2026-06-06',1,22500,'Menunggu Verifikasi','2026-05-31 15:32:35','Belum Check-in','Belum Check-out',NULL,NULL),(13,8,1,'Cemoro Lawang','Cemoro Lawang','NY-202605312B41C6','2026-06-01','2026-06-02',1,42500,'Menunggu Verifikasi','2026-05-31 18:46:37','Belum Check-in','Belum Check-out',NULL,NULL),(14,8,11,'Jalur Utama','Jalur Utama','NY-202606016DC6DD','2026-06-01','2026-06-01',1,52500,'Selesai','2026-06-01 05:00:33','Sudah Check-in','Sudah Check-out','2026-06-02 02:13:48','2026-06-02 02:16:01'),(15,8,8,'Jalur Utama','Jalur Utama','NY-202606015C95EE','2026-06-01','2026-06-01',3,62500,'Menunggu Verifikasi','2026-06-01 05:15:20','Belum Check-in','Belum Check-out',NULL,NULL),(16,8,11,'Jalur Utama','Jalur Utama','NY-20260601CFE058','2026-06-01','2026-06-01',3,152500,'Menunggu Verifikasi','2026-06-01 11:27:00','Belum Check-in','Belum Check-out',NULL,NULL),(17,8,11,'Jalur Utama','Jalur Utama','NY-20260601A1FC53','2026-06-27','2026-06-29',1,52500,'Menunggu Verifikasi','2026-06-01 12:16:08','Belum Check-in','Belum Check-out',NULL,NULL),(18,8,11,'Jalur Utama','Jalur Utama','NY-20260602958F7F','2026-06-02','2026-06-02',3,152500,'Selesai','2026-06-02 02:08:41','Sudah Check-in','Sudah Check-out','2026-06-02 09:12:10','2026-06-02 09:13:09'),(21,2,2,'Tamiajeng','Tamiajeng','NY-202606037F8E80','2026-06-03','2026-06-03',2,2000,'Selesai','2026-06-03 14:54:31','Sudah Check-in','Sudah Check-out','2026-06-03 22:06:02','2026-06-03 22:10:49'),(22,2,20,'via Bujulan','via Bujulan','NY-20260606CEAE8B','2026-06-06','2026-06-08',2,30000,'Menunggu Pembayaran','2026-06-06 10:02:36','Belum Check-in','Belum Check-out',NULL,NULL),(23,2,20,'via Bujulan','via Bujulan','NY-20260606CF4BAB','2026-06-06','2026-06-08',2,30000,'Menunggu Verifikasi','2026-06-06 10:02:51','Belum Check-in','Belum Check-out',NULL,NULL),(24,2,2,'Tamiajeng','Tamiajeng','NY-202606063F33D2','2026-06-06','2026-06-07',2,2000,'Menunggu Verifikasi','2026-06-06 10:04:53','Belum Check-in','Belum Check-out',NULL,NULL),(25,2,2,'Tamiajeng','Tamiajeng','NY-202606064D4786','2026-06-06','2026-06-06',1,1000,'Diterima','2026-06-06 10:44:45','Sudah Check-in','Belum Check-out','2026-06-07 14:36:46',NULL),(26,2,2,'Tamiajeng','Tamiajeng','NY-20260606317B67','2026-06-06','2026-06-07',1,1000,'Selesai','2026-06-06 14:34:33','Sudah Check-in','Sudah Check-out','2026-06-06 21:42:16','2026-06-06 21:42:31'),(27,2,2,'Kedungudi','Kedungudi','NY-202606068B3D76','2026-06-08','2026-06-09',1,1000,'Selesai','2026-06-06 15:15:15','Sudah Check-in','Sudah Check-out','2026-06-06 22:16:05','2026-06-06 22:16:17');
/*!40000 ALTER TABLE `pemesanan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengajuan_update_gunung`
--

DROP TABLE IF EXISTS `pengajuan_update_gunung`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengajuan_update_gunung` (
  `id_pengajuan` int NOT NULL AUTO_INCREMENT,
  `id_gunung` int NOT NULL,
  `id_mitra` int NOT NULL,
  `harga_tiket` int DEFAULT NULL,
  `id_kategori` int DEFAULT NULL,
  `tinggi` int DEFAULT NULL,
  `status_gunung` enum('Buka','Tutup') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estimasi_pendakian` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kuota_harian` int DEFAULT NULL,
  `pos_perizinan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fasilitas` text COLLATE utf8mb4_general_ci,
  `logistik` text COLLATE utf8mb4_general_ci,
  `info_terbaru` text COLLATE utf8mb4_general_ci,
  `status_pengajuan` enum('Menunggu Verifikasi','Diterima','Ditolak') COLLATE utf8mb4_general_ci DEFAULT 'Menunggu Verifikasi',
  `catatan_admin` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_pengajuan`),
  KEY `id_gunung` (`id_gunung`),
  KEY `id_mitra` (`id_mitra`),
  KEY `id_kategori` (`id_kategori`),
  CONSTRAINT `pengajuan_update_gunung_ibfk_1` FOREIGN KEY (`id_gunung`) REFERENCES `gunung` (`id_gunung`),
  CONSTRAINT `pengajuan_update_gunung_ibfk_2` FOREIGN KEY (`id_mitra`) REFERENCES `users` (`id_user`),
  CONSTRAINT `pengajuan_update_gunung_ibfk_3` FOREIGN KEY (`id_kategori`) REFERENCES `kategori_gunung` (`id_kategori`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengajuan_update_gunung`
--

LOCK TABLES `pengajuan_update_gunung` WRITE;
/*!40000 ALTER TABLE `pengajuan_update_gunung` DISABLE KEYS */;
INSERT INTO `pengajuan_update_gunung` VALUES (1,2,18,1000,1,1653,'Buka','3-5 Jam',300,'Tamiajeng, Jolotundo, Kedungudi','Pos Registrasi Simaksi, Camping Ground Puncak Bayangan, Toilet Basecamp, Warung Penjual Air, Pusat Informasi',NULL,'','Diterima',NULL,'2026-06-04 06:33:15','2026-06-06 06:50:11'),(2,2,18,1000,1,1653,'Buka','3-5 Jam',200,'Tamiajeng, Jolotundo, Kedungudi','Pos Registrasi Simaksi, Camping Ground Puncak Bayangan, Toilet Basecamp, Warung Penjual Air, Pusat Informasi',NULL,'Dipost diatara post 3 dan 4 hati hati tawon jahat','Diterima',NULL,'2026-06-06 13:37:19','2026-06-06 13:38:14'),(3,2,18,1000,1,1653,'Buka','3-5 Jam',149,'Tamiajeng, Jolotundo, Kedungudi','Pos Registrasi Simaksi, Camping Ground Puncak Bayangan, Toilet Basecamp, Warung Penjual Air, Pusat Informasi',NULL,'','Diterima',NULL,'2026-06-06 16:00:23','2026-06-06 16:38:44'),(4,2,18,1500,1,1653,'Buka','3-5 Jam',119,'Tamiajeng, Jolotundo, Kedungudi','Pos Registrasi Simaksi, Camping Ground Puncak Bayangan, Toilet Basecamp, Warung Penjual Air, Pusat Informasi',NULL,'','Diterima',NULL,'2026-06-06 16:39:49','2026-06-06 16:41:00'),(5,2,18,1500,1,1653,'Buka','3-5 Jam',1130,'Tamiajeng, Jolotundo, Kedungudi','Pos Registrasi Simaksi, Camping Ground Puncak Bayangan, Toilet Basecamp, Warung Penjual Air, Pusat Informasi',NULL,'','Ditolak',NULL,'2026-06-06 16:42:07','2026-06-06 16:42:52'),(6,2,18,1500,1,1653,'Buka','3-5 Jam',130,'Tamiajeng, Jolotundo, Kedungudi','Pos Registrasi Simaksi, Camping Ground Puncak Bayangan, Toilet Basecamp, Warung Penjual Air, Pusat Informasi',NULL,'','Diterima',NULL,'2026-06-06 16:43:23','2026-06-06 16:43:42'),(7,2,18,998,1,1653,'Buka','3-5 Jam',130,'Tamiajeng, Jolotundo, Kedungudi','Pos Registrasi Simaksi, Camping Ground Puncak Bayangan, Toilet Basecamp, Warung Penjual Air, Pusat Informasi',NULL,'','Diterima',NULL,'2026-06-06 16:47:24','2026-06-07 07:47:29'),(8,2,18,1000,1,1653,'Buka','3-5 Jam',130,'Tamiajeng, Jolotundo, Kedungudi','Pos Registrasi Simaksi, Camping Ground Puncak Bayangan, Toilet Basecamp, Warung Penjual Air, Pusat Informasi',NULL,'','Diterima',NULL,'2026-06-07 07:49:26','2026-06-07 07:58:01');
/*!40000 ALTER TABLE `pengajuan_update_gunung` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengajuan_update_logistik`
--

DROP TABLE IF EXISTS `pengajuan_update_logistik`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengajuan_update_logistik` (
  `id_pengajuan_logistik` int NOT NULL AUTO_INCREMENT,
  `id_pengajuan` int NOT NULL,
  `id_logistik` int NOT NULL,
  `status_wajib` enum('Wajib','Disarankan') COLLATE utf8mb4_general_ci DEFAULT 'Wajib',
  PRIMARY KEY (`id_pengajuan_logistik`),
  KEY `id_pengajuan` (`id_pengajuan`),
  KEY `id_logistik` (`id_logistik`),
  CONSTRAINT `pengajuan_update_logistik_ibfk_1` FOREIGN KEY (`id_pengajuan`) REFERENCES `pengajuan_update_gunung` (`id_pengajuan`) ON DELETE CASCADE,
  CONSTRAINT `pengajuan_update_logistik_ibfk_2` FOREIGN KEY (`id_logistik`) REFERENCES `logistik` (`id_logistik`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengajuan_update_logistik`
--

LOCK TABLES `pengajuan_update_logistik` WRITE;
/*!40000 ALTER TABLE `pengajuan_update_logistik` DISABLE KEYS */;
INSERT INTO `pengajuan_update_logistik` VALUES (1,1,2,'Wajib'),(2,1,1,'Wajib'),(3,1,3,'Wajib'),(4,1,5,'Wajib'),(5,1,6,'Wajib'),(6,1,11,'Disarankan'),(7,1,10,'Disarankan'),(8,1,9,'Disarankan'),(9,1,8,'Disarankan'),(10,1,12,'Wajib'),(11,2,2,'Wajib'),(12,2,1,'Wajib'),(13,2,3,'Wajib'),(14,2,5,'Wajib'),(15,2,6,'Wajib'),(16,2,11,'Disarankan'),(17,2,10,'Disarankan'),(18,2,9,'Disarankan'),(19,2,8,'Disarankan'),(20,2,12,'Wajib'),(21,3,2,'Wajib'),(22,3,1,'Wajib'),(23,3,3,'Wajib'),(24,3,5,'Wajib'),(25,3,6,'Wajib'),(26,3,11,'Disarankan'),(27,3,10,'Disarankan'),(28,3,9,'Disarankan'),(29,3,8,'Disarankan'),(30,3,12,'Wajib'),(31,4,2,'Wajib'),(32,4,1,'Wajib'),(33,4,3,'Wajib'),(34,4,5,'Wajib'),(35,4,6,'Wajib'),(36,4,11,'Disarankan'),(37,4,10,'Disarankan'),(38,4,9,'Disarankan'),(39,4,8,'Disarankan'),(40,4,12,'Wajib'),(41,5,2,'Wajib'),(42,5,1,'Wajib'),(43,5,3,'Wajib'),(44,5,5,'Wajib'),(45,5,6,'Wajib'),(46,5,11,'Disarankan'),(47,5,10,'Disarankan'),(48,5,9,'Disarankan'),(49,5,8,'Disarankan'),(50,5,12,'Wajib'),(51,6,2,'Wajib'),(52,6,1,'Wajib'),(53,6,3,'Wajib'),(54,6,5,'Wajib'),(55,6,6,'Wajib'),(56,6,11,'Disarankan'),(57,6,10,'Disarankan'),(58,6,9,'Disarankan'),(59,6,8,'Disarankan'),(60,6,12,'Wajib'),(61,7,2,'Wajib'),(62,7,1,'Wajib'),(63,7,3,'Wajib'),(64,7,5,'Wajib'),(65,7,6,'Wajib'),(66,7,11,'Disarankan'),(67,7,10,'Disarankan'),(68,7,9,'Disarankan'),(69,7,8,'Disarankan'),(70,7,12,'Wajib'),(71,8,2,'Wajib'),(72,8,1,'Wajib'),(73,8,3,'Wajib'),(74,8,5,'Wajib'),(75,8,6,'Wajib'),(76,8,11,'Disarankan'),(77,8,10,'Disarankan'),(78,8,9,'Disarankan'),(79,8,8,'Disarankan'),(80,8,12,'Wajib');
/*!40000 ALTER TABLE `pengajuan_update_logistik` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id_review` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
  `id_gunung` int NOT NULL,
  `rating` int NOT NULL,
  `komentar` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_review`),
  KEY `id_user` (`id_user`),
  KEY `id_gunung` (`id_gunung`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`),
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`id_gunung`) REFERENCES `gunung` (`id_gunung`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,1,1,5,'Sunrise di Bromo sangat indah dan akses jalur mudah.','2026-05-15 22:12:49'),(2,2,1,4,'Lautan pasirnya keren banget untuk foto.','2026-05-15 22:12:49'),(3,1,2,4,'Cocok untuk pendaki pemula dan camping santai.','2026-05-15 22:12:49'),(5,1,3,4,'Pendakian singkat dengan udara yang sejuk.','2026-05-15 22:12:49'),(6,2,3,4,'Pemandangan Kota Batu dari atas sangat bagus.','2026-05-15 22:12:49'),(7,1,4,3,'Jalur cukup mudah dan cocok wisata keluarga.','2026-05-15 22:12:49'),(8,2,4,4,'Kawah Kelud sangat menarik dikunjungi.','2026-05-15 22:12:49'),(9,1,5,4,'Camping ground nyaman dan suasana tenang.','2026-05-15 22:12:49'),(10,2,5,3,'Track lumayan menanjak untuk pemula.','2026-05-15 22:12:49'),(11,1,6,5,'Track panjang tapi pemandangannya luar biasa.','2026-05-15 22:12:49'),(12,2,6,5,'Arjuno favorit saya untuk latihan stamina.','2026-05-15 22:12:49'),(13,1,7,4,'Jalur belerang cukup unik dan menantang.','2026-05-15 22:12:49'),(14,2,7,4,'Cocok untuk pendaki yang suka trek panjang.','2026-05-15 22:12:49'),(15,1,8,5,'Blue fire Ijen sangat keren dilihat langsung.','2026-05-15 22:12:49'),(16,2,8,5,'Pendakian malam yang sangat berkesan.','2026-05-15 22:12:49'),(17,1,9,4,'Sabana luas dan cocok untuk camping ramai.','2026-05-15 22:12:49'),(18,2,9,4,'View sunrise di Butak bagus sekali.','2026-05-15 22:12:49'),(19,1,10,3,'Jalur cukup sepi dan tenang.','2026-05-15 22:12:49'),(20,2,10,4,'Hutan masih sangat asri dan alami.','2026-05-15 22:12:49'),(21,1,11,5,'Semeru memberikan pengalaman luar biasa.','2026-05-15 22:12:49'),(22,2,11,5,'Ranu Kumbolo sangat indah dan menenangkan.','2026-05-15 22:12:49'),(23,1,12,4,'Track sangat melelahkan tapi worth it.','2026-05-15 22:12:49'),(24,2,12,5,'Raung cocok untuk pendaki berpengalaman.','2026-05-15 22:12:49'),(25,1,13,5,'Pendakian panjang terbaik di Jawa.','2026-05-15 22:12:49'),(26,2,13,4,'Butuh stamina tinggi untuk Argopuro.','2026-05-15 22:12:49'),(27,1,14,3,'Jalur cukup curam dan licin saat hujan.','2026-05-15 22:12:49'),(28,2,14,4,'Suasana hutannya masih sangat alami.','2026-05-15 22:12:49'),(29,1,15,4,'Track panjang dan menantang stamina.','2026-05-15 22:12:49'),(30,2,15,3,'Sumber air cukup minim di beberapa titik.','2026-05-15 22:12:49'),(31,1,16,5,'Gunung Piramid benar-benar ekstrem.','2026-05-15 22:12:49'),(32,2,16,4,'Jalur sempit membuat adrenalin naik.','2026-05-15 22:12:49'),(33,1,17,5,'Pendakian sangat berat namun seru.','2026-05-15 22:12:49'),(34,2,17,4,'Track tajam dan perlu ekstra hati-hati.','2026-05-15 22:12:49'),(35,1,18,4,'Jalur vertikal cukup menguras tenaga.','2026-05-15 22:12:49'),(36,2,18,5,'Pendakian singkat tapi ekstrem.','2026-05-15 22:12:49'),(37,1,19,5,'Track panjang dan sangat sepi.','2026-05-15 22:12:49'),(38,2,19,4,'Cocok untuk survival dan latihan mental.','2026-05-15 22:12:49'),(39,1,20,4,'Jalur liar dan minim petunjuk arah.','2026-05-15 22:12:49'),(40,2,20,5,'Pendakian Liman sangat menantang.','2026-05-15 22:12:49'),(41,2,2,3,'Banyak sampah sekarang dan banyak monyet. terlalu banyak orang jualan. dan penegakan peraturan kebersihanya kurang','2026-06-06 15:07:29'),(42,2,2,2,'[NY-202606068B3D76] tracknya berat anjr katanya tiktok cocok untuk pemula','2026-06-06 15:26:57'),(43,2,2,5,'[NY-20260519795C87] baguss viewnya diatas baguss','2026-06-06 15:30:40');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id_role` int NOT NULL AUTO_INCREMENT,
  `nama_role` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_role`),
  UNIQUE KEY `nama_role` (`nama_role`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (3,'admin'),(2,'mitra'),(1,'pengguna');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_profile`
--

DROP TABLE IF EXISTS `user_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_profile` (
  `id_profile` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nik` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `no_hp` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `foto_profile` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_profile`),
  UNIQUE KEY `nik` (`nik`),
  KEY `fk_profile_user` (`id_user`),
  CONSTRAINT `fk_profile_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_profile`
--

LOCK TABLES `user_profile` WRITE;
/*!40000 ALTER TABLE `user_profile` DISABLE KEYS */;
INSERT INTO `user_profile` VALUES (1,1,'El','3512011312090003','082133459879','assets/img/profile/el.jpg',NULL),(2,2,'adi rabbani','352771839187002','6281218606687','assets/img/profile/profile_2_1779680350.jpg','ACC BANG'),(3,3,'pendaki3','356112934019','0823122412',NULL,NULL),(8,8,'Farrel Setiawan','12345678910','082264468882','assets/img/profile/profile_8_1780251491.jpg','tes'),(14,14,'tes','12345678901','12345678098765','assets/img/profile/profile_template.png',''),(15,15,'Mitra Ranu Pani','MITRA-SEMERU-001','081111111111',NULL,'Mitra pengelola Gunung Semeru'),(16,16,'Mitra Tretes Arjuno','MITRA-ARJUNO-001','082222222222',NULL,'Mitra pengelola Gunung Arjuno'),(17,17,'Mitra Cemoro Lawang','MITRA-BROMO-001','083333333333',NULL,'Mitra pengelola Gunung Bromo'),(18,18,'Mitra Penanggungan','MITRA-PENANGGUNGAN-001','08129988002',NULL,'Mitra pengelola Gunung Penanggungan'),(19,19,'Administrator NdakiYuk','ADMIN-NDAKIYUK-001','081234567890',NULL,'Pusat kendali otoritas data navigasi dan verifikasi jalur.');
/*!40000 ALTER TABLE `user_profile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `id_role` int NOT NULL DEFAULT '1',
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_users_role` (`id_role`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'el23','el@gmail.com','$2y$10$zSjJYEoeOMDCDPkHXsyyN.Scj71tJUOF0PX03tmjyZqMP7aevXny.','2026-05-14 18:32:39'),(2,1,'adi','adi@gmail.com','$2y$10$/CLSFCmNfLQyTsDoZ9AhDuOmmvOSNI.2NsukYOUMfD1oyy935S61K','2026-05-15 08:09:34'),(3,1,'pendaki3','pendaki3@gmail.com','$2y$10$Ikg3yp66DigejC.EuJ1WPeYFgvexAyW8uQDsFH9qm6ZG6i5XHbd.K','2026-05-24 13:00:09'),(8,1,'zwan','farrel@gmail.com','$2y$10$aMh3e4.gZx0FuYXUur55kukIm8tZvuHFlcvOPFhuwrVgKmdWPoCAK','2026-05-31 15:26:41'),(14,1,'lucy','lucy@gmail.com','$2y$10$zRaeAGc2.qc9mruZfJRxquI/N1u22OxBU/iXKLfRATvaMpIH3GYgi','2026-06-01 08:45:18'),(15,2,'mitra_semeru','mitra.semeru@gmail.com','$2y$10$XEk/lTPqiqlvOpzqmn8dv.Q27rWAzLPpgld/Ox8iW4UzmrA0/XcHq','2026-06-01 09:13:47'),(16,2,'mitra_arjuno','mitra.arjuno@gmail.com','$2y$10$UWdUJhxGX1dpOVlpiUNkhuivnTo1vnZxXnQJjA7dfkwckXtYkl7Su','2026-06-01 09:13:47'),(17,2,'mitra_bromo','mitra.bromo@gmail.com','$2y$10$Fjch.tsrGij7eD2hqnzEa.PQZI95KUo1Tk2iswHJO1qbMX.CzGQ8a','2026-06-01 09:13:47'),(18,2,'mitra_penanggungan','mitra.penanggungan@gmail.com','$2y$10$0FSbIi73g3LN40cS7FFMfOV5T5EpDA/BBymlJ1sijJlS94hYFl8S6','2026-06-03 14:24:18'),(19,3,'ndakiyuk','admin.ndakiyuk@gmail.com','$2y$10$hi5d49oQmpt2Yia.uie7IuYxV5hJp302A5qpBu6qj3wcaZ5b6tMJy','2026-06-06 06:00:29');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-07 15:52:59
