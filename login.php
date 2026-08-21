<?php
require_once 'conexao.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT id, nome, email, senha FROM usuarios WHERE email = :email'
    );

    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];

        header('Location: index.php');
        exit;
    }

    $erro = 'E-mail ou senha incorretos.';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="website icon" href="real.svg" type="svg">

    <title>Login - MyPocket</title>

    <style>
        body {
            background: #76a5af;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            border: 0;
            border-radius: 15px;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
        }

        .subtitulo {
            color: #6c757d;
        }
    </style>
</head>

<body>
    <div class="container px-3">
        <div class="card login-card shadow-sm mx-auto p-4">

            <div class="text-center mb-4">
                <div class="logo">💰 MyPocket</div>
                <p class="subtitulo mb-0">Entre na sua conta</p>
            </div>

            <?php if (isset($erro)): ?>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Ops!',
                        text: <?= json_encode($erro) ?>,
                        confirmButtonText: 'Entendi'
                    });
                </script>
            <?php endif; ?>

            <form method="POST">

                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="Digite seu e-mail"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>

                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        class="form-control"
                        placeholder="Digite sua senha"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-2">
                    Entrar
                </button>

            </form>

            <div class="text-center mt-4">
                <p class="mb-0">
                    Ainda não possui uma conta?

                    <a
                        href="cadastro.php"
                        class="text-decoration-none fw-bold"
                    >
                        Cadastrar
                    </a>
                </p>
            </div>

        </div>
    </div>
</body>
</html>