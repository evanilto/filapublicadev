class FilaPublicaController extends BaseController
{
    public function consulta()
    {
        $codigo = $this->request->getGet('codigo');
        $token  = $this->request->getGet('token');
        $exp    = (int) $this->request->getGet('exp');
        $ip     = $this->request->getIPAddress();

        dd('here');
        
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

        return $this->response->setJSON(
            $this->filaModel->consultaPublica($codigo)
        );
    }

    // Gera token HMAC para consulta segura
    public function token()
    {
        $codigo = $this->request->getPost('codigo');
        $ip     = $this->request->getIPAddress();

        if (!$codigo) {
            return $this->response->setJSON(['erro' => 'Código obrigatório']);
        }

        // 🔐 Rate limit adicional por código
        $keyCodigo = 'rl_codigo_' . md5($codigo);
        $tentativas = cache()->get($keyCodigo) ?? 0;

        if ($tentativas >= 5) {
            sleep(2);
            return $this->response->setJSON(['erro' => 'Código temporariamente bloqueado']);
        }

        cache()->save($keyCodigo, $tentativas + 1, 300);

        // 🔎 Valida código público (BD)
        if (!$this->filaModel->codigoValido($codigo)) {
            return $this->response->setJSON(['erro' => 'Código inválido']);
        }

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
    function gerarCodigoPublico()
    {
        return strtoupper(bin2hex(random_bytes(3)));
    }

}
