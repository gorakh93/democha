<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=spend_it;charset=utf8mb4', 'root', '');
$names = ['Acme Co.', 'Global Traders', 'Quick Mart', 'Sunrise Markets', 'BlueWave Supplies', 'Vertex Retail', 'Prime Goods', 'Metro Stores', 'Zenith Commerce', 'Nexus Deals'];
$stmt = $pdo->query('select id from bills');
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
$count = 0;
foreach ($ids as $id) {
    $name = $names[array_rand($names)];
    $u = $pdo->prepare('update bills set merchant = ? where id = ?');
    if ($u->execute([$name, $id])) {
        $count++;
    }
}
echo "Updated $count rows\n";
