<?php
// ໂຫຼດຄ່າຈາກ .env (ແບບງ່າຍສຳລັບເລີ່ມຕົ້ນ)
// ໃນ Production ຄວນໃຊ້ Library vlucas/phpdotenv
function getEnvVar($key) {
    $env = file_get_contents(__DIR__ . '/../.env');
    $lines = explode("\n", $env);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        if (trim($name) == $key) return trim($value);
    }
    return null;
}

$host = getEnvVar('DB_HOST');
$db   = getEnvVar('DB_NAME');
$user = getEnvVar('DB_USER');
$pass = getEnvVar('DB_PASS');
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // ເຊື່ອງ Error ບໍ່ໃຫ້ User ເຫັນ
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>