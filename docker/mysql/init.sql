-- Grant full privileges to myddleware user on myddleware database
GRANT ALL PRIVILEGES ON myddleware.* TO 'myddleware'@'%';
GRANT CREATE, ALTER, DROP, INSERT, UPDATE, DELETE, SELECT, REFERENCES, RELOAD on *.* TO 'myddleware'@'%';
FLUSH PRIVILEGES;

-- Ensure the database exists
CREATE DATABASE IF NOT EXISTS myddleware CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; 