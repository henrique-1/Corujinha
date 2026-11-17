<?php

namespace App\Services\Reports;

use App\Models\Assessment;

class AssessmentReportDataService
{
    public function __construct(
        protected ClassroomReportDataService $classroomReportDataService
    ) {}

    /**
     * Extrai e calcula todas as métricas consolidadas da avaliação somando todas as suas turmas.
     *
     * @return array<string, mixed>
     */
    public function getData(Assessment $assessment): array
    {
        $assessment->loadMissing([
            'questions.options',
            'classrooms.studentAttempts.answers.question.options',
            'user',
        ]);

        $classrooms = $assessment->classrooms;
        $questions = $assessment->questions;

        $totalClassrooms = $classrooms->count();
        $totalStudents = 0;
        $totalCompleted = 0;
        $allStudents = [];
        $classroomSummaries = [];

        $totalClassroomAccuracySum = 0;
        $totalTimeAllClassrooms = 0;
        $totalAnswersCount = 0;

        // Agrupamento por questão
        $aggregatedQuestionStats = [];
        foreach ($questions as $qIdx => $question) {
            $aggregatedQuestionStats[$question->id] = [
                'index' => $qIdx + 1,
                'id' => $question->id,
                'prompt' => $question->prompt,
                'support_title' => $question->support_title,
                'correct_count' => 0,
                'wrong_count' => 0,
                'unanswered_count' => 0,
                'time_total' => 0,
                'answers_count' => 0,
            ];
        }

        foreach ($classrooms as $classroom) {
            $cData = $this->classroomReportDataService->getData($classroom);
            $totalStudents += $cData['total_students'];
            $totalCompleted += $cData['completed_count'];
            $totalClassroomAccuracySum += $cData['classroom_accuracy'];

            $classroomSummaries[] = [
                'classroom_id' => $classroom->id,
                'name' => $classroom->name,
                'code' => $classroom->code,
                'game_mode' => $classroom->game_mode ?? 'corujinha',
                'is_active' => $classroom->is_active,
                'total_students' => $cData['total_students'],
                'completed_count' => $cData['completed_count'],
                'accuracy' => $cData['classroom_accuracy'],
                'avg_time_seconds' => $cData['overall_avg_response_time'],
            ];

            foreach ($cData['students'] as $student) {
                $student['classroom_name'] = $classroom->name;
                $student['classroom_code'] = $classroom->code;
                $allStudents[] = $student;
            }

            foreach ($cData['question_stats'] as $qId => $qStat) {
                if (isset($aggregatedQuestionStats[$qId])) {
                    $aggregatedQuestionStats[$qId]['correct_count'] += $qStat['correct_count'];
                    $aggregatedQuestionStats[$qId]['wrong_count'] += $qStat['wrong_count'];
                    $aggregatedQuestionStats[$qId]['unanswered_count'] += $qStat['unanswered_count'];
                }
            }
        }

        // Calcula taxas consolidadas das questões
        foreach ($aggregatedQuestionStats as $qId => &$qAgg) {
            $attempts = $qAgg['correct_count'] + $qAgg['wrong_count'];
            $qAgg['accuracy_rate'] = $attempts > 0 ? round(($qAgg['correct_count'] / $attempts) * 100, 1) : 0.0;
        }
        unset($qAgg);

        $sortedByCorrect = collect($aggregatedQuestionStats)->sortByDesc('correct_count')->values();
        $mostCorrectQuestion = $sortedByCorrect->first();

        $sortedByWrong = collect($aggregatedQuestionStats)->sortByDesc('wrong_count')->values();
        $mostWrongQuestion = $sortedByWrong->first();

        $overallAccuracy = $totalClassrooms > 0 ? round($totalClassroomAccuracySum / $totalClassrooms, 1) : 0.0;

        return [
            'assessment' => $assessment,
            'questions' => $questions,
            'total_classrooms' => $totalClassrooms,
            'total_students' => $totalStudents,
            'total_completed' => $totalCompleted,
            'overall_accuracy' => $overallAccuracy,
            'most_correct_question' => $mostCorrectQuestion,
            'most_wrong_question' => $mostWrongQuestion,
            'question_stats' => $aggregatedQuestionStats,
            'classroom_summaries' => $classroomSummaries,
            'students' => collect($allStudents)->sortByDesc('accuracy_percentage')->values()->all(),
        ];
    }
}
