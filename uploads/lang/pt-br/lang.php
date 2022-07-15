<?php return [
    'plugin' => [
        'name' => 'Uploads',
        'description' => ''
    ],
    'components' => [
        'uploader' => [
            'group_uploader'    => 'Configurações do Uploader',
            'uploader_maxsize'  => ['title' => 'Limite do Tamanho do Arquivo' , 'description' => 'O tamanho máximo de arquivo que pode ser carregado em megabytes'],
            'uploader_types'    => ['title' => 'Tipos de Arquivos Permitidos' , 'description' => 'Extensões de arquivo permitido ou asterisco (*) para todos os tipos (adicionar uma extensão por linha)'],
        ],
    ],
];