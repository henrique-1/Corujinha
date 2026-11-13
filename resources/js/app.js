import { initDialogSystem } from './dialog-modal';

document.addEventListener('DOMContentLoaded', () => {
    initDialogSystem();

    if (window.__CLASSROOM_DATA__) {
        Promise.all([
            import('./echo'),
            import('./classroom-player'),
        ]).then(([, { initClassroomPlayer }]) => {
            initClassroomPlayer();
        });
    }

    if (document.getElementById('support-text-editor')) {
        import('./rich-text-editor').then(({ initRichTextEditor }) => {
            initRichTextEditor();
        });
    }

    if (document.getElementById('preview-support-text')) {
        import('./question-highlighter').then(({ initQuestionHighlighter }) => {
            initQuestionHighlighter();
        });
    }

    if (window.__CLASSROOM_MONITOR__) {
        Promise.all([
            import('./echo'),
            import('./classroom-monitor'),
        ]).then(([, { initClassroomMonitor }]) => {
            initClassroomMonitor();
        });
    }

    if (document.getElementById('professores-table-body')) {
        Promise.all([
            import('./echo'),
            import('./professors-monitor'),
        ]).then(([, { initProfessorsMonitor }]) => {
            initProfessorsMonitor();
        });
    }

    if (window.__CLASSROOMS_LIST_CONFIG__) {
        Promise.all([
            import('./echo'),
            import('./classrooms-list-monitor'),
        ]).then(([, { initClassroomsListMonitor }]) => {
            initClassroomsListMonitor();
        });
    }

    if (document.getElementById('classroom-closed-modal')) {
        import('./echo');
    }

    if (document.getElementById('btn-login-passkey')) {
        import('./passkey-auth').then(({ initPasskeyLogin }) => {
            initPasskeyLogin();
        });
    }

    if (document.getElementById('btn-register-passkey')) {
        import('./passkey-auth').then(({ initPasskeyRegister }) => {
            initPasskeyRegister();
        });
    }
});
