/*global define*/
define(['underscore', 'jquery', 'orotranslation/js/translator', 'orolocale/js/formatter/datetime'
], function (_, $, __, datetimeFormatter) {
    'use strict';

    var defaultParam = {
        message: 'This date should be earlier than End date'
    };

    /**
     * @export orocalendar/js/validator/dateearlierthan
     */
    return [
        'Oro\\Bundle\\CalendarBundle\\Validator\\Constraints\\DateEarlierThan',
        function (value, element, options) {
            var elementId = $(element).attr('id');
            var strToReplace = elementId.substr(elementId.lastIndexOf('_') + 1);
            var comparedElId = elementId.replace(strToReplace, options.field);
            var comparedValue = $('#' + comparedElId).val();

            if (!value || !comparedValue) {
                return true;
            }

            var firstDate = new Date(value);
            var secondDate = new Date(comparedValue);

            return !(secondDate < firstDate);
        },
        function (param, element) {
            var value = String(this.elementValue(element)),
                placeholders = {};
            param = _.extend({}, defaultParam, param);
            placeholders.value = value;
            return __(param.message, placeholders);
        }
    ];
});
