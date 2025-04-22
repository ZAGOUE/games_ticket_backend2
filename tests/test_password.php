<?php

$hash = '$2y$10$li27wuskpZ8rCDdN1uCd4eGLaN.G0gN8znwDlGcGSbbeL2rdvQrTG';
$password = 'passe123';

if (password_verify($password, $hash)) {
    echo "✅ Mot de passe correct !";
} else {
    echo "❌ Mot de passe incorrect !";
}


