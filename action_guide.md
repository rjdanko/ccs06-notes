User table SQL Statement: 
CREATE TABLE `fin_RALM_user` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(255) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `firstname`  VARCHAR(255) NOT NULL,
  `middlename` VARCHAR(255)          DEFAULT NULL,
  `lastname`   VARCHAR(255) NOT NULL,
  `gender`     TINYINT(1)   NOT NULL,
  `dob`        DATE         NOT NULL,
  `status`     TINYINT(1)   NOT NULL DEFAULT 2,

  PRIMARY KEY (`id`),

  CONSTRAINT `chk_password_length`
    CHECK (CHAR_LENGTH(`password`) >= 8),

  CONSTRAINT `chk_status_range`
    CHECK (`status` IN (1, 2, 3))
);
