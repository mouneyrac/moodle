YUI.add('moodle-form-dateselector', function(Y) {

    /**
     * Calendar class
     *
     * This is our main class
     */
    var CALENDAR = function(config) {
        CALENDAR.superclass.constructor.apply(this, arguments);
    };
    CALENDAR.prototype = {
        initializer : function(config) {

        }
    };

//    Y.extend(CALENDAR, Y.Base, CALENDAR.prototype, {
//        NAME : 'Date Selector',
//        ATTRS : {
//            firstdayofweek  : {
//                validator : Y.Lang.isString
//            },
//            node : {
//                setter : function(node) {
//                    return Y.one(node);
//                }
//            }
//        }
//    });

    M.form = M.form || {};
    M.form.dateselector = {
        init_date_selectors : function(config) {
            console.log('init_date_selectors');
            console.log(this);

            var Align = Y.WidgetPositionAlign;

            // Render the overlay
            var overlay = new Y.Overlay({
                srcNode: '#calendar-overlay',
                visible: false,
                hideOn: [
                    { eventName: 'key', keyCode: 'esc', node: Y.one('document') },
                    { eventName: 'clickoutside' }
                ]
            }).render();

            overlay.set('align', {
                node: this.get('node').one('select'),
                points: [Align.TL, Align.BL]
            });

            // Render the calendar.
            var calendar = new Y.Calendar({
                contentBox: '#calendar',
                width:'270px',
                showPrevMonth: true,
                showNextMonth: true
            }).render();

            this.on('click', function(e) {
                overlay.show();
            });
        }
    }

}, '@VERSION@', {requires:['base','node','overlay', 'calendar', 'moodle-form-dateselector-skin']});
