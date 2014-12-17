-- phpMyAdmin SQL Dump
-- version 4.2.10
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Generation Time: 2014 年 12 月 17 日 08:07
-- サーバのバージョン： 5.5.38
-- PHP Version: 5.6.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `pinboard`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `pb_facebook_account`
--

CREATE TABLE `pb_facebook_account` (
`id` int(10) unsigned NOT NULL,
  `facebook_id` int(11) unsigned NOT NULL,
  `facebook_name` int(64) NOT NULL,
  `auth_token` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- テーブルの構造 `pb_github_account`
--

CREATE TABLE `pb_github_account` (
`id` int(11) unsigned NOT NULL,
  `github_id` int(11) unsigned NOT NULL,
  `github_name` varchar(64) NOT NULL,
  `auth_token` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- テーブルの構造 `pb_tags`
--

CREATE TABLE `pb_tags` (
  `url_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- テーブルの構造 `pb_twitter_account`
--

CREATE TABLE `pb_twitter_account` (
`id` int(10) unsigned NOT NULL,
  `twttier_id` int(10) unsigned NOT NULL,
  `twitter_name` varchar(64) NOT NULL,
  `auth_token` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- テーブルの構造 `pb_urls`
--

CREATE TABLE `pb_urls` (
`id` int(1) NOT NULL,
  `url` text NOT NULL,
  `title` varchar(255) NOT NULL,
  `readed` tinyint(1) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- テーブルの構造 `pb_users`
--

CREATE TABLE `pb_users` (
`id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `last_login` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pb_facebook_account`
--
ALTER TABLE `pb_facebook_account`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `facebook_id` (`facebook_id`);

--
-- Indexes for table `pb_github_account`
--
ALTER TABLE `pb_github_account`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `github_id` (`github_id`);

--
-- Indexes for table `pb_tags`
--
ALTER TABLE `pb_tags`
 ADD KEY `url_id` (`url_id`);

--
-- Indexes for table `pb_twitter_account`
--
ALTER TABLE `pb_twitter_account`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `twttier_id` (`twttier_id`);

--
-- Indexes for table `pb_urls`
--
ALTER TABLE `pb_urls`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pb_users`
--
ALTER TABLE `pb_users`
 ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pb_facebook_account`
--
ALTER TABLE `pb_facebook_account`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `pb_github_account`
--
ALTER TABLE `pb_github_account`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `pb_twitter_account`
--
ALTER TABLE `pb_twitter_account`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `pb_urls`
--
ALTER TABLE `pb_urls`
MODIFY `id` int(1) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `pb_users`
--
ALTER TABLE `pb_users`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
