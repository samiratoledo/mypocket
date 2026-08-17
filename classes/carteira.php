<?php

class Carteira
{
    private float $saldo = 0.0;
    private array $historico = []; // Inicializa como array vazio

    public function definirSaldo(float $saldo): void
    {
        $this->saldo = $saldo;
    }

    public function getSaldo(): float
    {
        return $this->saldo;
    }

    public function carregarTransacao(Transacao $transacao): void
    {
        $this->historico[] = $transacao; // O "[]" garante que cada item seja adicionado à lista
    }

    public function getHistorico(): array
    {
        return $this->historico;
    }
}