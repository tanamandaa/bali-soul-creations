<?php
session_start();
require_once __DIR__ . '/app/Config/Database.php';

// Konfigurasi Database
$host = 'localhost';
$dbname = 'balisoulcreations';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Logika Autentikasi Admin
if (isset($_POST['admin_login'])) {
    if ($_POST['username'] === 'admin' && $_POST['password'] === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $login_error = "Invalid admin credentials!";
    }
}

// Logika Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    header("Location: admin.php");
    exit;
}

// Memproses Operasi Data Jika Sesi Admin Aktif
if (isset($_SESSION['admin_logged_in'])) {

    // Update Status Pesanan
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['order_id']]);
        header("Location: admin.php?page=orders");
        exit;
    }

    // Input Produk Baru
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_product'])) {
        $imagePath = null;
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "uploads/products/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $imagePath = $target_dir . time() . "_" . basename($_FILES["product_image"]["name"]);
            move_uploaded_file($_FILES["product_image"]["tmp_name"], $imagePath);
        }
        $stmt = $pdo->prepare("INSERT INTO products (CategoryId, Name, Price, Stock, Material, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['category_id'], $_POST['name'], $_POST['price'], $_POST['stock'], $_POST['material'], $imagePath]);
        header("Location: admin.php?page=products");
        exit;
    }

    // Update Produk Terdaftar
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product_action'])) {
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "uploads/products/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $imagePath = $target_dir . time() . "_" . basename($_FILES["product_image"]["name"]);
            move_uploaded_file($_FILES["product_image"]["tmp_name"], $imagePath);
            $stmt = $pdo->prepare("UPDATE products SET CategoryId=?, Name=?, Price=?, Stock=?, Material=?, image=? WHERE Id=?");
            $stmt->execute([$_POST['category_id'], $_POST['name'], $_POST['price'], $_POST['stock'], $_POST['material'], $imagePath, $_POST['product_id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE products SET CategoryId=?, Name=?, Price=?, Stock=?, Material=? WHERE Id=?");
            $stmt->execute([$_POST['category_id'], $_POST['name'], $_POST['price'], $_POST['stock'], $_POST['material'], $_POST['product_id']]);
        }
        header("Location: admin.php?page=products");
        exit;
    }

    // Hapus Produk
    if (isset($_GET['delete_product'])) {
        $stmt = $pdo->prepare("DELETE FROM products WHERE Id = ?");
        $stmt->execute([$_GET['delete_product']]);
        header("Location: admin.php?page=products");
        exit;
    }

    // Tambah Kategori (Parent/Sub)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_category'])) {
        $parentId = $_POST['parent_id'] === '0' ? NULL : $_POST['parent_id'];
        $stmt = $pdo->prepare("INSERT INTO categories (Name, ParentId) VALUES (?, ?)");
        $stmt->execute([$_POST['cat_name'], $parentId]);
        header("Location: admin.php?page=categories");
        exit;
    }

    // Hapus Kategori
    if (isset($_GET['delete_category'])) {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE Id = ?");
        $stmt->execute([$_GET['delete_category']]);
        header("Location: admin.php?page=categories");
        exit;
    }

    // Penarikan Data untuk UI (Tree Structure)
    $stmtCat = $pdo->query("SELECT * FROM categories ORDER BY ParentId ASC, Name ASC");
    $allCategories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
    $categoriesTree = [];
    foreach ($allCategories as $cat) {
        if ($cat['ParentId'] == null) {
            $cat['SubCategories'] = [];
            $categoriesTree[$cat['Id']] = $cat;
        }
    }
    foreach ($allCategories as $cat) {
        if ($cat['ParentId'] != null && isset($categoriesTree[$cat['ParentId']])) {
            $categoriesTree[$cat['ParentId']]['SubCategories'][] = $cat;
        }
    }

    // Load Daftar Produk dan Orders
    $stmtProd = $pdo->query("SELECT p.*, c.Name as CategoryName FROM products p LEFT JOIN categories c ON p.CategoryId = c.Id ORDER BY p.Id DESC");
    $products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

    $stmtOrder = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
    $allOrders = $stmtOrder->fetchAll(PDO::FETCH_ASSOC);

    // Hitung Statistik Dashboard
    $totalProducts = count($products);
    $totalOrders = count($allOrders);
    $revenue = 0;
    foreach ($allOrders as $order) {
        if ($order['status'] !== 'Cancelled' && $order['status'] !== 'Awaiting Payment') {
            $revenue += $order['total'];
        }
    }
}

