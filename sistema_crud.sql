CREATE DATABASE IF NOT EXISTS sistema_crud;
USE sistema_crud;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS transacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    valor DECIMAL(10,2) NOT NULL,
    tipo ENUM('Entrada', 'Saida', 'Diario') NOT NULL,
    data DATE NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    usuario_id INT NOT NULL,

    CONSTRAINT fk_transacoes_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transacoes_fixas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    tipo ENUM('Entrada', 'Saida', 'Diario') NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    dia INT NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NULL,
    frequencia ENUM('mensal', 'anual') NOT NULL DEFAULT 'mensal',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_transacoes_fixas_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE,

    CONSTRAINT chk_transacoes_fixas_dia
        CHECK (dia BETWEEN 1 AND 31)
);

SELECT * FROM usuarios;

SELECT * FROM transacoes;

SELECT * FROM transacoes_fixas;
