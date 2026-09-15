<?php

return [
    // 1 = Produção, 2 = Homologação
    'tp_amb' => (int) env('NFSE_TP_AMB', 2),

    // Identificador da prefeitura (ver Storage/prefeituras.json)
    'prefeitura' => env('NFSE_PREFEITURA'),

    // sefin ou adn
    'tipo_api' => env('NFSE_TIPO_API', 'sefin'),

    'certificado' => [
        // Caminho absoluto para o arquivo .pfx/.p12 do certificado A1
        'path' => env('NFSE_CERTIFICADO_PATH'),
        'senha' => env('NFSE_CERTIFICADO_SENHA'),
    ],
];
