<?php
// api/register.php
session_start();
require_once 'database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $first_name = htmlspecialchars($_POST['first_name']);
    $last_name = htmlspecialchars($_POST['last_name']);
    
    // Validation
    if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = 'Tous les champs sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format d\'email invalide.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $dbh->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Cet email est déjà utilisé.';
            } else {
                // Hash du mot de passe
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insertion
                $stmt = $dbh->prepare("INSERT INTO users (email, password, first_name, last_name) VALUES (?, ?, ?, ?)");
                
                if ($stmt->execute([$email, $hashedPassword, $first_name, $last_name])) {
                    // Connecter automatiquement l'utilisateur après l'inscription
                    $user_id = $dbh->lastInsertId();
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_first_name'] = $first_name;
                    $_SESSION['user_last_name'] = $last_name;
                    
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                    exit;
                } else {
                    $error = 'Une erreur est survenue lors de l\'inscription.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }
    
    // Stocker l'erreur dans la session
    $_SESSION['register_error'] = $error;
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
?>