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
    if(isset($_POST['quantity'])) {
        $quantity = (int)$_POST['quantity'];
    }

    $stmt = $conn->prepare("SELECT available_quantity FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($product = $result->fetch_assoc()) {
        $available_quantity = $product['available_quantity'];
    } else {
        $available_quantity = 0;
    }

    switch ($action) {
        case 'update_quantity':
            if ($quantity > 0 && $quantity <= $available_quantity) {
                $_SESSION['cart'][$product_id] = $quantity;
            } else if ($quantity == 0) {
                unset($_SESSION['cart'][$product_id]);
            }
            break;
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
        
    }

    $stmt->close();
    header('Location: index.php');
    exit();
}

// Liczba różnych produktów w koszyku
$uniqueProductsInCart = count($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strona Główna Sklepu</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="cart-view">
        <a href="cart.php">Przejdź do koszyka (<?php echo $uniqueProductsInCart; ?> różne produkty)</a>
    </div>
    <h1>Produkty dostępne w naszym sklepie</h1>
    <div class="products-container">
        <?php
        $stmt = $conn->prepare("SELECT * FROM products");
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            echo "<div class='product-item'>";
            echo "<strong>{$row['name']}</strong><br>";
            echo "{$row['description']}<br>";
            echo "Cena: {$row['price']} zł<br>";
            echo "Dostępność: {$row['available_quantity']} sztuk<br>";
            if ($row['image_path']) {
                echo "<img src='{$row['image_path']}' alt='{$row['name']}' style='max-height: 100px;'><br>";
            }
            
            $quantityInCart = $_SESSION['cart'][$row['id']] ?? 0;
            echo "W koszyku: <form action='index.php' method='POST' style='display: inline;'>";
            echo "<input type='hidden' name='product_id' value='{$row['id']}'>";
            echo "<input type='hidden' name='action' value='update_quantity'>";
            echo "<input type='number' name='quantity' value='$quantityInCart' min='0' max='{$row['available_quantity']}' style='width: 50px;'>";
            echo "<input type='submit' value='Aktualizuj'>";
            echo "</form><br>";
            

            // Formularze do zmiany ilości w koszyku
            echo "<form action='index.php' method='POST' style='display: inline;'>";
            echo "<input type='hidden' name='product_id' value='{$row['id']}'>";
            echo "<input type='hidden' name='action' value='add_one'>";
            echo "<input type='submit' value='+'>";
            echo "</form>";

            if ($quantityInCart > 0) {
                echo "<form action='index.php' method='POST' style='display: inline;'>";
                echo "<input type='hidden' name='product_id' value='{$row['id']}'>";
                echo "<input type='hidden' name='action' value='remove_one'>";
                echo "<input type='submit' value='-'>";
                echo "</form>";

                echo "<form action='index.php' method='POST' style='display: inline;'>";
                echo "<input type='hidden' name='product_id' value='{$row['id']}'>";
                echo "<input type='hidden' name='action' value='remove_all'>";
                echo "<input type='submit' value='Kosz'>";
                echo "</form>";
            }
            echo "</div>";
        }

        $stmt->close();
        ?>
    </div>
</body>
</html>
