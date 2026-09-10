<?php

return [
    'unknown_user' => 'Unknown User',

    'ticket_created' => [
        'subject' => 'Ticket #:reference Created: :title',
        'greeting' => 'Hello,',
        'body' => 'A new ticket has been created with reference #:reference. Our team will review it and get back to you shortly.',
        'department' => 'Department: :department',
        'priority' => 'Priority: :priority',
    ],

    'ticket_assigned' => [
        'subject' => 'Ticket #:reference Assigned to You',
        'greeting' => 'Hello,',
        'body' => 'Ticket #:reference (:title) has been assigned to you. Please review it at your earliest convenience.',
        'priority' => 'Priority: :priority',
        'department' => 'Department: :department',
    ],

    'status_changed' => [
        'subject' => 'Ticket #:reference Status Updated',
        'greeting' => 'Hello,',
        'body' => 'The status of ticket #:reference (:title) has been updated.',
        'transition' => 'Status changed from :old to :new.',
    ],

    'new_comment' => [
        'subject' => 'New Comment on Ticket #:reference',
        'greeting' => 'Hello,',
        'body' => 'A new comment has been added to ticket #:reference (:title) by :author.',
        'comment_type' => 'Comment type: :type',
    ],

    'ticket_closed' => [
        'subject' => 'Ticket #:reference Closed',
        'greeting' => 'Hello,',
        'body' => 'Ticket #:reference (:title) has been closed.',
        'info' => 'If you believe this was done in error or need further assistance, you may reopen it.',
    ],

    'sla_breached' => [
        'subject' => 'SLA Breached on Ticket #:reference',
        'greeting' => 'Hello,',
        'body' => 'The :breach_type SLA has been breached on ticket #:reference (:title). Immediate attention is required.',
        'priority' => 'Priority: :priority',
    ],

    'sla_near_breach' => [
        'subject' => 'SLA Near Breach on Ticket #:reference',
        'greeting' => 'Hello,',
        'body' => 'The :breach_type SLA on ticket #:reference (:title) is approaching its deadline in :minutes minute(s). Please take action to avoid a breach.',
        'priority' => 'Priority: :priority',
    ],

    'escalation' => [
        'subject' => 'Ticket #:reference Escalated',
        'greeting' => 'Hello,',
        'body' => 'Ticket #:reference (:title) has been escalated (:breach_type breach, action: :action). Please review and take appropriate action.',
        'priority' => 'Priority: :priority',
    ],

    'approval_requested' => [
        'subject' => 'Approval Requested: :service',
        'greeting' => 'Hello,',
        'body' => 'Your approval is required for the ":service" request submitted by :requester.',
        'action_required' => 'Please review and provide your decision.',
    ],

    'approval_decision' => [
        'subject' => 'Approval Decision: :service (:status)',
        'greeting' => 'Hello,',
        'body' => 'The request for ":service" has been :status.',
        'comment' => 'Comment: :comment',
    ],
];
