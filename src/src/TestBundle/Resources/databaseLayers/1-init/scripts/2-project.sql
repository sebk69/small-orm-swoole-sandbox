CREATE TABLE `project` (
                           `id` int NOT NULL AUTO_INCREMENT,
                           `user_id` int NOT NULL,
                           `name` varchar(45) DEFAULT NULL,
                           PRIMARY KEY (`id`),
                           KEY `fk_new_table_1_idx` (`user_id`),
                           CONSTRAINT `fk_new_table_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
