CREATE DATABASE mypocket;

USE mypocket;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL
);

CREATE TABLE transacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    valor DECIMAL(10,2) NOT NULL,
    tipo ENUM('Entrada', 'Saída') NOT NULL,
    data DATE NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    usuario_id INT NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

SELECT * FROM usuarios;
SELECT * FROM transacoes;

USE mypocket;

ALTER TABLE transacoes
CHANGE COLUMN DATA data DATE NOT NULL;

describe transacoes;

SELECT * FROM transacoes;