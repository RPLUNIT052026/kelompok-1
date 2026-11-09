<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

const KATEGORI_MENU = ['Kopi', 'Non-Kopi', 'Snack', 'Makanan Berat', 'Dessert'];
const METODE_PEMBAYARAN = ['QRIS', 'DANA', 'OVO', 'GoPay', 'Transfer Bank'];
const TRANSISI_STATUS = [
    'menunggu' => ['diproses', 'dibatalkan'],
    'diproses' => ['selesai', 'dibatalkan'],
    'selesai' => [],
    'dibatalkan' => [],
];

final class ApiError extends RuntimeException
{
    public function __construct(public readonly int $status, string $message)
    {
        parent::__construct($message);
    }
}

function respond(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requestBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $body = json_decode($raw, true);
    if (!is_array($body)) {
        throw new ApiError(400, 'Format JSON tidak valid');
    }
    return $body;
}

function requiredString(array $body, string $key): string
{
    return isset($body[$key]) && is_string($body[$key]) ? trim($body[$key]) : '';
}

function jwtToken(array $user): string
{
    $secret = setting('JWT_SECRET', 'ganti-secret-ini-di-produksi');
    $header = rtrim(strtr(base64_encode('{"alg":"HS256","typ":"JWT"}'), '+/', '-_'), '=');
    $payload = rtrim(strtr(base64_encode(json_encode([
        'id' => (int) $user['id'],
        'nama' => $user['nama'],
        'role' => $user['role'],
        'iat' => time(),
        'exp' => time() + 28800,
    ], JSON_UNESCAPED_UNICODE)), '+/', '-_'), '=');
    $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $header . '.' . $payload, $secret, true)), '+/', '-_'), '=');
    return $header . '.' . $payload . '.' . $signature;
}

function authenticatedUser(): array
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $match)) {
        throw new ApiError(401, 'Silakan login terlebih dahulu');
    }

    $parts = explode('.', $match[1]);
    if (count($parts) !== 3) {
        throw new ApiError(401, 'Sesi tidak valid atau sudah berakhir, silakan login ulang');
    }
    [$encodedHeader, $encodedPayload, $signature] = $parts;
    $expected = rtrim(strtr(base64_encode(hash_hmac(
        'sha256',
        $encodedHeader . '.' . $encodedPayload,
        setting('JWT_SECRET', 'ganti-secret-ini-di-produksi'),
        true
    )), '+/', '-_'), '=');
    $payloadJson = base64_decode(strtr($encodedPayload, '-_', '+/'));
    $payload = $payloadJson === false ? null : json_decode($payloadJson, true);
    if (!hash_equals($expected, $signature) || !is_array($payload) || (int) ($payload['exp'] ?? 0) <= time()) {
        throw new ApiError(401, 'Sesi tidak valid atau sudah berakhir, silakan login ulang');
    }
    return $payload;
}

function requireAdmin(array $user): void
{
    if (($user['role'] ?? '') !== 'admin') {
        throw new ApiError(403, 'Hanya admin yang dapat mengakses fitur ini');
    }
}

function menuPayload(array $body): array
{
    $name = requiredString($body, 'nama_menu');
    $category = $body['kategori'] ?? null;
    $price = $body['harga'] ?? null;
    if ($name === '') {
        throw new ApiError(400, 'Nama menu wajib diisi');
    }
    if (!is_string($category) || !in_array($category, KATEGORI_MENU, true)) {
        throw new ApiError(400, 'Kategori harus salah satu dari: ' . implode(', ', KATEGORI_MENU));
    }
    if (!is_int($price) || $price < 0) {
        throw new ApiError(400, 'Harga harus berupa bilangan bulat tidak negatif');
    }
    $description = $body['deskripsi'] ?? null;
    if ($description !== null && !is_string($description)) {
        throw new ApiError(400, 'Deskripsi tidak valid');
    }
    return [$name, $category, $price, $description === '' ? null : $description];
}

