-- Generation Time: Apr 14, 2018 at 12:58 PM


SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `wsirc`
--

-- --------------------------------------------------------

--
-- Table structure for table `channels`
--

CREATE TABLE `channels` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `reg_ip` varchar(30) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_ids` varchar(400) NOT NULL COMMENT 'separated by comma',
  `join_count` int(11) NOT NULL DEFAULT '0',
  `last_join` datetime DEFAULT NULL,
  `allow_not_joined_msg` tinyint(4) NOT NULL,
  `removed` tinyint(4) NOT NULL DEFAULT '0',
  `insert_time` datetime NOT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `expires` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `adm_user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `temp_pass` varchar(100) NOT NULL,
  `temp_pass_expires` datetime NOT NULL,
  `reg_ip` varchar(30) NOT NULL,
  `last_ip` varchar(30) DEFAULT NULL,
  `login_count` int(11) NOT NULL DEFAULT '0',
  `last_login` datetime DEFAULT NULL,
  `removed` tinyint(4) NOT NULL DEFAULT '0',
  `insert_time` datetime NOT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `expires` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `channels`
--
ALTER TABLE `channels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `channels`
--
ALTER TABLE `channels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;