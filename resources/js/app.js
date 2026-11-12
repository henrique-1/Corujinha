import './echo';
import { initDialogSystem } from './dialog-modal';
import { initClassroomPlayer } from './classroom-player';
import { initQuestionHighlighter } from './question-highlighter';
import { initClassroomMonitor } from './classroom-monitor';
import { initProfessorsMonitor } from './professors-monitor';
import { initClassroomsListMonitor } from './classrooms-list-monitor';
import { initPasskeyLogin, initPasskeyRegister } from './passkey-auth';

document.addEventListener('DOMContentLoaded', () => {
    initDialogSystem();

    if (window.__CLASSROOM_DATA__) {
        initClassroomPlayer();
    }

    if (document.getElementById('support-text-editor')) {
        import('./rich-text-editor').then(({ initRichTextEditor }) => {
            initRichTextEditor();
        });
    }

    if (document.getElementById('preview-support-text')) {
        initQuestionHighlighter();
    }

    if (window.__CLASSROOM_MONITOR__) {
        initClassroomMonitor();
    }

    if (document.getElementById('professores-table-body')) {
        initProfessorsMonitor();
    }

    if (window.__CLASSROOMS_LIST_CONFIG__) {
        initClassroomsListMonitor();
    }

    if (document.getElementById('btn-login-passkey')) {
        initPasskeyLogin();
    }

    if (document.getElementById('btn-register-passkey')) {
        initPasskeyRegister();
    }
});