function attachOrderItems(PDO $pdo, array $orders): array
{
    if ($orders === []) {
        return $orders;
    }
    $ids = array_map(static fn(array $order): int => (int) $order['id'], $orders);
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $statement = $pdo->prepare(
        "SELECT d.pesanan_id, d.menu_id, m.nama_menu, d.jumlah, d.subtotal
         FROM detail_pesanan d JOIN menu m ON m.id = d.menu_id
         WHERE d.pesanan_id IN ({$marks}) ORDER BY d.id"
    );
    $statement->execute($ids);
    $items = $statement->fetchAll();
    foreach ($orders as &$order) {
        $order['id'] = (int) $order['id'];
        $order['user_id'] = (int) $order['user_id'];
        $order['total_harga'] = (int) $order['total_harga'];
        $order['items'] = [];
        foreach ($items as $item) {
            if ((int) $item['pesanan_id'] === $order['id']) {
                $order['items'][] = [
                    'pesanan_id' => (int) $item['pesanan_id'],
                    'menu_id' => (int) $item['menu_id'],
                    'nama_menu' => $item['nama_menu'],
                    'jumlah' => (int) $item['jumlah'],
                    'subtotal' => (int) $item['subtotal'],
                ];
            }
        }
        unset($order['status_pembayaran']);
        if (isset($order['status_pembayaran_internal'])) {
            $order['status_pembayaran'] = $order['status_pembayaran_internal'];
            unset($order['status_pembayaran_internal']);
        }
    }
    unset($order);
    return $orders;
}

function orderQuery(PDO $pdo, string $where = '', array $params = [], string $sort = ''): array
{
    $sql = "SELECT p.id, p.user_id, u.nama AS nama_pelanggan, p.tanggal, p.status, p.total_harga,
                   pb.metode, pb.status AS status_pembayaran_internal
            FROM pesanan p JOIN users u ON u.id = p.user_id
            LEFT JOIN pembayaran pb ON pb.pesanan_id = p.id";
    if ($where !== '') {
        $sql .= ' WHERE ' . $where;
    }
    $sql .= $sort;
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return attachOrderItems($pdo, $statement->fetchAll());
}

function findOrder(PDO $pdo, int $id): ?array
{
    $orders = orderQuery($pdo, 'p.id = ?', [$id]);
    return $orders[0] ?? null;
}

