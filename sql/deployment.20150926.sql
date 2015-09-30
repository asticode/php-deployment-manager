-- Build
DROP TABLE IF EXISTS build;
CREATE TABLE `build` (
  `id` INT(10) UNSIGNED AUTO_INCREMENT,
  `project` VARCHAR(255) NOT NULL,
  `build_state_id` TINYINT(3) NOT NULL,
  `commit_node` VARCHAR(255) DEFAULT NULL,
  `commit_author` VARCHAR(255) DEFAULT NULL,
  `commit_message` VARCHAR(255) DEFAULT NULL,
  `nb_of_steps` TINYINT(3) UNSIGNED DEFAULT NULL,
  `execute_log` TEXT,
  `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY(project),
  KEY(build_state_id)
) ENGINE=InnoDB AUTO_INCREMENT=0 CHARSET=utf8;

-- Build History
DROP TABLE IF EXISTS build_history;
CREATE TABLE `build_history` (
  `build_id` INT(10) UNSIGNED,
  `project` VARCHAR(255) NOT NULL,
  `build_state_id` TINYINT(3) NOT NULL,
  `commit_node` VARCHAR(255) DEFAULT NULL,
  `commit_author` VARCHAR(255) DEFAULT NULL,
  `commit_message` VARCHAR(255) DEFAULT NULL,
  `nb_of_steps` TINYINT(3) UNSIGNED DEFAULT NULL,
  `execute_log` TEXT,
  `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  KEY(build_id),
  KEY(project),
  KEY(build_state_id)
) ENGINE=InnoDB AUTO_INCREMENT=0 CHARSET=utf8;