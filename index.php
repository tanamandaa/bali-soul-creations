<?php
session_start();

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

require_once __DIR__ . '/app/Config/Database.php';
require_once __DIR__ . '/app/Models/Product.php';
require_once __DIR__ . '/app/Controllers/ProductController.php';

use App\Controllers\ProductController;

$controller = new ProductController();
$categoriesTree = $controller->getCategoriesTree();

$stmtProd = $pdo->query("SELECT * FROM products ORDER BY Id DESC");
$products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

function getProductById($products, $id)
{
    foreach ($products as $p) {
        if ($p['Id'] == $id) return $p;
    }
    return null;
}

if (isset($_GET['add_to_cart'])) {
    $id = (int)$_GET['add_to_cart'];
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
    header("Location: index.php?page=cart");
    exit;
}
if (isset($_GET['increase_qty'])) {
    $id = (int)$_GET['increase_qty'];
    if (isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id]++;
    header("Location: index.php?page=cart");
    exit;
}
if (isset($_GET['decrease_qty'])) {
    $id = (int)$_GET['decrease_qty'];
    if (isset($_SESSION['cart'][$id])) {
        if ($_SESSION['cart'][$id] > 1) {
            $_SESSION['cart'][$id]--;
        } else {
            unset($_SESSION['cart'][$id]);
        }
    }
    header("Location: index.php?page=cart");
    exit;
}
if (isset($_GET['remove_from_cart'])) {
    $id = (int)$_GET['remove_from_cart'];
    unset($_SESSION['cart'][$id]);
    header("Location: index.php?page=cart");
    exit;
}

if (isset($_POST['register_action'])) {
    $email = $_POST['email'];
    $name = $_POST['fullname'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header("Location: index.php?page=register&error=exists");
        exit;
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $pass]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['logged_in_user'] = $name;
        header("Location: index.php");
        exit;
    }
}

if (isset($_POST['login_action'])) {
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $stmt = $pdo->prepare("SELECT id, fullname, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['logged_in_user'] = $user['fullname'];
        header("Location: index.php");
        exit;
    } else {
        header("Location: index.php?page=login&error=invalid");
        exit;
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['user_id']);
    unset($_SESSION['logged_in_user']);
    header("Location: index.php");
    exit;
}

if (isset($_POST['process_checkout']) && isset($_SESSION['user_id'])) {
    $orderId = "BSC-" . strtoupper(substr(uniqid(), -5));
    $paymentMethod = $_POST['payment_method'];
    $subtotal = 0;
    foreach ($_SESSION['cart'] as $id => $qty) {
        $p = getProductById($products, $id);
        if ($p) $subtotal += ($p['Price'] * $qty);
    }
    $tax = $subtotal * 0.11;
    $shipping = 50000;
    $grandTotal = $subtotal + $tax + $shipping;
    $stmt = $pdo->prepare("INSERT INTO orders (id, user_id, fullname, phone, address, payment_method, subtotal, tax, shipping, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Awaiting Payment')");
    $stmt->execute([$orderId, $_SESSION['user_id'], $_POST['fullname'], $_POST['phone'], $_POST['address'], $paymentMethod, $subtotal, $tax, $shipping, $grandTotal]);
    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
    foreach ($_SESSION['cart'] as $id => $qty) {
        $p = getProductById($products, $id);
        if ($p) $stmtItem->execute([$orderId, $id, $qty, $p['Price']]);
    }
    $_SESSION['cart'] = [];
    header("Location: index.php?page=payment&order_id=" . $orderId);
    exit;
}

if (isset($_POST['submit_proof'])) {
    $orderId = $_POST['order_id'];
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/payments/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . time() . "_" . basename($_FILES["payment_proof"]["name"]);
        if (move_uploaded_file($_FILES["payment_proof"]["tmp_name"], $target_file)) {
            $stmt = $pdo->prepare("UPDATE orders SET status = 'Awaiting Verification', payment_proof = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$target_file, $orderId, $_SESSION['user_id']]);
        }
    }
    header("Location: index.php?page=status");
    exit;
}

