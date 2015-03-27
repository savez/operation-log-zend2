DROP TABLE IF EXISTS `operation_log`;

CREATE TABLE IF NOT EXISTS `operation_log` (
  `id_operation_log` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `note` VARCHAR(50) NULL,
  `table` VARCHAR(255) NULL,
  `operation` ENUM('insert','update','delete','trigger','create') NULL,
  `id_user` VARCHAR(255) NULL,
  `username` VARCHAR(255) NULL,
  `event_date` DATETIME NULL,
  `id_row` VARCHAR(255) NULL COMMENT 'id che identifica la riga aggiunta,cancellata,modificata',
  `field` VARCHAR(255) NULL,
  `value_old` VARCHAR(255) NULL,
  `value_new` VARCHAR(255) NULL,
  `source` VARCHAR(255) NULL,
  `uri` VARCHAR(255) NULL,
  `ip` CHAR(15) NULL,
  `session_id` TEXT NULL,
  `event` TEXT NULL,
  `priority` VARCHAR(50) NULL,
  PRIMARY KEY (`id_operation_log`))
ENGINE = InnoDB

