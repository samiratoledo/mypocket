CREATE DATABASE sistema_crud;
USE sistema_crud;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL
);

CREATE TABLE transacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    valor DECIMAL(10,2) NOT NULL,
    tipo ENUM('Entrada', 'Saida') NOT NULL,
    data DATE NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    usuario_id INT NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

DESCRIBE transacoes;

SELECT * FROM usuarios;
SELECT * FROM transacoes;