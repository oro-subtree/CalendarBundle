/*jslint nomen:true*/
/*global define, console*/
define(['jquery', 'underscore', 'oroui/js/app/views/base/view', 'orotranslation/js/translator', 'oroui/js/messenger',
    'jquery.simplecolorpicker', 'jquery.minicolors'
    ], function ($, _, BaseView, __, messenger) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/menu/change-calendar-color
     * @class   orocalendar.calendar.menu.ChangeCalendarColor
     * @extends oroui/js/app/views/base/view
     */
    return BaseView.extend({
        /** @property */
        customColorPickerActionsTemplate: _.template('<div class="form-actions">' +
                '<button class="btn btn-primary pull-right" data-action="ok" type="button"><%= __("Ok") %></button>' +
                '<button class="btn pull-right" data-action="cancel" type="button"><%= __("Cancel") %></button>' +
            '</div>'),

        initialize: function (options) {
            this.colorManager = options.colorManager;
            this.connectionsView = options.connectionsView;
            this.actionSyncObject = options.actionSyncObject;
            this.$colorPicker = this.$el.find('.color-picker');
            this.$customColor = this.$el.find('.custom-color');
            this.$customColorParent = this.$customColor.parent();

            this.customColor = this.model.get('backgroundColor');
            if (_.indexOf(this.colorManager.colors, this.model.get('backgroundColor')) !== -1) {
                this.customColor = null;
            }

            this.initializeColorPicker();
            this.initializeCustomColorPicker();
        },

        initializeColorPicker: function () {
            var colors = _.map(this.colorManager.colors, function (value) {
                    return {'id': value, 'text': value};
                });

            this.$colorPicker
                .simplecolorpicker({theme: 'fontawesome', data: colors})
                .one('change', _.bind(function (e) {
                    this.$el.remove();
                    this.onChangeColor(e.currentTarget.value);
                }, this));
            if (!this.customColor) {
                this.$colorPicker.simplecolorpicker('selectColor', this.model.get('backgroundColor'));
            }
        },

        initializeCustomColorPicker: function () {
            this.$customColor.minicolors({
                control: 'wheel',
                letterCase: 'uppercase',
                defaultValue: this.model.get('backgroundColor'),
                change: _.bind(function (hex, opacity) {
                    this.$customColor.css('color', this.colorManager.getContrastColor(hex));
                }, this),
                show: _.bind(function () {
                    var color = this.customColor || this.model.get('backgroundColor'),
                        $panel = this.$customColorParent.find('.minicolors-panel'),
                        h;
                    $panel.css('top', 0);
                    h = $panel.outerHeight() + 39;
                    $panel.css('top', $(document).height() < $panel.offset().top + h ? -h : 0);
                    this.$colorPicker.simplecolorpicker('selectColor', null);
                    this.$customColor.minicolors('value', color);
                    this.$customColor.attr('data-selected', '');
                    this.$customColorParent.find('.minicolors-picker').show();
                }, this)
            });

            this.$customColorParent.find('.minicolors-picker').hide();

            if (this.customColor) {
                this.$customColor.attr('data-selected', '');
                this.$customColor.css('color', this.colorManager.getContrastColor(this.model.get('backgroundColor')));
            } else {
                this.$customColor.hide();
            }

            this.$customColorParent.on('click', '.custom-color-link', _.bind(function () {
                this.$customColor.minicolors('show');
                this.$customColor.show();
            }, this));

            // add buttons
            this.$customColorParent.find('.minicolors-panel').append(this.customColorPickerActionsTemplate({__: __}));
            this.$customColorParent.one('click', 'button[data-action=ok]', _.bind(function (e) {
                e.preventDefault();
                this.$customColor.minicolors('hide');
                $('.context-menu-button').css('display', '');
                this.$el.remove();
                this.onChangeColor(this.$customColor.minicolors('value'));
            }, this));
            this.$customColorParent.on('click', 'button[data-action=cancel]', _.bind(function (e) {
                e.preventDefault();
                this.$customColor.minicolors('hide');
                if (this.customColor) {
                    this.$customColor.css({
                        'background-color': this.customColor,
                        'color': this.colorManager.getContrastColor(this.customColor)
                    });
                } else {
                    this.$customColor.removeAttr('data-selected');
                    this.$colorPicker.simplecolorpicker('selectColor', this.model.get('backgroundColor'));
                    this.$customColor.hide();
                }
            }, this));
        },

        onChangeColor: function (color) {
            var savingMsg = messenger.notificationMessage('warning', __('Updating the calendar, please wait ...'));
            try {
                this.model.save('backgroundColor', color, {
                    wait: true,
                    success: _.bind(function () {
                        savingMsg.close();
                        messenger.notificationFlashMessage('success', __('The calendar was updated.'));
                        this.colorManager.setCalendarColors(this.model.get('calendarUid'), this.model.get('backgroundColor'));
                        this.changeVisibleButton(this.model);
                        this.connectionsView.trigger('connectionAdd', this.model);
                        if (this.actionSyncObject) {
                            this.actionSyncObject.resolve();
                        }
                    }, this),
                    error: _.bind(function (model, response) {
                        savingMsg.close();
                        this._showError(__('Sorry, the calendar updating was failed'), response.responseJSON || {});
                        if (this.actionSyncObject) {
                            this.actionSyncObject.reject();
                        }
                    }, this)
                });
            } catch (err) {
                savingMsg.close();
                this._showError(__('Sorry, unexpected error was occurred'), err);
                if (this.actionSyncObject) {
                    this.actionSyncObject.reject();
                }
            }
        },

        changeVisibleButton: function (model) {
            if (model.get('visible')) {
                var $connection = this.connectionsView.findItem(model),
                    $visibilityButton = $connection.find(this.connectionsView.selectors.visibilityButton);
                this.connectionsView._setItemVisibility($visibilityButton, model.get('backgroundColor'));
            }
        },

        _showError: function (message, err) {
            messenger.showErrorMessage(message, err);
        }
    });
});
