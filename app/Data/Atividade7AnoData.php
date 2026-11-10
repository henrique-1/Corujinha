<?php

namespace App\Data;

final class Atividade7AnoData
{
    /**
     * Retorna a lista de questoes da atividade interativa do 7o ano.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            [
                'focus' => 'Efeito do adjetivo',
                'source_title' => 'Tarzan é livro surpreendente que vai além dos filmes',
                'stimulus' => '[...] No geral, Tarzan mostrou ser uma grata surpresa. Suas páginas possuem reflexões interessantíssimas e que mostram o quão pouco aproveitada a história foi até agora. Além disso, vale ressaltar que o final é sensacional, trazendo uma curiosidade infinita ao leitor quanto às continuações da saga. Recomendo veementemente a leitura deste clássico, que abrirá a mente de todos sobre o incrível personagem que é Tarzan.',
                'prompt' => 'Na manchete, o adjetivo “surpreendente” contribui para',
                'options' => [
                    'indicar que os acontecimentos do livro ocorreram de maneira inesperada na vida real.',
                    'expressar uma avaliação positiva da obra, sugerindo que ela supera as expectativas do leitor.',
                    'informar que a leitura exige conhecimentos prévios sobre todos os filmes de Tarzan.',
                    'afirmar que os filmes são mais interessantes e completos do que o livro.',
                    'descrever uma característica física da personagem principal.',
                ],
            ],
            [
                'focus' => 'Finalidade do texto',
                'source_title' => 'Coraline',
                'stimulus' => '[...] O livro “Coraline” acompanha a história de uma garotinha de onze anos que acaba de se mudar para uma casa antiga junto com seus pais. Logo de início, a narrativa já nos conta que a família não dá a devida atenção para a criança. Certo dia, Coraline tira o dia para explorar a casa nova, descobrindo que seus vizinhos são bastante excêntricos. Nessa mesma experiência, a menina também descobre que existe uma porta secreta em uma das salas da casa nova. No entanto, quando a abre, encontra apenas uma parede de tijolos. Ou seja, não havia nada de interessante por ali. Certa noite, Coraline é acordada por um barulho estranho, percebendo que havia uma presença na casa. Curiosa, a garota começa a perseguir os rastros, que a levam até a porta misteriosa que tinha achado mais cedo. Contudo, ao abrir novamente, vê que a parede não está mais lá. Agora, o que havia atrás da porta era um túnel que levava para outro mundo, que era exatamente como o de Coraline, só que bem mais interessante e empolgante. A garota ainda encontra seus pais nesse mundo, mas os dois estão totalmente diferentes. [...]',
                'prompt' => 'O texto apresentado tem como finalidade',
                'options' => [
                    'narrar o mistério de uma porta secreta que leva a outro mundo, criando uma atmosfera de suspense.',
                    'contar a história de uma situação cômica e peculiar sobre uma experiência com vizinhos excêntricos.',
                    'instigar o leitor a refletir sobre os efeitos de uma mudança para uma casa antiga na vida familiar.',
                    'fazer o leitor pensar sobre os impactos da falta de atenção dos pais no desenvolvimento das crianças.',
                    'despertar o interesse pela história de uma menina em outro mundo, explorando as descobertas e mistérios.',
                ],
            ],
            [
                'focus' => 'Características da notícia',
                'source_title' => 'Simulado de incêndio mobilizou estudantes e bombeiros no Sesi de Santa Cruz do Rio Pardo',
                'stimulus' => "Na manhã da última segunda-feira, 1º de setembro, a Escola Sesi de Santa Cruz do Rio Pardo realizou um simulado de incêndio ocasionado por uma fictícia explosão na cozinha, em parceria com a ETEC, que trouxe também a representação de vítimas para tornar o exercício ainda mais próximo da realidade.\nA atividade integrou a rotina de segurança da unidade e teve como objetivo treinar a todos na evacuação do prédio em caso de ocorrência real, além de preparar colaboradores e brigadistas para o correto cumprimento dos protocolos de emergência.\n[...]",
                'prompt' => 'Uma característica típica de uma notícia presente no texto apresentado é o(a)',
                'options' => [
                    'emprego de instruções específicas, indicando como alguém deve agir diante de um incêndio.',
                    'estrutura em forma de depoimento, expondo a experiência sob a perspectiva de um especialista.',
                    'utilização de linguagem figurada, intensificando o impacto emocional do relato do diretor escolar.',
                    'registro sequencial dos acontecimentos, detalhando quais etapas foram seguidas pelos estudantes.',
                    'apresentação das informações essenciais sobre o fato, descrevendo como a simulação aconteceu.',
                ],
            ],
            [
                'focus' => 'Discurso direto',
                'source_title' => 'Luccas Neto fala sobre vídeo em Libras',
                'stimulus' => "O youtuber Luccas Neto lançou seu primeiro vídeo totalmente em Libras no YouTube. [...]\n“Foi uma experiência muito especial e emocionante. A gente já vinha inserindo trechos em Libras em alguns vídeos e, além disso, temos o canal Luccas Toon – Libras, onde todos os conteúdos do canal original são republicados com interpretação de Libras. Esse canal recentemente alcançou a marca incrível de 4 milhões de inscritos, o que mostra a força e a importância deste trabalho”. [...]",
                'prompt' => 'No texto apresentado, o uso de discurso direto pode ser identificado a partir do(a)',
                'options' => [
                    'explicação sobre a comunicação entre os personagens do canal do youtuber.',
                    'resumo escrito pelo jornalista para descrever os sentimentos do youtuber.',
                    'relato de todas as ações feitas pelo youtuber promovendo acessibilidade.',
                    'reprodução literal da fala do youtuber sobre a gravação de seu vídeo.',
                    'utilização de terceira pessoa para recontar o que o youtuber disse.',
                ],
            ],
            [
                'focus' => 'Argumentação e ponto de vista',
                'source_title' => 'Pesquisadora analisa a desinformação nas redes sociais',
                'stimulus' => "De que forma a sua pesquisa pode contribuir para a sociedade?\nEntendemos que a proliferação massiva das desinformações é um problema social complexo. Não existe um único motivo para que as pessoas acreditem e propaguem desinformação. As estratégias de combate a esse problema perpassam diversos níveis: educacionais, sociais, políticos e individuais. Consideramos que o desenvolvimento de um letramento científico, político e midiático, para que indivíduos sejam capazes de realizar análises críticas das informações consumidas nas redes sociais, é uma importante barreira para frear a propagação de desinformação. [...]",
                'prompt' => 'No trecho da entrevista apresentada, a pesquisadora argumenta que a melhor forma de combater a desinformação é',
                'options' => [
                    'realizar campanhas de conscientização nas redes sociais, incentivando os usuários a verificar as informações antes de compartilhá-las.',
                    'priorizar a divulgação de conteúdos produzidos por especialistas, garantindo que as informações disponibilizadas sejam confiáveis.',
                    'promover a formação da população, possibilitando que as pessoas analisem de forma crítica as informações consumidas nas redes sociais.',
                    'reduzir o acesso a publicações nas plataformas digitais, limitando assim a circulação de textos e a propagação de boatos.',
                    'criar leis mais rígidas, possibilitando que medidas jurídicas impeçam o compartilhamento de conteúdo enganoso.',
                ],
            ],
            [
                'focus' => 'Confiabilidade da informação',
                'source_title' => 'Desmatamento na Mata Atlântica cai 59% entre janeiro e agosto de 2023',
                'stimulus' => 'Queda foi observada em todos os 15 estados que compõem o bioma; dados são do Sistema de Alertas de Desmatamento Mata Atlântica.',
                'prompt' => 'Entre os elementos que indicam confiabilidade nessa manchete jornalística, está a',
                'options' => [
                    'exposição do intervalo de meses referido.',
                    'referência a dados de um sistema de monitoramento.',
                    'numeração dos estados que compõem o bioma citado.',
                    'indicação do ano em que houve queda no desmatamento.',
                    'informação de queda no desmatamento da Mata Atlântica.',
                ],
            ],
            [
                'focus' => 'Conjunções e relações de sentido',
                'source_title' => 'Brasil melhora acesso de jovens ao ensino médio na idade certa, mas fica longe de meta estabelecida em 2014',
                'stimulus' => 'Plano Nacional de Educação previa que o país chegasse em 2024 com 85% de adolescentes de 15 a 17 anos cursando a etapa escolar adequada.',
                'prompt' => 'No título da notícia, a conjunção “mas” ajuda na construção de sentido da manchete, pois introduz um(a)',
                'options' => [
                    'contraste entre o avanço mencionado e o fato de o país permanecer distante da meta estabelecida.',
                    'consequência esperada a partir da melhoria no acesso de jovens ao ensino médio na idade adequada.',
                    'explicação sobre a importância das metas educacionais definidas no Plano Nacional de Educação.',
                    'justificativa para o fato de o Brasil ainda estar distante dos índices que eram previstos para 2024.',
                    'exemplo complementar que reforça o resultado positivo apresentado na primeira parte do título.',
                ],
            ],
            [
                'focus' => 'Efeito da pontuação',
                'source_title' => 'O Fantástico mistério de Feiurinha',
                'stimulus' => "[...]\nBranca: Chapeuzinho Vermelho! Querida! Há quanto tempo! Como vai a Vovozinha?\nChapéu: Branca!\nAs duas dão-se três beijinhos nas faces.\nChapéu: Um… dois… e três! Pra ver se eu caso, Branca! Ai, ai! Sou uma das poucas neste País das Fadas que não é princesa! Também… você sabe, não é?\n[...]",
                'prompt' => 'No trecho em destaque, o uso das reticências cumpre a função de',
                'options' => [
                    'sugerir que a personagem prefere evitar finalizar a frase por estar irritada.',
                    'indicar que a personagem está pensando antes de continuar sua fala.',
                    'mostrar que a personagem esqueceu completamente o que ia dizer.',
                    'exibir uma interrupção repentina causada por outra personagem.',
                    'marcar a dúvida expressa pela personagem na sequência.',
                ],
            ],
            [
                'focus' => 'Sujeito da oração',
                'source_title' => 'Imprensa argentina repercute goleada sobre o Brasil: “Baile histórico”',
                'stimulus' => 'Principais veículos do país exaltaram a atuação da Seleção Argentina, que garantiu a classificação para a Copa do Mundo de 2026.',
                'prompt' => 'Na manchete apresentada, o termo que exerce a função de sujeito é',
                'options' => [
                    '“Imprensa argentina”, pois indica quem pratica a ação do verbo “repercute”.',
                    '“repercute”, pois indica a ação expressa na oração em relação a “goleada”.',
                    '“Brasil”, pois indica o termo que complementa o sentido de “goleada”.',
                    '“Baile histórico”, pois indica o termo que caracteriza o fato “goleada”.',
                    '“goleada”, pois indica o fato relacionado à ação do verbo “repercute”.',
                ],
            ],
            [
                'focus' => 'Linguagem verbal e visual',
                'source_title' => null,
                'image' => '/images/q10_desmatar.png',
                'prompt' => 'A combinação entre os recursos verbais e visuais da campanha produz um efeito de',
                'options' => [
                    'conscientização sobre diferentes formas de exploração da natureza.',
                    'previsão do futuro das florestas caso o desmatamento continue.',
                    'crítica à atuação das leis ambientais na proteção das florestas.',
                    'denúncia da falta de divulgação sobre a destruição ambiental.',
                    'sensibilização apelativa à responsabilidade humana.',
                ],
            ],
        ];
    }
}
