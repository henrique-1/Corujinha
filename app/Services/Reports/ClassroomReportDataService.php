<?php

namespace App\Services\Reports;

use App\Models\Classroom;

class ClassroomReportDataService
{
    /**
     * Extrai e calcula todas as métricas pedagógicas da sala de aula.
     *
     * @return array<string, mixed>
     */
    public function getData(Classroom $classroom): array
    {
        $classroom->loadMissing([
            'assessment.questions.options',
            'studentAttempts.answers.question.options',
            'user',
        ]);

        $assessment = $classroom->assessment;
        $questions = $assessment?->questions ?? collect();
        $attempts = $classroom->studentAttempts;

        $totalStudents = $attempts->count();
        $completedAttempts = $attempts->where('status', 'completed');
        $completedCount = $completedAttempts->count();

        // Questões e estatísticas por questão
        $questionStats = [];
        $totalTimeAllAnswers = 0;
        $totalAnswersCount = 0;

        foreach ($questions as $qIndex => $question) {
            $correctCount = 0;
            $wrongCount = 0;
            $unansweredCount = 0;
            $timeSpentTotal = 0;
            $answersCountForQ = 0;

            foreach ($attempts as $attempt) {
                $ans = $attempt->answers->firstWhere('question_id', $question->id);
                if (! $ans || empty($ans->initial_answer)) {
                    $unansweredCount++;
                } elseif ($ans->is_correct_initial) {
                    $correctCount++;
                } else {
                    $wrongCount++;
                }

                if ($ans && $ans->time_spent_seconds !== null) {
                    $timeSpentTotal += (int) $ans->time_spent_seconds;
                    $answersCountForQ++;
                    $totalTimeAllAnswers += (int) $ans->time_spent_seconds;
                    $totalAnswersCount++;
                }
            }

            $attemptCount = $correctCount + $wrongCount;
            $accuracyRate = $attemptCount > 0 ? round(($correctCount / $attemptCount) * 100, 1) : 0.0;
            $avgTimeQ = $answersCountForQ > 0 ? round($timeSpentTotal / $answersCountForQ, 1) : 0.0;

            $questionStats[$question->id] = [
                'index' => $qIndex + 1,
                'id' => $question->id,
                'prompt' => $question->prompt,
                'support_title' => $question->support_title,
                'correct_count' => $correctCount,
                'wrong_count' => $wrongCount,
                'unanswered_count' => $unansweredCount,
                'accuracy_rate' => $accuracyRate,
                'avg_time_seconds' => $avgTimeQ,
            ];
        }

        // Questão com mais acertos e com mais erros
        $sortedByCorrect = collect($questionStats)->sortByDesc('correct_count')->values();
        $mostCorrectQuestion = $sortedByCorrect->first();

        $sortedByWrong = collect($questionStats)->sortByDesc('wrong_count')->values();
        $mostWrongQuestion = $sortedByWrong->first();

        // Média geral da sala e tempo médio
        $totalQuestions = $questions->count();
        $overallClassroomAccuracy = 0.0;
        $totalScoreSum = 0;

        // Dados por estudante
        $studentRows = [];
        foreach ($attempts as $attempt) {
            $studentCorrectCount = 0;
            $studentTimeTotal = 0;
            $answersPerQuestion = [];

            foreach ($questions as $question) {
                $ans = $attempt->answers->firstWhere('question_id', $question->id);
                $isCorrect = (bool) ($ans?->is_correct_initial ?? false);
                if ($isCorrect) {
                    $studentCorrectCount++;
                }

                $timeSpent = (int) ($ans?->time_spent_seconds ?? 0);
                $studentTimeTotal += $timeSpent;

                $answersPerQuestion[$question->id] = [
                    'answer' => $ans?->initial_answer ?? 'N/A',
                    'is_correct' => $isCorrect,
                    'time_seconds' => $timeSpent,
                ];
            }

            $studentAccuracy = $totalQuestions > 0 ? round(($studentCorrectCount / $totalQuestions) * 100, 1) : 0.0;
            $studentAvgTime = $totalQuestions > 0 ? round($studentTimeTotal / $totalQuestions, 1) : 0.0;
            $totalScoreSum += $studentAccuracy;

            $studentRows[] = [
                'attempt_id' => $attempt->id,
                'student_name' => $attempt->student_name,
                'access_code' => $attempt->access_code,
                'status' => $attempt->status === 'completed' ? 'Concluído' : 'Em Andamento',
                'raw_status' => $attempt->status,
                'started_at' => $attempt->created_at ? $attempt->created_at->format('d/m/Y H:i') : '-',
                'finished_at' => $attempt->completed_at ? $attempt->completed_at->format('d/m/Y H:i') : '-',
                'tab_switches_count' => (int) ($attempt->tab_switches_count ?? 0),
                'correct_count' => $studentCorrectCount,
                'total_questions' => $totalQuestions,
                'accuracy_percentage' => $studentAccuracy,
                'student_avg_time_seconds' => $studentAvgTime,
                'total_time_seconds' => $studentTimeTotal,
                'answers_per_question' => $answersPerQuestion,
            ];
        }

        if ($totalStudents > 0) {
            $overallClassroomAccuracy = round($totalScoreSum / $totalStudents, 1);
        }

        $overallAverageResponseTime = $totalAnswersCount > 0 ? round($totalTimeAllAnswers / $totalAnswersCount, 1) : 0.0;

        return [
            'classroom' => $classroom,
            'assessment' => $assessment,
            'questions' => $questions,
            'total_students' => $totalStudents,
            'completed_count' => $completedCount,
            'classroom_accuracy' => $overallClassroomAccuracy,
            'overall_avg_response_time' => $overallAverageResponseTime,
            'most_correct_question' => $mostCorrectQuestion,
            'most_wrong_question' => $mostWrongQuestion,
            'question_stats' => $questionStats,
            'students' => collect($studentRows)->sortByDesc('accuracy_percentage')->values()->all(),
        ];
    }
}
