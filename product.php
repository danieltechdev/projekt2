<?php
require 'db.php'; // Zakładamy, że ten plik zawiera połączenie z bazą danych sklep_internetowy
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function redirect($url) {
    header('Location: ' . $url);
    exit();
}

// Obsługa akcji dodania/aktualizacji produktu
if(isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $price = isset($_POST['price']) ? $_POST['price'] : 0;
    $available_quantity = isset($_POST['available_quantity']) ? $_POST['available_quantity'] : 0;
    $image_path = isset($_POST['image_path']) ? $_POST['image_path'] : '';

    if($action == 'add' || $action == 'update') {
        $query = $action == 'add' ?
            "INSERT INTO products (name, description, price, available_quantity, image_path) VALUES (?, ?, ?, ?, ?)" :
            "UPDATE products SET name = ?, description = ?, price = ?, available_quantity = ?, image_path = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        if($action == 'update') {
            $stmt->bind_param("ssdisi", $name, $description, $price, $available_quantity, $image_path, $id);
        } else {
            $stmt->bind_param("ssdis", $name, $description, $price, $available_quantity, $image_path);
        }
        $stmt->execute();
        $stmt->close();
        redirect('product.php');
    }
}

// Dodanie obsługi żądania GET dla akcji usuwania
if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $stmt->close();
        redirect('product.php');
    } else {
        echo "Błąd podczas usuwania produktu: " . $stmt->error;
    }
}

// Obsługa edycji produktu
if(isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($product = $result->fetch_assoc()) {
        // Produkt został pomyślnie załadowany dla edycji
    } else {
        echo "Nie znaleziono produktu.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sklep Internetowy - Zarządzanie Produktami</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h2><?php echo isset($product) ? 'Edytuj produkt' : 'Dodaj produkt'; ?></h2>
    <form method="POST" action="product.php">
        <input type="hidden" name="action" value="<?php echo isset($product) ? 'update' : 'add'; ?>">
        <?php if(isset($product)): ?>
            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
        <?php endif; ?>
        <input type="text" name="name" placeholder="Nazwa produktu" value="<?php echo isset($product) ? $product['name'] : ''; ?>" required><br>
        <textarea name="description" placeholder="Opis produktu" required><?php echo isset($product) ? $product['description'] : ''; ?></textarea><br>
        <input type="number" step="0.01" name="price" placeholder="Cena" value="<?php echo isset($product) ? $product['price'] : ''; ?>" required><br>
        <input type="number" name="available_quantity" placeholder="Dostępna ilość" value="<?php echo isset($product) ? $product['available_quantity'] : ''; ?>" required><br>
        <input type="text" name="image_path" placeholder="Ścieżka do obrazka" value="<?php echo isset($product) ? $product['image_path'] : ''; ?>"><br>
        <input type="submit" value="<?php echo isset($product) ? 'Aktualizuj' : 'Dodaj'; ?> produkt">
    </form>
    <a href="index.php" class="link">Powrót do sklepu</a>
    <h2>Lista produktów</h2>
    <div class="products-container">
        <?php
        $result = $conn->query("SELECT * FROM products");
        while($row = $result->fetch_assoc()) {
            echo "<div class='product-item'>";
            if($row['image_path']) {
                echo "<img src='{$row['image_path']}' alt='{$row['name']}'>";
            }
            echo "<h3>{$row['name']}</h3>";
            echo "<p>{$row['description']}</p>";
            echo "<p>Cena: {$row['price']} zł</p>";
            echo "<p>Dostępna ilość: {$row['available_quantity']}</p>";
            echo "<a href='?edit={$row['id']}'>Edytuj</a> | <a href='product.php?action=delete&id={$row['id']}' onclick='return confirm(\"Czy na pewno chcesz usunąć ten produkt?\");'>Usuń</a>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>