function dispatch(): void
{
    $pdo = database();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = trim((string) ($_GET['path'] ?? ''), '/');
    $segments = $path === '' ? [] : explode('/', $path);
    $body = requestBody();

    if (($segments[0] ?? '') === 'users') {
        $action = $segments[1] ?? '';
        if ($method === 'POST' && $action === 'register') {
            $name = requiredString($body, 'nama');
            $email = requiredString($body, 'email');
            $password = $body['password'] ?? '';
            if ($name === '') {
                throw new ApiError(400, 'Nama wajib diisi');
            }
            if (!is_string($password) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new ApiError(400, 'Format email tidak valid');
            }
            if (strlen($password) < 6) {
                throw new ApiError(400, 'Password minimal 6 karakter');
            }
            $statement = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $statement->execute([$email]);
            if ($statement->fetch()) {
                throw new ApiError(409, 'Email sudah terdaftar');
            }
            try {
                $statement = $pdo->prepare('INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, "pelanggan")');
                $statement->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT)]);
            } catch (PDOException $error) {
                if ($error->getCode() === '23000') {
                    throw new ApiError(409, 'Email sudah terdaftar');
                }
                throw $error;
            }
            respond(['id' => (int) $pdo->lastInsertId(), 'message' => 'Registrasi berhasil'], 201);
        }
        if ($method === 'POST' && $action === 'login') {
            $email = requiredString($body, 'email');
            $password = $body['password'] ?? '';
            if ($email === '' || !is_string($password) || $password === '') {
                throw new ApiError(400, 'Email dan password wajib diisi');
            }
            $statement = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $statement->execute([$email]);
            $user = $statement->fetch();
            if (!$user || !password_verify($password, $user['password'])) {
                throw new ApiError(401, 'Email atau password salah');
            }
            $publicUser = ['id' => (int) $user['id'], 'nama' => $user['nama'], 'email' => $user['email'], 'role' => $user['role']];
            respond(['message' => 'Login berhasil', 'token' => jwtToken($publicUser), 'user' => $publicUser]);
        }
        if ($method === 'GET' && $action === 'me') {
            $user = authenticatedUser();
            $statement = $pdo->prepare('SELECT id, nama, email, role FROM users WHERE id = ?');
            $statement->execute([(int) $user['id']]);
            $profile = $statement->fetch();
            if (!$profile) {
                throw new ApiError(404, 'Pengguna tidak ditemukan');
            }
            $profile['id'] = (int) $profile['id'];
            respond($profile);
        }
    }

    if (($segments[0] ?? '') === 'menu') {
        $id = isset($segments[1]) ? filter_var($segments[1], FILTER_VALIDATE_INT) : null;
        if ($method === 'GET' && $id === null) {
            $category = $_GET['kategori'] ?? null;
            if ($category !== null && !in_array($category, KATEGORI_MENU, true)) {
                throw new ApiError(400, 'Kategori tidak dikenal');
            }
            $sql = 'SELECT id, nama_menu, kategori, harga, deskripsi FROM menu WHERE tersedia = 1';
            $params = [];
            if ($category !== null) {
                $sql .= ' AND kategori = ?';
                $params[] = $category;
            }
            $sql .= ' ORDER BY FIELD(kategori, "Kopi", "Non-Kopi", "Snack", "Makanan Berat", "Dessert"), id';
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            $menus = $statement->fetchAll();
            foreach ($menus as &$item) {
                $item['id'] = (int) $item['id'];
                $item['harga'] = (int) $item['harga'];
            }
            unset($item);
            respond($menus);
        }
        if ($method === 'GET' && $id !== null) {
            $statement = $pdo->prepare('SELECT id, nama_menu, kategori, harga, deskripsi FROM menu WHERE id = ? AND tersedia = 1');
            $statement->execute([$id]);
            $item = $statement->fetch();
            if (!$item) {
                throw new ApiError(404, 'Menu tidak ditemukan');
            }
            $item['id'] = (int) $item['id'];
            $item['harga'] = (int) $item['harga'];
            respond($item);
        }
        if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
            $user = authenticatedUser();
            requireAdmin($user);
            if ($method === 'POST' && $id === null) {
                $statement = $pdo->prepare('INSERT INTO menu (nama_menu, kategori, harga, deskripsi) VALUES (?, ?, ?, ?)');
                $statement->execute(menuPayload($body));
                respond(['id' => (int) $pdo->lastInsertId(), 'message' => 'Menu berhasil ditambahkan'], 201);
            }
            if ($id === null) {
                throw new ApiError(400, 'ID menu tidak valid');
            }
            if ($method === 'PUT') {
                $statement = $pdo->prepare('UPDATE menu SET nama_menu = ?, kategori = ?, harga = ?, deskripsi = ? WHERE id = ? AND tersedia = 1');
                $statement->execute([...menuPayload($body), $id]);
                $check = $pdo->prepare('SELECT id FROM menu WHERE id = ? AND tersedia = 1');
                $check->execute([$id]);
                if (!$check->fetch()) {
                    throw new ApiError(404, 'Menu tidak ditemukan');
                }
                respond(['message' => 'Menu berhasil diperbarui']);
            }
            if ($method === 'DELETE') {
                $statement = $pdo->prepare('UPDATE menu SET tersedia = 0 WHERE id = ? AND tersedia = 1');
                $statement->execute([$id]);
                if ($statement->rowCount() === 0) {
                    throw new ApiError(404, 'Menu tidak ditemukan');
                }
                respond(['message' => 'Menu berhasil dihapus']);
            }
        }
    }

    if (($segments[0] ?? '') === 'pesanan') {
        if ($method === 'POST' && count($segments) === 1) {
            $user = authenticatedUser();
            $items = $body['items'] ?? null;
            if (!is_array($items) || $items === []) {
                throw new ApiError(400, 'Keranjang kosong: tambahkan minimal satu menu');
            }
            $combined = [];
            foreach ($items as $item) {
                $menuId = filter_var($item['menu_id'] ?? null, FILTER_VALIDATE_INT);
                $quantity = filter_var($item['jumlah'] ?? null, FILTER_VALIDATE_INT);
                if ($menuId === false || $menuId === null || $menuId <= 0) {
                    throw new ApiError(400, 'menu_id tidak valid');
                }
                if ($quantity === false || $quantity === null || $quantity < 1 || $quantity > 50) {
                    throw new ApiError(400, 'Jumlah tiap menu harus 1 sampai 50');
                }
                $combined[$menuId] = ($combined[$menuId] ?? 0) + $quantity;
            }
            $pdo->beginTransaction();
            try {
                $ids = array_keys($combined);
                $marks = implode(',', array_fill(0, count($ids), '?'));
                $statement = $pdo->prepare("SELECT id, harga FROM menu WHERE tersedia = 1 AND id IN ({$marks})");
                $statement->execute($ids);
                $prices = [];
                foreach ($statement->fetchAll() as $menu) {
                    $prices[(int) $menu['id']] = (int) $menu['harga'];
                }
                foreach ($combined as $menuId => $quantity) {
                    if (!array_key_exists($menuId, $prices)) {
                        throw new ApiError(400, "Menu dengan id {$menuId} tidak tersedia");
                    }
                }
                $total = 0;
                foreach ($combined as $menuId => $quantity) {
                    $total += $prices[$menuId] * $quantity;
                }
                $statement = $pdo->prepare('INSERT INTO pesanan (user_id, status, total_harga) VALUES (?, "menunggu", ?)');
                $statement->execute([(int) $user['id'], $total]);
                $orderId = (int) $pdo->lastInsertId();
                $detail = $pdo->prepare('INSERT INTO detail_pesanan (pesanan_id, menu_id, jumlah, subtotal) VALUES (?, ?, ?, ?)');
                foreach ($combined as $menuId => $quantity) {
                    $detail->execute([$orderId, $menuId, $quantity, $prices[$menuId] * $quantity]);
                }
                $pdo->commit();
            } catch (Throwable $error) {
                $pdo->rollBack();
                throw $error;
            }
            respond(findOrder($pdo, $orderId), 201);
        }
        if ($method === 'GET' && count($segments) === 1) {
            $user = authenticatedUser();
            requireAdmin($user);
            respond(orderQuery($pdo, '', [], ' ORDER BY p.id DESC'));
        }
        if ($method === 'GET' && ($segments[1] ?? '') === 'user' && isset($segments[2])) {
            $user = authenticatedUser();
            $userId = filter_var($segments[2], FILTER_VALIDATE_INT);
            if ($userId === false) {
                throw new ApiError(400, 'ID pengguna tidak valid');
            }
            if (($user['role'] ?? '') !== 'admin' && $userId !== (int) $user['id']) {
                throw new ApiError(403, 'Anda hanya dapat melihat pesanan milik sendiri');
            }
            respond(orderQuery($pdo, 'p.user_id = ?', [$userId], ' ORDER BY p.id DESC'));
        }
        $orderId = isset($segments[1]) ? filter_var($segments[1], FILTER_VALIDATE_INT) : false;
        if ($orderId !== false && $orderId !== null && $method === 'PUT' && ($segments[2] ?? '') === 'status') {
            $user = authenticatedUser();
            requireAdmin($user);
            $status = $body['status'] ?? '';
            if (!is_string($status) || !array_key_exists($status, TRANSISI_STATUS)) {
                throw new ApiError(400, 'Status harus salah satu dari: ' . implode(', ', array_keys(TRANSISI_STATUS)));
            }
            $order = findOrder($pdo, $orderId);
            if (!$order) {
                throw new ApiError(404, 'Pesanan tidak ditemukan');
            }
            if (!in_array($status, TRANSISI_STATUS[$order['status']], true)) {
                throw new ApiError(409, "Status tidak dapat diubah dari \"{$order['status']}\" ke \"{$status}\"");
            }
            $statement = $pdo->prepare('UPDATE pesanan SET status = ? WHERE id = ?');
            $statement->execute([$status, $orderId]);
            respond(['message' => 'Status pesanan diperbarui', 'id' => $orderId, 'status' => $status]);
        }
        if ($orderId !== false && $orderId !== null && $method === 'PUT' && ($segments[2] ?? '') === 'batal') {
            $user = authenticatedUser();
            $order = findOrder($pdo, $orderId);
            if (!$order) {
                throw new ApiError(404, 'Pesanan tidak ditemukan');
            }
            if ($order['user_id'] !== (int) $user['id']) {
                throw new ApiError(403, 'Anda hanya dapat membatalkan pesanan milik sendiri');
            }
            if ($order['status'] !== 'menunggu') {
                throw new ApiError(409, 'Hanya pesanan berstatus "menunggu" yang dapat dibatalkan');
            }
            $pdo->prepare('UPDATE pesanan SET status = "dibatalkan" WHERE id = ?')->execute([$orderId]);
            respond(['message' => 'Pesanan dibatalkan', 'id' => $orderId, 'status' => 'dibatalkan']);
        }
    }

    if (($segments[0] ?? '') === 'pembayaran' && $method === 'POST' && count($segments) === 1) {
        $user = authenticatedUser();
        $orderId = $body['pesanan_id'] ?? null;
        $methodName = $body['metode'] ?? null;
        if (!is_int($orderId)) {
            throw new ApiError(400, 'pesanan_id wajib diisi');
        }
        if (!is_string($methodName) || !in_array($methodName, METODE_PEMBAYARAN, true)) {
            throw new ApiError(400, 'Metode pembayaran harus salah satu dari: ' . implode(', ', METODE_PEMBAYARAN));
        }
        $order = findOrder($pdo, $orderId);
        if (!$order) {
            throw new ApiError(404, 'Pesanan tidak ditemukan');
        }
        if ($order['user_id'] !== (int) $user['id']) {
            throw new ApiError(403, 'Anda hanya dapat membayar pesanan milik sendiri');
        }
        if ($order['status'] !== 'menunggu') {
            throw new ApiError(409, 'Pesanan ini tidak dapat dibayar (status: ' . $order['status'] . ')');
        }
        if ($order['total_harga'] <= 0) {
            throw new ApiError(400, 'Jumlah pembayaran tidak valid');
        }
        $failed = setting('APP_ENV', 'development') !== 'production' && ($body['simulasi_gagal'] ?? false) === true;
        $paymentStatus = $failed ? 'gagal' : 'berhasil';
        $reference = $failed ? null : 'PAY-' . strtoupper(bin2hex(random_bytes(5)));
        $statement = $pdo->prepare(
            'INSERT INTO pembayaran (pesanan_id, metode, jumlah_bayar, status, referensi)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE metode = VALUES(metode), jumlah_bayar = VALUES(jumlah_bayar),
               status = VALUES(status), referensi = VALUES(referensi), waktu_bayar = CURRENT_TIMESTAMP'
        );
        $statement->execute([$orderId, $methodName, $order['total_harga'], $paymentStatus, $reference]);
        if ($failed) {
            respond(['status' => 'gagal', 'message' => 'Pembayaran ditolak oleh penyedia (simulasi)', 'pesanan_status' => 'menunggu'], 402);
        }
        $pdo->prepare('UPDATE pesanan SET status = "diproses" WHERE id = ?')->execute([$orderId]);
        respond(['status' => 'berhasil', 'message' => 'Pembayaran berhasil', 'referensi' => $reference, 'pesanan_id' => $orderId, 'pesanan_status' => 'diproses']);
    }

    throw new ApiError(404, 'Endpoint tidak ditemukan');
}

try {
    dispatch();
} catch (ApiError $error) {
    respond(['message' => $error->getMessage()], $error->status);
} catch (Throwable $error) {
    error_log((string) $error);
    respond(['message' => 'Terjadi kesalahan pada server'], 500);
}
