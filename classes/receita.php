<?php
declare(strict_types=1);

class Receita extends Transacao {
    public function getTipo(): string {
        return "Entrada";
    }

}
?>