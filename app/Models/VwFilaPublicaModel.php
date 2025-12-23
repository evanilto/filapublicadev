<?php

namespace App\Models;

use CodeIgniter\Model;
use DateTime;
use DateTimeZone;


class VwFilaPublicaModel extends Model
{
    protected $DBGroup = 'default';
    protected $table      = 'vw_fila_publica';
    protected $primaryKey = 'idlistaespera';
    protected $returnType = 'array';
    protected $allowedFields = [];
    /**
     * Consulta a VIEW da fila por código
     * e retorna no formato esperado pelo frontend
     */
    public function consultarFila(string $codigo): array
    {
        $row = $this->asArray()->find($codigo);

        if (!$row) {
            return ['erro' => 'Código não encontrado na fila'];
        }

        $dt = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));

        return [
            'status' => $row['status'],
            'posicao' => (int) $row['posicao_fila'],
            'pacientes_a_frente' => (int) $row['posicao_fila'] - 1,
            'ultima_atualizacao' => $dt->format('Y-m-d H:i:s') //$row['ultima_atualizacao']
        ];
    }
}
