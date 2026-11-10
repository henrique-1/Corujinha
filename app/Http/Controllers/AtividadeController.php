<?php

namespace App\Http\Controllers;

use App\Data\Atividade7AnoData;
use Illuminate\Contracts\View\View;

class AtividadeController extends Controller
{
    /**
     * Exibe a atividade interativa do 7º ano.
     */
    public function index(): View
    {
        $questions = Atividade7AnoData::all();

        return view('atividade.index', compact('questions'));
    }
}
