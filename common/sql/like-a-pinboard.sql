-- phpMyAdmin SQL Dump
-- version 4.1.12
-- http://www.phpmyadmin.net
--
-- Host: localhost:8889
-- Generation Time: 2014 年 12 月 19 日 00:05
-- サーバのバージョン： 5.5.34
-- PHP Version: 5.5.10

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
  `user_id` int(11) unsigned NOT NULL,
  `facebook_id` int(11) unsigned NOT NULL,
  `facebook_name` varchar(64) NOT NULL,
  `access_token` varchar(255) NOT NULL,
  UNIQUE KEY `facebook_id` (`facebook_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- テーブルの構造 `pb_github_account`
--

CREATE TABLE `pb_github_account` (
  `user_id` int(11) unsigned NOT NULL,
  `github_id` int(11) unsigned NOT NULL,
  `github_name` varchar(64) NOT NULL,
  `access_token` varchar(255) NOT NULL,
  UNIQUE KEY `github_id` (`github_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- テーブルの構造 `pb_tags`
--

CREATE TABLE `pb_tags` (
  `url_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  KEY `url_id` (`url_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- テーブルの構造 `pb_twitter_account`
--

CREATE TABLE `pb_twitter_account` (
  `user_id` int(11) unsigned NOT NULL,
  `twitter_id` int(10) unsigned NOT NULL,
  `twitter_name` varchar(64) NOT NULL,
  `access_token` varchar(255) NOT NULL,
  UNIQUE KEY `twttier_id` (`twitter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- テーブルの構造 `pb_urls`
--

CREATE TABLE `pb_urls` (
  `id` int(1) NOT NULL AUTO_INCREMENT,
  `url` text NOT NULL,
  `title` varchar(255) NOT NULL,
  `readed` tinyint(1) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `pb_users`
--

CREATE TABLE `pb_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `last_login` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `token` varchar(64) DEFAULT NULL,
  `twitter_access_token` varchar(255) DEFAULT NULL,
  `facebook_access_token` varchar(255) DEFAULT NULL,
  `github_access_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