if (isset($_GET['export_katalog'])) {
    $filename = "BSC_Archive.txt";
    $file = fopen($filename, "w");
    fwrite($file, "BALI SOUL CREATIONS - ARCHIVE\n\n");
    foreach ($products as $p) {
        fwrite($file, $p['Name'] . " | IDR " . number_format($p['Price'], 0, ',', '.') . "\n");
    }
    fclose($file);
    echo "<script>alert('Archive Exported'); window.location.href='index.php';</script>";
}

$page = $_GET['page'] ?? 'catalog';
$cartCount = array_sum($_SESSION['cart']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bali Soul Creations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400&family=Playfair+Display&display=swap" rel="stylesheet">
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

        .nav-link {
            font-size: 11px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: #8B5E3C;
        }

        input[type="radio"]:checked+div {
            border-color: #8B5E3C;
            background-color: #8B5E3C10;
        }

        input[type="radio"]:checked+div span {
            color: #8B5E3C;
        }
    </style>
</head>

<body class="text-baliDark font-sans flex flex-col min-h-screen">

    <nav class="w-full h-28 flex items-center justify-between px-8 lg:px-20 bg-baliBg/90 backdrop-blur sticky top-0 z-50 border-b border-baliDark/10">

        <div class="flex-1 flex justify-start">
            <a href="index.php" class="flex items-center gap-4 group">
                <img src="bali-soul-creations-logo.png" alt="Bali Soul Logo" class="h-20 w-auto object-contain transition-transform group-hover:scale-105 py-1">
                <span class="font-display text-xl tracking-[0.3em] uppercase text-baliDark group-hover:text-baliWood transition-colors hidden lg:block">
                    Bali Soul
                </span>
            </a>
        </div>

        <div class="hidden md:flex flex-1 justify-center space-x-10 items-center">
            <a href="index.php" class="nav-link">Catalog</a>
            <a href="index.php?page=cart" class="nav-link flex items-center gap-2">
                Cart <span class="bg-baliWood text-white px-2 py-0.5 rounded-full text-[10px]"><?= $cartCount; ?></span>
            </a>
            <a href="index.php?page=status" class="nav-link">Orders</a>
        </div>

        <div class="flex flex-1 justify-end items-center space-x-8">
            <?php if (isset($_SESSION['logged_in_user'])): ?>
                <span class="nav-link text-baliWood hidden md:block">Hi, <?= htmlspecialchars($_SESSION['logged_in_user']); ?></span>
                <a href="index.php?logout=true" class="nav-link">Logout</a>
            <?php else: ?>
                <a href="index.php?page=login" class="nav-link">Login</a>
                <a href="index.php?page=register" class="nav-link hidden md:block">Register</a>
            <?php endif; ?>
        </div>

    </nav>

    <?php if ($page === 'catalog'): ?>
        <section class="w-full h-[70vh] flex items-center justify-center bg-cover bg-center relative" style="background-image:url('https://images.unsplash.com/photo-1618220179428-22790b461013?q=80&w=1600&auto=format&fit=crop')">
            <div class="absolute inset-0 bg-black/20"></div>
            <div class="text-center relative z-10 text-white">
                <h1 class="font-display text-4xl md:text-6xl tracking-[0.2em] uppercase mb-6">Bali Soul Creations</h1>
                <p class="text-sm tracking-[0.4em] uppercase text-baliBg">Handmade Balinese Craft</p>
            </div>
        </section>

        <div class="flex max-w-[1800px] mx-auto w-full">
            <aside class="w-72 hidden lg:block p-16 border-r border-baliDark/10">
                <h2 class="text-xs tracking-[0.4em] uppercase mb-12 text-baliWood">Collections</h2>
                <ul class="space-y-8">
                    <?php foreach ($categoriesTree as $parent): ?>
                        <li>
                            <div class="text-xs uppercase tracking-[0.2em] mb-3"><?= htmlspecialchars($parent['Name']); ?></div>
                            <?php if (!empty($parent['SubCategories'])): ?>
                                <ul class="space-y-2 ml-3">
                                    <?php foreach ($parent['SubCategories'] as $child): ?>
                                        <li class="text-xs text-gray-500 hover:text-baliWood cursor-pointer"><?= htmlspecialchars($child['Name']); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="mt-16"><a href="index.php?export_katalog=true" class="text-xs uppercase tracking-[0.2em] border-b border-baliWood pb-1 text-baliWood">Export Archive</a></div>
            </aside>

            <main class="flex-1 p-10 lg:p-20">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-16">
                    <?php if (empty($products)): ?>
                        <p class="text-sm tracking-widest text-gray-500 uppercase">No Products</p>
                    <?php else: ?>
                        <?php foreach ($products as $prod):
                            $imgSrc = !empty($prod['image']) ? htmlspecialchars($prod['image']) : 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?q=80&w=800&auto=format&fit=crop';
                        ?>
                            <div class="group flex flex-col">
                                <a href="index.php?page=detail&id=<?= $prod['Id']; ?>" class="aspect-[3/4] overflow-hidden mb-5 block relative">
                                    <img src="<?= $imgSrc ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-105">
                                </a>
                                <div class="flex justify-between items-start gap-4">
                                    <a href="index.php?page=detail&id=<?= $prod['Id']; ?>" class="block flex-1">
                                        <h3 class="text-sm uppercase tracking-[0.2em] hover:text-baliWood transition-colors"><?= htmlspecialchars($prod['Name']); ?></h3>
                                        <p class="text-xs text-baliWood tracking-[0.15em] mt-1">IDR <?= number_format($prod['Price'], 0, ',', '.'); ?></p>
                                    </a>
                                    <?php if ($prod['Stock'] > 0): ?>
                                        <a href="index.php?add_to_cart=<?= $prod['Id']; ?>" class="p-2.5 border border-baliDark/20 text-baliDark hover:bg-baliWood hover:text-white hover:border-baliWood transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                            </svg>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-[9px] uppercase tracking-widest text-red-700 border border-red-700/20 px-2 py-1 mt-1">Sold</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>

    <?php elseif ($page === 'login'): ?>
        <div class="flex-1 flex items-center justify-center p-10">
            <div class="w-full max-w-md bg-white/50 p-12 border border-baliDark/10">
                <h1 class="font-display text-3xl tracking-[0.2em] uppercase mb-8 text-center">Login</h1>
                <?php if (isset($_GET['error']) && $_GET['error'] == 'invalid'): ?>
                    <p class="text-[10px] uppercase tracking-widest text-red-600 text-center mb-6 bg-red-50 p-3 border border-red-200">Invalid email or password!</p>
                <?php endif; ?>
                <form action="index.php" method="POST" class="space-y-8">
                    <div><label class="block text-[10px] uppercase tracking-[0.3em] text-baliWood mb-3">Email Address</label><input type="email" name="email" required class="w-full bg-transparent border-b border-baliDark/20 px-0 py-2 text-sm focus:outline-none focus:border-baliWood transition-colors"></div>
                    <div><label class="block text-[10px] uppercase tracking-[0.3em] text-baliWood mb-3">Password</label><input type="password" name="password" required class="w-full bg-transparent border-b border-baliDark/20 px-0 py-2 text-sm focus:outline-none focus:border-baliWood transition-colors"></div>
                    <button type="submit" name="login_action" class="w-full bg-baliDark text-baliBg px-8 py-4 text-xs uppercase tracking-[0.25em] hover:bg-baliWood transition-colors mt-4">Sign In</button>
                </form>
                <div class="mt-8 text-center"><a href="index.php?page=register" class="text-[10px] uppercase tracking-[0.2em] text-gray-500 hover:text-baliWood">Create an Account</a></div>
            </div>
        </div>

    <?php elseif ($page === 'register'): ?>
        <div class="flex-1 flex items-center justify-center p-10 py-20">
            <div class="w-full max-w-md bg-white/50 p-12 border border-baliDark/10">
                <h1 class="font-display text-3xl tracking-[0.2em] uppercase mb-8 text-center">Register</h1>
                <?php if (isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
                    <p class="text-[10px] uppercase tracking-widest text-red-600 text-center mb-6 bg-red-50 p-3 border border-red-200">Email is already registered!</p>
                <?php endif; ?>
                <form action="index.php" method="POST" class="space-y-8">
                    <div><label class="block text-[10px] uppercase tracking-[0.3em] text-baliWood mb-3">Full Name</label><input type="text" name="fullname" required class="w-full bg-transparent border-b border-baliDark/20 px-0 py-2 text-sm focus:outline-none focus:border-baliWood transition-colors"></div>
                    <div><label class="block text-[10px] uppercase tracking-[0.3em] text-baliWood mb-3">Email Address</label><input type="email" name="email" required class="w-full bg-transparent border-b border-baliDark/20 px-0 py-2 text-sm focus:outline-none focus:border-baliWood transition-colors"></div>
                    <div><label class="block text-[10px] uppercase tracking-[0.3em] text-baliWood mb-3">Password</label><input type="password" name="password" required class="w-full bg-transparent border-b border-baliDark/20 px-0 py-2 text-sm focus:outline-none focus:border-baliWood transition-colors"></div>
                    <button type="submit" name="register_action" class="w-full bg-baliDark text-baliBg px-8 py-4 text-xs uppercase tracking-[0.25em] hover:bg-baliWood transition-colors mt-4">Create Account</button>
                </form>
                <div class="mt-8 text-center"><a href="index.php?page=login" class="text-[10px] uppercase tracking-[0.2em] text-gray-500 hover:text-baliWood">Already have an account? Login</a></div>
            </div>
        </div>

    <?php elseif ($page === 'detail' && isset($_GET['id'])): ?>
        <?php
        $product = getProductById($products, $_GET['id']);
        $imgSrc = !empty($product['image']) ? htmlspecialchars($product['image']) : 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?q=80&w=800&auto=format&fit=crop';
        ?>
        <?php if ($product): ?>
            <div class="max-w-6xl mx-auto px-10 py-24 flex flex-col md:flex-row gap-16 flex-1 w-full">
                <div class="w-full md:w-1/2 aspect-[4/5] overflow-hidden"><img src="<?= $imgSrc ?>" class="w-full h-full object-cover"></div>
                <div class="w-full md:w-1/2 flex flex-col justify-center">
                    <h1 class="font-display text-3xl md:text-5xl tracking-[0.1em] uppercase mb-6"><?= htmlspecialchars($product['Name']); ?></h1>
                    <p class="text-xl tracking-widest text-baliWood mb-8">IDR <?= number_format($product['Price'], 0, ',', '.'); ?></p>
                    <p class="text-sm leading-relaxed text-gray-600 mb-10 max-w-md">Material: <?= htmlspecialchars($product['Material'] ?? 'Authentic'); ?><br><br>Current Stock: <?= $product['Stock']; ?> units.</p>
                    <?php if ($product['Stock'] > 0): ?>
                        <a href="index.php?add_to_cart=<?= $product['Id']; ?>" class="inline-block text-center bg-baliDark text-baliBg hover:bg-baliWood transition-colors px-12 py-4 text-xs uppercase tracking-[0.25em] w-fit">Add to Cart</a>
                    <?php else: ?>
                        <span class="text-xs uppercase tracking-widest text-red-700">Out of Stock</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($page === 'cart'): ?>
        <div class="max-w-4xl mx-auto px-10 py-24 flex-1 w-full">
            <h1 class="font-display text-3xl tracking-[0.2em] uppercase mb-12 border-b border-baliDark/10 pb-6">Your Cart</h1>
            <?php if (empty($_SESSION['cart'])): ?>
                <p class="text-sm tracking-widest uppercase text-gray-500 mb-10">Your cart is currently empty.</p>
                <a href="index.php" class="text-xs uppercase tracking-[0.2em] border-b border-baliWood text-baliWood pb-1">Return to Catalog</a>
            <?php else: ?>
                <div class="space-y-8 mb-16">
                    <?php $total = 0;
                    foreach ($_SESSION['cart'] as $id => $qty): ?>
                        <?php
                        $p = getProductById($products, $id);
                        if ($p):
                            $subtotal = $p['Price'] * $qty;
                            $total += $subtotal;
                            $imgSrc = !empty($p['image']) ? htmlspecialchars($p['image']) : 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?q=80&w=200&auto=format&fit=crop';
                        ?>
                            <div class="flex flex-col md:flex-row items-center justify-between border-b border-baliDark/10 pb-8 gap-6">
                                <div class="flex items-center gap-8 w-full md:w-auto">
                                    <div class="w-24 h-32 overflow-hidden shrink-0"><img src="<?= $imgSrc ?>" class="w-full h-full object-cover"></div>
                                    <div>
                                        <h3 class="text-sm uppercase tracking-[0.2em] mb-3"><?= htmlspecialchars($p['Name']); ?></h3>
                                        <div class="flex items-center gap-4">
                                            <a href="index.php?decrease_qty=<?= $id; ?>" class="w-6 h-6 border border-baliDark/20 flex items-center justify-center hover:bg-baliDark hover:text-white transition-colors text-xs">-</a>
                                            <span class="text-xs tracking-widest text-gray-500 w-4 text-center"><?= $qty; ?></span>
                                            <a href="index.php?increase_qty=<?= $id; ?>" class="w-6 h-6 border border-baliDark/20 flex items-center justify-center hover:bg-baliDark hover:text-white transition-colors text-xs">+</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-left w-full md:w-auto md:text-right">
                                    <p class="text-sm tracking-widest text-baliWood mb-2">IDR <?= number_format($subtotal, 0, ',', '.'); ?></p>
                                    <a href="index.php?remove_from_cart=<?= $id; ?>" class="text-[10px] uppercase tracking-[0.2em] text-gray-400 hover:text-red-700 transition-all">Remove</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <div class="flex flex-col items-end">
                    <p class="text-xs tracking-[0.3em] uppercase text-gray-500 mb-2">Cart Subtotal</p>
                    <p class="text-3xl font-display tracking-widest mb-10">IDR <?= number_format($total, 0, ',', '.'); ?></p>
                    <?php if (isset($_SESSION['logged_in_user'])): ?>
                        <a href="index.php?page=checkout" class="bg-baliDark text-baliBg px-12 py-5 text-xs uppercase tracking-[0.25em] hover:bg-baliWood transition-colors">Proceed to Checkout</a>
                    <?php else: ?>
                        <a href="index.php?page=login" class="bg-baliWood text-white px-12 py-5 text-xs uppercase tracking-[0.25em] hover:bg-baliDark transition-colors">Login to Checkout</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($page === 'checkout'): ?>
        <?php
        if (!isset($_SESSION['logged_in_user'])) {
            header("Location: index.php?page=login");
            exit;
        }
        $subtotal = 0;
        foreach ($_SESSION['cart'] as $id => $qty) {
            $p = getProductById($products, $id);
            if ($p) $subtotal += ($p['Price'] * $qty);
        }
        $tax = $subtotal * 0.11;
        $shipping = 50000;
        $baseTotal = $subtotal + $tax + $shipping;
        ?>
        <div class="max-w-4xl mx-auto px-10 py-24 flex-1 w-full flex flex-col lg:flex-row gap-16">
            <div class="w-full lg:w-3/5">
                <h1 class="font-display text-3xl tracking-[0.2em] uppercase mb-12 border-b border-baliDark/10 pb-6">Checkout</h1>
                <form action="index.php" method="POST" class="space-y-10">
                    <div class="space-y-6">
                        <h2 class="text-xs uppercase tracking-[0.3em] text-baliWood mb-6">Shipping Details</h2>
                        <div><input type="text" name="fullname" value="<?= htmlspecialchars($_SESSION['logged_in_user']); ?>" required class="w-full bg-transparent border-b border-baliDark/20 px-0 py-3 text-sm focus:outline-none focus:border-baliWood transition-colors" placeholder="Full Name"></div>
                        <div><textarea name="address" required rows="3" class="w-full bg-transparent border-b border-baliDark/20 px-0 py-3 text-sm focus:outline-none focus:border-baliWood transition-colors resize-none" placeholder="Complete Address"></textarea></div>
                        <div><input type="text" name="phone" required class="w-full bg-transparent border-b border-baliDark/20 px-0 py-3 text-sm focus:outline-none focus:border-baliWood transition-colors" placeholder="Phone Number"></div>
                    </div>
                    <div class="pt-8">
                        <h2 class="text-xs uppercase tracking-[0.3em] text-baliWood mb-6">Payment Method</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="relative cursor-pointer"><input type="radio" name="payment_method" value="Bank Transfer" checked class="peer sr-only">
                                <div class="border border-baliDark/20 p-4 text-center transition-all peer-checked:border-baliWood peer-checked:bg-baliWood/5"><span class="text-[10px] uppercase tracking-[0.2em] text-gray-500 font-medium peer-checked:text-baliWood">Bank Transfer</span></div>
                            </label>
                            <label class="relative cursor-pointer"><input type="radio" name="payment_method" value="QRIS" class="peer sr-only">
                                <div class="border border-baliDark/20 p-4 text-center transition-all peer-checked:border-baliWood peer-checked:bg-baliWood/5"><span class="text-[10px] uppercase tracking-[0.2em] text-gray-500 font-medium peer-checked:text-baliWood">QRIS</span></div>
                            </label>
                        </div>
                    </div>
                    <input type="hidden" name="total_amount" value="<?= $baseTotal; ?>">
                    <button type="submit" name="process_checkout" class="w-full bg-baliWood text-white px-12 py-5 text-xs uppercase tracking-[0.25em] hover:bg-baliDark transition-colors mt-8">Place Order</button>
                </form>
            </div>
            <div class="w-full lg:w-2/5 bg-white/40 p-10 border border-baliDark/10 h-fit sticky top-32">
                <h2 class="text-xs uppercase tracking-[0.3em] text-baliWood mb-8">Order Summary</h2>
                <div class="space-y-4 mb-8 text-sm tracking-widest border-b border-baliDark/10 pb-8">
                    <div class="flex justify-between text-gray-600"><span>Subtotal</span> <span>IDR <?= number_format($subtotal, 0, ',', '.'); ?></span></div>
                    <div class="flex justify-between text-gray-600"><span>Tax (11%)</span> <span>IDR <?= number_format($tax, 0, ',', '.'); ?></span></div>
                    <div class="flex justify-between text-gray-600"><span>Shipping</span> <span>IDR <?= number_format($shipping, 0, ',', '.'); ?></span></div>
                </div>
                <div class="flex justify-between items-end"><span class="text-xs uppercase tracking-[0.3em] text-baliDark">Total</span><span class="font-display text-2xl tracking-widest text-baliDark">IDR <?= number_format($baseTotal, 0, ',', '.'); ?></span></div>
            </div>
        </div>

    <?php elseif ($page === 'payment' && isset($_GET['order_id'])): ?>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$_GET['order_id'], $_SESSION['user_id']]);
        $orderData = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        <?php if ($orderData): ?>
            <div class="max-w-xl mx-auto px-10 py-24 flex-1 w-full text-center">
                <h1 class="font-display text-3xl tracking-[0.2em] uppercase mb-4">Complete Payment</h1>
                <p class="text-sm tracking-widest text-gray-500 mb-8">Order Ref: <?= htmlspecialchars($orderData['id']); ?></p>
                <p class="font-display text-4xl tracking-widest mb-10 text-baliDark">IDR <?= number_format($orderData['total'], 0, ',', '.'); ?></p>
                <?php if ($orderData['payment_method'] === 'QRIS'): ?>
                    <div class="bg-white/50 p-8 border border-baliDark/10 mb-10 flex flex-col items-center">
                        <p class="text-[10px] uppercase tracking-[0.2em] text-baliWood mb-6">Scan QRIS to Pay</p>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= $orderData['id'] ?>-<?= $orderData['total'] ?>" class="w-48 h-48 border border-baliDark/10 p-2 bg-white">
                    </div>
                <?php else: ?>
                    <div class="bg-white/50 p-8 border border-baliDark/10 mb-10 text-left">
                        <p class="text-[10px] uppercase tracking-[0.2em] text-baliWood mb-4">Transfer Instructions</p>
                        <p class="text-xs tracking-widest text-gray-500">Bank BCA</p>
                        <p class="text-2xl tracking-widest text-baliDark mb-4 font-medium">8800 1234 5678</p>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500">A.N. Bali Soul Creations</p>
                    </div>
                <?php endif; ?>
                <form action="index.php" method="POST" enctype="multipart/form-data" class="space-y-6 text-left border-t border-baliDark/10 pt-10">
                    <input type="hidden" name="order_id" value="<?= $orderData['id']; ?>">
                    <label class="block text-[10px] uppercase tracking-[0.3em] text-baliWood mb-4 text-center">Upload Proof of Payment</label>
                    <input type="file" name="payment_proof" required accept="image/*" class="w-full text-xs text-gray-500 file:bg-baliWood/10 file:border-0 file:py-3 file:px-6 cursor-pointer border border-baliDark/20 p-2 rounded-xl">
                    <button type="submit" name="submit_proof" class="w-full bg-baliWood text-white py-5 text-xs uppercase tracking-[0.25em] hover:bg-baliDark">Confirm Payment</button>
                </form>
            </div>
        <?php endif; ?>

    <?php elseif ($page === 'status'): ?>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id'] ?? 0]);
        $ordersDB = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="max-w-4xl mx-auto px-10 py-24 flex-1 w-full">
            <h1 class="font-display text-3xl tracking-[0.2em] uppercase mb-12 border-b border-baliDark/10 pb-6">Order History</h1>
            <?php if (empty($ordersDB)): ?>
                <p class="text-sm tracking-widest uppercase text-gray-500">You have no active orders.</p>
            <?php else: ?>
                <div class="space-y-8">
                    <?php foreach ($ordersDB as $order): ?>
                        <div class="border border-baliDark/10 p-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 bg-white/40 backdrop-blur-sm">
                            <div class="flex-1">
                                <p class="text-[10px] uppercase tracking-[0.3em] text-baliWood mb-4">Ref: <?= $order['id']; ?></p>
                                <h3 class="font-display text-xl tracking-wide mb-3"><?= htmlspecialchars($order['fullname']); ?></h3>
                                <div class="flex gap-4 items-center text-[9px] uppercase tracking-[0.2em] text-gray-500">
                                    <span>Via <?= $order['payment_method']; ?></span>
                                    <span><?= date('d M Y, H:i', strtotime($order['created_at'])); ?></span>
                                </div>
                            </div>
                            <div class="text-left md:text-right">
                                <?php
                                $statusClass = $order['status'] === 'Awaiting Verification' ? 'border-baliWood text-baliWood' : ($order['status'] === 'Awaiting Payment' ? 'border-red-700 text-red-700' : 'bg-baliDark text-white');
                                ?>
                                <p class="text-[10px] uppercase tracking-[0.3em] border <?= $statusClass ?> px-4 py-2 inline-block mb-4"><?= $order['status']; ?></p>
                                <p class="text-lg font-display tracking-widest mb-3">IDR <?= number_format($order['total'], 0, ',', '.'); ?></p>
                                <?php if ($order['status'] === 'Awaiting Payment'): ?>
                                    <div class="mt-2"><a href="index.php?page=payment&order_id=<?= $order['id']; ?>" class="text-[10px] uppercase tracking-[0.2em] bg-baliWood text-white px-6 py-3 hover:bg-baliDark">Pay Now</a></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <footer class="border-t border-baliDark/10 py-10 px-10 text-[10px] tracking-[0.25em] uppercase flex flex-col md:flex-row justify-between items-center gap-6 mt-auto">
        <span class="text-gray-500">© 2026 Bali Soul Creations</span>
        <a href="admin.php" class="text-gray-400 hover:text-baliDark">Admin Panel</a>
    </footer>
</body>

</html>