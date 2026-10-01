-- Izin tambahan agar `php artisan test` bisa membuat/menghapus database uji
CREATE DATABASE IF NOT EXISTS `v2mysterypassenger_test`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Database terpisah untuk menguji `migrate:from-v1` tanpa tabrakan DDL.
CREATE DATABASE IF NOT EXISTS `v1_legacy_test`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON `v2mysterypassenger_test`.* TO 'mp'@'%';
GRANT ALL PRIVILEGES ON `v1_legacy_test`.* TO 'mp'@'%';
FLUSH PRIVILEGES;
