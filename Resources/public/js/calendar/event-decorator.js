/*jslint nomen:true*/
/*global define*/
define(['underscore', 'orotranslation/js/translator'
    ], function (_, __) {
    'use strict';

    return {
        /** @property {Object} */
        templates: {
            reminderIcon: '<i class="reminder-status icon-bell" title="' + __('Reminders') + '"></i>',
            notRespondedIcon: '<i class="invitation-status icon-reply" title="' + __('Not responded') + '"></i>',
            tentativelyIcon: '<i class="invitation-status icon-question-sign" title="' + __('Tentatively accepted') + '"></i>',
            acceptedIcon: '<i class="invitation-status icon-ok" title="' + __('Accepted') + '"></i>'
        },

        decorate: function (eventModel, $el) {
            var $body = $el.find('.fc-content'),
                reminders = eventModel.get('reminders'),
                invitationStatus = eventModel.get('invitationStatus');
            if (reminders && _.keys(reminders).length) {
                $body.append(this.templates.reminderIcon);
            } else {
                $body.find('.reminder-status').remove();
            }
            switch (invitationStatus) {
                case 'not_responded':
                    $body.prepend(this.templates.notRespondedIcon);
                    break;
                case 'accepted':
                    $body.prepend(this.templates.acceptedIcon);
                    break;
                case 'tentatively_accepted':
                    $body.prepend(this.templates.tentativelyIcon);
                    break;
                case 'declined':
                    $body.addClass('invitation-status-declined');
                    break;
                default:
                    $body.find('.invitation-status').remove();
                    $body.removeClass('invitation-status-declined');
                    break;
            }
        }
    };
});