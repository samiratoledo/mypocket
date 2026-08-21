<?php
declare(strict_types=1);

class Carteira
{
    private float $saldo = 0.0;

    public function adicionar(float $valor): void
    {
        $this->saldo += $valor;
    }

    public function retirar(float $valor): void
    {
        if ($valor > $this->saldo) {
            throw new Exception('Saldo insuficiente.');
        }

        $this->saldo -= $valor;
    }

    public function getSaldo(): float
    {
        return $this->saldo;
    }

    public function definirSaldo(float $saldo): void
    {
        $this->saldo = $saldo;
    }
}