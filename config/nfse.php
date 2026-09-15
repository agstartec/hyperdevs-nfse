<?php

return [
    // 1 = Produção, 2 = Homologação
    'tp_amb' => (int) env('NFSE_TP_AMB', 2),

    // Identificador da prefeitura (ver Storage/prefeituras.json)
    'prefeitura' => env('NFSE_PREFEITURA'),

    // sefin ou adn
    'tipo_api' => env('NFSE_TIPO_API', 'sefin'),
];
