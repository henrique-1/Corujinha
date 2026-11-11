import './echo';
import { initClassroomPlayer } from './classroom-player';
import { initQuestionHighlighter } from './question-highlighter';
import { initClassroomMonitor } from './classroom-monitor';

document.addEventListener('DOMContentLoaded', () => {
    if (window.__CLASSROOM_DATA__) {
        initClassroomPlayer();
    }

    if (document.getElementById('preview-support-text')) {
        initQuestionHighlighter();
    }

    if (window.__CLASSROOM_MONITOR__) {
        initClassroomMonitor();
    }
});
