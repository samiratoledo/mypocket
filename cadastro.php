<?php

require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
}

$stmt = $pdo->prepare("
    INSERT INTO USUARIOS (email, senha)
    VALUES (:email, :senhaHash)
");

$stmt->execute([
    'email' => $email,
    'senha' => $senhaHash
]);
?>

<form method="POST">

    <div>
        <label for="email">E-mail:</label>
        <input
            type="email"
            id="email"
            name="email"
            required
        >
    </div>

    <div>
        <label for="senha">Senha:</label>
        <input
            type="password"
            id="senha"
            name="senha"
            required
        >
    </div>

    <button type="submit">Cadastrar</button>

</form>

<p>
    Já possui uma conta?
    <a href="login.php">Fazer login</a>
</p>