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
   public function consultarFila(string $prontuario): array
    {
        $rows = $this->asArray()
                    ->where('prontuario', $prontuario)
                    ->orderBy('posicao_fila', 'ASC')
                    ->findAll();

        if (empty($rows)) {
            return ['erro' => 'Paciente não encontrado na fila'];
        }

        $dt = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));

        $resultado = [];

        foreach ($rows as $row) {
            $resultado[] = [
                'status' => $row['status'],
                'nome' => $row['nome'],
                'fila' => $row['fila'],
                'posicao' => (int) $row['posicao_fila'],
                'pacientes_a_frente' => max(0, (int) $row['posicao_fila'] - 1),
                'idlistaespera' => $row['idlistaespera'],
                'ultima_atualizacao' => $dt->format('Y-m-d H:i:s')
            ];
        }

        return [
            'total_ocorrencias' => count($resultado),
            'registros' => $resultado
        ];
    }

}
