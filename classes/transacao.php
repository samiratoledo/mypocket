<?php
declare(strict_types=1);

abstract class Transacao
{
    public function __construct(
        protected float $valor,
        protected string $data,
        protected string $descricao
    ) {
        if ($valor <= 0) {
            throw new Exception('Valor deve ser maior que 0.');
        }
    }

    public function getValor(): float
    {
        return $this->valor;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    abstract public function getTipo(): string;
}