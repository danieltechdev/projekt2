<?php
require 'db.php'; // Dołączenie pliku z połączeniem do bazy danych
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
        case 'buy':
            // Obsługa zakupu - aktualizacja bazy danych i koszyka
            $productIds = array_keys($_SESSION['cart']);
            $purchasedProducts = [];
            foreach ($productIds as $productId) {
                $quantityInCart = $_SESSION['cart'][$productId];
                $stmt = $conn->prepare("UPDATE products SET available_quantity = available_quantity - ? WHERE id = ?");
                $stmt->bind_param("ii", $quantityInCart, $productId);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $purchasedProducts[] = [
                        'name' => $row['name'],
                        'quantity' => $quantityInCart,
                        'price' => $row['price'],
                    ];
                }
                $stmt->close();
            }
            $_SESSION['cart'] = []; // Wyczyszczenie koszyka po zakupie

            // Wyświetlenie informacji o zakupie i listy zakupionych produktów
            $purchaseMessage = "Gratulacje! Twój zakup został zrealizowany.\n\n";
            $purchaseMessage .= "<h2>Lista zakupionych produktów:</h2>";
            $purchaseMessage .= "<ul>";
            foreach ($purchasedProducts as $product) {
                $purchaseMessage .= "<li>{$product['name']} (Ilość: {$product['quantity']}, Cena: {$product['price']} zł)</li>";
            }
            $purchaseMessage .= "</ul>";
           // echo "<div class='purchase-message'>$purchaseMessage</div>";
            break;
    }
}

// Pobranie produktów z koszyka
$cartProducts = [];
$totalCost = 0;
if (!empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id IN (" . implode(",", $productIds) . ")");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $quantity = $_SESSION['cart'][$row['id']];
        $cost = $quantity * $row['price'];
        $totalCost += $cost;

        $cartProducts[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'quantity' => $quantity,
            'price' => $row['price'],
            'cost' => $cost,
        ];
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koszyk</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h2>Zawartość Twojego koszyka</h2>
    <?php if (empty($cartProducts)): ?>
        <p>Koszyk jest pusty</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nazwa Produktu</th>
                    <th>Ilość</th>
                    <th>Akcje</th>
                    <th>Koszt jednego</th>
                    <th>Koszt łącznie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartProducts as $product): ?>
                    <tr>
                        <td><?php echo $product['name']; ?></td>
                        <td>
                            <form action="cart.php" method="post">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="action" value="update_quantity">
                                <input type="number" name="quantity" value="<?php echo $product['quantity']; ?>" min="1" style="width: 50px;">
                                <input type="submit" value="Aktualizuj">
                            </form>
                        </td>
                        <td>
                            <form action="cart.php" method="post" style="display: inline;">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="action" value="add_one">
                                <input type="submit" value="+">
                            </form>
                            <form action="cart.php" method="post" style="display: inline;">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="action" value="remove_one">
                                <input type="submit" value="-">
                            </form>
                            <form action="cart.php" method="post" style="display: inline;">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="action" value="remove_all">
                                <input type="submit" value="Usuń">
                            </form>
                        </td>
                        <td><?php echo $product['price']; ?> zł</td>
                        <td><?php echo $product['cost']; ?> zł</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3"></td>
                    <td>Podsumowanie</td>
                    <td><?php echo $totalCost; ?> zł</td>
                </tr>
            </tfoot>
        </table>
        <form action="cart.php" method="post">
            <input type="hidden" name="action" value="buy">
            <input type="submit" value="Kup">
        </form>
    <?php endif; ?>

    <?php if (!empty($purchasedProducts)): ?>
        <div class="purchase-info">
            <h3>Gratulacje! Twój zakup został zrealizowany.</h3>
            <h4>Lista zakupionych produktów:</h4>
            <ul>
                <?php foreach ($purchasedProducts as $product): ?>
                    <li><?php echo "{$product['name']} (Ilość: {$product['quantity']}, Cena: {$product['price']} zł)"; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <a href="index.php" class='link'>Wróć do sklepu</a>
</body>
</html>
