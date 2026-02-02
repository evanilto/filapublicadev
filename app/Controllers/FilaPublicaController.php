<?php

namespace App\Controllers;

use App\Models\VwFilaPublicaModel as FilaModel;

class FilaPublicaController extends BaseController
{
    /* =====================================================
       GERA TOKEN TEMPORÁRIO
       ===================================================== */
    public function token()
    {
        $data   = $this->request->getJSON(true);
        $codigo = $data['codigo'] ?? null;

        if (!$codigo) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'success' => false,
                    'error'   => 'Código obrigatório'
                ]);
        }

        /* Rate limit adicional por código */
        $keyCodigo  = 'rl_codigo_' . md5($codigo);
        $tentativas = cache()->get($keyCodigo) ?? 0;

        if ($tentativas >= 5) {
            return $this->response
                ->setStatusCode(429)
                ->setJSON([
                    'success' => false,
                    'error'   => 'Código temporariamente bloqueado'
                ]);
        }

        cache()->save($keyCodigo, $tentativas + 1, 300);

        /* Geração do token */
        $expiraEm = time() + 120; // 2 minutos
        $payload  = $codigo . '|' . $expiraEm;
        $token    = hash_hmac('sha256', $payload, getenv('HMAC_SECRET'));

        cache()->save('hmac_' . $token, true, 120);

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'token' => $token,
                'exp'   => $expiraEm
            ]
        ]);
    }

    /* =====================================================
       CONSULTA DA FILA
       ===================================================== */
    public function consulta()
    {
        $codigo = $this->request->getGet('codigo');
        $token  = $this->request->getGet('token');
        $exp    = (int) $this->request->getGet('exp');

        /* Validação de parâmetros */
        if (!$codigo || !$token || !$exp) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'error'   => 'Parâmetros inválidos'
                ]);
        }

        /* Token expirado */
        if (time() > $exp) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'error'   => 'Token expirado'
                ]);
        }

        /* Uso único */
        if (!cache()->get('hmac_' . $token)) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'error'   => 'Token inválido ou já utilizado'
                ]);
        }

        cache()->delete('hmac_' . $token);

        /* Validação HMAC */
        $payloadEsperado = $codigo . '|' . $exp;
        $tokenEsperado   = hash_hmac(
            'sha256',
            $payloadEsperado,
            getenv('HMAC_SECRET')
        );

        if (!hash_equals($tokenEsperado, $token)) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'error'   => 'Token inválido'
                ]);
        }

        /* Consulta segura */
        $model     = new FilaModel();
        $resultado = $model->consultarFila($codigo);

        /* Erro funcional (paciente não encontrado) */
        if (isset($resultado['erro'])) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => $resultado['erro']
            ]);
        }

        /* Garantia de contrato */
        if (
            !isset($resultado['registros']) ||
            !is_array($resultado['registros']) ||
            count($resultado['registros']) === 0
        ) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Nenhuma fila encontrada para este prontuário'
            ]);
        }

        /* Sucesso */
        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'registros' => $resultado['registros']
            ]
        ]);
    }
}
