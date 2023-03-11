SET time_zone='+00:00';
drop procedure if exists mockUsersEmails;
DELIMITER //
CREATE PROCEDURE mockUsersEmails()
BEGIN
    DECLARE i INT DEFAULT 0;

    DELETE FROM emails;
    DELETE FROM users;

    WHILE (i <= 1000000) DO
            INSERT INTO `users` (username, email, validts, confirmed) values (
                CONCAT('user_', i),
                CONCAT('email_', i, '@gmail.com'),
                CURRENT_TIMESTAMP()  + interval rand()*30 DAY,
                UNIX_TIMESTAMP() % 2
            );

            INSERT INTO `emails` (email, checked, valid) values (
                CONCAT('email_', i, '@gmail.com'),
                (CURRENT_TIMESTAMP()  + interval rand()*30 SECOND ) % 2 > 0,
                UNIX_TIMESTAMP() % 5 > 0
            );
            SET i = i+1;
        END WHILE;
END;
//

CALL mockUsersEmails();