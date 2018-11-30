INSERT INTO `users` (`type`, `first_name`, `last_name`, `username`, `password`)
VALUES ('Administrador', 'Admin', 'Admin', 'admin', SHA1(CONCAT('###SALT###', 'admin')));