document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btnConsultar')
        .addEventListener('click', consultarFila);
});

async function consultarFila() {
    const codigo = document.getElementById('codigo').value.trim();
    const erro = document.getElementById('erro');
    const resultado = document.getElementById('resultado');

    erro.classList.add('d-none');
    resultado.classList.add('d-none');

    if (!codigo) {
        erro.innerText = 'Informe o código.';
        erro.classList.remove('d-none');
        return;
    }

    try {
        // 1️⃣ Solicita token HMAC
        const tokenResp = await fetch('/api/public/fila/token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ codigo })
        });

        /* if (!tokenResp.ok) {
            const errorText = await tokenResp.text();
            console.error('Erro do servidor:', errorText);
            throw new Error(`Erro HTTP ${tokenResp.status}`);
        } */

        var contentType = tokenResp.headers.get('content-type');

        if (!contentType || !contentType.includes('application/json')) {
            const text = await tokenResp.text();
            console.error('Resposta NÃO é JSON:', text);
            throw new Error('Resposta inválida (não JSON)');
        }

        const tokenData = await tokenResp.json();

        if (tokenData.erro) throw tokenData.erro;

        if (
            !tokenData ||
            typeof tokenData !== 'object'
        ) {
            throw new Error(`Resposta inválida do servidor - Erro HTTP ${tokenResp.status}`);
        }

        if (
            !('token' in tokenData) ||
            typeof tokenData.token !== 'string' ||
            tokenData.token.trim() === ''
        ) {
            console.error('Token ausente ou inválido:', tokenData);

            const msg =
                tokenData.erro ||
                tokenData.mensagem ||
                'Token não retornado pela API';

            throw new Error(msg);
        }

        if (tokenData.erro) throw new Error(tokenData.erro);

        // 2️⃣ Consulta Fila com token
        const url = `/api/public/fila/consulta?codigo=${encodeURIComponent(codigo)}`
            + `&token=${encodeURIComponent(tokenData.token)}`
            + `&exp=${encodeURIComponent(tokenData.exp)}`;

        const resp = await fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        });

        console.log({
            status: resp.status,
            ok: resp.ok,
            contentType: resp.headers.get('content-type')
        });

        if (!resp.ok) {
            const errorText = await resp.text();
            throw new Error(`Erro HTTP ${resp.status}: ${errorText}`);
        }

        contentType = resp.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const raw = await resp.text();
            throw new Error('Resposta inválida (não JSON): ' + raw);
        }

        const data = await resp.json();
        console.log('consulta response', data);

        if (data.erro) throw new Error(data.erro);

        document.getElementById('status').innerText = data.status;
        document.getElementById('posicao').innerText = data.posicao;
        document.getElementById('frente').innerText = data.pacientes_a_frente;
        document.getElementById('atualizacao').innerText = data.ultima_atualizacao;

        resultado.classList.remove('d-none');

    } catch (e) {
        erro.innerText = e;
        erro.classList.remove('d-none');
    }
}