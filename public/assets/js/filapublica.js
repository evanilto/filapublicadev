let ultimoCodigoConsultado = '';
let countdownInterval = null;
let tentativas429 = 0;

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btnConsultar')
        .addEventListener('click', consultarFila);

    document.getElementById('codigo')
        .addEventListener('input', limparResultadoAoEditar);
});

async function consultarFila() {
    const codigo = document.getElementById('codigo').value.trim();

    limparMensagens();

    if (!codigo) {
        mostrarErro('Informe o número do prontuário.');
        return;
    }

    try {
        /* =========================
           TOKEN
        ========================== */
        const tokenResp = await fetch('/api/public/fila/token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ codigo })
        });

        const tokenData = await lerJsonSeguro(tokenResp);

        if (!tokenResp.ok) {
            tratarErroHttp(tokenResp, tokenData);
            return;
        }

        if (!tokenData.success) {
            throw new Error(tokenData.error || 'Erro ao solicitar token');
        }

        tentativas429 = 0;

        /* =========================
           CONSULTA
        ========================== */
        const url =
            `/api/public/fila/consulta?codigo=${encodeURIComponent(codigo)}` +
            `&token=${encodeURIComponent(tokenData.data.token)}` +
            `&exp=${encodeURIComponent(tokenData.data.exp)}`;

        const resp = await fetch(url, {
            headers: { 'Accept': 'application/json' }
        });

        const data = await lerJsonSeguro(resp);

        if (!resp.ok) {
            tratarErroHttp(resp, data);
            return;
        }

        if (!data.success) {
            throw new Error(data.error || 'Consulta inválida');
        }

        renderizarResultado(data.data, codigo);

    } catch (e) {
        mostrarErro(e.message || 'Erro inesperado');
    }
}

/* =========================
   UTILITÁRIOS DE HTTP
========================== */
async function lerJsonSeguro(resp) {
    try {
        return await resp.json();
    } catch {
        return { success: false, error: 'Resposta inválida do servidor' };
    }
}

function tratarErroHttp(resp, data) {
    if (resp.status === 429) {
        const retryAfter = resp.headers.get('Retry-After');
        aplicarBackoff(
            data.error || 'Muitas requisições.',
            retryAfter
        );
        return;
    }

    throw new Error(data.error || 'Erro de comunicação');
}

/* =========================
   BACKOFF EXPONENCIAL
========================== */
function aplicarBackoff(mensagem, retryAfterHeader) {
    tentativas429++;

    const base = retryAfterHeader
        ? parseInt(retryAfterHeader, 10)
        : 60;

    const segundos = Math.min(
        base * Math.pow(2, tentativas429 - 1),
        600
    );

    iniciarBloqueio(mensagem, segundos);
}

/* =========================
   BLOQUEIO + CONTAGEM
========================== */
function iniciarBloqueio(mensagem, segundos) {
    const erro = document.getElementById('erro');
    const btn = document.getElementById('btnConsultar');

    if (countdownInterval) {
        clearInterval(countdownInterval);
    }

    let restante = segundos;
    btn.disabled = true;

    erro.classList.remove('d-none');
    erro.innerText = `${mensagem} Aguarde ${restante}s.`;

    countdownInterval = setInterval(() => {
        restante--;

        if (restante <= 0) {
            clearInterval(countdownInterval);
            countdownInterval = null;
            btn.disabled = false;
            erro.classList.add('d-none');
        } else {
            erro.innerText = `${mensagem} Aguarde ${restante}s.`;
        }
    }, 1000);
}

/* =========================
   RENDER SEGURO
========================== */
function renderizarResultado(data, codigo) {
    if (!Array.isArray(data.registros) || data.registros.length === 0) {
        throw new Error('Nenhuma fila encontrada para este prontuário');
    }

    document.getElementById('nome').innerText =
        data.registros[0].nome;

    const lista = document.getElementById('listaFilas');
    lista.innerHTML = '';

    data.registros.forEach(item => {
        const card = document.createElement('div');
        card.className = 'card card-fila';

        card.innerHTML = `
            <div class="card-body">
                <h6 class="fw-semibold mb-2">${item.fila}</h6>
                <div class="info-line mb-1">
                    <span>Status</span>
                    <span>${item.status}</span>
                </div>
                <div class="info-line mb-1">
                    <span>Sua posição</span>
                    <span class="fw-bold">${item.posicao}</span>
                </div>
                <div class="info-line">
                    <span>Pacientes à frente</span>
                    <span>${item.pacientes_a_frente}</span>
                </div>
            </div>
        `;

        lista.appendChild(card);
    });

    document.getElementById('atualizacao').innerText =
        data.registros[0].ultima_atualizacao;

    ultimoCodigoConsultado = codigo;
    document.getElementById('resultado').classList.remove('d-none');
}

/* =========================
   LIMPEZA AO EDITAR
========================== */
function limparResultadoAoEditar() {
    const atual = document.getElementById('codigo').value;

    if (atual !== ultimoCodigoConsultado) {
        limparMensagens();
        document.getElementById('listaFilas').innerHTML = '';
    }
}

function limparMensagens() {
    document.getElementById('resultado').classList.add('d-none');
    document.getElementById('erro').classList.add('d-none');
}

/* =========================
   ERRO PADRÃO
========================== */
function mostrarErro(msg) {
    const erro = document.getElementById('erro');
    erro.innerText = msg;
    erro.classList.remove('d-none');
}
