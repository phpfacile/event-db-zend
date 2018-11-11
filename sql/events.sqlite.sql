/* Here, no database upgrade is porvided, we assume 1.0.0.0 version was never installed in a production environment */
/* Relies on geocoded_locations table Cf. phpfacile/geocoding-db-zend */
CREATE TABLE IF NOT EXISTS metadata (
    keyword VARCHAR(64) NOT NULL,
    value VARCHAR(64),
    UNIQUE(keyword)
);
CREATE TABLE events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(200) NOT NULL,
    datetime_start DATETIME NOT NULL,
    datetime_end DATETIME NULL,
    datetime_start_utc DATETIME NULL,
    datetime_end_utc DATETIME NULL,
    address VARCHAR(255) NULL,
    place_name VARCHAR(200) NOT NULL,
    postal_code VARCHAR(32) NULL,
    country_code VARCHAR(2) NOT NULL,
    url VARCHAR(255) NULL,
    type VARCHAR(32) NULL,
    submitter_name VARCHAR(64) NOT NULL,
    submitter_email VARCHAR(64) NOT NULL,
    locale VARCHAR(5) NOT NULL,
    insertion_datetime_utc DATETIME NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'submitted',
    place_geocoder_location_id INTEGER NULL,
    FOREIGN KEY (place_geocoder_location_id) REFERENCES geocoder_locations(id)
);

INSERT INTO metadata (keyword, value) VALUES ('events_version', '1.0.1.0');
