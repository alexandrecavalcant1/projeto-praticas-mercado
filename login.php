<?php
// Redirecionamento permanente para a nova estrutura de autenticação em index.php
header("Location: index.php", true, 301);
exit;