-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 09, 2025 at 05:50 PM
-- Server version: 5.7.24
-- PHP Version: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shelftrade`
--

-- --------------------------------------------------------

--
-- Table structure for table `book`
--

CREATE TABLE `book` (
  `bookId` int(11) NOT NULL,
  `image` text,
  `title` text,
  `description` text,
  `price` decimal(10,2) DEFAULT NULL,
  `category` enum('Literature','History','Novels','Philosophy') DEFAULT NULL,
  `bookStatus` enum('In stock','Out of stock') DEFAULT 'In stock',
  `userId` int(11) DEFAULT NULL,
  `cartId` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `book`
--

INSERT INTO `book` (`bookId`, `image`, `title`, `description`, `price`, `category`, `bookStatus`, `userId`, `cartId`) VALUES
(1, 'book_novel_1.jpg', 'عداء الطائرة الورقية', 'A touching story of friendship, betrayal, and redemption in Afghanistan.', '25.00', 'Novels', 'In stock', 1, NULL),
(2, 'book_philosophy_1.jpg', 'التداوي بالفلسفة', 'Exploring how philosophy can help navigate life’s challenges.', '30.00', 'Philosophy', 'In stock', 1, NULL),
(3, 'book_history_1.jpg', 'الملك فيصل الأول', 'A historical account of King Faisal I and his role in Arab nationalism.', '22.00', 'History', 'In stock', 2, NULL),
(4, 'book_literature_2.jpg', 'صاحب الظل الطويل', 'A heartwarming novel about an orphan girl who corresponds with her unknown benefactor through letters.', '27.00', 'Literature', 'In stock', 2, NULL),
(5, 'book_novel_2.jpg', 'عرين الأسد', 'A fantasy novel about an epic battle between good and evil.', '29.00', 'Novels', 'In stock', 1, NULL),
(6, 'book_philosophy_2.jpg', 'نظرية الإنسان', 'An analysis of Al-Farabi’s philosophical views on human nature and society.', '24.00', 'Philosophy', 'In stock', 2, NULL),
(7, 'book_history_2.jpg', 'تاريخ العرب', 'A concise overview of Arab history and the development of Islamic civilization.', '26.00', 'History', 'In stock', 3, NULL),
(8, 'book_literature_3.jpg', 'ظل الريح', 'A mysterious novel about a lost book that unveils dark secrets in Barcelona.', '28.00', 'Literature', 'In stock', 3, NULL);

--
-- Triggers `book`
--
DELIMITER $$
CREATE TRIGGER `update_exchange_request_status` AFTER UPDATE ON `book` FOR EACH ROW BEGIN
    IF NEW.bookStatus = 'Out of stock' THEN
        UPDATE ExchangeRequest
        SET status = 'Rejected'
        WHERE (bookToExchange = NEW.bookId OR bookToExchangeWith = NEW.bookId)
        AND status = 'Pending';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cartId` int(11) NOT NULL,
  `totalPrice` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cartId`, `totalPrice`) VALUES
(1, '0.00'),
(2, '0.00'),
(4, '0.00');

-- --------------------------------------------------------

--
-- Table structure for table `exchangerequest`
--

CREATE TABLE `exchangerequest` (
  `requestId` int(11) NOT NULL,
  `senderId` int(11) DEFAULT NULL,
  `receiverId` int(11) DEFAULT NULL,
  `status` enum('Pending','Rejected','Accepted','Completed') DEFAULT 'Pending',
  `bookToExchange` int(11) DEFAULT NULL,
  `bookToExchangeWith` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rating`
--

