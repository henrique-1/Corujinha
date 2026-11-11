import 'webauthn-polyfills';

function bufferToBase64Url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

/**
 * Inicializa a autenticação via Passkey na tela de Login
 * (botão explícito + Conditional UI autofill quando suportado pelo navegador).
 */
export function initPasskeyLogin() {
    const btn = document.getElementById('btn-login-passkey');
    if (!btn) return;

    const optionsUrl = btn.dataset.optionsUrl;
    const verifyUrl = btn.dataset.verifyUrl;
    const emailInput = document.getElementById('email');
    const rememberInput = document.querySelector('input[name="remember"]');
    const errorBox = document.getElementById('passkey-login-error');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    let autofillAbortController = new AbortController();

    function showError(msg) {
        if (!errorBox) return;
        const textEl = errorBox.querySelector('[data-passkey-error-text]');
        if (textEl) textEl.textContent = msg;
        errorBox.classList.remove('hidden');
    }

    function clearError() {
        if (!errorBox) return;
        errorBox.classList.add('hidden');
    }

    async function fetchLoginOptions(email = '') {
        const res = await fetch(optionsUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ email: email || null }),
        });

        if (!res.ok) {
            throw new Error('Não foi possível iniciar o desafio de Passkey.');
        }

        return res.json();
    }

    async function verifyCredential(credential, publicKeyOptions) {
        const encoded = credential.toJSON();
        const response = await fetch(verifyUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                id: encoded.id,
                clientDataJSON: encoded.response.clientDataJSON,
                authenticatorData: encoded.response.authenticatorData,
                signature: encoded.response.signature,
                remember: Boolean(rememberInput?.checked),
            }),
        });

        if (!response.ok && response.status === 404) {
            if (PublicKeyCredential.signalUnknownCredential) {
                try {
                    await PublicKeyCredential.signalUnknownCredential({
                        rpId: publicKeyOptions.rpId,
                        credentialId: encoded.id,
                    });
                } catch {
                    // Ignora falhas de sincronização de gerenciador
                }
            }
        }

        const data = await response.json();
        if (!response.ok) {
            showError(data.error || 'Falha ao validar a Passkey no servidor.');
            return;
        }

        if (data.redirect_url) {
            window.location.href = data.redirect_url;
        }
    }

    async function initializeConditionalAutofill() {
        if (!window.PublicKeyCredential || !PublicKeyCredential.getClientCapabilities) {
            return;
        }

        try {
            const capabilities = await PublicKeyCredential.getClientCapabilities();
            if (capabilities.conditionalGet !== true) {
                return;
            }

            const loginOptionsJSON = await fetchLoginOptions();
            const publicKey = PublicKeyCredential.parseRequestOptionsFromJSON(loginOptionsJSON);

            const credential = await navigator.credentials.get({
                publicKey,
                signal: autofillAbortController.signal,
                mediation: 'conditional',
            });

            if (credential) {
                await verifyCredential(credential, publicKey);
            }
        } catch (err) {
            if (err && ['NotAllowedError', 'AbortError'].includes(err.name)) {
                return;
            }
        }
    }

    btn.addEventListener('click', async () => {
        clearError();

        if (!window.PublicKeyCredential) {
            showError('Seu navegador não possui suporte a Passkeys (WebAuthn).');
            return;
        }

        btn.disabled = true;
        autofillAbortController.abort();
        autofillAbortController = new AbortController();

        try {
            const emailVal = emailInput?.value?.trim() || '';
            const loginOptionsJSON = await fetchLoginOptions(emailVal);
            const publicKey = PublicKeyCredential.parseRequestOptionsFromJSON(loginOptionsJSON);

            const credential = await navigator.credentials.get({
                publicKey,
                signal: autofillAbortController.signal,
            });

            if (credential) {
                await verifyCredential(credential, publicKey);
            }
        } catch (err) {
            if (err?.name === 'NotAllowedError') {
                showError('Autenticação com Passkey cancelada ou expirada.');
            } else if (err?.name !== 'AbortError') {
                showError(err?.message || 'Erro ao autenticar com Passkey.');
            }
            initializeConditionalAutofill();
        } finally {
            btn.disabled = false;
        }
    });

    initializeConditionalAutofill();
}

/**
 * Inicializa o registro de nova Passkey na Central de Segurança.
 */
export function initPasskeyRegister() {
    const btn = document.getElementById('btn-register-passkey');
    if (!btn) return;

    const optionsUrl = btn.dataset.optionsUrl;
    const storeUrl = btn.dataset.storeUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    btn.addEventListener('click', async () => {
        if (!window.PublicKeyCredential) {
            alert('Seu navegador não possui suporte a Passkeys (WebAuthn).');
            return;
        }

        try {
            const res = await fetch(optionsUrl, {
                headers: { Accept: 'application/json' },
            });
            const creationOptionsJSON = await res.json();
            const publicKey = PublicKeyCredential.parseCreationOptionsFromJSON(creationOptionsJSON);

            const cred = await navigator.credentials.create({ publicKey });
            if (!cred) return;

            const encoded = cred.toJSON();
            const spkiBuffer = typeof cred.response.getPublicKey === 'function'
                ? cred.response.getPublicKey()
                : null;

            if (!spkiBuffer) {
                alert('Seu navegador não exportou a chave pública SPKI necessária para o login seguro.');
                return;
            }

            const publicKeySpki = bufferToBase64Url(spkiBuffer);
            const publicKeyAlg = typeof cred.response.getPublicKeyAlgorithm === 'function'
                ? cred.response.getPublicKeyAlgorithm()
                : -7;

            const name = prompt('Dê um nome para esta chave (ex: Notebook, Celular)', 'Minha Passkey');
            const saveRes = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    id: encoded.id,
                    clientDataJSON: encoded.response.clientDataJSON,
                    publicKeySpki,
                    publicKeyAlg,
                    name: name || 'Minha Passkey',
                }),
            });

            if (!saveRes.ok) {
                const errData = await saveRes.json().catch(() => ({}));
                if (PublicKeyCredential.signalUnknownCredential) {
                    try {
                        await PublicKeyCredential.signalUnknownCredential({
                            rpId: publicKey.rp.id,
                            credentialId: encoded.id,
                        });
                    } catch {
                        // Ignora
                    }
                }
                alert(errData.error || 'Erro ao registrar a Passkey no servidor.');
                return;
            }

            alert('Passkey registrada com sucesso!');
            window.location.reload();
        } catch (err) {
            if (err?.name === 'InvalidStateError') {
                alert('Já existe uma Passkey cadastrada neste autenticador para esta conta.');
            } else if (err?.name === 'SecurityError') {
                alert('Erro de segurança ou domínio inválido para WebAuthn (use HTTPS ou localhost).');
            } else if (err?.name !== 'NotAllowedError' && err?.name !== 'AbortError') {
                alert('Erro ao criar Passkey: ' + (err?.message || 'erro desconhecido'));
            }
        }
    });
}
