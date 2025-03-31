<?php

$hash = '$2y$10$li27wuskpZ8rCDdN1uCd4eGLaN.G0gN8znwDlGcGSbbeL2rdvQrTG'; // Ton hash correct
$password = 'passe123'; // Mot de passe en clair que l'utilisateur doit entrer

if (password_verify($password, $hash)) {
    echo "✅ Mot de passe correct !";
} else {
    echo "❌ Mot de passe incorrect !";
}


