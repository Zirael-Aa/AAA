-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-8.4
-- Время создания: Ноя 18 2026 г., 04:55
-- Версия сервера: 8.4.6
-- Версия PHP: 8.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `kurs`
--

-- --------------------------------------------------------

--
-- Структура таблицы `meter_readings`
--

CREATE TABLE `meter_readings` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `reading_date` date NOT NULL,
  `cold_water` decimal(10,2) NOT NULL,
  `hot_water` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `meter_readings`
--

INSERT INTO `meter_readings` (`id`, `user_id`, `reading_date`, `cold_water`, `hot_water`, `created_at`) VALUES
(3, 4, '2026-11-06', 45678.00, 4567.00, '2026-11-06 03:44:31'),
(4, 4, '2026-11-07', 4567.00, 4567.00, '2026-11-07 21:27:46'),
(5, 4, '2026-11-10', 45567.00, 4566.00, '2026-11-10 09:42:01');

-- --------------------------------------------------------

--
-- Структура таблицы `news`
--

CREATE TABLE `news` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `preview` text,
  `full_text` text,
  `image` varchar(255) DEFAULT '',
  `news_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `news`
--

INSERT INTO `news` (`id`, `title`, `preview`, `full_text`, `image`, `news_date`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'МКР Лиховского будет с пониженным давлением', 'ОАО «Исток» сообщает, что в связи с устранением аварии на магистральном водоводе подача холодной воды абонентам МКР Лиховского будет с пониженным давлением 13.02.2025г с 9.00...', 'ОАО «Исток» сообщает, что в связи с устранением аварии на магистральном водоводе подача холодной воды абонентам МКР Лиховского будет с пониженным давлением 13.02.2025г с 9.00 и до окончания аварийновосстановительных работ, что может привести к временному ухудшению качества воды. Приносим извинения за причиненные неудобства.', 'фото/news1.png', '2025-02-13', 1, 1, '2026-10-13 20:40:49'),
(2, 'Ватутина, 29 — прекращение подачи холодной воды', 'ОАО «Исток» сообщает, что в связи с проведением аварийновосстановительных работ по ул. Ватутина, 29, будет прекращена подача холодной воды...', 'ОАО «Исток» сообщает, что в связи с проведением аварийновосстановительных работ по ул. Ватутина, 29, будет прекращена подача холодной воды по ул. Ватутина от ул. Танкистов до ул. Первомайская ориентировочно с 10.00ч. до 15.00ч. 10.02.2025г., что может привести к временному ухудшению качества воды. Приносим извинения за причиненные неудобства.', 'фото/news2.png', '2025-02-10', 1, 2, '2026-10-13 20:40:49'),
(3, 'Мусина — пер. Астаховский — прекращение подачи холодной воды', 'ОАО «Исток» сообщает, что в связи с проведением аварийновосстановительных работ по ул. Мусина — пер. Астаховский, будет прекращена подача холодной воды...', 'ОАО «Исток» сообщает, что в связи с проведением аварийновосстановительных работ по ул. Мусина — пер. Астаховский, будет прекращена подача холодной воды по ул. Мусина от пер. Железнодорожный до пер. Крупской ориентировочно с 9.30ч. до 13.00ч. 04.02.2025г., что может привести к временному ухудшению качества воды. Приносим извинения за причиненные неудобства.', 'фото/news3.png', '2025-02-04', 1, 3, '2026-10-13 20:40:49'),
(5, 'Нет воды', '', '', '', '2026-11-10', 1, 0, '2026-11-10 09:45:44');

-- --------------------------------------------------------

--
-- Структура таблицы `requests`
--

CREATE TABLE `requests` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` enum('repair','connection','question','complaint') NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `status` enum('new','in_progress','done','rejected') DEFAULT 'new',
  `admin_comment` text,
  `viewed` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_paid` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `requests`
--

INSERT INTO `requests` (`id`, `user_id`, `type`, `subject`, `message`, `address`, `phone`, `status`, `admin_comment`, `viewed`, `created_at`, `is_paid`) VALUES
(16, 4, 'connection', 'Нет воды', 'Вода к нашему дому не проведена', 'Красная 59.д', '+79185377186', 'done', NULL, 1, '2026-11-06 04:04:24', 0),
(17, 4, 'repair', 'Нет воды', 'Треснула труба', 'Красная 59.д', '+79185377186', 'new', NULL, 1, '2026-11-06 04:07:32', 0),
(18, 4, 'complaint', 'Нет воды', 'Вы перекопали весь дом, а воды нет', 'Красная 59.д', '+79185377186', 'done', NULL, 1, '2026-11-06 04:08:04', 0),
(19, 4, 'question', 'Нет воды', 'Помогите', 'Красная 59.д', '+79185377186', 'new', NULL, 1, '2026-11-06 04:13:00', 0),
(20, 4, 'connection', 'Был колодец', 'Был колодец, но сейчас требуется подключить к центральной системе водоснабжения города', 'Красная 59.д', '+79185377186', 'done', 'К вам выехали рабочие', 1, '2026-11-10 09:43:08', 0),
(21, 4, 'question', 'Помогите у вас есть вода', 'После того как на улице выключили и включили свет пропала вода', NULL, '+79185377186', 'done', 'Мы решаем эту проблему', 1, '2026-11-10 09:44:24', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `reviews`
--

CREATE TABLE `reviews` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` tinyint NOT NULL DEFAULT '5',
  `message` text NOT NULL,
  `status` enum('published','pending','rejected') DEFAULT 'published',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `rating`, `message`, `status`, `created_at`) VALUES
