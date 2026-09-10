<?php
// Génération du hash pour le mot de passe "password123"
$password = 'password123';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
echo $hashedPassword;
?>
