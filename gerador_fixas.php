<?php

declare(strict_types=1);

function gerarFixas(PDO $pdo, int $usuarioId): void
{
    $hoje = new DateTime();

    $stmt = $pdo->prepare("
        SELECT *
        FROM transacoes_fixas
        WHERE usuario_id = :usuario_id
          AND ativo = 1
          AND data_inicio <= :hoje
    ");

    $stmt->execute([
        'usuario_id' => $usuarioId,
        'hoje' => $hoje->format('Y-m-d')
    ]);

    $fixas = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $verifica = $pdo->prepare("
        SELECT id
        FROM transacoes
        WHERE fixa_id = :fixa_id
          AND data = :data
        LIMIT 1
    ");

    $insert = $pdo->prepare("
        INSERT INTO transacoes
        (
            valor,
            tipo,
            data,
            descricao,
            usuario_id,
            fixa_id,
            parcela,
            total_parcelas,
            competencia
        )
        VALUES
        (
            :valor,
            :tipo,
            :data,
            :descricao,
            :usuario_id,
            :fixa_id,
            :parcela,
            :total_parcelas,
            :competencia
        )
    ");


    foreach ($fixas as $fixa) {

        $inicio = new DateTime(
            $fixa['data_inicio']
        );

        $dia = (int) $fixa['dia'];

        $parcelada =
            $fixa['tipo_cobranca'] === 'parcelada';

        $limite = $parcelada
            ? (int) $fixa['total_parcelas']
            : 120;


        for ($i = 0; $i < $limite; $i++) {

            $data = clone $inicio;


            if ($fixa['frequencia'] === 'anual') {

                $data->modify("+{$i} year");

            } else {

                $data->modify("+{$i} month");
            }


            $ultimoDia =
                (int) $data->format('t');

            $data->setDate(
                (int) $data->format('Y'),
                (int) $data->format('m'),
                min($dia, $ultimoDia)
            );


            if ($data > $hoje) {
                break;
            }


            if (
                !empty($fixa['data_fim']) &&
                $data->format('Y-m-d') >
                $fixa['data_fim']
            ) {
                break;
            }


            $dataFormatada =
                $data->format('Y-m-d');


            $verifica->execute([
                'fixa_id' => $fixa['id'],
                'data' => $dataFormatada
            ]);

            if ($verifica->fetchColumn()) {
                continue;
            }


            $parcela = $parcelada
                ? $i + 1
                : null;


            $descricao =
                $fixa['descricao'];

            if ($parcelada) {

                $descricao .=
                    " - Parcela {$parcela}/" .
                    "{$fixa['total_parcelas']}";
            }


            $insert->execute([
                'valor' => $fixa['valor'],
                'tipo' => $fixa['tipo'],
                'data' => $dataFormatada,
                'descricao' => $descricao,
                'usuario_id' => $usuarioId,
                'fixa_id' => $fixa['id'],
                'parcela' => $parcela,
                'total_parcelas' => $parcelada
                    ? $fixa['total_parcelas']
                    : null,
                'competencia' =>
                    $data->format('Y-m')
            ]);
        }
    }
}