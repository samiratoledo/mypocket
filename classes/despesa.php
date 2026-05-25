<?php
declare(strict_types=1);

class Despesa extends Transacao {
    public function getTipo(): string {
        return "Saída";
    }

}
?>