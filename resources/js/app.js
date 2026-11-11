import './echo';
import { initClassroomPlayer } from './classroom-player';
import { initQuestionHighlighter } from './question-highlighter';
import { initClassroomMonitor } from './classroom-monitor';
import { initProfessorsMonitor } from './professors-monitor';
import { initClassroomsListMonitor } from './classrooms-list-monitor';

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

    if (document.getElementById('professores-table-body')) {
        initProfessorsMonitor();
    }

    if (window.__CLASSROOMS_LIST_CONFIG__) {
        initClassroomsListMonitor();
    }
});