$page = $_GET['page'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | Bali Soul Creations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Playfair+Display&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        baliBg: "#F5F2EA",
                        baliDark: "#2D241E",
                        baliWood: "#8B5E3C"
                    },
                    fontFamily: {
                        sans: ["Inter"],
                        display: ["Playfair Display"]
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background: #F5F2EA;
            letter-spacing: 0.03em;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            background-color: rgba(139, 94, 60, 0.1);
            color: #8B5E3C;
            font-weight: 500;
        }
    </style>
</head>

<body class="font-sans text-baliDark flex min-h-screen">

    <?php if (!isset($_SESSION['admin_logged_in'])): ?>
        <div class="w-full min-h-screen flex items-center justify-center p-10">
            <div class="w-full max-w-md bg-white/60 backdrop-blur-md p-14 border border-baliDark/10 rounded-2xl shadow-xl text-center">
                <img src="bali-soul-creations-logo.png" alt="Logo" class="h-10 w-auto">
                <h1 class="font-display text-2xl tracking-[0.3em] uppercase mb-10">Bali Soul</h1>
                <?php if (isset($login_error)): ?>
                    <p class="text-[10px] uppercase tracking-widest text-red-600 mb-6 bg-red-50 p-3 border border-red-200"><?= $login_error; ?></p>
                <?php endif; ?>
                <form action="admin.php" method="POST" class="space-y-8 text-left">
                    <div>
                        <label class="block text-[10px] uppercase tracking-[0.3em] text-baliWood mb-3">Username</label>
                        <input type="text" name="username" required class="w-full bg-transparent border-b border-baliDark/20 py-2 text-sm focus:outline-none focus:border-baliWood transition-colors">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-[0.3em] text-baliWood mb-3">Password</label>
                        <input type="password" name="password" required class="w-full bg-transparent border-b border-baliDark/20 py-2 text-sm focus:outline-none focus:border-baliWood transition-colors">
                    </div>
                    <button type="submit" name="admin_login" class="w-full bg-baliDark text-baliBg py-4 text-xs uppercase tracking-[0.25em] hover:bg-baliWood mt-6 transition-colors">Authorize</button>
                </form>
                <a href="index.php" class="inline-block mt-8 text-[10px] uppercase tracking-[0.2em] text-gray-500 hover:text-baliWood">&larr; Return to Website</a>
            </div>
        </div>
    <?php else: ?>
        <aside class="w-64 min-h-screen bg-white/70 backdrop-blur border-r border-baliWood/10 p-10 fixed h-full overflow-y-auto z-50">
            <h1 class="font-display text-xl tracking-[0.3em] uppercase mb-16">Bali Soul</h1>
            <nav class="space-y-3 text-xs uppercase tracking-[0.2em]">
                <a href="admin.php?page=dashboard" class="sidebar-link <?= $page === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                <a href="admin.php?page=products" class="sidebar-link <?= in_array($page, ['products', 'add_product', 'edit_product', 'view_product']) ? 'active' : '' ?>">Products</a>
                <a href="admin.php?page=categories" class="sidebar-link <?= $page === 'categories' ? 'active' : '' ?>">Categories</a>
                <a href="admin.php?page=orders" class="sidebar-link <?= $page === 'orders' ? 'active' : '' ?>">Orders</a>
                <div class="pt-10 mt-10 border-t border-baliDark/10 space-y-3">
                    <a href="index.php" class="sidebar-link text-gray-500 hover:text-baliDark">View Website</a>
                    <a href="admin.php?action=logout" class="sidebar-link text-red-800 hover:bg-red-50">Logout</a>
                </div>
            </nav>
        </aside>

        <main class="flex-1 ml-64 p-12 lg:p-16">
            <?php if ($page === 'dashboard'): ?>
                <div class="mb-12">
                    <h2 class="font-display text-3xl tracking-[0.2em] uppercase">Dashboard</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white/80 p-8 rounded-3xl border border-baliDark/10 shadow-sm">
                        <p class="text-[10px] uppercase tracking-[0.3em] text-baliWood mb-2">Total Revenue</p>
                        <p class="font-display text-3xl tracking-widest">IDR <?= number_format($revenue, 0, ',', '.'); ?></p>
                    </div>
                    <div class="bg-white/80 p-8 rounded-3xl border border-baliDark/10 shadow-sm">
                        <p class="text-[10px] uppercase tracking-[0.3em] text-baliWood mb-2">Total Orders</p>
                        <p class="font-display text-3xl tracking-widest"><?= $totalOrders; ?></p>
                    </div>
                    <div class="bg-white/80 p-8 rounded-3xl border border-baliDark/10 shadow-sm">
                        <p class="text-[10px] uppercase tracking-[0.3em] text-baliWood mb-2">Active Products</p>
                        <p class="font-display text-3xl tracking-widest"><?= $totalProducts; ?></p>
                    </div>
                </div>

            <?php elseif ($page === 'products'): ?>
                <div class="flex justify-between items-end mb-10">
                    <div>
                        <h2 class="font-display text-3xl tracking-[0.2em] uppercase">Products</h2>
                    </div>
                    <a href="admin.php?page=add_product" class="bg-baliDark text-white px-8 py-3 text-xs uppercase tracking-[0.2em] hover:bg-baliWood rounded-xl">Add New Product</a>
                </div>
                <div class="bg-white/80 backdrop-blur rounded-3xl shadow-sm border border-baliDark/10 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-baliWood/5 text-[10px] uppercase tracking-[0.2em] text-baliWood border-b border-baliDark/10">
                                <th class="p-6">Image</th>
                                <th class="p-6">Product</th>
                                <th class="p-6">Price</th>
                                <th class="p-6">Stock</th>
                                <th class="p-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php foreach ($products as $prod):
                                $img = !empty($prod['image']) ? $prod['image'] : 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?q=80&w=100&auto=format&fit=crop';
                            ?>
                                <tr class="border-b border-baliDark/5 hover:bg-white transition-colors">
                                    <td class="p-6"><img src="<?= $img ?>" class="w-12 h-12 object-cover rounded-md"></td>
                                    <td class="p-6 uppercase tracking-widest text-xs font-medium"><?= $prod['Name'] ?></td>
                                    <td class="p-6 tracking-widest"><?= number_format($prod['Price'], 0, ',', '.') ?></td>
                                    <td class="p-6 text-gray-500"><?= $prod['Stock'] ?> units</td>
                                    <td class="p-6 text-right space-x-3 text-[10px] uppercase tracking-widest">
                                        <a href="admin.php?page=view_product&id=<?= $prod['Id'] ?>" class="text-gray-500 hover:text-baliDark">View</a>
                                        <a href="admin.php?page=edit_product&id=<?= $prod['Id'] ?>" class="text-baliWood hover:text-baliDark">Edit</a>
                                        <a href="admin.php?delete_product=<?= $prod['Id'] ?>" class="text-red-600" onclick="return confirm('Delete permanently?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($page === 'view_product' && isset($_GET['id'])): ?>
                <?php
                $stmt = $pdo->prepare("SELECT p.*, c.Name as Cat FROM products p LEFT JOIN categories c ON p.CategoryId = c.Id WHERE p.Id = ?");
                $stmt->execute([$_GET['id']]);
                $v = $stmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <a href="admin.php?page=products" class="text-xs uppercase tracking-widest text-gray-500 mb-8 inline-block">&larr; Back</a>
                <div class="max-w-4xl bg-white/80 p-10 rounded-3xl border border-baliDark/10 flex flex-col md:flex-row gap-10">
                    <img src="<?= $v['image'] ?? 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?q=80&w=500' ?>" class="w-full md:w-1/3 aspect-[4/5] object-cover rounded-2xl">
                    <div class="flex-1 flex flex-col justify-center space-y-6">
                        <p class="text-[10px] uppercase tracking-[0.3em] text-baliWood"><?= $v['Cat'] ?></p>
                        <h2 class="font-display text-4xl uppercase"><?= $v['Name'] ?></h2>
                        <div class="border-t border-b border-baliDark/10 py-6 space-y-4">
                            <p class="text-xl font-display tracking-widest">IDR <?= number_format($v['Price'], 0, ',', '.') ?></p>
                            <p class="text-sm tracking-widest text-gray-600">Stock: <?= $v['Stock'] ?> | Material: <?= $v['Material'] ?></p>
                        </div>
                        <a href="admin.php?page=edit_product&id=<?= $v['Id'] ?>" class="w-fit bg-baliWood text-white px-8 py-3 rounded-xl text-xs uppercase tracking-widest">Edit Details</a>
                    </div>
                </div>

            <?php elseif ($page === 'edit_product' && isset($_GET['id'])): ?>
                <?php
                $stmt = $pdo->prepare("SELECT * FROM products WHERE Id = ?");
                $stmt->execute([$_GET['id']]);
                $e = $stmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <h2 class="font-display text-2xl uppercase mb-8">Edit Product</h2>
                <form method="POST" enctype="multipart/form-data" class="max-w-2xl bg-white/80 p-10 rounded-3xl border border-baliDark/10 space-y-8">
                    <input type="hidden" name="product_id" value="<?= $e['Id'] ?>">
                    <select name="category_id" required class="w-full px-4 py-3 rounded-xl border border-baliWood/20 bg-transparent text-sm">
                        <?php foreach ($categoriesTree as $p): ?>
                            <option value="<?= $p['Id'] ?>" <?= $p['Id'] == $e['CategoryId'] ? 'selected' : '' ?>><?= $p['Name'] ?></option>
                            <?php foreach ($p['SubCategories'] as $s): ?>
                                <option value="<?= $s['Id'] ?>" <?= $s['Id'] == $e['CategoryId'] ? 'selected' : '' ?>>-- <?= $s['Name'] ?></option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="name" value="<?= $e['Name'] ?>" required class="w-full px-4 py-3 rounded-xl border border-baliWood/20 bg-transparent">
                    <div class="grid grid-cols-2 gap-6">
                        <input type="number" name="price" value="<?= $e['Price'] ?>" required class="w-full px-4 py-3 rounded-xl border border-baliWood/20 bg-transparent">
                        <input type="number" name="stock" value="<?= $e['Stock'] ?>" required class="w-full px-4 py-3 rounded-xl border border-baliWood/20 bg-transparent">
                    </div>
                    <input type="text" name="material" value="<?= $e['Material'] ?>" class="w-full px-4 py-3 rounded-xl border border-baliWood/20 bg-transparent">
                    <input type="file" name="product_image" class="w-full text-xs file:bg-baliWood/10 file:border-0 file:py-2 file:px-4 file:rounded-xl">
                    <button type="submit" name="edit_product_action" class="bg-baliDark text-white px-10 py-4 rounded-xl uppercase tracking-widest hover:bg-baliWood transition-colors">Update Product</button>
                </form>

            <?php elseif ($page === 'add_product'): ?>
                <h2 class="font-display text-2xl uppercase mb-8">Add Product</h2>
                <form method="POST" enctype="multipart/form-data" class="max-w-2xl bg-white/80 p-10 rounded-3xl border border-baliDark/10 space-y-8">
                    <select name="category_id" required class="w-full px-4 py-3 rounded-xl border border-baliWood/20 bg-transparent text-sm">
                        <option value="">Select Category</option>
                        <?php foreach ($categoriesTree as $p): ?>
                            <option value="<?= $p['Id'] ?>"><?= $p['Name'] ?></option>
                            <?php foreach ($p['SubCategories'] as $s): ?><option value="<?= $s['Id'] ?>">-- <?= $s['Name'] ?></option><?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="name" required placeholder="Name" class="w-full px-4 py-3 rounded-xl border border-baliWood/20 bg-transparent">
                    <div class="grid grid-cols-2 gap-6">
                        <input type="number" name="price" required placeholder="Price" class="w-full px-4 py-3 rounded-xl border border-baliWood/20 bg-transparent">
                        <input type="number" name="stock" required placeholder="Stock" class="w-full px-4 py-3 rounded-xl border border-baliWood/20 bg-transparent">
                    </div>
                    <input type="text" name="material" placeholder="Material" class="w-full px-4 py-3 rounded-xl border border-baliWood/20 bg-transparent">
                    <input type="file" name="product_image" class="w-full text-xs file:bg-baliWood/10 file:border-0 file:py-2 file:px-4 file:rounded-xl">
                    <button type="submit" name="submit_product" class="bg-baliDark text-white px-10 py-4 rounded-xl uppercase tracking-widest hover:bg-baliWood transition-colors">Save Product</button>
                </form>

            <?php elseif ($page === 'categories'): ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    <div class="bg-white/80 p-10 rounded-3xl border border-baliDark/10 h-fit">
                        <h3 class="text-xs uppercase tracking-widest text-baliWood mb-6">New Category</h3>
                        <form action="admin.php" method="POST" class="space-y-6">
                            <input type="text" name="cat_name" required placeholder="Name" class="w-full px-4 py-3 rounded-xl border border-baliWood/20 bg-transparent">
                            <select name="parent_id" class="w-full px-4 py-3 rounded-xl border border-baliWood/20 bg-transparent">
                                <option value="0">Main Category</option>
                                <?php foreach ($categoriesTree as $p): ?><option value="<?= $p['Id'] ?>"><?= $p['Name'] ?></option><?php endforeach; ?>
                            </select>
                            <button type="submit" name="submit_category" class="w-full bg-baliDark text-white py-3 rounded-xl uppercase tracking-widest text-xs">Add</button>
                        </form>
                    </div>
                    <div class="bg-white/80 p-10 rounded-3xl border border-baliDark/10">
                        <h3 class="text-xs uppercase tracking-widest text-baliWood mb-6">List</h3>
                        <ul class="space-y-6">
                            <?php foreach ($categoriesTree as $p): ?>
                                <li class="pb-6 border-b border-baliDark/10 last:border-0">
                                    <div class="flex justify-between items-center mb-4"><span class="text-sm uppercase tracking-widest font-medium"><?= $p['Name'] ?></span><a href="admin.php?delete_category=<?= $p['Id'] ?>" class="text-[9px] text-red-500 uppercase border border-red-100 px-2 py-1">Del</a></div>
                                    <?php foreach ($p['SubCategories'] as $s): ?>
                                        <div class="bg-baliBg p-3 rounded-lg flex justify-between items-center mb-2 ml-4"><span class="text-xs text-gray-500"><?= $s['Name'] ?></span><a href="admin.php?delete_category=<?= $s['Id'] ?>" class="text-[9px] text-red-400">Del</a></div>
                                    <?php endforeach; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

            <?php elseif ($page === 'orders'): ?>
                <div class="space-y-6">
                    <?php foreach ($allOrders as $o): ?>
                        <div class="bg-white/80 p-8 rounded-3xl border border-baliDark/10 flex flex-col md:flex-row justify-between items-center gap-6">
                            <div class="flex-1 text-left">
                                <p class="text-[10px] uppercase tracking-widest text-baliWood mb-2"><?= $o['id'] ?></p>
                                <h3 class="font-display text-xl mb-1 uppercase"><?= $o['fullname'] ?></h3>
                                <p class="text-[10px] text-gray-400 uppercase tracking-widest"><?= date('d M Y', strtotime($o['created_at'])) ?> • VIA <?= $o['payment_method'] ?></p>
                                <?php if ($o['payment_proof']): ?><a href="<?= $o['payment_proof'] ?>" target="_blank" class="inline-block mt-3 text-[9px] uppercase tracking-widest border-b border-baliWood text-baliWood">Proof of Payment</a><?php endif; ?>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-display tracking-widest mb-4">IDR <?= number_format($o['total'], 0, ',', '.') ?></p>
                                <form action="admin.php" method="POST" class="flex gap-2">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <select name="status" class="px-3 py-2 rounded-lg text-[9px] uppercase border border-baliDark/10 bg-transparent">
                                        <?php foreach (['Awaiting Payment', 'Awaiting Verification', 'Processing Order', 'Shipped', 'Completed', 'Cancelled'] as $st): ?>
                                            <option value="<?= $st ?>" <?= $o['status'] == $st ? 'selected' : '' ?>><?= $st ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="update_order" class="bg-baliDark text-white px-4 py-2 rounded-lg text-[9px] uppercase hover:bg-baliWood transition-colors">Update</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    <?php endif; ?>
</body>

</html>