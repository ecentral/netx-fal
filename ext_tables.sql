CREATE TABLE cf_netx_fal (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    identifier VARCHAR(250) DEFAULT '' NOT NULL,
    crdate INT(11) UNSIGNED DEFAULT 0 NOT NULL,
    content mediumblob,
    expires INT(11) UNSIGNED DEFAULT 0 NOT NULL,
    PRIMARY KEY (id),
    KEY cache_id (identifier)
) ENGINE=InnoDB;

CREATE TABLE `cache_netx_fal` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `identifier` VARCHAR(250) DEFAULT '' NOT NULL,
   `expires` INT(11) UNSIGNED DEFAULT 0 NOT NULL,
   `content` longblob,
   PRIMARY KEY (`id`),
   KEY `cache_id` (`identifier`(180),`expires`)
) ENGINE=InnoDB;

CREATE TABLE `cache_netx_fal_tags` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `identifier` VARCHAR(250) DEFAULT '' NOT NULL,
    `tag` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    KEY `cache_id` (`identifier`(191)),
    KEY `cache_tag` (`tag`(191))
) ENGINE=InnoDB;


