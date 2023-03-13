SET time_zone='+00:00';
drop procedure if exists mockUsersEmails;
DELIMITER //
CREATE PROCEDURE mockUsersEmails()
BEGIN
    DECLARE i INT DEFAULT 0;

    DELETE FROM emails;
    DELETE FROM users;

    WHILE (i <= 9999) DO #i = 999999 too long with indexes
            INSERT INTO `users` (username, email, validts, confirmed) values (
                CONCAT('user_', i),
                CONCAT('email_', i, '@gmail.com'),
                CURRENT_TIMESTAMP()  + interval rand()*30 DAY,
                UNIX_TIMESTAMP() % 2
            );

            INSERT INTO `emails` (email, checked, valid) values (
                CONCAT('email_', i, '@gmail.com'),
                RAND() * 2 > 1,
                RAND() * 5 > 4
            );
            SET i = i+1;
        END WHILE;
END;
//

CALL mockUsersEmails();