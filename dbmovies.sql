-- ============================================================
-- Database: dbmovies
-- Description: Movie ticket booking system schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS `dbmovies`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `dbmovies`;

-- ------------------------------------------------------------
-- Table: category
-- Stores movie categories (Hollywood, Bollywood, etc.)
-- ------------------------------------------------------------
CREATE TABLE `category` (
  `catid`   INT(11)      NOT NULL AUTO_INCREMENT,
  `catname` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Table: class
-- Stores seating class types (e.g., Gold, Silver, etc.)
-- ------------------------------------------------------------
CREATE TABLE `class` (
  `classid`   INT(11)      NOT NULL AUTO_INCREMENT,
  `classname` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`classid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Table: users
-- Stores registered user accounts
-- ------------------------------------------------------------
CREATE TABLE `users` (
  `userid`       INT(11)       NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(100)  NOT NULL,
  `email`        VARCHAR(100)  NOT NULL,
  `password`     VARCHAR(255)  NOT NULL,
  `roletype`     TINYINT(1)    NOT NULL DEFAULT 2 COMMENT '1 = Admin, 2 = User',
  `phone_number` VARCHAR(20)   NOT NULL,
  `profile_pic`  VARCHAR(1000) NOT NULL DEFAULT '',
  PRIMARY KEY (`userid`),
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Table: movies
-- Stores movie listings
-- ------------------------------------------------------------
CREATE TABLE `movies` (
  `movieid`      INT(11)                            NOT NULL AUTO_INCREMENT,
  `title`        VARCHAR(200)                       NOT NULL,
  `description`  TEXT                               NOT NULL,
  `release_date` DATE                               NOT NULL,
  `image`        VARCHAR(1000)                      NOT NULL,
  `trailer`      VARCHAR(1000)                      NOT NULL,
  `movie`        VARCHAR(1000)                      NOT NULL DEFAULT '',
  `rating`       DECIMAL(3,1)                       NOT NULL DEFAULT 0.0,
  `catid`        INT(11)                            NOT NULL,
  `movie_type`   ENUM('now_showing', 'coming_soon') NOT NULL DEFAULT 'coming_soon',
  `price`        DECIMAL(10,2)                      NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`movieid`),
  KEY `fk_movies_category` (`catid`),
  CONSTRAINT `fk_movies_category`
    FOREIGN KEY (`catid`) REFERENCES `category` (`catid`)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Table: theater
-- Stores theater and showtime information per movie
-- ------------------------------------------------------------
CREATE TABLE `theater` (
  `theaterid`    INT(11)       NOT NULL AUTO_INCREMENT,
  `theater_name` VARCHAR(100)  NOT NULL,
  `movieid`      INT(11)       NOT NULL,
  `timing`       VARCHAR(10)   NOT NULL DEFAULT '',
  `timing2`      VARCHAR(10)   NOT NULL DEFAULT '',
  `timing3`      VARCHAR(10)   NOT NULL DEFAULT '',
  `timing4`      VARCHAR(10)   NOT NULL DEFAULT '',
  `days`         VARCHAR(100)  NOT NULL DEFAULT '',
  `date`         DATE          NOT NULL,
  `price`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `location`     VARCHAR(400)  NOT NULL,
  PRIMARY KEY (`theaterid`),
  KEY `fk_theater_movie` (`movieid`),
  CONSTRAINT `fk_theater_movie`
    FOREIGN KEY (`movieid`) REFERENCES `movies` (`movieid`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Table: booking
-- Stores ticket booking records
-- ------------------------------------------------------------
CREATE TABLE `booking` (
  `bookingid`               INT(11)                                        NOT NULL AUTO_INCREMENT,
  `movieid`                 INT(11)                                        NOT NULL,
  `booking_date`            DATE                                           NOT NULL,
  `timing`                  VARCHAR(50)                                    NOT NULL,
  `seats`                   TEXT                                           NOT NULL,
  `userid`                  INT(11)                                        NOT NULL,
  `status`                  ENUM('pending','confirmed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `esewa_transaction_uuid`  VARCHAR(100)                                   DEFAULT NULL,
  `esewa_ref_id`            VARCHAR(100)                                   DEFAULT NULL,
  PRIMARY KEY (`bookingid`),
  KEY `fk_booking_movie`  (`movieid`),
  KEY `fk_booking_user`   (`userid`),
  CONSTRAINT `fk_booking_movie`
    FOREIGN KEY (`movieid`) REFERENCES `movies` (`movieid`)
    ON UPDATE CASCADE,
  CONSTRAINT `fk_booking_user`
    FOREIGN KEY (`userid`) REFERENCES `users` (`userid`)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Table: payments
-- Stores payment transactions linked to bookings
-- ------------------------------------------------------------
CREATE TABLE `payments` (
  `payment_id`     INT(11)                       NOT NULL AUTO_INCREMENT,
  `bookingid`      INT(11)                       NOT NULL,
  `userid`         INT(11)                       NOT NULL,
  `amount`         DECIMAL(10,2)                 NOT NULL,
  `payment_method` ENUM('esewa','cod')           NOT NULL,
  `transaction_id` VARCHAR(100)                  DEFAULT NULL,
  `status`         ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `payment_date`   TIMESTAMP                     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `fk_payments_booking` (`bookingid`),
  KEY `fk_payments_user`    (`userid`),
  CONSTRAINT `fk_payments_booking`
    FOREIGN KEY (`bookingid`) REFERENCES `booking` (`bookingid`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_user`
    FOREIGN KEY (`userid`) REFERENCES `users` (`userid`)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Table: transactions
-- Stores confirmed transaction records with seat and timing details
-- ------------------------------------------------------------
CREATE TABLE `transactions` (
  `transactionid`   INT(11)       NOT NULL AUTO_INCREMENT,
  `transaction_ref` VARCHAR(30)   NOT NULL,
  `bookingid`       INT(11)       NOT NULL,
  `userid`          INT(11)       NOT NULL,
  `movieid`         INT(11)       NOT NULL,
  `seats`           VARCHAR(255)  NOT NULL,
  `seat_count`      INT(11)       NOT NULL DEFAULT 1,
  `timing`          VARCHAR(50)   NOT NULL,
  `booking_date`    DATE          NOT NULL,
  `amount`          DECIMAL(10,2) NOT NULL,
  `payment_method`  VARCHAR(50)   NOT NULL DEFAULT 'Direct',
  `status`          VARCHAR(20)   NOT NULL DEFAULT 'success',
  `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transactionid`),
  UNIQUE KEY `uq_transaction_ref` (`transaction_ref`),
  KEY `fk_transactions_booking` (`bookingid`),
  KEY `fk_transactions_user`    (`userid`),
  KEY `fk_transactions_movie`   (`movieid`),
  CONSTRAINT `fk_transactions_booking`
    FOREIGN KEY (`bookingid`) REFERENCES `booking` (`bookingid`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_transactions_user`
    FOREIGN KEY (`userid`) REFERENCES `users` (`userid`)
    ON UPDATE CASCADE,
  CONSTRAINT `fk_transactions_movie`
    FOREIGN KEY (`movieid`) REFERENCES `movies` (`movieid`)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;