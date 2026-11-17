@props(['targetId' => '', 'class' => ''])

<div class="math-symbols-toolbar flex flex-wrap items-center gap-1.5 p-2 bg-royal-purple-50/70 border border-royal-purple-200 rounded-xl text-xs {{ $class }}"
     data-math-target="{{ $targetId }}">
    <span class="text-[10px] font-extrabold font-heading uppercase text-royal-purple-900 tracking-wider mr-1 flex items-center gap-1">
        <span>Símbolos Matemáticos:</span>
    </span>

    <button type="button" data-insert-math="\frac{a}{b}" title="Fração (a/b)"
            class="cursor-pointer px-2 py-1 rounded bg-white hover:bg-royal-purple-100 border border-royal-purple-200 font-mono text-royal-purple-950 font-bold transition shadow-2xs">
        a/b
    </button>
    <button type="button" data-insert-math="\sqrt{x}" title="Raiz quadrada"
            class="cursor-pointer px-2 py-1 rounded bg-white hover:bg-royal-purple-100 border border-royal-purple-200 font-mono text-royal-purple-950 font-bold transition shadow-2xs">
        √x
    </button>
    <button type="button" data-insert-math="\sqrt[n]{x}" title="Raiz n-ésima"
            class="cursor-pointer px-2 py-1 rounded bg-white hover:bg-royal-purple-100 border border-royal-purple-200 font-mono text-royal-purple-950 font-bold transition shadow-2xs">
        ⁿ√x
    </button>
    <button type="button" data-insert-math="^{2}" title="Expoente ao quadrado"
            class="cursor-pointer px-2 py-1 rounded bg-white hover:bg-royal-purple-100 border border-royal-purple-200 font-mono text-royal-purple-950 font-bold transition shadow-2xs">
        x²
    </button>
    <button type="button" data-insert-math="^{n}" title="Expoente n"
            class="cursor-pointer px-2 py-1 rounded bg-white hover:bg-royal-purple-100 border border-royal-purple-200 font-mono text-royal-purple-950 font-bold transition shadow-2xs">
        xⁿ
    </button>
    <button type="button" data-insert-math="_{n}" title="Subscrito n"
            class="cursor-pointer px-2 py-1 rounded bg-white hover:bg-royal-purple-100 border border-royal-purple-200 font-mono text-royal-purple-950 font-bold transition shadow-2xs">
        xₙ
    </button>
    <button type="button" data-insert-math="\pm" title="Mais ou menos"
            class="cursor-pointer px-2 py-1 rounded bg-white hover:bg-royal-purple-100 border border-royal-purple-200 font-mono text-royal-purple-950 font-bold transition shadow-2xs">
        ±
    </button>
    <button type="button" data-insert-math="\times" title="Multiplicação"
            class="cursor-pointer px-2 py-1 rounded bg-white hover:bg-royal-purple-100 border border-royal-purple-200 font-mono text-royal-purple-950 font-bold transition shadow-2xs">
        ×
    </button>
    <button type="button" data-insert-math="\div" title="Divisão"
            class="cursor-pointer px-2 py-1 rounded bg-white hover:bg-royal-purple-100 border border-royal-purple-200 font-mono text-royal-purple-950 font-bold transition shadow-2xs">
        ÷
    </button>
    <button type="button" data-insert-math="\pi" title="Pi"
            class="cursor-pointer px-2 py-1 rounded bg-white hover:bg-royal-purple-100 border border-royal-purple-200 font-mono text-royal-purple-950 font-bold transition shadow-2xs">
        π
    </button>
    <button type="button" data-insert-math="\le" title="Menor ou igual"
            class="cursor-pointer px-2 py-1 rounded bg-white hover:bg-royal-purple-100 border border-royal-purple-200 font-mono text-royal-purple-950 font-bold transition shadow-2xs">
        ≤
    </button>
    <button type="button" data-insert-math="\ge" title="Maior ou igual"
            class="cursor-pointer px-2 py-1 rounded bg-white hover:bg-royal-purple-100 border border-royal-purple-200 font-mono text-royal-purple-950 font-bold transition shadow-2xs">
        ≥
    </button>
    <button type="button" data-insert-math="\ne" title="Diferente"
            class="cursor-pointer px-2 py-1 rounded bg-white hover:bg-royal-purple-100 border border-royal-purple-200 font-mono text-royal-purple-950 font-bold transition shadow-2xs">
        ≠
    </button>
    <button type="button" data-insert-math="\approx" title="Aproximadamente"
            class="cursor-pointer px-2 py-1 rounded bg-white hover:bg-royal-purple-100 border border-royal-purple-200 font-mono text-royal-purple-950 font-bold transition shadow-2xs">
        ≈
    </button>
    <button type="button" data-insert-math="\infty" title="Infinito"
            class="cursor-pointer px-2 py-1 rounded bg-white hover:bg-royal-purple-100 border border-royal-purple-200 font-mono text-royal-purple-950 font-bold transition shadow-2xs">
        ∞
    </button>
    <button type="button" data-insert-math="\left( \right)" title="Parênteses automáticos"
            class="cursor-pointer px-2 py-1 rounded bg-white hover:bg-royal-purple-100 border border-royal-purple-200 font-mono text-royal-purple-950 font-bold transition shadow-2xs">
        ( )
    </button>
</div>
