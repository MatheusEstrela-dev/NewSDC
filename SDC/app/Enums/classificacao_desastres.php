<?php

$cobrade = [
    [
        "id" => 1,
        "grupo" => "Geológico",
        "subgrupo" => "Terremoto",
        "tipo" => "Tremor de terra",
        "subtipo" => null,
        "definicao" => "Vibrações do terreno que provocam oscilações verticais e horizontais na superfície da Terra (ondas sísmicas). Pode ser natural (tectônica) ou induzido (explosões, injeção profunda de líquidos e gás, extração de fluidos, alívio de carga de minas, enchimento de lagos artificiais)",
        "cobrade" => "1.1.1.1.0",
        "a_definicao" => "Tremor de terra (terremoto)."
    ],
    [
        "id" => 2,
        "grupo" => "Geológico",
        "subgrupo" => "Terremoto",
        "tipo" => "Tsunami",
        "subtipo" => null,
        "definicao" => "Série de ondas geradas por deslocamento de um grande volume de água causado geralmente por terremotos, erupções vulcânicas ou movimentos de massa",
        "cobrade" => "1.1.1.2.0",
        "a_definicao" => "Tsunami causado por terremoto ou erupção."
    ],
    [
        "id" => 3,
        "grupo" => "Geológico",
        "subgrupo" => "Emanação vulcânica",
        "tipo" => null,
        "subtipo" => null,
        "definicao" => "Produtos/materiais vulcânicos lançados na atmosfera a partir de erupções vulcânicas.",
        "cobrade" => "1.1.2.0.0",
        "a_definicao" => "Emanação vulcânica."
    ],
    [
        "id" => 4,
        "grupo" => "Geológico",
        "subgrupo" => "Movimento de massa",
        "tipo" => "Quedas, tombamentos e rolamentos",
        "subtipo" => "Blocos",
        "definicao" => "As quedas de blocos são movimentos rápidos e acontecem quando materiais rochosos diversos e de volumes variáveis se destacam de encostas muito íngremes, num movimento tipo queda livre. Os tombamentos de blocos são movimentos de massa em que ocorre rotação de um bloco de solo ou rocha em torno de um ponto ou abaixo do centro de gravidade da massa desprendida. Rolamentos de blocos são movimentos de blocos rochosos ao longo de encostas, que ocorrem geralmente pela perda de apoio (descalçamento).",
        "cobrade" => "1.1.3.1.1",
        "a_definicao" => "Queda, tombamento ou rolamento de blocos em encostas."
    ],
    [
        "id" => 5,
        "grupo" => "Geológico",
        "subgrupo" => "Movimento de massa",
        "tipo" => "Quedas, tombamentos e rolamentos",
        "subtipo" => "Lascas",
        "definicao" => "As quedas de lascas são movimentos rápidos e acontecem quando fatias delgadas formadas pelos fragmentos de rochas se destacam de encostas muito íngremes, num movimento tipo queda livre.",
        "cobrade" => "1.1.3.1.2",
        "a_definicao" => "Queda de lascas de rocha em encostas."
    ],
    [
        "id" => 6,
        "grupo" => "Geológico",
        "subgrupo" => "Movimento de massa",
        "tipo" => "Quedas, tombamentos e rolamentos",
        "subtipo" => "Matacães",
        "definicao" => "Os rolamentos de matacães são caracterizados por movimentos rápidos e acontecem quando materiais rochosos diversos e de volumes variáveis se destacam de encostas e movimentam-se num plano inclinado.",
        "cobrade" => "1.1.3.1.3",
        "a_definicao" => "Rolamento de matacães em encostas."
    ],
    [
        "id" => 7,
        "grupo" => "Geológico",
        "subgrupo" => "Movimento de massa",
        "tipo" => "Quedas, tombamentos e rolamentos",
        "subtipo" => "Lajes",
        "definicao" => "As quedas de lajes são movimentos rápidos e acontecem quando fragmentos de rochas extensas de superfície mais ou menos plana e de pouca espessura se destacam de encostas muito íngremes, num movimento tipo queda livre.",
        "cobrade" => "1.1.3.1.4",
        "a_definicao" => "Queda de lajes de rocha em encostas."
    ],
    [
        "id" => 8,
        "grupo" => "Geológico",
        "subgrupo" => "Movimento de massa",
        "tipo" => "Deslizamentos",
        "subtipo" => "Deslizamentos de solo e/ou rocha",
        "definicao" => "São movimentos rápidos de solo ou rocha, apresentando superfície de ruptura bem definida, de duração relativamente curta, de massas de terreno geralmente bem definidas quanto ao seu volume, cujo centro de gravidade se desloca para baixo e para fora do talude. Frequentemente, os primeiros sinais desses movimentos são a presença de fissuras.",
        "cobrade" => "1.1.3.2.1",
        "a_definicao" => "Deslizamento de solo ou rocha."
    ],
    //
    [
        "id" => 9,
        "grupo" => "Geológico",
        "subgrupo" => null,
        "tipo" => "Corridas de massa",
        "subtipo" => "Solo/Lama",
        "definicao" => "Ocorrem quando, por índices pluviométricos excepcionais, o solo/lama, misturado com a água, tem comportamento de líquido viscoso, de extenso raio de ação e alto poder destrutivo.",
        "cobrade" => "1.1.3.3.1",
        "a_definicao" => "Corrida de massa: solo/lama."
    ],   
    [
        "id" => 10,
        "grupo" => "Geológico",
        "subgrupo" => null,
        "tipo" => "Corridas de massa",
        "subtipo" => "Rocha/Detrito",
        "definicao" => "Ocorrem quando, por índices pluviométricos excepcionais, rocha/detrito, misturado com a água, tem comportamento de líquido viscoso, de extenso raio de ação e alto poder destrutivo.",
        "cobrade" => "1.1.3.3.2",
        "a_definicao" => "Corrida de massa: rocha/detrito."
    ],   
    [//novo
        "id" => 42,
        "grupo" => "Geológico",
        "subgrupo" => "Movimento de massa",
        "tipo" => "Subsidências e colapsos",
        "subtipo" => null,
        "definicao" => "Afundamento rápido ou gradual do terreno devido ao colapso de cavidades, redução da porosidade do solo ou deformação de material argiloso.",
        "cobrade" => "1.1.3.4.0",
        "a_definicao" => "Subsidência ou colapso do terreno."
    ],  
    [//novo
        "id" => 43,
        "grupo" => "Geológico",
        "subgrupo" => "Erosão",
        "tipo" => "Erosão costeira/Marinha",
        "subtipo" => null,
        "definicao" => "Processo de desgaste (mecânico ou químico) que ocorre ao longo da linha da costa (rochosa ou praia) e se deve à ação das ondas, correntes marrinhas e marés.",
        "cobrade" => "1.1.4.1.0",
        "a_definicao" => "Erosão costeira ou marinha."
    ],    
    [
        "id" => 11,
        "grupo" => "Geológico",
        "subgrupo" => "Erosão",
        "tipo" => "Erosão de margem fluvial",
        "subtipo" => null,
        "definicao" => "Desgaste das encostas dos rios que provoca desmoronamento de barrancos.",
        "cobrade" => "1.1.4.2.0",
        "a_definicao" => "Erosão de margem fluvial."
    ],   
    [
        "id" => 12,
        "grupo" => "Geológico",
        "subgrupo" => "Erosão",
        "tipo" => "Erosão continental",
        "subtipo" => "Laminar",
        "definicao" => "Remoção de uma camada delgada e uniforme do solo superficial provocada por fluxo hídrico não concentrado.",
        "cobrade" => "1.1.4.3.1",
        "a_definicao" => "Erosão continental laminar."
    ],
    [
        "id" => 13,
        "grupo" => "Geológico",
        "subgrupo" => "Erosão",
        "tipo" => "Erosão continental",
        "subtipo" => "Ravinas",
        "definicao" => "Evolução, em tamanho e profundidade, da desagregação e remoção das partículas do solo de sulcos provocada por escoamento hídrico superficial concentrado.",
        "cobrade" => "1.1.4.3.2",
        "a_definicao" => "Erosão continental: ravinas."
    ],   
    [
        "id" => 14,
        "grupo" => "Geológico",
        "subgrupo" => "Erosão",
        "tipo" => "Erosão continental",
        "subtipo" => "Boçorocas",
        "definicao" => "Evolução do processo de ravinamento, em tamanho e profundidade, em que a desagregação e remoção das partículas do solo são provocadas por escoamento hídrico superficial e subsuperficial (escoamento freático) concentrado.",
        "cobrade" => "1.1.4.3.3",
        "a_definicao" => "Erosão continental: boçorocas."
    ], 
    [
        "id" => 15,
        "grupo" => "Hidrológico",
        "subgrupo" => "Inundações",
        "tipo" => null,
        "subtipo" => null,
        "definicao" => "Submersão de áreas fora dos limites normais de um curso de água em zonas que normalmente não se encontram submersas. O transbordamento ocorre de modo gradual, geralmente ocasionado por chuvas prolongadas em áreas de planície.",
        "cobrade" => "1.2.1.0.0",
        "a_definicao" => "Inundação gradual de áreas por rios."
    ], 
    [
        "id" => 16,
        "grupo" => "Hidrológico",
        "subgrupo" => "Enxurradas",
        "tipo" => null,
        "subtipo" => null,
        "definicao" => "Escoamento superficial de alta velocidade e energia, provocado por chuvas intensas e concentradas, normalmente em pequenas bacias de relevo acidentado. Caracterizada pela elevação súbita das vazões de determinada drenagem e transbordamento brusco da calha fluvial. Apresenta grande poder destrutivo.",
        "cobrade" => "1.2.2.0.0",
        "a_definicao" => "Enxurrada: escoamento rápido e destrutivo."
    ], 
    [
        "id" => 17,
        "grupo" => "Hidrológico",
        "subgrupo" => "Alagamentos",
        "tipo" => null,
        "subtipo" => null,
        "definicao" => "Extrapolação da capacidade de escoamento de sistemas de drenagem urbana e consequente acúmulo de água em ruas, calçadas ou outras infraestruturas urbanas, em decorrência de precipitações intensas.",
        "cobrade" => "1.2.3.0.0",
        "a_definicao" => "Alagamento urbano por chuva intensa."
    ], 
    //
    [
        "id" => 18,
        "grupo" => "Meteorológico",
        "subgrupo" => "Sistemas de grande escala/Escala regional",
        "tipo" => "Ciclones",
        "subtipo" => "Ventos costeiros (mobilidade de dunas)",
        "definicao" => "Intensificação dos ventos nas regiões litorâneas, movimentando dunas de areia sobre construções na orla.",
        "cobrade" => "1.3.1.1.1",
        "a_definicao" => "Ciclone: ventos costeiros e dunas."
    ],
    [
        "id" => 19,
        "grupo" => "Meteorológico",
        "subgrupo" => "Sistemas de grande escala/Escala regional",
        "tipo" => "Ciclones",
        "subtipo" => "Marés de tempestade (ressaca)",
        "definicao" => "São ondas violentas que geram uma maior agitação do mar próximo à praia. Ocorrem quando rajadas fortes de vento fazem subir o nível do oceano em mar aberto e essa intensificação das correntes marítimas carrega uma enorme quantidade de água em direção ao litoral. Em consequência, as praias inundam, as ondas se tornam maiores e a orla pode ser devastada alagando ruas e destruindo edificações.",
        "cobrade" => "1.3.1.1.2",
        "a_definicao" => "Ciclone: maré de tempestade (ressaca)."
    ],
    [
        "id" => 20,
        "grupo" => "Meteorológico",
        "subgrupo" => "Sistemas de grande escala/Escala regional",
        "tipo" => "Frentes frias/Zonas de convergência",
        "subtipo" => null,
        "definicao" => "Frente fria é uma massa de ar frio que avança sobre uma região, provocando queda brusca da temperatura local, com período de duração inferior à friagem. Zona de convergência é uma região que está ligada à tempestade causada por uma zona de baixa pressão atmosférica, provocando forte deslocamento de massas de ar, vendavais, chuvas intensas e até queda de granizo.",
        "cobrade" => "1.3.1.2.0",
        "a_definicao" => "Frente fria ou zona de convergência."
    ],
    [
        "id" => 21,
        "grupo" => "Meteorológico",
        "subgrupo" => "Tempestades",
        "tipo" => "Tempestade local/Convectiva",
        "subtipo" => "Tornados",
        "definicao" => "Coluna de ar que gira de forma violenta e muito perigosa, estando em contato com a terra e a base de uma nuvem de grande desenvolvimento vertical. Essa coluna de ar pode percorrer vários quilômetros e deixa um rastro de destruição pelo caminho percorrido.",
        "cobrade" => "1.3.2.1.1",
        "a_definicao" => "Tempestade local: tornado."
    ],
    [
        "id" => 22,
        "grupo" => "Meteorológico",
        "subgrupo" => "Tempestades",
        "tipo" => "Tempestade local/Convectiva",
        "subtipo" => "Tempestade de raios",
        "definicao" => "Tempestade com intensa atividade elétrica no interior das nuvens, com grande desenvolvimento vertical.",
        "cobrade" => "1.3.2.1.2",
        "a_definicao" => "Tempestade local: tempestade de raios."
    ],
    [
        "id" => 23,
        "grupo" => "Meteorológico",
        "subgrupo" => "Tempestades",
        "tipo" => "Tempestade local/Convectiva",
        "subtipo" => "Granizo",
        "definicao" => "Precipitação de pedaços irregulares de gelo.",
        "cobrade" => "1.3.2.1.3",
        "a_definicao" => "Tempestade local: granizo."
    ],
    [
        "id" => 24,
        "grupo" => "Meteorológico",
        "subgrupo" => "Tempestades",
        "tipo" => "Tempestade local/Convectiva",
        "subtipo" => "Chuvas intensas",
        "definicao" => "São chuvas que ocorrem com acumulados significativos, causando múltiplos desastres (ex.: inundações, movimentos de massa, enxurradas, etc.).",
        "cobrade" => "1.3.2.1.4",
        "a_definicao" => "Tempestade local: chuvas intensas."
    ],
    [
        "id" => 25,
        "grupo" => "Meteorológico",
        "subgrupo" => "Tempestades",
        "tipo" => "Tempestade local/Convectiva",
        "subtipo" => "Vendaval",
        "definicao" => "Forte deslocamento de uma massa de ar em uma região.",
        "cobrade" => "1.3.2.1.5",
        "a_definicao" => "Tempestade local: vendaval."
    ],
    [
        "id" => 26,
        "grupo" => "Meteorológico",
        "subgrupo" => "Temperaturas extremas",
        "tipo" => "Onda de calor",
        "subtipo" => null,
        "definicao" => "É um período prolongado de tempo excessivamente quente e desconfortável, onde as temperaturas ficam acima de um valor normal esperado para aquela região em determinado período do ano. Geralmente é adotado um período mínimo de três dias com temperaturas 5°C acima dos valores máximos médios.",
        "cobrade" => "1.3.3.1.0",
        "a_definicao" => "Onda de calor."
    ],
    //
    [
        "id" => 27,
        "grupo" => "Meteorológico",
        "subgrupo" => null,
        "tipo" => "Onda de frio",
        "subtipo" => "Friagem",
        "definicao" => "Período de tempo que dura, no mínimo, de três a quatro dias, e os valores de temperatura mínima do ar ficam abaixo dos valores esperados para determinada região em um período do ano",
        "cobrade" => "1.3.3.2.1",
        "a_definicao" => "Onda de frio: friagem."
    ],
    [
        "id" => 28,
        "grupo" => "Meteorológico",
        "subgrupo" => null,
        "tipo" => "Onda de frio",
        "subtipo" => "Geadas",
        "definicao" => "Formação de uma camada de cristais de gelo na superfície ou na folhagem exposta.",
        "cobrade" => "1.3.3.2.2",
        "a_definicao" => "Onda de frio: geadas."
    ],
    [
        "id" => 29,
        "grupo" => "Climatológico",
        "subgrupo" => "Seca",
        "tipo" => "Estiagem",
        "subtipo" => null,
        "definicao" => "Período prolongado de baixa ou nenhuma pluviosidade, em que a perda de umidade do solo é superior à sua reposição.",
        "cobrade" => "1.4.1.1.0",
        "a_definicao" => "Estiagem prolongada."
    ],
    [
        "id" => 30,
        "grupo" => "Climatológico",
        "subgrupo" => "Seca",
        "tipo" => "Seca",
        "subtipo" => null,
        "definicao" => "A seca é uma estiagem prolongada, durante o período de tempo suficiente para que a falta de precipitação provoque grave desequilíbrio hidrológico.",
        "cobrade" => "1.4.1.2.0",
        "a_definicao" => "Seca prolongada."
    ],
    [
        "id" => 31,
        "grupo" => "Climatológico",
        "subgrupo" => "Seca",
        "tipo" => "Incêndio florestal",
        "subtipo" => "Incêndios em parques, áreas de proteção ambiental e áreas de preservação permanente nacionais, estaduais ou municipais.",
        "definicao" => "Propagação de fogo sem controle, em qualquer tipo de vegetação situada em áreas legalmente protegidas.",
        "cobrade" => "1.4.1.3.1",
        "a_definicao" => "Incêndio florestal em áreas protegidas."
    ],
    [
        "id" => 32,
        "grupo" => "Climatológico",
        "subgrupo" => "Seca",
        "tipo" => "Incêndio florestal",
        "subtipo" => "Incêndios em áreas não protegidas, com reflexos na qualidade do ar",
        "definicao" => "Propagação de fogo sem controle, em qualquer tipo de vegetação que não se encontre em áreas sob proteção legal, acarretando queda da qualidade do ar.",
        "cobrade" => "1.4.1.3.2",
        "a_definicao" => "Incêndio florestal em áreas não protegidas."
    ],
    [
        "id" => 33,
        "grupo" => "Climatológico",
        "subgrupo" => "Seca",
        "tipo" => "Baixa umidade do ar",
        "subtipo" => null,
        "definicao" => "Queda da taxa de vapor de água suspensa na atmosfera para níveis abaixo de 20%.",
        "cobrade" => "1.4.1.4.0",
        "a_definicao" => "Baixa umidade do ar."
    ],
    [
        "id" => 34,
        "grupo" => "Biológico",
        "subgrupo" => "Epidemias",
        "tipo" => "Doenças infecciosas virais",
        "subtipo" => null,
        "definicao" => "Aumento brusco, significativo e transitório da ocorrência de doenças infecciosas geradas por vírus.",
        "cobrade" => "1.5.1.1.0",
        "a_definicao" => "Epidemia de doenças virais."
    ],
    [
        "id" => 35,
        "grupo" => "Biológico",
        "subgrupo" => "Epidemias",
        "tipo" => "Doenças infecciosas bacterianas",
        "subtipo" => null,
        "definicao" => "Aumento brusco, significativo e transitório da ocorrência de doenças infecciosas geradas por bactérias.",
        "cobrade" => "1.5.1.2.0",
        "a_definicao" => "Epidemia de doenças bacterianas."
    ],
    [
        "id" => 36,
        "grupo" => "Biológico",
        "subgrupo" => "Epidemias",
        "tipo" => "Doenças infecciosas parasíticas",
        "subtipo" => null,
        "definicao" => "Aumento brusco, significativo e transitório da ocorrência de doenças infecciosas geradas por parasitas.",
        "cobrade" => "1.5.1.3.0",
        "a_definicao" => "Epidemia de doenças parasíticas."
    ],
    [
        "id" => 37,
        "grupo" => "Biológico",
        "subgrupo" => "Epidemias",
        "tipo" => "Doenças infecciosas fúngicas",
        "subtipo" => null,
        "definicao" => "Aumento brusco, significativo e transitório da ocorrência de doenças infecciosas geradas por fungos.",
        "cobrade" => "1.5.1.4.0",
        "a_definicao" => "Epidemia de doenças fúngicas."
    ],
    [
        "id" => 38,
        "grupo" => "Geológico",
        "subgrupo" => "Infestações/Pragas",
        "tipo" => "Infestações de animais",
        "subtipo" => null,
        "definicao" => "Infestações por animais que alterem o equilíbrio ecológico de uma região, bacia hidrográfica ou bioma afetado por suas ações predatórias.",
        "cobrade" => "1.5.2.1.0",
        "a_definicao" => "Infestação de animais que afeta o equilíbrio ecológico."
    ],
    [
        "id" => 39,
        "grupo" => "Geológico",
        "subgrupo" => "Infestações/Pragas",
        "tipo" => "Infestações de algas",
        "subtipo" => "Marés vermelhas",
        "definicao" => "Aglomeração de cianobactérias em reservatórios receptores de descargas de dejetos domésticos, industriais e/ou agrícolas, provocando alterações das propriedades físicas, químicas ou biológicas da água.",
        "cobrade" => "1.5.2.2.1",
        "a_definicao" => "Infestação de algas (marés vermelhas) em reservatórios, alterando a qualidade da água."
    ],
    [
        "id" => 40,
        "grupo" => "Geológico",
        "subgrupo" => "Infestações/Pragas",
        "tipo" => "Infestações de algas",
        "subtipo" => "Cianobactérias em reservatórios",
        "definicao" => "Aglomeração de cianobactérias em reservatórios receptores de descargas de dejetos domésticos, industriais e/ou agrícolas, provocando alterações das propriedades físicas, químicas ou biológicas da água.",
        "cobrade" => "1.5.2.2.2",
        "a_definicao" => "Infestação de cianobactérias em reservatórios, alterando a qualidade da água."
    ],
    [
        "id" => 41,
        "grupo" => "Geológico",
        "subgrupo" => "Infestações/Pragas",
        "tipo" => "Outras infestações",
        "subtipo" => null,
        "definicao" => "Infestações que alterem o equilíbrio ecológico de uma região, bacia hidrográfica ou bioma afetado por suas ações predatórias.",
        "cobrade" => "1.5.2.3.0",
        "a_definicao" => "Outras infestações que afetam o equilíbrio ecológico."
    ],
    //
    [
        "id" => 44,
        "grupo" => "Desastres relacionados a substâncias radioativas",
        "subgrupo" => "Desastres siderais com riscos radioativos",
        "tipo" => "Queda de satélite (radionuclídeos)",
        "subtipo" => null,
        "definicao" => "Queda de satélites que possuem, na sua composição, motores ou corpos radioativos, podendo ocasionar a liberação deste material.",
        "cobrade" => "2.1.1.1.0",
        "a_definicao" => "Queda de satélite com risco radioativo."
    ],
    [
        "id" => 45,
        "grupo" => "Desastres relacionados a substâncias radioativas",
        "subgrupo" => "Desastres com substâncias e equipamentos radioativos de uso em pesquisas, indústrias e usinas nucleares",
        "tipo" => "Fontes radioativas em processos de produção",
        "subtipo" => null,
        "definicao" => "Escapamento acidental de radiação que excede os níveis de segurança estabelecidos na norma NN 3.01/006:2011 da CNEN.",
        "cobrade" => "2.1.2.1.0",
        "a_definicao" => "Vazamento de radiação em processos industriais."
    ],
    [
        "id" => 46,
        "grupo" => "Desastres relacionados a substâncias radioativas",
        "subgrupo" => "Desastres relacionados com riscos de intensa poluição ambiental provocada por resíduos radioativos",
        "tipo" => "Outras fontes de liberação de radionuclídeos para o meio ambiente",
        "subtipo" => null,
        "definicao" => "Escapamento acidental ou não acidental de radiação originária de fontes radioativas diversas e que excede os níveis de segurança estabelecidos na norma NN 3.01/006:2011 e NN 3.01/011:2011 da CNEN.",
        "cobrade" => "2.1.3.1.0",
        "a_definicao" => "Liberação de radionuclídeos para o meio ambiente."
    ], 
    [
        "id" => 47,
        "grupo" => "Desastres relacionados a produtos perigosos",
        "subgrupo" => "Desastres em plantas e distritos industriais, parques e armazenamentos com extravasamento de produtos perigosos",
        "tipo" => "Liberação de produtos químicos para a atmosfera causada por explosão ou incêndio",
        "subtipo" => null,
        "definicao" => "Liberação de produtos químicos diversos para o ambiente, provocada por explosão/incêndio em platans industriais ou outros sítios.",
        "cobrade" => "2.2.1.1.0",
        "a_definicao" => "Explosão ou incêndio com liberação de produtos químicos."
    ],   
    [
        "id" => 48,
        "grupo" => "Desastres relacionados a produtos perigosos",
        "subgrupo" => "Desastres relacionados à contaminação da água",
        "tipo" => "Liberação de produtos químicos nos sistemas de água potável",
        "subtipo" => null,
        "definicao" => "Derramento de produtos químicos diversos em um sistema de abastecimento de água potável, que pode causar alterações nas qualidades físicas, químicas, biológicas",
        "cobrade" => "2.2.2.1.0",
        "a_definicao" => "Contaminação de água potável por produtos químicos."
    ],
    [
        "id" => 49,
        "grupo" => "Desastres relacionados a produtos perigosos",
        "subgrupo" => "Desastres relacionados à contaminação da água",
        "tipo" => "Derramamento de produtos químicos em ambiente lacustre, fluvial, marinho e aquífero",
        "subtipo" => null,
        "definicao" => "Derramamento de produtos químicos diversos em lagos, rios, mar e reservatórios subterrâneos de água, que pode causar alterações nas qualidades físicas, químicas e biológicas.",
        "cobrade" => "2.2.2.2.0",
        "a_definicao" => "Derramamento químico em ambientes aquáticos."
    ],
    [
        "id" => 50,
        "grupo" => "Desastres relacionados a produtos perigosos",
        "subgrupo" => "Desastres relacionados a conflitos bélicos",
        "tipo" => "Liberação de produtos químicos e contaminação como consequência de ações militares",
        "subtipo" => null,
        "definicao" => "Agente de natureza nuclear ou radiológica, química ou biológica, considerado como perigoso, e que pode ser utilizado intencionalmente por terroristas ou grupamentos militares em atentados ou em caso de guerra.",
        "cobrade" => "2.2.3.1.0",
        "a_definicao" => "Contaminação química por ações militares."
    ],
    [
        "id" => 51,
        "grupo" => "Desastres relacionados a produtos perigosos",
        "subgrupo" => "Desastres relacionados a transporte de produtos perigosos",
        "tipo" => "Transporte rodoviário",
        "subtipo" => null,
        "definicao" => "Extravasamento de produtos perigosos transportados no modal rodoviário.",
        "cobrade" => "2.2.4.1.0",
        "a_definicao" => "Acidente com produtos perigosos no transporte rodoviário."
    ],
    [
        "id" => 52,
        "grupo" => "Desastres relacionados a produtos perigosos",
        "subgrupo" => "Desastres relacionados a transporte de produtos perigosos",
        "tipo" => "Transporte ferroviário",
        "subtipo" => null,
        "definicao" => "Extravasamento de produtos perigosos transportados no modal ferroviário.",
        "cobrade" => "2.2.4.2.0",
        "a_definicao" => "Acidente com produtos perigosos no transporte ferroviário."
    ],
    [
        "id" => 53,
        "grupo" => "Desastres relacionados a produtos perigosos",
        "subgrupo" => "Desastres relacionados a transporte de produtos perigosos",
        "tipo" => "Transporte aéreo",
        "subtipo" => null,
        "definicao" => "Extravasamento de produtos perigosos transportados no modal aéreo.",
        "cobrade" => "2.2.4.3.0",
        "a_definicao" => "Acidente com produtos perigosos no transporte aéreo."
    ],
    [
        "id" => 54,
        "grupo" => "Desastres relacionados a produtos perigosos",
        "subgrupo" => "Desastres relacionados a transporte de produtos perigosos",
        "tipo" => "Transporte dutoviário",
        "subtipo" => null,
        "definicao" => "Extravasamento de produtos perigosos transportados no modal dutoviário.",
        "cobrade" => "2.2.4.4.0",
        "a_definicao" => "Acidente com produtos perigosos no transporte dutoviário."
    ],
    [
        "id" => 55,
        "grupo" => "Desastres relacionados a produtos perigosos",
        "subgrupo" => "Desastres relacionados a transporte de produtos perigosos",
        "tipo" => "Transporte marítimo",
        "subtipo" => null,
        "definicao" => "Extravasamento de produtos perigosos transportados no modal marítimo.",
        "cobrade" => "2.2.4.5.0",
        "a_definicao" => "Acidente com produtos perigosos no transporte marítimo."
    ],
    [
        "id" => 56,
        "grupo" => "Desastres relacionados a produtos perigosos",
        "subgrupo" => "Desastres relacionados a transporte de produtos perigosos",
        "tipo" => "Transporte aquaviário",
        "subtipo" => null,
        "definicao" => "Extravasamento de produtos perigosos transportados no modal aquaviário.",
        "cobrade" => "2.2.4.6.0",
        "a_definicao" => "Acidente com produtos perigosos no transporte aquaviário."
    ],
    [
        "id" => 57,
        "grupo" => "Desastres relacionados a incêndios urbanos",
        "subgrupo" => "Incêndios urbanos",
        "tipo" => "Incêndios em plantas e distritos industriais, parques e depósitos",
        "subtipo" => null,
        "definicao" => "Propagação descontrolada do fogo em platans e distrítos industriais, parques e depósitos.",
        "cobrade" => "2.3.1.1.0",
        "a_definicao" => "Incêndio urbano em plantas industriais, parques ou depósitos."
    ],
    [
        "id" => 58,
        "grupo" => "Desastres relacionados a incêndios urbanos",
        "subgrupo" => "Incêndios urbanos",
        "tipo" => "Incêndios em aglomerados residenciais",
        "subtipo" => null,
        "definicao" => "Propagação descontrolada do fogo em conjuntos habitacionais de grande densidade.",
        "cobrade" => "2.3.1.2.0",
        "a_definicao" => "Incêndio urbano em aglomerados residenciais."
    ],
    [
        "id" => 59,
        "grupo" => "Desastres relacionados a obras civis",
        "subgrupo" => "Colapso de edificações",
        "tipo" => null,
        "subtipo" => null,
        "definicao" => "Queda de estrutura civil.",
        "cobrade" => "2.4.1.0.0",
        "a_definicao" => "Queda de estrutura civil."
    ],
    [
        "id" => 60,
        "grupo" => "Desastres relacionados a obras civis",
        "subgrupo" => "Rompimento/colapso de barragens",
        "tipo" => null,
        "subtipo" => null,
        "definicao" => "Rompimento ou colapso de barragens.",
        "cobrade" => "2.4.2.0.0",
        "a_definicao" => "Rompimento ou colapso de barragens."
    ],
    //
    [
        "id" => 61,
        "grupo" => "Desastres relacionados a transporte de passageiro e cargas não perigosas",
        "subgrupo" => "Transporte rodoviário",
        "tipo" => null,
        "subtipo" => null,
        "definicao" => "Acidente no modal rodoviário envolvendo o transporte de passageiros ou cargas não perigosas.",
        "cobrade" => "2.5.1.0.0",
        "a_definicao" => "Acidente rodoviário com passageiros ou cargas não perigosas."
    ],
    [
        "id" => 62,
        "grupo" => "Desastres relacionados a transporte de passageiro e cargas não perigosas",
        "subgrupo" => "Transporte ferroviário",
        "tipo" => null,
        "subtipo" => null,
        "definicao" => "Acidente com a participação direta de veículo ferroviário de transporte de passageiros ou cargas não perigosas.",
        "cobrade" => "2.5.2.0.0",
        "a_definicao" => "Acidente ferroviário com passageiros ou cargas não perigosas."
    ],
    [
        "id" => 63,
        "grupo" => "Desastres relacionados a transporte de passageiro e cargas não perigosas",
        "subgrupo" => "Transporte aéreo",
        "tipo" => null,
        "subtipo" => null,
        "definicao" => "Acidente no modal aéreo envolvendo o transporte de passageiros ou cargas não perigosas.",
        "cobrade" => "2.5.3.0.0",
        "a_definicao" => "Acidente aéreo com passageiros ou cargas não perigosas."
    ],
    [
        "id" => 64,
        "grupo" => "Desastres relacionados a transporte de passageiro e cargas não perigosas",
        "subgrupo" => "Transporte marítimo",
        "tipo" => null,
        "subtipo" => null,
        "definicao" => "Acidente com embarcações marítimas destinadas ao transporte de passageiros e cargas não perigosas.",
        "cobrade" => "2.5.4.0.0",
        "a_definicao" => "Acidente marítimo com passageiros ou cargas não perigosas."
    ],
    [
        "id" => 65,
        "grupo" => "Desastres relacionados a transporte de passageiro e cargas não perigosas",
        "subgrupo" => "Transporte aquaviário",
        "tipo" => null,
        "subtipo" => null,
        "definicao" => "Acidente com embarcações destinadas ao transporte de passageiros e cargas não pergiosas.",
        "cobrade" => "2.5.5.0.0",
        "a_definicao" => "Acidente aquaviário com passageiros ou cargas não perigosas."
    ],
];

return $cobrade;
