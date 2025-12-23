<?php

namespace App\Controllers;  
class FilaPublicaController extends BaseController
// Controlador para acesso público à fila
// Métodos: token() e consulta()
{
    public function consulta()
    {
        $codigo = $this->request->getGet('codigo');
        $token  = $this->request->getGet('token');
        $exp    = (int) $this->request->getGet('exp');
        $ip     = $this->request->getIPAddress();

        // 🔐 Valida presença
        if (!$codigo || !$token || !$exp) {
            log_message('warning',"Tentativa inválida IP={$ip} codigo={$codigo}");

            return $this->response->setStatusCode(401)
                ->setJSON(['erro' => 'Token inválido']);
        }

        // ⏳ Valida expiração
        if (time() > $exp) {
            log_message('warning',"Tentativa inválida IP={$ip} codigo={$codigo}");

            return $this->response->setJSON(['erro' => 'Token expirado']);
        }

        // Validar uso único
        if (!cache()->get('hmac_' . $token)) {
            log_message('warning',"Tentativa inválida IP={$ip} codigo={$codigo}");

            return $this->response->setJSON(['erro' => 'Token já utilizado']);
        }
        cache()->delete('hmac_' . $token);

        // 🔑 Recalcula HMAC
        $payloadEsperado = $codigo . '|' . $exp . '|' . $ip;
        $tokenEsperado   = hash_hmac(
            'sha256',
            $payloadEsperado,
            getenv('HMAC_SECRET')
        );

        if (!hash_equals($tokenEsperado, $token)) {
            log_message('warning',"Tentativa inválida IP={$ip} codigo={$codigo}");

            sleep(2);
            return $this->response->setStatusCode(401)
                ->setJSON(['erro' => 'Token inválido']);
        }

        // 🔎 Consulta segura
        log_message(
            'info',
            "Consulta pública IP={$ip} codigo={$codigo}"
        );

       /*  return $this->response->setJSON(
            $this->filaModel->consultaPublica($codigo)
        ); */
        // SUCESSO MOCKED
        return $this->response
        ->setHeader('Content-Type', 'application/json')
        ->setJSON([
            'status' => 'Em atendimento',
            'posicao' => 5,
            'pacientes_a_frente' => 4,
            'ultima_atualizacao' => date('Y-m-d H:i:s'),
        ]);
        // ERRO MOCKED
        /* return $this->response
        ->setStatusCode(401)
        ->setJSON([
            'erro' => 'Token inválido ou expirado - Entre em contato com a TI do HUAP'
        ]); */

    }
    // Gera token HMAC para consulta segura
    // Requer código público válido
    public function token()
    {
        $data   = $this->request->getJSON(true);
        $codigo = $data['codigo'] ?? null;
        $ip     = $this->request->getIPAddress();

        if (!$codigo) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['erro' => 'Código obrigatório']);
        }

        /* return $this->response->setJSON([
            'codigo' => $codigo,
            'ip'     => $ip
        ]); */

        // 🔐 Rate limit adicional por código
        $keyCodigo = 'rl_codigo_' . md5($codigo);
        $tentativas = cache()->get($keyCodigo) ?? 0;

        if ($tentativas >= 5) {
            sleep(2);
            return $this->response->setJSON(['erro' => 'Código temporariamente bloqueado']);
        }

        cache()->save($keyCodigo, $tentativas + 1, 300);

        // 🔎 Valida código público (BD)
       /*  if (!$this->filaModel->codigoValido($codigo)) {
            return $this->response->setJSON(['erro' => 'Código inválido']);
        } */

        // 🔑 Gera HMAC
        $expiraEm = time() + 120; // 2 minutos
        $payload  = $codigo . '|' . $expiraEm . '|' . $ip;
        $token    = hash_hmac('sha256', $payload, getenv('HMAC_SECRET'));

        cache()->save('hmac_' . $token, true, 120);

        return $this->response->setJSON([
            'token' => $token,
            'exp'   => $expiraEm
        ]);
    }
    // Gera código público único
    // Usado internamente ao criar nova entrada na fila
    function gerarCodigoPublico()
    {
        return strtoupper(bin2hex(random_bytes(3)));
    }

}
