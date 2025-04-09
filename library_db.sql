-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 08, 2025 at 01:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `library_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `copies` int(11) NOT NULL,
  `available` tinyint(1) DEFAULT 1,
  `image_path` varchar(255) DEFAULT 'default_book.jpg',
  `bookshelf` varchar(100) DEFAULT NULL,
  `bookshelf_location` varchar(100) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_year` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `copies`, `available`, `image_path`, `bookshelf`, `bookshelf_location`, `isbn`, `publisher`, `publication_year`, `description`, `quantity`, `created_at`, `updated_at`) VALUES
(12, 'The Well Balanced Leader', 'Ron Robert', 1, 1, 'uploads/books/1744029945_615hKFyLUjL._AC_UF1000,1000_QL80_.jpg', 'Marketing', 'Study Room', '', '', 2011, '', 1, '2025-04-02 11:30:41', '2025-04-07 20:44:28'),
(14, 'Inside Job', 'Charles Ferguson', 1, 1, 'uploads/books/1744112022_487955085_1934436633753296_554684875455582637_n.jpg', 'Marketing', 'Corner', '9781780740720', 'Charles Ferguson', 0, '', 1, '2025-04-08 11:33:42', '2025-04-08 11:33:42'),
(15, 'Balagtasan', 'Galileo S. Zafra', 1, 1, 'uploads/books/1744112380_covers_balagtasan_kasaysayan_at_antolohiya.jpg', 'Filipiñana', '', '9789715503198.', '', 1999, '', 1, '2025-04-08 11:39:40', '2025-04-08 11:40:04'),
(17, 'The Family Book of Manners', 'Hermine Hartley', 1, 1, 'uploads/books/1744112543_images.jpg', 'Filipiñana', '', '9780883657690', '', 1990, '', 1, '2025-04-08 11:42:23', '2025-04-08 11:42:23'),
(18, 'A Nation Aborted: Rizal, American Hegemony and Philippine Nationalism', 'Floro C. Quibuyen', 1, 1, 'uploads/books/1744112795_13607858.jpg', 'History', '', '9789715505741', '', 2008, '', 1, '2025-04-08 11:46:35', '2025-04-08 11:46:35');

-- --------------------------------------------------------

--
-- Table structure for table `borrowed_books`
--

CREATE TABLE `borrowed_books` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `book_id` int(11) DEFAULT NULL,
  `borrow_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `return_date` timestamp NULL DEFAULT NULL,
  `status` enum('borrowed','returned') DEFAULT 'borrowed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowed_books`
--

INSERT INTO `borrowed_books` (`id`, `user_id`, `book_id`, `borrow_date`, `return_date`, `status`) VALUES
(37, 11, 12, '2025-04-02 11:32:34', '2025-04-02 11:32:38', 'borrowed'),
(38, 11, 12, '2025-04-05 02:01:26', '2025-04-05 02:01:28', 'borrowed'),
(39, 11, 12, '2025-04-05 02:14:51', '2025-04-05 02:14:55', 'borrowed'),
(40, 11, 12, '2025-04-05 02:33:55', '2025-04-05 02:34:03', 'borrowed'),
(41, 11, 12, '2025-04-05 02:36:54', '2025-04-05 02:37:21', 'borrowed'),
(42, 11, 12, '2025-04-05 03:06:34', '2025-04-05 07:23:30', 'borrowed'),
(43, 12, 12, '2025-04-05 10:57:42', '2025-04-05 15:28:43', 'borrowed'),
(44, 12, 12, '2025-04-05 15:28:48', '2025-04-05 15:29:13', 'borrowed'),
(45, 12, 12, '2025-04-05 15:34:47', '2025-04-05 15:34:50', 'borrowed'),
(46, 12, 12, '2025-04-05 15:58:21', '2025-04-05 15:58:26', 'borrowed'),
(47, 12, 12, '2025-04-05 16:06:12', '2025-04-05 16:22:19', 'borrowed'),
(48, 12, 12, '2025-04-05 16:25:46', '2025-04-05 16:25:55', 'borrowed'),
(49, 11, 12, '2025-04-07 12:46:09', '2025-04-07 12:46:14', 'borrowed');

-- --------------------------------------------------------

--
-- Table structure for table `borrowings`
--

CREATE TABLE `borrowings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrow_date` datetime DEFAULT NULL,
  `due_date` date NOT NULL,
  `return_date` datetime DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') NOT NULL DEFAULT 'borrowed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowings`
--

INSERT INTO `borrowings` (`id`, `user_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `status`) VALUES
(1, 11, 12, '2025-04-07 00:00:00', '2025-04-21', '2025-04-07 00:00:00', 'returned'),
(2, 11, 12, '2025-04-07 00:00:00', '2025-04-21', '2025-04-07 00:00:00', 'returned'),
(3, 16, 12, '2025-04-07 00:00:00', '2025-04-21', '2025-04-07 00:00:00', 'returned'),
(8, 16, 12, '2025-04-08 03:04:33', '2025-04-21', '2025-04-08 03:04:58', 'returned'),
(9, 17, 12, '2025-04-08 04:37:30', '2025-04-21', '2025-04-08 04:37:43', 'returned'),
(10, 18, 12, '2025-04-08 04:44:19', '2025-04-21', '2025-04-08 04:44:28', 'returned');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Fiction', 'Novels and made-up stories'),
(2, 'Non-fiction', 'Factual books'),
(3, 'Science', 'Books about scientific subjects'),
(4, 'History', 'Historical books and references'),
(5, 'Technology', 'Books about technology and computing');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `role` varchar(20) DEFAULT 'student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `is_admin`, `role`) VALUES
(11, 'who', '$2y$10$86y17jCnel.ibqEwYIIQiu7cZ.wzFbdox9LWDbrgpcRkj9ZzWdfqW', 1, 'student'),
(12, 'wow', '$2y$10$LilQXfW39UW3xqVd/IeM2OkI1kj59623cCr9eSpgom9mXteqZnCqS', 1, 'admin'),
(13, 'new_admin', 'hashed_password_value', 1, 'student'),
(16, 'Jesu', '$2y$10$3U9QVex2JISR99L6vOKeMOe.3wAsybK5PqQA/fnViBpaU/owF1Xse', 0, 'student'),
(17, 'Student', '$2y$10$8jkMWOTmxKMCpPuZzPJcEOdOhIZP3.hWeQZ1mLMudiw2QPVzwMna6', 0, 'student'),
(18, 'Student 1', '$2y$10$jVWDDuUOqSLkGoj1wZI5ZO4sJyJVorJMSPuZFBkptHsf5d/Xqb9W.', 0, 'student');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_isbn` (`isbn`),
  ADD KEY `idx_title` (`title`),
  ADD KEY `idx_author` (`author`),
  ADD KEY `idx_bookshelf` (`bookshelf`);

--
-- Indexes for table `borrowed_books`
--
ALTER TABLE `borrowed_books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `borrowed_books`
--
ALTER TABLE `borrowed_books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrowed_books`
--
ALTER TABLE `borrowed_books`
  ADD CONSTRAINT `borrowed_books_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrowed_books_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD CONSTRAINT `borrowings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `borrowings_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