CREATE TABLE `rating` (
  `ratingId` int(11) NOT NULL,
  `ratingValue` int(11) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `requested`
--

CREATE TABLE `requested` (
  `bookId` int(11) NOT NULL,
  `requestId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `userId` int(11) NOT NULL,
  `fullName` text,
  `email` text,
  `password` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`userId`, `fullName`, `email`, `password`) VALUES
(1, 'Mohammed Al-Ali', 'mohamed@example.com', '$2y$10$QGt1j9KVRGJ7uFYageEkBOzkMxgSqKLq6UtTzDik49s2lwmT9Khsq'),
(2, 'Fatima Al-Saad', 'fatima@example.com', '$2y$10$PRuuCswWzjy2x./Gk8zlUOPCAUsCG6NwzBk0UM.kYDzfwn80kApbW'),
(3, 'Danah', 'danah@gmail.com', '$2y$10$yMmq3ybg69v8u2pnw.qHXuJ4tCSxzXDCc/PhrL.H1M5eX8Rkh8zSG');

--
-- Triggers `user`
--
DELIMITER $$
CREATE TRIGGER `create_user_cart` AFTER INSERT ON `user` FOR EACH ROW BEGIN
    DECLARE new_cart_id INT;
    
    -- إنشاء عربة تسوق جديدة
    INSERT INTO cart (totalPrice) VALUES (0.00);
    SET new_cart_id = LAST_INSERT_ID();
    
    -- ربط المستخدم الجديد بعربة التسوق
    INSERT INTO usercart (userId, cartId) VALUES (NEW.userId, new_cart_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `usercart`
--

CREATE TABLE `usercart` (
  `userId` int(11) NOT NULL,
  `cartId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `usercart`
--

INSERT INTO `usercart` (`userId`, `cartId`) VALUES
(1, 1),
(2, 2),
(3, 4);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `book`
--
ALTER TABLE `book`
  ADD PRIMARY KEY (`bookId`),
  ADD KEY `userId` (`userId`),
  ADD KEY `cartId` (`cartId`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cartId`);

--
-- Indexes for table `exchangerequest`
--
ALTER TABLE `exchangerequest`
  ADD PRIMARY KEY (`requestId`),
  ADD KEY `senderId` (`senderId`),
  ADD KEY `receiverId` (`receiverId`),
  ADD KEY `bookToExchange` (`bookToExchange`),
  ADD KEY `bookToExchangeWith` (`bookToExchangeWith`);

--
-- Indexes for table `rating`
--
ALTER TABLE `rating`
  ADD PRIMARY KEY (`ratingId`),
  ADD KEY `userId` (`userId`);

--
-- Indexes for table `requested`
--
ALTER TABLE `requested`
  ADD PRIMARY KEY (`bookId`,`requestId`),
  ADD KEY `requestId` (`requestId`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`userId`);

--
-- Indexes for table `usercart`
--
ALTER TABLE `usercart`
  ADD PRIMARY KEY (`userId`,`cartId`),
  ADD KEY `cartId` (`cartId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `book`
--
ALTER TABLE `book`
  MODIFY `bookId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cartId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `exchangerequest`
--
ALTER TABLE `exchangerequest`
  MODIFY `requestId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rating`
--
ALTER TABLE `rating`
  MODIFY `ratingId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `book`
--
ALTER TABLE `book`
  ADD CONSTRAINT `book_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `user` (`userId`),
  ADD CONSTRAINT `book_ibfk_2` FOREIGN KEY (`cartId`) REFERENCES `cart` (`cartId`);

--
-- Constraints for table `exchangerequest`
--
ALTER TABLE `exchangerequest`
  ADD CONSTRAINT `exchangerequest_ibfk_1` FOREIGN KEY (`senderId`) REFERENCES `user` (`userId`),
  ADD CONSTRAINT `exchangerequest_ibfk_2` FOREIGN KEY (`receiverId`) REFERENCES `user` (`userId`),
  ADD CONSTRAINT `exchangerequest_ibfk_3` FOREIGN KEY (`bookToExchange`) REFERENCES `book` (`bookId`),
  ADD CONSTRAINT `exchangerequest_ibfk_4` FOREIGN KEY (`bookToExchangeWith`) REFERENCES `book` (`bookId`);

--
-- Constraints for table `rating`
--
ALTER TABLE `rating`
  ADD CONSTRAINT `rating_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `user` (`userId`);

--
-- Constraints for table `requested`
--
ALTER TABLE `requested`
  ADD CONSTRAINT `requested_ibfk_1` FOREIGN KEY (`bookId`) REFERENCES `book` (`bookId`),
  ADD CONSTRAINT `requested_ibfk_2` FOREIGN KEY (`requestId`) REFERENCES `exchangerequest` (`requestId`);

--
-- Constraints for table `usercart`
--
ALTER TABLE `usercart`
  ADD CONSTRAINT `usercart_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `user` (`userId`),
  ADD CONSTRAINT `usercart_ibfk_2` FOREIGN KEY (`cartId`) REFERENCES `cart` (`cartId`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
