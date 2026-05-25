<?php
declare(strict_types=1);
class Carteira
{
    private float $saldo = 0;
    private array $historico = [];

    public function getSaldo(): float
    {
        return $this->saldo;
    }

    public function getHistorico(): array
    {
        return $this->historico;
    }

    public function addTransicao(Transacao $transacao)
    {

        if ($transacao->getTipo() == "Entrada") {
            $this->saldo += $transacao->getValor();
        } else if ($transacao->getTipo() == "Saída") {

            if ($transacao->getValor() > $this->saldo) {
                throw new Exception("Saldo insuficiente");
            }
            $this->saldo -= $transacao->getValor();
        }
        $this->historico[] = $transacao;
    }
}
?>