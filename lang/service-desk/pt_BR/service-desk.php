<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Status dos Tickets
    |--------------------------------------------------------------------------
    */
    'status' => [
        'open' => 'Aberto',
        'pending' => 'Pendente',
        'in_progress' => 'Em Andamento',
        'on_hold' => 'Em Espera',
        'resolved' => 'Resolvido',
        'closed' => 'Fechado',
    ],

    /*
    |--------------------------------------------------------------------------
    | Prioridades dos Tickets
    |--------------------------------------------------------------------------
    */
    'priority' => [
        'low' => 'Baixa',
        'medium' => 'Media',
        'high' => 'Alta',
        'urgent' => 'Urgente',
    ],

    /*
    |--------------------------------------------------------------------------
    | Origens dos Tickets
    |--------------------------------------------------------------------------
    */
    'source' => [
        'web' => 'Web',
        'email' => 'E-mail',
        'api' => 'API',
        'service_request' => 'Requisicao de Servico',
        'phone' => 'Telefone',
        'chat' => 'Chat',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de Comentario
    |--------------------------------------------------------------------------
    */
    'comment_type' => [
        'reply' => 'Resposta',
        'note' => 'Nota Interna',
        'system' => 'Sistema',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de Item de Inventario
    |--------------------------------------------------------------------------
    */
    'inventory_item_type' => [
        'asset' => 'Ativo',
        'stock' => 'Estoque',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status de Item de Inventario
    |--------------------------------------------------------------------------
    */
    'inventory_item_status' => [
        'available' => 'Disponível',
        'in_repair' => 'Em Reparo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Acoes do Historico
    |--------------------------------------------------------------------------
    */
    'history_action' => [
        'created' => 'Ticket criado',
        'status_changed' => 'Status alterado para :status',
        'priority_changed' => 'Prioridade alterada para :priority',
        'assigned' => 'Atribuido a :agent',
        'unassigned' => 'Agente removido',
        'department_changed' => 'Departamento alterado para :department',
        'category_changed' => 'Categoria alterada para :category',
        'comment_added' => 'Comentario adicionado',
        'attachment_added' => 'Anexo adicionado',
        'attachment_removed' => 'Anexo removido',
        'closed' => 'Ticket fechado',
        'reopened' => 'Ticket reaberto',
        'title_changed' => 'Titulo alterado',
        'merged' => 'Ticket mesclado com :reference',
        'tags_changed' => 'Tags atualizadas',
        'sla_applied' => 'Politica de SLA aplicada',
        'sla_breached' => 'SLA violado',
        'escalated' => 'Ticket escalonado',
        'service_request_linked' => 'Requisicao de servico vinculada',
        'article_linked' => 'Artigo da base de conhecimento vinculado',
    ],

    /*
    |--------------------------------------------------------------------------
    | SLA
    |--------------------------------------------------------------------------
    */
    'sla' => [
        'breach_type' => [
            'first_response' => 'Primeira Resposta',
            'next_response' => 'Proxima Resposta',
            'resolution' => 'Resolucao',
        ],
        'applied' => 'Politica de SLA ":policy" aplicada ao ticket :reference',
        'breached' => 'SLA de :type violado no ticket :reference',
        'near_breach' => 'SLA de :type proximo de ser violado no ticket :reference',
        'metric_met' => 'SLA de :type cumprido no ticket :reference',
    ],

    /*
    |--------------------------------------------------------------------------
    | Escalonamento
    |--------------------------------------------------------------------------
    */
    'escalation' => [
        'action' => [
            'notify' => 'Notificar',
            'reassign' => 'Reatribuir',
            'change_priority' => 'Alterar Prioridade',
            'custom' => 'Personalizado',
        ],
        'triggered' => 'Regra de escalonamento ":rule" acionada no ticket :reference',
    ],

    /*
    |--------------------------------------------------------------------------
    | Base de Conhecimento
    |--------------------------------------------------------------------------
    */
    'knowledge_base' => [
        'article_status' => [
            'draft' => 'Rascunho',
            'published' => 'Publicado',
            'archived' => 'Arquivado',
        ],
        'visibility' => [
            'public' => 'Publico',
            'internal' => 'Interno',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Catalogo de Servicos
    |--------------------------------------------------------------------------
    */
    'service_catalog' => [
        'visibility' => [
            'public' => 'Publico',
            'internal' => 'Interno',
            'draft' => 'Rascunho',
        ],
        'request_status' => [
            'pending' => 'Pendente',
            'approved' => 'Aprovado',
            'rejected' => 'Rejeitado',
            'in_progress' => 'Em Andamento',
            'fulfilled' => 'Concluido',
            'cancelled' => 'Cancelado',
        ],
        'approval_status' => [
            'pending' => 'Pendente',
            'approved' => 'Aprovado',
            'rejected' => 'Rejeitado',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de Campo de Formulario
    |--------------------------------------------------------------------------
    */
    'form_field_type' => [
        'text' => 'Texto',
        'textarea' => 'Area de Texto',
        'select' => 'Selecao',
        'checkbox' => 'Caixa de Selecao',
        'radio' => 'Opcao Unica',
        'date' => 'Data',
        'datetime' => 'Data e Hora',
        'file' => 'Arquivo',
        'number' => 'Numero',
        'email' => 'E-mail',
        'url' => 'URL',
        'tel' => 'Telefone',
        'toggle' => 'Alternador',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dias da Semana
    |--------------------------------------------------------------------------
    */
    'day_of_week' => [
        'sunday' => 'Domingo',
        'monday' => 'Segunda-feira',
        'tuesday' => 'Terca-feira',
        'wednesday' => 'Quarta-feira',
        'thursday' => 'Quinta-feira',
        'friday' => 'Sexta-feira',
        'saturday' => 'Sabado',
    ],

    /*
    |--------------------------------------------------------------------------
    | Geral
    |--------------------------------------------------------------------------
    */
    'ticket' => 'Ticket',
    'tickets' => 'Tickets',
    'department' => 'Departamento',
    'category' => 'Categoria',
    'attachment' => 'Anexo',
    'comment' => 'Comentario',
    'tag' => 'Tag',
    'article' => 'Artigo',
    'service' => 'Servico',
];
