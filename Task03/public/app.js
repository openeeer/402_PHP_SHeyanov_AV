(function () {
    const state = {
        currentGameId: null,
    };

    const elements = {
        newGameForm: document.getElementById('new-game-form'),
        playerNameInput: document.getElementById('player-name'),
        globalNotice: document.getElementById('global-notice'),
        currentGamePanel: document.getElementById('current-game-panel'),
        currentExpression: document.getElementById('current-expression'),
        stepForm: document.getElementById('step-form'),
        userAnswerInput: document.getElementById('user-answer'),
        stepResult: document.getElementById('step-result'),
        historyBody: document.getElementById('history-body'),
        refreshHistoryButton: document.getElementById('refresh-history'),
        detailsPanel: document.getElementById('details-panel'),
        detailsContent: document.getElementById('details-content'),
    };

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showNotice(text, type) {
        const classes = {
            success: 'notice notice-success',
            error: 'notice notice-warning',
            info: 'notice',
        };

        elements.globalNotice.className = classes[type] || classes.info;
        elements.globalNotice.textContent = text;
        elements.globalNotice.classList.remove('hidden');
    }

    function hideNotice() {
        elements.globalNotice.className = 'notice hidden';
        elements.globalNotice.textContent = '';
    }

    async function parseResponsePayload(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            return response.json();
        }

        return response.text();
    }

    function extractErrorMessage(payload, statusCode) {
        if (payload && typeof payload === 'object' && payload.error) {
            return String(payload.error);
        }

        if (typeof payload === 'string' && payload.trim() !== '') {
            return payload;
        }

        return 'HTTP ' + statusCode;
    }

    async function requestApi(path, options) {
        const attempts = [path, '/index.php' + path];
        const requestOptions = Object.assign({}, options || {});

        requestOptions.headers = Object.assign(
            { Accept: 'application/json' },
            requestOptions.body ? { 'Content-Type': 'application/json' } : {},
            requestOptions.headers || {}
        );

        let lastError = null;

        for (let index = 0; index < attempts.length; index++) {
            const endpoint = attempts[index];

            try {
                const response = await fetch(endpoint, requestOptions);

                if (index === 0 && response.status === 404) {
                    continue;
                }

                const payload = await parseResponsePayload(response);

                if (!response.ok) {
                    throw new Error(extractErrorMessage(payload, response.status));
                }

                return payload;
            } catch (error) {
                lastError = error;

                if (index === attempts.length - 1) {
                    throw lastError;
                }
            }
        }

        throw lastError || new Error('Unknown API error.');
    }

    function renderCurrentGame(game) {
        state.currentGameId = game.id;
        elements.currentExpression.textContent = game.expression;
        elements.currentGamePanel.classList.remove('hidden');
    }

    function renderStepResult(stepPayload) {
        const isCorrect = stepPayload.step.is_correct;

        elements.stepResult.innerHTML = [
            '<p>Ваш ответ: <strong>' + escapeHtml(stepPayload.step.user_answer) + '</strong></p>',
            '<p>Правильный ответ: <strong>' + escapeHtml(stepPayload.correct_answer) + '</strong></p>',
            '<p class="' + (isCorrect ? 'status-ok' : 'status-bad') + '"><strong>' +
            (isCorrect ? 'Верно! :-) ' : 'Неверно. :-( ') + '</strong></p>',
        ].join('');
    }

    function gameStatusLabel(game) {
        if (game.steps.length === 0) {
            return 'Ожидает ход';
        }

        const lastStep = game.steps[game.steps.length - 1];
        return lastStep.is_correct ? 'Последний ход верный' : 'Последний ход неверный';
    }

    function renderHistory(games) {
        if (!Array.isArray(games) || games.length === 0) {
            elements.historyBody.innerHTML = '<tr><td colspan="7">Пока нет игр в базе данных.</td></tr>';
            return;
        }

        const rows = games.map(function (game) {
            return [
                '<tr>',
                '<td>' + escapeHtml(game.id) + '</td>',
                '<td>' + escapeHtml(game.started_at) + '</td>',
                '<td>' + escapeHtml(game.player_name) + '</td>',
                '<td><span class="inline-code">' + escapeHtml(game.expression) + '</span></td>',
                '<td>' + escapeHtml(game.steps.length) + '</td>',
                '<td>' + escapeHtml(gameStatusLabel(game)) + '</td>',
                '<td><button class="retro-btn table-btn" type="button" data-game-id="' + escapeHtml(game.id) + '">Открыть</button></td>',
                '</tr>',
            ].join('');
        });

        elements.historyBody.innerHTML = rows.join('');
    }

    function renderGameDetails(game) {
        const lines = [
            '<p><strong>ID:</strong> ' + escapeHtml(game.id) + '</p>',
            '<p><strong>Игрок:</strong> ' + escapeHtml(game.player_name) + '</p>',
            '<p><strong>Выражение:</strong> <span class="inline-code">' + escapeHtml(game.expression) + '</span></p>',
            '<p><strong>Правильный ответ:</strong> ' + escapeHtml(game.correct_answer) + '</p>',
            '<p><strong>Дата начала:</strong> ' + escapeHtml(game.started_at) + '</p>',
        ];

        if (game.steps.length === 0) {
            lines.push('<p>Пока нет ходов.</p>');
        } else {
            lines.push('<h3>Ходы</h3>');
            lines.push('<ul class="steps-list">');

            game.steps.forEach(function (step) {
                lines.push(
                    '<li>' +
                    'Шаг #' + escapeHtml(step.id) +
                    ': ответ <strong>' + escapeHtml(step.user_answer) + '</strong>, ' +
                    (step.is_correct ? '<span class="status-ok">верно</span>' : '<span class="status-bad">неверно</span>') +
                    ', время: ' + escapeHtml(step.created_at) +
                    '</li>'
                );
            });

            lines.push('</ul>');
        }

        elements.detailsContent.innerHTML = lines.join('');
        elements.detailsPanel.classList.remove('hidden');
    }

    async function loadHistory() {
        const games = await requestApi('/games');
        renderHistory(games);
    }

    async function loadGameDetails(gameId) {
        const game = await requestApi('/games/' + gameId);
        renderCurrentGame(game);
        renderGameDetails(game);
    }

    elements.newGameForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        hideNotice();

        const playerName = elements.playerNameInput.value.trim();
        if (playerName === '') {
            showNotice('Введите имя игрока.', 'error');
            return;
        }

        try {
            const game = await requestApi('/games', {
                method: 'POST',
                body: JSON.stringify({ player_name: playerName }),
            });

            renderCurrentGame(game);
            elements.userAnswerInput.value = '';
            elements.stepResult.innerHTML = '';
            showNotice('Новая игра создана. Можно отправить ход.', 'success');
            await loadHistory();
            renderGameDetails(game);
        } catch (error) {
            showNotice(error.message, 'error');
        }
    });

    elements.stepForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        hideNotice();

        if (state.currentGameId === null) {
            showNotice('Сначала начните новую игру.', 'error');
            return;
        }

        const userAnswer = elements.userAnswerInput.value.trim();
        if (userAnswer === '') {
            showNotice('Введите ответ игрока.', 'error');
            return;
        }

        try {
            const stepPayload = await requestApi('/step/' + state.currentGameId, {
                method: 'POST',
                body: JSON.stringify({ user_answer: userAnswer }),
            });

            renderStepResult(stepPayload);
            await loadGameDetails(state.currentGameId);
            await loadHistory();
        } catch (error) {
            showNotice(error.message, 'error');
        }
    });

    elements.refreshHistoryButton.addEventListener('click', async function () {
        hideNotice();

        try {
            await loadHistory();
        } catch (error) {
            showNotice(error.message, 'error');
        }
    });

    elements.historyBody.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-game-id]');
        if (!button) {
            return;
        }

        hideNotice();
        const gameId = button.getAttribute('data-game-id');

        try {
            await loadGameDetails(gameId);
            showNotice('Детали игры загружены.', 'info');
        } catch (error) {
            showNotice(error.message, 'error');
        }
    });

    (async function bootstrap() {
        try {
            await loadHistory();
        } catch (error) {
            showNotice(error.message, 'error');
        }
    })();
}());

