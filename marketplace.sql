-- ============================================================
-- NEXUS MARKETPLACE - Complete Database Schema
-- Generated: Apr 03, 2026
-- Server: MariaDB 10.4+ / MySQL 8.0+
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- Drop existing tables (in FK-safe order)
-- ============================================================

DROP TABLE IF EXISTS `testimonials`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- ============================================================
-- 1. USERS TABLE
-- Stores all platform accounts: Buyers, Sellers, and Admins.
-- Includes password reset token support and seller store info.
-- Sellers can configure: physical store address, delivery fee,
-- and which fulfillment methods they offer (delivery/pickup).
-- ============================================================

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Buyer','Seller','Admin') NOT NULL DEFAULT 'Buyer',
  `profile_pic` varchar(255) DEFAULT 'assets/img/default_user.png',
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `reset_token` varchar(64) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  -- Seller Store & Fulfillment Settings
  `store_name` varchar(150) DEFAULT NULL,
  `store_address` text DEFAULT NULL,
  `store_city` varchar(100) DEFAULT NULL,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `offers_delivery` tinyint(1) NOT NULL DEFAULT 1,
  `offers_pickup` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_reset_token` (`reset_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 2. CATEGORIES TABLE
-- Marketplace product classification nodes managed by admin.
-- Each category has an icon (Font Awesome class) and brand color.
-- ============================================================

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(100) DEFAULT 'fas fa-box',
  `color` varchar(20) DEFAULT '#6366f1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 3. PRODUCTS TABLE
-- All marketplace listings. Each product belongs to a seller
-- (FK -> users) and has a category, condition, stock level.
-- ============================================================

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `condition` enum('New','Like New','Used','Fair') NOT NULL DEFAULT 'Used',
  `stock` int(11) NOT NULL DEFAULT 1,
  `seller_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_products_seller` (`seller_id`),
  KEY `idx_products_category` (`category`),
  KEY `idx_products_stock` (`stock`),
  KEY `idx_products_created` (`created_at`),
  CONSTRAINT `fk_products_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 4. CART TABLE
-- Temporary shopping cart for buyer accounts.
-- Each row = one product in a user's cart with quantity.
-- Sellers are blocked from adding to cart at the application layer.
-- ============================================================

CREATE TABLE `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_cart_user` (`user_id`),
  KEY `idx_cart_product` (`product_id`),
  UNIQUE KEY `unique_cart_item` (`user_id`, `product_id`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 5. ORDERS TABLE
-- Finalized purchase records. Created at checkout.
-- user_id = the buyer who placed the order.
-- Tracks: fulfillment method (Delivery/Pickup), shipping address,
-- delivery fee, payment method, and order lifecycle status.
-- ============================================================

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_method` enum('Delivery','Pickup') NOT NULL DEFAULT 'Delivery',
  `shipping_address` text DEFAULT NULL,
  `pickup_seller_id` int(11) DEFAULT NULL,
  `payment_method` enum('Card','PayPal') NOT NULL DEFAULT 'Card',
  `status` enum('Pending','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_date` (`order_date`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 6. ORDER_ITEMS TABLE
-- Line items for each order. Each row = one product in an order.
-- price is recorded at time of purchase (snapshot) to preserve
-- historical accuracy. This is the "Selling Price" used for
-- revenue calculation: Total Revenue = Sigma (price x quantity).
-- ============================================================

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_orderitems_order` (`order_id`),
  KEY `idx_orderitems_product` (`product_id`),
  CONSTRAINT `fk_orderitems_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_orderitems_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 7. MESSAGES TABLE
-- Direct messaging between users (buyer <-> seller communication).
-- ============================================================

CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_messages_sender` (`sender_id`),
  KEY `idx_messages_receiver` (`receiver_id`),
  KEY `idx_messages_conversation` (`sender_id`, `receiver_id`),
  KEY `idx_messages_created` (`created_at`),
  CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 8. TESTIMONIALS TABLE
-- Platform reviews/ratings submitted by authenticated users.
-- ============================================================

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_testimonials_user` (`user_id`),
  KEY `idx_testimonials_rating` (`rating`),
  CONSTRAINT `fk_testimonials_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 9. SUBSCRIBERS TABLE
-- Emails captured for news and updates.
-- ============================================================

CREATE TABLE `subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- SEED DATA: Default Admin Account
-- Email: admin1@gmail.com | Password: admin123 (bcrypt hashed)
-- ============================================================

INSERT INTO `users` (`name`, `email`, `password`, `role`, `profile_pic`, `is_admin`) VALUES
('Admin User', 'admin1@gmail.com', '$2y$12$ixeZdOm5AAl5k5EBB2b0neUw5dOW0niC57EBZndCGfmQ/OigP3NYC', 'Admin', 'assets/img/default_user.png', 1);

-- ============================================================
-- SEED DATA: Default Categories
-- ============================================================

INSERT INTO `categories` (`name`, `icon`, `color`) VALUES
('Electronics', 'fas fa-laptop', '#6366f1'),
('Clothing', 'fas fa-tshirt', '#ec4899'),
('Books', 'fas fa-book', '#10b981'),
('Furniture', 'fas fa-couch', '#f59e0b'),
('Sports', 'fas fa-futbol', '#ef4444'),
('Collectibles', 'fas fa-gem', '#8b5cf6'),
('Accessories', 'fas fa-glasses', '#14b8a6'),
('Home & Garden', 'fas fa-home', '#f97316');

-- ============================================================
-- SEED DATA: Sample Products (linked to Admin as seller for demo)
-- ============================================================

INSERT INTO `products` (`name`, `description`, `price`, `image_url`, `category`, `condition`, `stock`, `seller_id`) VALUES
('Vintage Record Player', 'Beautiful wooden turntable with high-fidelity sound.', 150.00, 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=400', 'Electronics', 'Like New', 1, 1),
('Denim Jacket', 'Classic blue denim jacket, oversized fit.', 45.00, 'https://images.unsplash.com/photo-1551537482-f2075a1d41f2?w=400', 'Clothing', 'Used', 3, 1),
('The Great Gatsby', 'Hardcover edition of the classic novel.', 12.50, 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400', 'Books', 'New', 5, 1);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
