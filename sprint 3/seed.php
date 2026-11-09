<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

$menu = [
    ['Espresso', 'Kopi', 18000, 'Kopi murni, pekat dan singkat'],
    ['Americano', 'Kopi', 20000, 'Espresso dengan air panas'],
    ['Cappuccino', 'Kopi', 25000, 'Espresso, susu, dan foam lembut'],
    ['Café Latte', 'Kopi', 25000, 'Espresso dengan susu steamed'],
    ['Caramel Macchiato', 'Kopi', 28000, 'Espresso, susu, dan saus karamel'],
    ['Vanilla Latte', 'Kopi', 27000, 'Latte dengan sirup vanila'],
    ['Hazelnut Latte', 'Kopi', 27000, 'Latte dengan sirup hazelnut'],
    ['Cold Brew', 'Kopi', 24000, 'Kopi seduh dingin 12 jam'],
    ['Es Kopi Susu Gula Aren', 'Kopi', 22000, 'Kopi susu dengan gula aren'],
    ['Affogato', 'Kopi', 30000, 'Espresso di atas es krim vanila'],
    ['Solace Signature Coffee', 'Kopi', 32000, 'Racikan khas rumah'],
    ['Matcha Latte', 'Non-Kopi', 27000, 'Teh hijau Jepang dengan susu'],
    ['Chocolate Latte', 'Non-Kopi', 25000, 'Coklat hangat dengan susu'],
    ['Taro Latte', 'Non-Kopi', 27000, 'Minuman talas yang lembut'],
    ['Red Velvet Latte', 'Non-Kopi', 27000, 'Latte rasa red velvet'],
    ['Lemon Tea', 'Non-Kopi', 18000, 'Teh dengan perasan lemon'],
    ['Thai Tea', 'Non-Kopi', 22000, 'Teh susu khas Thailand'],
    ['Fresh Orange Juice', 'Non-Kopi', 20000, 'Jus jeruk segar'],
    ['Mango Smoothie', 'Non-Kopi', 25000, 'Smoothie mangga'],
    ['Strawberry Yogurt Smoothie', 'Non-Kopi', 26000, 'Smoothie stroberi dengan yogurt'],
    ['Air Mineral', 'Non-Kopi', 8000, 'Air mineral dingin'],
    ['French Fries', 'Snack', 22000, 'Kentang goreng renyah'],
    ['Croissant', 'Snack', 20000, 'Pastry mentega lapis-lapis'],
    ['Chicken Sandwich', 'Snack', 28000, 'Roti isi ayam dan sayur segar'],
    ['Banana Pancake', 'Snack', 25000, 'Pancake pisang dengan madu'],
    ['Beef Burger', 'Snack', 32000, 'Burger daging sapi dengan keju'],
    ['Onion Rings', 'Snack', 20000, 'Bawang bombay goreng tepung'],
    ['Nachos Cheese', 'Snack', 24000, 'Nachos dengan saus keju'],
    ['Roti Bakar Coklat Keju', 'Snack', 18000, 'Roti bakar dengan coklat dan keju'],
    ['Spaghetti Aglio Olio', 'Makanan Berat', 30000, 'Spaghetti bawang putih dan cabai'],
    ['Nasi Goreng Solace', 'Makanan Berat', 28000, 'Nasi goreng khas cafe'],
    ['Chicken Katsu Rice', 'Makanan Berat', 32000, 'Nasi dengan ayam katsu'],
    ['Beef Steak', 'Makanan Berat', 45000, 'Steak daging sapi dengan saus'],
    ['Tiramisu', 'Dessert', 27000, 'Kue kopi khas Italia'],
    ['Chocolate Lava Cake', 'Dessert', 30000, 'Kue coklat dengan isi lumer'],
    ['Cheesecake', 'Dessert', 28000, 'Lembut dengan dasar biskuit'],
    ['Waffle Ice Cream', 'Dessert', 26000, 'Waffle hangat dengan es krim'],
    ['Pudding Coklat', 'Dessert', 18000, 'Puding coklat lembut'],
];

try {
    $pdo = database();
    $pdo->beginTransaction();
    $count = (int) $pdo->query('SELECT COUNT(*) FROM menu')->fetchColumn();
    if ($count === 0) {
        $insertMenu = $pdo->prepare('INSERT INTO menu (nama_menu, kategori, harga, deskripsi) VALUES (?, ?, ?, ?)');
        foreach ($menu as $item) {
            $insertMenu->execute($item);
        }
        echo count($menu) . " menu ditambahkan.\n";
    } else {
        echo "Menu: {$count} item sudah tersedia, dilewati.\n";
    }

    $email = setting('ADMIN_EMAIL', 'admin@solace.coffee');
    $password = setting('ADMIN_PASSWORD', 'Admin123!');
    if ($password === '') {
        throw new RuntimeException('ADMIN_PASSWORD tidak boleh kosong.');
    }

    $statement = $pdo->prepare('SELECT id, role FROM users WHERE email = ?');
    $statement->execute([$email]);
    $existingAdmin = $statement->fetch();
    if (!$existingAdmin) {
        $statement = $pdo->prepare('INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, "admin")');
        $statement->execute(['Admin Solace', $email, password_hash($password, PASSWORD_BCRYPT)]);
        echo "Admin dibuat: {$email}. Ganti password setelah login pertama.\n";
    } elseif ($existingAdmin['role'] !== 'admin') {
        throw new RuntimeException("Email {$email} sudah digunakan akun non-admin; akun tidak diubah.");
    } else {
        $statement = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $statement->execute([password_hash($password, PASSWORD_BCRYPT), (int) $existingAdmin['id']]);
        echo "Password admin {$email} diselaraskan dengan konfigurasi.\n";
    }
    $pdo->commit();
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Seed gagal: {$error->getMessage()}\n");
    exit(1);
}
