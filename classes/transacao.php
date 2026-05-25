<?php
declare(strict_types=1);

abstract class Transacao {
    protected float $valor;
    protected string $data;
    protected string $descricao;

    public function __construct(float $valor, string $data, string $descricao) {
        // Inicializa as propriedades
        $this->valor = $valor;
        $this->data = $data;
        $this->descricao = $descricao;
    }

    public function getValor (): float {
        return $this->valor;
    } 

    public function getData (): string {
        return $this->data;
    } 

    public function getDescricao (): string {
        return $this->descricao;
    } 

    public function getDetalhes () : string {
        return $this->descricao;  
    }

    abstract public function getTipo (): string;
    }
?>