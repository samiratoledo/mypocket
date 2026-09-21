DROP DATABASE IF EXISTS sistema_crud;

CREATE DATABASE sistema_crud;
USE sistema_crud;

-- USUÁRIOS

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL
);

-- DESPESAS FIXAS / PARCELADAS

CREATE TABLE transacoes_fixas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    tipo ENUM('Entrada', 'Saida', 'Diario') NOT NULL DEFAULT 'Saida',
    descricao VARCHAR(255) NOT NULL,
    dia INT NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NULL,
    frequencia ENUM('mensal', 'anual') NOT NULL DEFAULT 'mensal',
    tipo_cobranca ENUM('fixa', 'parcelada') NOT NULL DEFAULT 'fixa',
    total_parcelas INT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_fixas_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE,

    CONSTRAINT chk_dia
        CHECK (dia BETWEEN 1 AND 31),

    CONSTRAINT chk_parcelas
        CHECK (
            total_parcelas IS NULL
            OR total_parcelas > 0
        )
);

-- TRANSAÇÕES

CREATE TABLE transacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    valor DECIMAL(10,2) NOT NULL,
    tipo ENUM('Entrada', 'Saida', 'Diario') NOT NULL,
    data DATE NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    usuario_id INT NOT NULL,

    -- Ligação com despesa fixa/parcelada
    fixa_id INT NULL,

    -- Controle de parcelas
    parcela INT NULL,
    total_parcelas INT NULL,

    -- Mês de referência
    competencia CHAR(7) NULL,

    CONSTRAINT fk_transacoes_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_transacoes_fixa
        FOREIGN KEY (fixa_id)
        REFERENCES transacoes_fixas(id)
        ON DELETE SET NULL
);


-- CONSULTAS PARA CONFERÊNCIA

DESCRIBE usuarios;

DESCRIBE transacoes;

DESCRIBE transacoes_fixas;