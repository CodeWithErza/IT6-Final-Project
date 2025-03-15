-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: erc_pos
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (2,1,'login','users',7,NULL,NULL,'2025-03-09 05:23:13'),(3,1,'login','users',1,NULL,NULL,'2025-03-09 05:32:45'),(5,1,'login','users',1,NULL,NULL,'2025-03-09 05:48:20'),(6,1,'create','menu_items',1,NULL,'{\"name\":\"Sprite\",\"category_id\":\"1\",\"price\":\"15\",\"is_inventory_item\":1,\"image_path\":\"uploads\\/menu_items\\/menu_67cd2e8e21e9c3.99026552.jpg\",\"initial_stock\":\"50\"}','2025-03-09 06:00:46'),(7,8,'login','users',8,NULL,NULL,'2025-03-09 06:01:55'),(8,8,'login','users',8,NULL,NULL,'2025-03-09 06:02:06'),(9,1,'login','users',1,NULL,NULL,'2025-03-09 06:02:16'),(10,1,'create','categories',4,NULL,'{\"name\":\"Main Dish\"}','2025-03-09 06:17:32'),(11,1,'update','categories',1,'{\"name\":\"Beverages\"}','{\"name\":\"Beverage\"}','2025-03-09 06:36:59'),(12,1,'update','categories',1,'{\"name\":\"Beverage\"}','{\"name\":\"Beverages\"}','2025-03-09 06:37:04'),(13,1,'create','menu_items',2,NULL,'{\"name\":\"Fried Chicken\",\"category_id\":\"4\",\"price\":\"55\",\"is_inventory_item\":0,\"image_path\":\"uploads\\/menu_items\\/menu_67cd37c9eb9e15.47496026.webp\",\"initial_stock\":0}','2025-03-09 06:40:09'),(14,1,'create','categories',5,NULL,'{\"name\":\"Add-ons\",\"created_by\":1}','2025-03-09 06:40:22'),(15,1,'create','menu_items',3,NULL,'{\"name\":\"Gravy\",\"category_id\":\"5\",\"price\":\"10\",\"is_inventory_item\":0,\"image_path\":\"uploads\\/menu_items\\/menu_67cd380caecb74.60702431.jpg\",\"initial_stock\":0}','2025-03-09 06:41:16'),(16,1,'create','menu_items',4,NULL,'{\"name\":\"Dinuguan\",\"category_id\":\"4\",\"price\":\"75\",\"is_inventory_item\":0,\"image_path\":\"uploads\\/menu_items\\/menu_67cd383b0eff59.91239248.webp\",\"initial_stock\":0}','2025-03-09 06:42:03'),(17,1,'update','categories',5,'{\"name\":\"Add-ons\"}','{\"name\":\"Add-on\"}','2025-03-09 06:54:43'),(18,8,'login','users',8,NULL,NULL,'2025-03-09 08:01:47'),(19,1,'login','users',1,NULL,NULL,'2025-03-09 23:38:19'),(20,1,'create','menu_items',5,NULL,'{\"name\":\"Cobra\",\"category_id\":\"1\",\"price\":\"20\",\"is_inventory_item\":1,\"image_path\":\"uploads\\/menu_items\\/menu_67ce26d9515536.75981576.png\",\"initial_stock\":\"50\"}','2025-03-09 23:40:09'),(21,1,'create','menu_items',6,NULL,'{\"name\":\"Pork Adobo\",\"category_id\":\"4\",\"price\":\"75\",\"is_inventory_item\":0,\"image_path\":\"uploads\\/menu_items\\/menu_67ce26fd8f5cf9.70953515.jpg\",\"initial_stock\":0}','2025-03-09 23:40:45'),(22,1,'create','menu_items',7,NULL,'{\"name\":\"Beef Caldereta\",\"category_id\":\"4\",\"price\":\"75\",\"is_inventory_item\":0,\"image_path\":\"uploads\\/menu_items\\/menu_67ce271a8d6728.14818456.jpg\",\"initial_stock\":0}','2025-03-09 23:41:14'),(23,1,'create','menu_items',8,NULL,'{\"name\":\"Pancit\",\"category_id\":\"4\",\"price\":\"75\",\"is_inventory_item\":0,\"image_path\":\"uploads\\/menu_items\\/menu_67ce27e284af30.39274602.webp\",\"initial_stock\":0}','2025-03-09 23:44:34'),(24,1,'create','menu_items',9,NULL,'{\"name\":\"Coke\",\"category_id\":\"1\",\"price\":\"15\",\"is_inventory_item\":1,\"image_path\":\"uploads\\/menu_items\\/menu_67ce2820828a23.44625619.webp\",\"initial_stock\":\"50\"}','2025-03-09 23:45:36'),(25,1,'create','menu_items',10,NULL,'{\"name\":\"Longganisa\",\"category_id\":\"4\",\"price\":\"75\",\"is_inventory_item\":0,\"image_path\":\"uploads\\/menu_items\\/menu_67ce2876ca40b8.80353022.jpg\",\"initial_stock\":0}','2025-03-09 23:47:02'),(26,1,'create','menu_items',11,NULL,'{\"name\":\"Rice\",\"category_id\":\"5\",\"price\":\"20\",\"is_inventory_item\":0,\"image_path\":\"uploads\\/menu_items\\/menu_67ce28f47e0660.94906399.webp\",\"initial_stock\":0}','2025-03-09 23:49:08'),(27,1,'login','users',1,NULL,NULL,'2025-03-10 07:52:13'),(28,1,'login','users',1,NULL,NULL,'2025-03-10 07:58:29'),(29,1,'login','users',1,NULL,NULL,'2025-03-10 08:22:17'),(30,1,'create','menu_items',12,NULL,'{\"name\":\"Fruit Soda\",\"category_id\":\"1\",\"price\":\"15\",\"is_inventory_item\":1,\"image_path\":\"uploads\\/menu_items\\/menu_67cea18b735258.43920459.webp\",\"initial_stock\":\"50\"}','2025-03-10 08:23:39'),(31,1,'create','menu_items',13,NULL,'{\"name\":\"Test\",\"category_id\":\"4\",\"price\":\"75\",\"is_inventory_item\":0,\"image_path\":null,\"initial_stock\":0}','2025-03-10 08:24:06'),(32,1,'create','categories',6,NULL,'{\"name\":\"Dessert\",\"created_by\":1}','2025-03-10 08:25:20'),(33,1,'update','categories',6,'{\"name\":\"Dessert\"}','{\"name\":\"Desserts\"}','2025-03-10 08:25:29'),(34,1,'delete','categories',6,'{\"name\":\"Desserts\"}',NULL,'2025-03-10 08:25:43'),(35,1,'login','users',1,NULL,NULL,'2025-03-11 06:00:47'),(36,1,'create','expenses',1,NULL,'{\"description\":\"Oil\",\"expense_type\":\"ingredient\",\"amount\":300,\"expense_date\":\"2025-03-11\",\"multiple_items\":\"Yes\"}','2025-03-11 22:37:36'),(37,1,'login','users',1,NULL,NULL,'2025-03-12 01:06:04'),(38,1,'login','users',1,NULL,NULL,'2025-03-12 01:06:17'),(39,1,'login','users',1,NULL,NULL,'2025-03-12 01:08:08'),(40,1,'create','expenses',2,NULL,'{\"description\":\"2 ingredient items from Palengke\",\"expense_type\":\"ingredient\",\"amount\":588,\"expense_date\":\"2025-03-12\",\"multiple_items\":\"Yes\"}','2025-03-12 07:39:22'),(41,1,'login','users',1,NULL,NULL,'2025-03-13 11:41:07'),(42,1,'login','users',1,NULL,NULL,'2025-03-13 11:44:18'),(43,1,'login','users',1,NULL,NULL,'2025-03-13 12:03:05'),(44,1,'login','users',1,NULL,NULL,'2025-03-13 12:23:56'),(45,8,'login','users',8,NULL,NULL,'2025-03-13 12:56:02'),(46,1,'login','users',1,NULL,NULL,'2025-03-13 12:56:24'),(47,8,'login','users',8,NULL,NULL,'2025-03-13 12:56:51'),(48,1,'login','users',1,NULL,NULL,'2025-03-13 13:12:26'),(49,1,'login','users',1,NULL,NULL,'2025-03-13 14:23:08'),(50,1,'login','users',1,NULL,NULL,'2025-03-13 14:24:00'),(51,1,'login','users',1,NULL,NULL,'2025-03-13 14:54:05'),(52,1,'login','users',1,NULL,NULL,'2025-03-13 14:56:17'),(53,1,'login','users',1,NULL,NULL,'2025-03-13 15:02:14'),(54,1,'login','users',1,NULL,NULL,'2025-03-13 15:03:46'),(55,1,'login','users',1,NULL,NULL,'2025-03-13 20:57:11'),(56,1,'login','users',1,NULL,NULL,'2025-03-14 13:09:10'),(57,1,'update','expenses',2,'{\"description\":\"2 ingredient items from Palengke\",\"amount\":\"588.00\",\"expense_type\":\"ingredient\",\"expense_date\":\"2025-03-12\",\"supplier\":\"Palengke\",\"invoice_number\":\"\",\"notes\":\"Budget for 1 month\\r\\n\\r\\nITEMS INCLUDED:\\r\\nEgg (24 pcs) - \\u20b112.00\\r\\nOil (1 L) - \\u20b1300.00\"}','{\"description\":\"2 ingredient items from Palengke\",\"amount\":588,\"expense_type\":\"ingredient\",\"expense_date\":\"2025-03-12\",\"supplier\":\"Palengke\",\"invoice_number\":\"\",\"notes\":\"Budget for 1 month\\r\\n\\r\\nITEMS INCLUDED:\\r\\nEgg (24 pcs) - \\u20b1200\\r\\nOil (1 L) - \\u20b1300\"}','2025-03-14 13:24:31'),(58,1,'update','expenses',2,'{\"description\":\"2 ingredient items from Palengke\",\"amount\":\"588.00\",\"expense_type\":\"ingredient\",\"expense_date\":\"2025-03-12\",\"supplier\":\"Palengke\",\"invoice_number\":\"\",\"notes\":\"Budget for 1 month\\r\\n\\r\\nITEMS INCLUDED:\\r\\nEgg (24 pcs) - \\u20b1200\\r\\nOil (1 L) - \\u20b1300\"}','{\"description\":\"2 ingredient items from Palengke\",\"amount\":588,\"expense_type\":\"ingredient\",\"expense_date\":\"2025-03-12\",\"supplier\":\"Palengke\",\"invoice_number\":\"0002\",\"notes\":\"Budget for 1 month\\r\\n\\r\\nITEMS INCLUDED:\\r\\nEgg (24 pcs) - \\u20b1300\\r\\nOil (1 L) - \\u20b1300\"}','2025-03-14 13:25:50'),(59,1,'create','expenses',3,NULL,'{\"description\":\"Test Expense\",\"expense_type\":\"other\",\"amount\":100,\"expense_date\":\"2025-03-14\",\"multiple_items\":\"No\",\"direct_submit\":\"No\"}','2025-03-14 16:10:49'),(60,1,'create','expenses',6,NULL,'{\"description\":\"Test Direct Expense 3\\/15\\/2025, 12:44:28 AM\",\"expense_type\":\"other\",\"amount\":100,\"expense_date\":\"2025-03-14\",\"multiple_items\":\"No\"}','2025-03-14 16:44:28'),(61,1,'create','expenses',7,NULL,'{\"description\":\"Test Direct Expense 3\\/15\\/2025, 12:44:37 AM\",\"expense_type\":\"other\",\"amount\":100,\"expense_date\":\"2025-03-14\",\"multiple_items\":\"No\"}','2025-03-14 16:44:37'),(62,1,'create','expenses',8,NULL,'{\"description\":\"rice\",\"expense_type\":\"ingredient\",\"amount\":55,\"expense_date\":\"2025-03-14\",\"multiple_items\":\"Yes\"}','2025-03-14 17:09:29'),(63,1,'deactivate','menu_items',13,'{\"is_active\":1}','{\"is_active\":0}','2025-03-14 23:40:09'),(64,1,'deactivate','menu_items',13,'{\"is_active\":1}','{\"is_active\":0}','2025-03-14 23:40:24'),(65,1,'update','menu_items',13,'{\"name\":\"Test\",\"category_id\":4,\"price\":\"75.00\",\"image_path\":null,\"is_active\":0}','{\"name\":\"Test\",\"category_id\":\"4\",\"price\":\"75.00\",\"image_path\":null,\"is_active\":1}','2025-03-14 23:41:27'),(66,1,'update','menu_items',13,'{\"name\":\"Test\",\"category_id\":4,\"price\":\"75.00\",\"image_path\":null,\"is_active\":1}','{\"name\":\"Test\",\"category_id\":\"4\",\"price\":\"75.00\",\"image_path\":null,\"is_active\":0}','2025-03-14 23:41:34'),(67,1,'delete','menu_items',13,'{\"id\":13,\"category_id\":4,\"name\":\"Test\",\"price\":\"75.00\",\"is_inventory_item\":0,\"current_stock\":0,\"is_active\":0,\"image_path\":null,\"created_at\":\"2025-03-10 16:24:06\",\"updated_at\":\"2025-03-15 07:41:34\",\"created_by\":1,\"updated_by\":1}',NULL,'2025-03-14 23:42:38'),(68,1,'delete','menu_items',1,'{\"id\":1,\"category_id\":1,\"name\":\"Sprite\",\"price\":\"15.00\",\"is_inventory_item\":1,\"current_stock\":48,\"is_active\":1,\"image_path\":\"uploads\\/menu_items\\/menu_67cd2e8e21e9c3.99026552.jpg\",\"created_at\":\"2025-03-09 14:00:46\",\"updated_at\":\"2025-03-09 14:52:01\",\"created_by\":1,\"updated_by\":null}',NULL,'2025-03-14 23:43:28'),(69,1,'soft_delete','menu_items',12,'{\"id\":12,\"category_id\":1,\"name\":\"Fruit Soda\",\"price\":\"15.00\",\"is_inventory_item\":1,\"current_stock\":0,\"is_active\":1,\"image_path\":\"uploads\\/menu_items\\/menu_67cea18b735258.43920459.webp\",\"created_at\":\"2025-03-10 16:23:39\",\"updated_at\":\"2025-03-10 16:23:39\",\"created_by\":1,\"updated_by\":null}','{\"is_active\":0,\"is_deleted\":1}','2025-03-14 23:53:06'),(70,1,'soft_delete','menu_items',5,'{\"id\":5,\"category_id\":1,\"name\":\"Cobra\",\"price\":\"20.00\",\"is_inventory_item\":1,\"current_stock\":55,\"is_active\":1,\"image_path\":\"uploads\\/menu_items\\/menu_67ce26d9515536.75981576.png\",\"created_at\":\"2025-03-10 07:40:09\",\"updated_at\":\"2025-03-10 16:27:15\",\"created_by\":1,\"updated_by\":null,\"is_deleted\":0}','{\"is_active\":0,\"is_deleted\":1}','2025-03-14 23:53:17'),(71,1,'deactivate','menu_items',7,'{\"is_active\":1}','{\"is_active\":0}','2025-03-15 00:04:47'),(72,1,'deactivate','menu_items',5,'{\"is_active\":1}','{\"is_active\":0}','2025-03-15 00:04:58'),(73,1,'create','expenses',9,NULL,'{\"description\":\"Rice\",\"expense_type\":\"ingredient\",\"amount\":12,\"expense_date\":\"2025-03-15\",\"multiple_items\":\"Yes\"}','2025-03-15 01:43:21'),(74,1,'update','categories',5,'{\"name\":\"Add-on\"}','{\"name\":\"Add-on\"}','2025-03-15 01:59:42'),(75,1,'update','categories',5,'{\"name\":\"Add-on\"}','{\"name\":\"Add-on\"}','2025-03-15 02:02:25'),(76,1,'update','categories',1,'{\"name\":\"Beverages\"}','{\"name\":\"Beverages\"}','2025-03-15 02:16:19'),(77,1,'update','categories',1,'{\"name\":\"Beverages\"}','{\"name\":\"Beverages\"}','2025-03-15 02:16:29'),(78,1,'update','categories',5,'{\"name\":\"Add-on\"}','{\"name\":\"Add-on\"}','2025-03-15 02:16:33'),(79,1,'create','menu_items',14,NULL,'{\"name\":\"test2\",\"category_id\":\"5\",\"price\":\"12\",\"is_inventory_item\":0,\"image_path\":null,\"initial_stock\":0}','2025-03-15 03:08:18'),(80,1,'create','expenses',10,NULL,'{\"description\":\"Egg\",\"expense_type\":\"ingredient\",\"amount\":14,\"expense_date\":\"2025-03-15\",\"multiple_items\":\"Yes\"}','2025-03-15 03:18:19'),(81,1,'login','users',1,NULL,NULL,'2025-03-15 04:42:52'),(82,1,'update','menu_items',7,'{\"name\":\"Beef Caldereta\",\"category_id\":4,\"price\":\"75.00\",\"image_path\":\"uploads\\/menu_items\\/menu_67ce271a8d6728.14818456.jpg\",\"is_active\":0}','{\"name\":\"Beef Caldereta\",\"category_id\":\"4\",\"price\":\"75.00\",\"image_path\":\"uploads\\/menu_items\\/menu_67ce271a8d6728.14818456.jpg\",\"is_active\":1}','2025-03-15 04:44:39');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `categories_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Beverages','2025-03-09 04:51:12',NULL,'2025-03-15 02:16:29',NULL,1),(4,'Main Dish','2025-03-09 06:17:32',NULL,'2025-03-09 06:30:19',NULL,1),(5,'Add-on','2025-03-09 06:40:22',1,'2025-03-15 02:16:33',NULL,1);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL,
  `expense_type` enum('ingredient','utility','salary','rent','equipment','maintenance','other') NOT NULL DEFAULT 'other',
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_expense_type` (`expense_type`),
  KEY `idx_expense_date` (`expense_date`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `expenses_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (1,'Oil','ingredient',300.00,'2025-03-11','SM Supermarket','0001','ITEMS INCLUDED:\nOil (1 L) - ₱300.00',NULL,'2025-03-11 22:37:36',1,NULL,'2025-03-13 13:29:14'),(2,'2 ingredient items from Palengke','ingredient',588.00,'2025-03-12','Palengke','0002','Budget for 1 month\r\n\r\nITEMS INCLUDED:\r\nEgg (24 pcs) - ₱300\r\nOil (1 L) - ₱300',NULL,'2025-03-12 07:39:22',1,1,'2025-03-14 13:25:50'),(3,'Test Expense','other',100.00,'2025-03-14','','','',NULL,'2025-03-14 16:10:49',1,NULL,'2025-03-14 16:10:49'),(4,'Test Expense 2025-03-14 17:25:31','other',100.00,'2025-03-14','Test Supplier','TEST-9742','This is a test expense created by the test script.',NULL,'2025-03-14 16:25:31',1,NULL,'2025-03-14 16:25:31'),(5,'Test Expense 2025-03-14 17:35:22','other',100.00,'2025-03-14','Test Supplier','TEST-6038','Test submission from test_expense_form.php',NULL,'2025-03-14 16:35:22',1,NULL,'2025-03-14 16:35:22'),(6,'Test Direct Expense 3/15/2025, 12:44:28 AM','other',100.00,'2025-03-14','Test Supplier','TEST-5469','This is a test expense created by the test button.',NULL,'2025-03-14 16:44:28',1,NULL,'2025-03-14 16:44:28'),(7,'Test Direct Expense 3/15/2025, 12:44:37 AM','other',100.00,'2025-03-14','Test Supplier','TEST-7413','This is a test expense created by the test button.',NULL,'2025-03-14 16:44:37',1,NULL,'2025-03-14 16:44:37'),(8,'rice','ingredient',55.00,'2025-03-14','h ghv','23456','ITEMS INCLUDED:\nrice (1 pcs) - ₱55.00',NULL,'2025-03-14 17:09:29',1,NULL,'2025-03-14 17:09:29'),(9,'Rice','ingredient',12.00,'2025-03-15','','','ITEMS INCLUDED:\nRice (1 pcs) - ₱12.00',NULL,'2025-03-15 01:43:21',1,NULL,'2025-03-15 01:43:21'),(10,'Egg','ingredient',14.00,'2025-03-15','Palengke','320','ITEMS INCLUDED:\nEgg (1 pcs) - ₱14.00',NULL,'2025-03-15 03:18:19',1,NULL,'2025-03-15 03:18:19');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_transactions`
--

DROP TABLE IF EXISTS `inventory_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_item_id` int(11) NOT NULL,
  `transaction_type` enum('stock_in','stock_out','adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `menu_item_id_idx` (`menu_item_id`),
  CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`),
  CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_transactions`
--

LOCK TABLES `inventory_transactions` WRITE;
/*!40000 ALTER TABLE `inventory_transactions` DISABLE KEYS */;
INSERT INTO `inventory_transactions` VALUES (9,9,'stock_in',50,NULL,'Initial stock setup',NULL,NULL,1,'2025-03-09 23:45:36'),(14,9,'adjustment',5,NULL,'[Damage] Decrease by 5 - asdasdas',NULL,NULL,1,'2025-03-10 08:41:34'),(16,9,'stock_in',1000,12.00,'Testing...','ERC Market','000012',1,'2025-03-14 17:15:00'),(17,9,'stock_in',1,12.00,'','','',1,'2025-03-14 18:39:00'),(18,9,'stock_in',0,NULL,'\nSupplier: \nInvoice: ',NULL,NULL,1,'2025-03-15 03:01:52');
/*!40000 ALTER TABLE `inventory_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_inventory_item` tinyint(1) DEFAULT 0,
  `current_stock` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `category_id_idx` (`category_id`),
  CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `menu_items_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `menu_items_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_items`
--

LOCK TABLES `menu_items` WRITE;
/*!40000 ALTER TABLE `menu_items` DISABLE KEYS */;
INSERT INTO `menu_items` VALUES (2,4,'Fried Chicken',55.00,0,0,1,'uploads/menu_items/menu_67cd37c9eb9e15.47496026.webp','2025-03-09 06:40:09','2025-03-09 06:40:09',1,NULL,0),(3,5,'Gravy',10.00,0,0,1,'uploads/menu_items/menu_67cd380caecb74.60702431.jpg','2025-03-09 06:41:16','2025-03-09 06:41:16',1,NULL,0),(4,4,'Dinuguan',75.00,0,0,1,'uploads/menu_items/menu_67cd383b0eff59.91239248.webp','2025-03-09 06:42:03','2025-03-09 06:42:03',1,NULL,0),(6,4,'Pork Adobo',75.00,0,0,1,'uploads/menu_items/menu_67ce26fd8f5cf9.70953515.jpg','2025-03-09 23:40:45','2025-03-09 23:40:45',1,NULL,0),(7,4,'Beef Caldereta',75.00,0,0,1,'uploads/menu_items/menu_67ce271a8d6728.14818456.jpg','2025-03-09 23:41:14','2025-03-15 04:44:39',1,1,0),(8,4,'Pancit',75.00,0,0,1,'uploads/menu_items/menu_67ce27e284af30.39274602.webp','2025-03-09 23:44:34','2025-03-09 23:44:34',1,NULL,0),(9,1,'Coke',15.00,1,1046,1,'uploads/menu_items/menu_67ce2820828a23.44625619.webp','2025-03-09 23:45:36','2025-03-15 01:40:13',1,NULL,0),(10,4,'Longganisa',75.00,0,0,1,'uploads/menu_items/menu_67ce2876ca40b8.80353022.jpg','2025-03-09 23:47:02','2025-03-09 23:47:02',1,NULL,0),(11,5,'Rice',20.00,0,0,1,'uploads/menu_items/menu_67ce28f47e0660.94906399.webp','2025-03-09 23:49:08','2025-03-09 23:49:08',1,NULL,0),(14,5,'test2',12.00,0,0,1,NULL,'2025-03-15 03:08:18','2025-03-15 03:08:18',1,NULL,0);
/*!40000 ALTER TABLE `menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `menu_item_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `menu_item_id` (`menu_item_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`),
  CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,3,1,10.00,10.00,NULL,'2025-03-09 06:50:30',NULL),(2,1,4,1,75.00,75.00,NULL,'2025-03-09 06:50:30',NULL),(4,2,2,1,55.00,55.00,NULL,'2025-03-09 07:36:22',NULL),(5,2,4,1,75.00,75.00,NULL,'2025-03-09 07:36:22',NULL),(6,3,3,1,10.00,10.00,NULL,'2025-03-09 07:43:05',NULL),(7,3,2,1,55.00,55.00,NULL,'2025-03-09 07:43:05',NULL),(9,4,3,1,10.00,10.00,NULL,'2025-03-09 07:43:16',NULL),(10,4,2,1,55.00,55.00,NULL,'2025-03-09 07:43:16',NULL),(12,5,3,1,10.00,10.00,NULL,'2025-03-09 07:45:30',NULL),(13,5,2,1,55.00,55.00,NULL,'2025-03-09 07:45:30',NULL),(15,6,3,1,10.00,10.00,NULL,'2025-03-09 07:47:04',NULL),(16,6,2,1,55.00,55.00,NULL,'2025-03-09 07:47:04',NULL),(18,7,3,1,10.00,10.00,NULL,'2025-03-09 07:47:20',NULL),(19,7,4,1,75.00,75.00,NULL,'2025-03-09 07:47:20',NULL),(21,8,2,1,55.00,55.00,NULL,'2025-03-10 08:35:07',NULL),(22,8,7,1,75.00,75.00,NULL,'2025-03-10 08:35:07',NULL),(23,9,7,1,75.00,75.00,NULL,'2025-03-11 06:05:03',NULL),(24,9,4,1,75.00,75.00,NULL,'2025-03-11 06:05:03',NULL),(25,9,3,1,10.00,10.00,NULL,'2025-03-11 06:05:03',NULL),(26,11,4,1,75.00,75.00,NULL,'2025-03-11 21:58:09',NULL),(27,11,7,1,75.00,75.00,NULL,'2025-03-11 21:58:09',NULL),(29,12,7,2,75.00,150.00,NULL,'2025-03-12 00:17:52',NULL),(30,12,4,1,75.00,75.00,NULL,'2025-03-12 00:17:52',NULL),(32,13,7,1,75.00,75.00,NULL,'2025-03-12 01:13:34',NULL),(34,14,4,1,75.00,75.00,NULL,'2025-03-12 01:32:35',NULL),(36,15,7,1,75.00,75.00,NULL,'2025-03-12 01:44:04',NULL),(38,15,9,1,15.00,15.00,NULL,'2025-03-12 01:44:04',NULL),(39,15,10,1,75.00,75.00,NULL,'2025-03-12 01:44:04',NULL),(40,15,2,1,55.00,55.00,NULL,'2025-03-12 01:44:04',NULL),(41,16,7,2,75.00,150.00,NULL,'2025-03-12 07:42:57',NULL),(42,16,4,1,75.00,75.00,NULL,'2025-03-12 07:42:57',NULL),(43,17,2,1,55.00,55.00,NULL,'2025-03-14 17:19:24',NULL),(44,17,10,1,75.00,75.00,NULL,'2025-03-14 17:19:24',NULL),(45,17,8,1,75.00,75.00,NULL,'2025-03-14 17:19:24',NULL),(46,18,7,1,75.00,75.00,NULL,'2025-03-14 17:20:16',NULL),(48,19,7,1,75.00,75.00,NULL,'2025-03-14 17:23:30',NULL),(49,19,8,1,75.00,75.00,NULL,'2025-03-14 17:23:30',NULL),(50,19,10,1,75.00,75.00,NULL,'2025-03-14 17:23:30',NULL),(52,21,10,1,75.00,75.00,NULL,'2025-03-14 17:24:34',NULL),(53,21,8,1,75.00,75.00,NULL,'2025-03-14 17:24:34',NULL),(54,22,11,1,20.00,20.00,NULL,'2025-03-15 03:01:30',NULL),(55,22,3,1,10.00,10.00,NULL,'2025-03-15 03:01:30',NULL),(56,23,11,1,20.00,20.00,NULL,'2025-03-15 04:43:48',NULL);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `subtotal_amount` decimal(10,2) NOT NULL,
  `discount_type` varchar(20) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `cash_received` decimal(10,2) NOT NULL,
  `cash_change` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','other') DEFAULT 'cash',
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `user_id` (`user_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'20250309-0001',NULL,80.00,100.00,'senior',20.00,100.00,20.00,'cash','completed','','2025-03-09 06:50:30',1),(2,'20250309-0002',NULL,130.00,130.00,'0',0.00,150.00,20.00,'cash','completed','','2025-03-09 07:36:22',1),(3,'20250309-0003',NULL,95.00,95.00,'0',0.00,100.00,5.00,'cash','completed','','2025-03-09 07:43:05',1),(4,'20250309-0004',NULL,95.00,95.00,'0',0.00,100.00,5.00,'cash','completed','','2025-03-09 07:43:16',1),(5,'20250309-0005',NULL,95.00,95.00,'0',0.00,100.00,5.00,'cash','completed','','2025-03-09 07:45:30',1),(6,'20250309-0006',NULL,95.00,95.00,'0',0.00,100.00,5.00,'cash','completed','','2025-03-09 07:47:04',1),(7,'20250309-0007',NULL,85.00,85.00,'0',0.00,100.00,15.00,'cash','completed','','2025-03-09 07:47:20',1),(8,'20250310-0001',NULL,120.00,150.00,'senior',30.00,150.00,30.00,'cash','completed','','2025-03-10 08:35:07',1),(9,'20250311-0001',NULL,128.00,160.00,'senior',32.00,150.00,22.00,'cash','completed','','2025-03-11 06:05:03',1),(11,'20250311-0002',NULL,165.00,165.00,'0',0.00,170.00,5.00,'cash','completed','','2025-03-11 21:58:09',1),(12,'20250312-0001',NULL,300.00,300.00,'0',0.00,300.00,0.00,'cash','completed','','2025-03-12 00:17:52',1),(13,'20250312-0002',NULL,105.00,105.00,'0',0.00,150.00,45.00,'cash','completed','','2025-03-12 01:13:34',1),(14,'20250312-0003',NULL,150.00,150.00,'0',0.00,150.00,0.00,'cash','completed','','2025-03-12 01:32:35',1),(15,'20250312-0004',NULL,235.00,235.00,'0',0.00,500.00,265.00,'cash','completed','','2025-03-12 01:44:04',1),(16,'20250312-0005',NULL,225.00,225.00,'0',0.00,225.00,0.00,'cash','completed','','2025-03-12 07:42:57',1),(17,'20250314-0001',NULL,205.00,205.00,'0',0.00,205.00,0.00,'cash','completed','','2025-03-14 17:19:24',1),(18,'20250314-0002',NULL,90.00,90.00,'0',0.00,100.00,10.00,'cash','completed','','2025-03-14 17:20:16',1),(19,'20250314-0003',NULL,225.00,225.00,'0',0.00,225.00,0.00,'cash','completed','','2025-03-14 17:23:30',1),(20,'20250314-0004',NULL,75.00,75.00,'0',0.00,75.00,0.00,'cash','completed','','2025-03-14 17:24:19',1),(21,'20250314-0005',NULL,150.00,150.00,'0',0.00,150.00,0.00,'cash','completed','','2025-03-14 17:24:34',1),(22,'20250315-0001',NULL,30.00,30.00,'0',0.00,30.00,0.00,'cash','completed','','2025-03-15 03:01:30',1),(23,'20250315-0002',NULL,20.00,20.00,'0',0.00,20.00,0.00,'cash','completed','','2025-03-15 04:43:48',1);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_name` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','textarea') NOT NULL,
  `setting_group` varchar(50) NOT NULL,
  `setting_label` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `help_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `settings_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'business_name','ERC Carinderia','text','business','Business Name','Name of your business','This will appear on receipts and reports','2025-03-09 04:44:30','2025-03-09 04:44:30',NULL,NULL),(2,'business_address','','textarea','business','Business Address','Complete address of your business','This will appear on receipts and reports','2025-03-09 04:44:30','2025-03-09 04:44:30',NULL,NULL),(3,'business_phone','','text','business','Business Phone','Contact number of your business','This will appear on receipts and reports','2025-03-09 04:44:30','2025-03-09 04:44:30',NULL,NULL),(4,'business_email','','text','business','Business Email','Email address of your business','This will appear on receipts and reports','2025-03-09 04:44:30','2025-03-09 04:44:30',NULL,NULL),(5,'low_stock_threshold','10','number','system','Low Stock Alert Threshold','Minimum stock level before showing low stock alert','Set to 0 to disable low stock alerts','2025-03-09 04:44:30','2025-03-09 04:44:30',NULL,NULL),(6,'enable_audit_log','1','boolean','system','Enable Audit Log','Track all changes made in the system','Helps in monitoring user activities and changes','2025-03-09 04:44:30','2025-03-09 04:44:30',NULL,NULL),(7,'default_currency','PHP','text','system','Default Currency','Currency symbol to use in the system','This will be used throughout the system','2025-03-09 04:44:30','2025-03-09 04:44:30',NULL,NULL),(8,'receipt_footer','Thank you for your business!','textarea','system','Receipt Footer Message','Message to display at the bottom of receipts','You can use this for thank you messages or business policies','2025-03-09 04:44:30','2025-03-09 04:44:30',NULL,NULL),(9,'show_receipt_logo','1','boolean','receipt','Show Logo on Receipt','Display business logo on receipts',NULL,'2025-03-09 04:44:30','2025-03-09 04:44:30',NULL,NULL),(10,'receipt_printer_type','thermal','text','receipt','Receipt Printer Type','Type of receipt printer being used','Common types: thermal, dot-matrix','2025-03-09 04:44:30','2025-03-09 04:44:30',NULL,NULL),(11,'receipt_width','80','number','receipt','Receipt Width (mm)','Width of the receipt paper in millimeters','Standard sizes: 58mm, 80mm','2025-03-09 04:44:30','2025-03-09 04:44:30',NULL,NULL),(12,'enable_stock_alerts','1','boolean','inventory','Enable Stock Alerts','Show notifications for low stock items',NULL,'2025-03-09 04:44:30','2025-03-09 04:44:30',NULL,NULL),(13,'track_inventory_history','1','boolean','inventory','Track Inventory History','Keep detailed records of all inventory changes','Helps in tracking stock movements and auditing','2025-03-09 04:44:30','2025-03-09 04:44:30',NULL,NULL),(14,'default_stock_adjustment_notes','Regular stock count adjustment','text','inventory','Default Stock Adjustment Notes','Default notes for stock adjustments','Can be changed during actual stock adjustment','2025-03-09 04:44:30','2025-03-09 04:44:30',NULL,NULL);
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','staff') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$Yo7djTSBO6RxIMZEAk0IMO1jEkyNhcThru9oTiqeQXsZSe0Nq6kvi','System Admin','admin',1,'2025-03-09 05:22:53','2025-03-15 04:42:52','2025-03-15 04:42:52'),(8,'era','$2y$10$B1XTByR7Wj1izW3Amht9wedQE4JAFH.I3E3yW8ZJ6fdpikuLXr.a2','Era Dumangcas','staff',0,'2025-03-09 05:33:02','2025-03-15 01:10:09','2025-03-13 12:56:51');
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

-- Dump completed on 2025-03-15 13:32:30