(7, 4, 5, 'всё круто', 'published', '2026-11-12 20:36:31');

-- --------------------------------------------------------

--
-- Структура таблицы `tariffs`
--

CREATE TABLE `tariffs` (
  `id` int NOT NULL,
  `name` varchar(128) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `unit` varchar(64) NOT NULL DEFAULT 'за 1 кубический метр',
  `description` varchar(255) NOT NULL DEFAULT '',
  `features` text,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `tariffs`
--

INSERT INTO `tariffs` (`id`, `name`, `price`, `unit`, `description`, `features`, `sort_order`, `is_active`, `updated_at`) VALUES
(1, 'Водоснабжение', 50.80, 'за 1 кубический метр', 'Холодная вода высшей категории качества', 'Ежедневный контроль качества воды\r\nКруглосуточная бесперебойная подача\r\nСовременные системы очистки и фильтрации\r\nСоответствие всем санитарным нормам', 1, 1, '2026-11-10 09:45:56'),
(2, 'Водоотведение', 32.15, 'за 1 кубический метр', 'Канализация и очистка сточных вод', 'Современные очистные сооружения\nЭкологичная утилизация отходов\nРегулярное техническое обслуживание\nМониторинг состояния канализационных сетей', 2, 1, '2026-10-07 00:55:17'),
(3, 'Техническая вода', 28.90, 'за 1 кубический метр', 'Для промышленных и технических нужд', 'Использование в производственных процессах\nПолив территорий и орошение земель\nСтроительные и ремонтные работы\nТехническое водоснабжение предприятий', 3, 1, '2026-10-07 00:55:17'),
(4, 'Техническое обслуживание', 150.00, 'ежемесячно', 'Абонентская плата за обслуживание систем', 'Обслуживание водопроводных сетей\nАварийно-восстановительные работы\nТехническая поддержка 24/7\nПлановые проверки и диагностика', 4, 1, '2026-10-07 00:55:17'),
(5, 'Водоснабжение', 45.80, 'за 1 кубический метр', 'Холодная вода высшей категории качества', 'Ежедневный контроль качества воды\nКруглосуточная бесперебойная подача\nСовременные системы очистки и фильтрации\nСоответствие всем санитарным нормам', 1, 0, '2026-10-09 21:46:35'),
(6, 'Водоотведение', 32.15, 'за 1 кубический метр', 'Канализация и очистка сточных вод', 'Современные очистные сооружения\nЭкологичная утилизация отходов\nРегулярное техническое обслуживание\nМониторинг состояния канализационных сетей', 2, 0, '2026-10-09 21:46:40'),
(7, 'Техническая вода', 28.90, 'за 1 кубический метр', 'Для промышленных и технических нужд', 'Использование в производственных процессах\nПолив территорий и орошение земель\nСтроительные и ремонтные работы\nТехническое водоснабжение предприятий', 3, 0, '2026-10-09 21:46:42'),
(8, 'Техническое обслуживание', 150.00, 'ежемесячно', 'Абонентская плата за обслуживание систем', 'Обслуживание водопроводных сетей\nАварийно-восстановительные работы\nТехническая поддержка 24/7\nПлановые проверки и диагностика', 4, 0, '2026-10-09 21:46:44');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `login` varchar(64) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(32) NOT NULL,
  `email` varchar(128) NOT NULL,
  `account_number` varchar(32) NOT NULL,
  `address` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `login`, `password`, `name`, `phone`, `email`, `account_number`, `address`, `created_at`) VALUES
(4, 'AKKAYNT5', '$2y$12$CB.NWpmXdOdMYR57LOrAFO7Xf/7gpadshGodClGA/plgHdrwJKcYC', 'Пилюгин Данил Михайлович', '+79185377186', 'danil.pilyug@yandex.ru', 'ЛС-9629211', 'Красная 59.д', '2026-11-06 03:42:52');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `meter_readings`
--
ALTER TABLE `meter_readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `tariffs`
--
ALTER TABLE `tariffs`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `meter_readings`
--
ALTER TABLE `meter_readings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `news`
--
ALTER TABLE `news`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT для таблицы `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `tariffs`
--
ALTER TABLE `tariffs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `meter_readings`
--
ALTER TABLE `meter_readings`
  ADD CONSTRAINT `meter_readings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
