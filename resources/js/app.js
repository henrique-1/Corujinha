import { initClassroomPlayer } from './classroom-player';
import { initQuestionHighlighter } from './question-highlighter';

document.addEventListener('DOMContentLoaded', () => {
    if (window.__CLASSROOM_DATA__) {
        initClassroomPlayer();
    }

    if (document.getElementById('preview-support-text')) {
        initQuestionHighlighter();
    }
});
