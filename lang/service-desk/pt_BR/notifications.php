<?php

return [
    'unknown_user' => 'Usuario Desconhecido',

    'ticket_created' => [
        'subject' => 'Ticket #:reference Criado: :title',
        'greeting' => 'Ola,',
        'body' => 'Um novo ticket foi criado com a referencia #:reference. Nossa equipe ira analisa-lo e retornara em breve.',
        'department' => 'Departamento: :department',
        'priority' => 'Prioridade: :priority',
    ],

    'ticket_assigned' => [
        'subject' => 'Ticket #:reference Atribuido a Voce',
        'greeting' => 'Ola,',
        'body' => 'O ticket #:reference (:title) foi atribuido a voce. Por favor, analise-o assim que possivel.',
        'priority' => 'Prioridade: :priority',
        'department' => 'Departamento: :department',
    ],

    'status_changed' => [
        'subject' => 'Ticket #:reference - Status Atualizado',
        'greeting' => 'Ola,',
        'body' => 'O status do ticket #:reference (:title) foi atualizado.',
        'transition' => 'Status alterado de :old para :new.',
    ],

    'new_comment' => [
        'subject' => 'Novo Comentario no Ticket #:reference',
        'greeting' => 'Ola,',
        'body' => 'Um novo comentario foi adicionado ao ticket #:reference (:title) por :author.',
        'comment_type' => 'Tipo de comentario: :type',
    ],

    'ticket_closed' => [
        'subject' => 'Ticket #:reference Fechado',
        'greeting' => 'Ola,',
        'body' => 'O ticket #:reference (:title) foi fechado.',
        'info' => 'Se voce acredita que isso foi feito por engano ou precisa de mais ajuda, voce pode reabri-lo.',
    ],

    'sla_breached' => [
        'subject' => 'SLA Violado no Ticket #:reference',
        'greeting' => 'Ola,',
        'body' => 'O SLA de :breach_type foi violado no ticket #:reference (:title). Atencao imediata e necessaria.',
        'priority' => 'Prioridade: :priority',
    ],

    'sla_near_breach' => [
        'subject' => 'SLA Proximo de Violacao no Ticket #:reference',
        'greeting' => 'Ola,',
        'body' => 'O SLA de :breach_type no ticket #:reference (:title) esta se aproximando do prazo em :minutes minuto(s). Por favor, tome uma acao para evitar a violacao.',
        'priority' => 'Prioridade: :priority',
    ],

    'escalation' => [
        'subject' => 'Ticket #:reference Escalonado',
        'greeting' => 'Ola,',
        'body' => 'O ticket #:reference (:title) foi escalonado (violacao de :breach_type, acao: :action). Por favor, analise e tome as medidas adequadas.',
        'priority' => 'Prioridade: :priority',
    ],

    'approval_requested' => [
        'subject' => 'Aprovacao Solicitada: :service',
        'greeting' => 'Ola,',
        'body' => 'Sua aprovacao e necessaria para a requisicao ":service" enviada por :requester.',
        'action_required' => 'Por favor, analise e forneca sua decisao.',
    ],

    'approval_decision' => [
        'subject' => 'Decisao de Aprovacao: :service (:status)',
        'greeting' => 'Ola,',
        'body' => 'A requisicao ":service" foi :status.',
        'comment' => 'Comentario: :comment',
    ],
];
