<?php
declare(strict_types=1)
class Transacao {
    protected float $valor;
    protected string $data;
    protected string $descricao;

    public function __construct($valor, $data, $descricao) {
        // Inicializa as propriedades
        $this->valor = $valor;
        $this->data = $data;
        $this->descricao = $descricao;
    }

    public function getValor () {
        return $valor;
    } 

    public function getData () {
        return $data;
    } 

    public function getDescricao () {
        return $descricao;
    } 

    public function getTipo ();
    }
?>