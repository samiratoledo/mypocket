<?php

require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (!empty($email) && !empty($senha)) {

        try {

            $senhaHash = password_hash(
                $senha,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                INSERT INTO usuarios (email, senha)
                VALUES (:email, :senha)
            ");

            $stmt->execute([
                'email' => $email,
                'senha' => $senhaHash
            ]);

            header('Location: login.php');
            exit;

        } catch (PDOException $e) {

            $erro = "Erro ao cadastrar. Este e-mail pode já estar cadastrado.";

        }

    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link
        rel="website icon"
        href="real.svg"
        type="svg"
    >

    <title>Cadastro - MyPocket</title>

    <style>

        body {
            background: #76a5af;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cadastro-card {
            width: 100%;
            max-width: 420px;
            border: none;
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

        <div class="card cadastro-card shadow-sm mx-auto p-4">

            <div class="text-center mb-4">

                <div class="logo">
                    💰 MyPocket
                </div>

                <p class="subtitulo mb-0">
                    Crie sua conta
                </p>

            </div>


            <?php if (isset($erro)): ?>

                <script>

                    Swal.fire({
                        icon: 'error',
                        title: 'Não foi possível cadastrar',
                        text: '<?= htmlspecialchars($erro) ?>',
                        confirmButtonText: 'Entendi'
                    });

                </script>

            <?php endif; ?>


            <form method="POST">

                <div class="mb-3">

                    <label
                        for="email"
                        class="form-label"
                    >
                        E-mail
                    </label>

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

                    <label
                        for="senha"
                        class="form-label"
                    >
                        Senha
                    </label>

                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        class="form-control"
                        placeholder="Crie uma senha"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-primary w-100 mt-2"
                >
                    Cadastrar
                </button>

            </form>


            <div class="text-center mt-4">

                <p class="mb-0">

                    Já possui uma conta?

                    <a
                        href="login.php"
                        class="text-decoration-none fw-bold"
                    >
                        Fazer login
                    </a>

                </p>

            </div>

        </div>

    </div>

</body>

</html>