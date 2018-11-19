USE mc_config;
DROP TABLE IF EXISTS internal_access;

CREATE TABLE internal_access (
    id INT(10) NOT NULL AUTO_INCREMENT,
    time_created TIMESTAMP DEFAULT NOW(),
    private_key VARCHAR(4000) NOT NULL,
    public_key VARCHAR(1000) NOT NULL,
    PRIMARY KEY (id)
);
