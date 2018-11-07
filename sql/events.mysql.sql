/* Relies on locations table Cf. phpfacile/geocoding-db-zend */
CREATE TABLE IF NOT EXISTS metadata (keyword VARCHAR(64) NOT NULL, value VARCHAR(64), UNIQUE(keyword));
CREATE TABLE events (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, name VARCHAR(200) NOT NULL, datetime_start DATETIME NOT NULL, datetime_end DATETIME NOT NULL, datetime_start_utc DATETIME NOT NULL, datetime_end_utc DATETIME NOT NULL, address_full TEXT NOT NULL, place_id BIGINT UNSIGNED NULL, FOREIGN KEY (place_id) REFERENCES locations(id)) ENGINE=INNODB CHARACTER SET=utf8;
INSERT INTO metadata (keyword, value) VALUES ('events_version', '1.0.0.0');
