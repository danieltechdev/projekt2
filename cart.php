<?php
session_start();

// Inicjalizacja koszyka, jeśli nie istnieje
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Obsługa akcji przesłanych do koszyka
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $action = $_POST['action'];

    switch ($action) {
        case 'add_one':
            $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + 1;
            break;
        case 'remove_one':
            if (isset($_SESSION['cart'][$product_id]) && $_SESSION['cart'][$product_id] > 1) {
                $_SESSION['cart'][$product_id]--;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
            break;
        case 'remove_all':
            unset($_SESSION['cart'][$product_id]);
            break;
        case 'update_quantity':
            $new_quantity = $_POST['quantity'] ?? 0;
            if ($new_quantity > 0) {
                $_SESSION['cart'][$product_id] = $new_quantity;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
            break;
    }

    header('Location: cart.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koszyk</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h2>Zawartość Twojego koszyka</h2>
    <?php if (empty($_SESSION['cart'])): ?>
        <p>Koszyk jest pusty</p>
    <?php else: ?>
        <?php foreach ($_SESSION['cart'] as $product_id => $quantity): ?>
            <div>
                <!-- Załóżmy, że tutaj jest wyświetlanie nazwy i innych informacji o produkcie na podstawie $product_id -->
                Produkt ID: <?php echo $product_id; ?>, Ilość: <?php echo $quantity; ?><br>
                <form action="cart.php" method="post">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    <input type="hidden" name="action" value="add_one">
                    <input type="submit" value="+">
                </form>
                <form action="cart.php" method="post">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    <input type="hidden" name="action" value="remove_one">
                    <input type="submit" value="-">
                </form>
                <form action="cart.php" method="post" style="display: inline;">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    <input type="hidden" name="action" value="remove_all">
                    <input type="submit" value="Kosz">
                </form>
                <form action="cart.php" method="post" style="display: inline;">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    <input type="hidden" name="action" value="update_quantity">
                    <input type="number" name="quantity" value="<?php echo $quantity; ?>" min="1" style="width: 50px;">
                    <input type="submit" value="Aktualizuj">
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>