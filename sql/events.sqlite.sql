/* Relies on locations table Cf. phpfacile/geocoding-db-zend */
CREATE TABLE IF NOT EXISTS metadata (keyword VARCHAR(64) NOT NULL, value VARCHAR(64), UNIQUE(keyword));
CREATE TABLE events (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(200) NOT NULL, datetime_start DATETIME NOT NULL, datetime_end DATETIME NOT NULL, datetime_start_utc DATETIME NOT NULL, datetime_end_utc DATETIME NOT NULL, address_full TEXT NOT NULL, place_id INTEGER NULL);
INSERT INTO metadata (keyword, value) VALUES ('events_version', '1.0.0.0');
