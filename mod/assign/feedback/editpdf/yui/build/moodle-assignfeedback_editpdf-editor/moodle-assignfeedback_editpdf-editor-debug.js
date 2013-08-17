YUI.add('moodle-assignfeedback_editpdf-editor', function (Y, NAME) {

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Provides an in browser PDF editor.
 *
 * @module moodle-assignfeedback_editpdf-editor
 */

// Globals
var AJAXBASE = M.cfg.wwwroot + '/mod/assign/feedback/editpdf/ajax.php',
    CSS = {
        DIALOGUE : 'assignfeedback_editpdf_widget'
    },
    SELECTOR = {
        PREVIOUSBUTTON : '.' + CSS.DIALOGUE + ' .navigate-previous-button',
        NEXTBUTTON : '.' + CSS.DIALOGUE + ' .navigate-next-button',
        PAGESELECT : '.' + CSS.DIALOGUE + ' .navigate-page-select',
        LOADINGICON : '.' + CSS.DIALOGUE + ' .loading',
        DRAWINGREGION : '.' + CSS.DIALOGUE + ' .drawingregion',
        DRAWINGCANVAS : '.' + CSS.DIALOGUE + ' .drawingcanvas',
        DIALOGUE : '.' + CSS.DIALOGUE
    };
/**
 * Drawable
 *
 * @namespace M.assignfeedback_editpdf.editor
 * @class Drawable
 */
Drawable = function() {
    /**
     * Array of Y.Shape
     * @property type
     * @type Y.Shape[]
     * @public
     */
    this.shapes = [];

    /**
     * Array of Y.Node
     * @property type
     * @type Y.Node[]
     * @public
     */
    this.nodes = [];
};

/**
 * EDITOR
 * This is an in browser PDF editor.
 *
 * @namespace M.assignfeedback_editpdf.editor
 * @class Editor
 * @constructor
 * @extends Y.Base
 */
EDITOR = function() {
    EDITOR.superclass.constructor.apply(this, arguments);
};
EDITOR.prototype = {

    // Instance variables.
    /**
     * The dialogue used for all action menu displays.
     * @property type
     * @type M.core.dialogue
     * @protected
     */
    dialogue : null,

    /**
     * The number of pages in the pdf.
     * @property type
     * @type int
     * @protected
     */
    pagecount : 0,

    /**
     * The active page in the editor.
     * @property type
     * @type int
     * @protected
     */
    currentpage : 0,

    /**
     * A list of page objects. Each page has a list of comments and annotations.
     * @property type
     * @type array
     * @protected
     */
    pages : [],

    /**
     * The yui node for the loading icon.
     * @property type
     * @type Y.Node
     * @protected
     */
    loadingicon : null,

    /**
     * Image object of the current page image.
     * @property type
     * @type Image
     * @protected
     */
    pageimage : null,

    /**
     * YUI Graphic class for drawing shapes.
     * @property type
     * @type Y.Graphic
     * @protected
     */
    graphic : null,

    /**
     * Foreground colour.
     * @property type
     * @type string
     * @protected
     */
    currentfgcolour : 'black',

    /**
     * Background colour.
     * @property type
     * @type string
     * @protected
     */
    currentbgcolour : 'yellow',

    /**
     * Selected tool
     * @property type
     * @type string
     * @protected
     */
    currenttool : 'comment',

    /**
     * Info about the current edit operation.
     * @property type
     * @type object containing start and end points (x and y)
     * @protected
     */
    currentedit : {},

    /**
     * Current drawable.
     * @property type
     * @type Drawable (or false)
     * @protected
     */
    currentdrawable : false,

    /**
     * Current drawables.
     * @property type
     * @type array(Drawable)
     * @protected
     */
    drawables : [],

    /**
     * Called during the initialisation process of the object.
     * @method initializer
     */
    initializer : function() {
        Y.log('Initialising M.assignfeedback_editpdf.editor');
        Y.log(this);
        var link = Y.one('#' + this.get('linkid'));

        link.on('click', this.link_handler, this);
        link.on('key', this.link_handler, 'down:13', this);
        this.currentedit.start = false;
        this.currentedit.end = false;
    },

    /**
     * Called to open the pdf editing dialogue.
     * @method link_handler
     */
    link_handler : function(e) {
        var drawingcanvas;
        Y.log('Launch pdf editor');
        e.preventDefault();

        if (!this.dialogue) {
            this.dialogue = new M.core.dialogue({
                headerContent: this.get('header'),
                bodyContent: this.get('body'),
                footerContent: this.get('footer'),
                width: '840px',
                visible: true
            });

            this.dialogue.centerDialogue();
            // Add custom class for styling.
            this.dialogue.get('boundingBox').addClass(CSS.DIALOGUE);

            this.loadingicon = Y.one(SELECTOR.LOADINGICON);

            drawingcanvas = Y.one(SELECTOR.DRAWINGCANVAS);
            this.graphic = new Y.Graphic({render : SELECTOR.DRAWINGCANVAS});

            drawingcanvas.on('mousedown', this.edit_start, this);
            drawingcanvas.on('mousemove', this.edit_move, this);
            drawingcanvas.on('mouseup', this.edit_end, this);

        } else {
            this.dialogue.show();
        }

        this.load_all_pages();
    },

    /**
     * Called to load the information and annotations for all pages.
     * @method load_all_pages
     */
    load_all_pages : function() {
        var ajaxurl = AJAXBASE;
        config = {
            method: 'get',
            context: this,
            sync: false,
            data : {
                'sesskey' : M.cfg.sesskey,
                'action' : 'loadallpages',
                'userid' : this.get('userid'),
                'attemptnumber' : this.get('attemptnumber'),
                'assignmentid' : this.get('assignmentid')
            },
            on: {
                success: function(tid, response) {
                    Y.log(response.responseText);
                    this.all_pages_loaded(response.responseText);
                },
                failure: function(tid, response) {
                    return new M.core.ajaxException(response);
                }
            }
        };

        Y.io(ajaxurl, config);
    },

    /**
     * The info about all pages in the pdf has been returned.
     * @param string The ajax response as text.
     * @protected
     * @method all_pages_loaded
     */
    all_pages_loaded : function(responsetext) {
        var data;

        try {
            data = Y.JSON.parse(responsetext);
        } catch (e) {
             this.dialogue.hide();
             new M.core.exception(responsetext);
        }

        this.pagecount = data.pagecount;
        this.pages = data.pages;

        // Update the ui.
        this.setup_navigation();
        this.change_page();


    },

    /**
     * Event handler for mousedown or touchstart
     * @protected
     * @param Event
     * @method edit_start
     */
    edit_start : function(e) {
        var offset = Y.one(SELECTOR.DRAWINGCANVAS).getXY(),
            point = {x : e.clientX - offset[0],
                     y : e.clientY - offset[1]};

        this.currentedit.start = point;
    },

    /**
     * Generate a drawable from the current in progress edit.
     * @protected
     * @method get_current_drawable
     */
    get_current_drawable : function() {
        var drawable = new Drawable(),
            shape, width, height, x, y;

        if (!this.currentedit.start || !this.currentedit.end) {
            return false;
        }

        // Work out the boundary box.
        x = this.currentedit.start.x;
        if (this.currentedit.end.x > x) {
            width = this.currentedit.end.x - x;
        } else {
            x = this.currentedit.end.x;
            width = this.currentedit.start.x - x;
        }
        y = this.currentedit.start.y;
        if (this.currentedit.end.y > y) {
            height = this.currentedit.end.y - y;
        } else {
            y = this.currentedit.end.y;
            height = this.currentedit.start.y - y;
        }

        if (this.currenttool === 'comment') {
            // We will draw a box with the current background colour.
            shape = this.graphic.addShape({
                type: Y.Rect,
                width: width,
                height: height,
                fill: {
                   color: this.currentbgcolour
                },
                x: x,
                y: y
            });

            drawable.shapes.push(shape);
        }

        return drawable;
    },

    /**
     * Delete the shapes from the drawable.
     * @protected
     * @method erase_drawable
     */
    erase_drawable : function(drawable) {
        while (drawable.shapes.length > 0) {
            this.graphic.removeShape(drawable.shapes.pop());
        }
        while (drawable.nodes.length > 0) {
            drawable.nodes.pop().remove();
        }
    },

    /**
     * Redraw the active edit.
     * @protected
     * @method redraw_active_edit
     */
    redraw_current_edit : function() {
        if (this.currentdrawable) {
            this.erase_drawable(this.currentdrawable);
        }
        this.currentdrawable = this.get_current_drawable();
    },

    /**
     * Event handler for mousemove
     * @protected
     * @param Event
     * @method edit_move
     */
    edit_move : function(e) {
        var offset = Y.one(SELECTOR.DRAWINGCANVAS).getXY(),
            point = {x : e.clientX - offset[0],
                     y : e.clientY - offset[1]};
        if (this.currentedit.start) {
            this.currentedit.end = point;
            this.redraw_current_edit();
        }
    },

    /**
     * Event handler for mouseup or touchend
     * @protected
     * @param Event
     * @method edit_end
     */
    edit_end : function() {
        var data,
            width,
            height,
            x,
            y;
        // Work out the boundary box.
        x = this.currentedit.start.x;
        if (this.currentedit.end.x > x) {
            width = this.currentedit.end.x - x;
        } else {
            x = this.currentedit.end.x;
            width = this.currentedit.start.x - x;
        }
        y = this.currentedit.start.y;
        if (this.currentedit.end.y > y) {
            height = this.currentedit.end.y - y;
        } else {
            y = this.currentedit.end.y;
            height = this.currentedit.start.y - y;
        }
        // Save the current edit to the server and the current page list.

        if (this.currenttool === 'comment') {
            data = {
                gradeid : this.get('gradeid'),
                posx : x,
                posy : y,
                width : width,
                rawtext : '',
                pageno : this.currentpage,
                bgcolour : this.currentbgcolour,
                fgcolour : this.currentfgcolour
            };

            this.pages[this.currentpage].comments.push(data);
            this.drawables.push(this.draw_comment(data));
        }


        this.currentedit.start = false;
        this.currentedit.end = false;
        this.erase_drawable(this.currentdrawable);
        this.currentdrawable = false;
    },

    /**
     * Draw an annotation
     * @protected
     * @method draw_annotation
     * @param annotation
     * @return Drawable
     */
    draw_annotation : function(annotation) {
        var drawable = new Drawable();

        return drawable;
    },

    /**
     * Draw a comment
     * @protected
     * @method draw_comment
     * @param comment
     * @return Drawable
     */
    draw_comment : function(comment) {
        var drawable = new Drawable(),
            node,
            drawingregion = Y.one(SELECTOR.DRAWINGREGION),
            offsetcanvas = Y.one(SELECTOR.DRAWINGCANVAS).getXY(),
            offsetdialogue = Y.one(SELECTOR.DIALOGUE).getXY(),
            offsetleft = offsetcanvas[0] - offsetdialogue[0],
            offsettop = offsetcanvas[1] - offsetdialogue[1];

        // Lets add a contenteditable div.
        node = Y.Node.create('<div contenteditable="true"/>');
        if (comment.width < 60) {
            comment.width = 60;
        }
        node.setStyles({
            position: 'absolute',
            left: (comment.posx + offsetleft) + 'px',
            top: (comment.posy + offsettop) + 'px',
            width: comment.width + 'px',
            backgroundColor: comment.bgcolour,
            color: comment.fgcolour,
            border: '2px solid black',
            fontSize: '16pt',
            minHeight: '1.2em'
        });

        drawingregion.append(node);
        node.focus();

        drawable.nodes.push(node);
        return drawable;
    },

    /**
     * Load the image for this pdf page and remove the loading icon (if there).
     * @protected
     * @method all_pages_loaded
     */
    change_page : function() {
        var drawingcanvas = Y.one(SELECTOR.DRAWINGCANVAS), i;

        this.loadingicon.hide();
        drawingcanvas.setStyle('backgroundImage', 'url("' + this.pages[this.currentpage].url + '")');

        while (this.drawables.length > 0) {
            this.erase_drawable(this.drawables.pop());
        }

        for (i = 0; i < this.pages[this.currentpage].annotations.length; i++) {
            this.drawables.push(this.draw_annotation(this.pages[this.currentpage].annotations[i]));
        }
        for (i = 0; i < this.pages[this.currentpage].comments.length; i++) {
            this.drawables.push(this.draw_comment(this.pages[this.currentpage].comments[i]));
        }

    },

    /**
     * Now we know how many pages there are,
     * we can enable the navigation controls.
     * @protected
     * @method all_pages_loaded
     */
    setup_navigation : function() {
        var pageselect,
            previousbutton,
            nextbutton,
            i,
            option;

        previousbutton = Y.one(SELECTOR.PREVIOUSBUTTON);
        nextbutton = Y.one(SELECTOR.NEXTBUTTON);
        pageselect = Y.one(SELECTOR.PAGESELECT);

        if (this.currentpage > 0) {
            previousbutton.removeAttribute('disabled');
        } else {
            previousbutton.setAttribute('disabled', 'true');
        }
        if (this.currentpage < (this.pagecount - 1)) {
            nextbutton.removeAttribute('disabled');
        } else {
            nextbutton.setAttribute('disabled', 'true');
        }

        options = pageselect.all('option');
        if (options.size() <= 1) {
            for (i = 0; i < this.pages.length; i++) {
                option = Y.Node.create('<option/>');
                option.setAttribute('value', i);
                option.setHTML((i+1));
                pageselect.append(option);
            }
        }
        pageselect.removeAttribute('disabled');
    }



};

Y.extend(EDITOR, Y.Base, EDITOR.prototype, {
    NAME : 'moodle-assignfeedback_editpdf-editor',
    ATTRS : {
        userid : {
            validator : Y.Lang.isInteger,
            value : 0
        },
        assignmentid : {
            validator : Y.Lang.isInteger,
            value : 0
        },
        attemptnumber : {
            validator : Y.Lang.isInteger,
            value : 0
        },
        header : {
            validator : Y.Lang.isString,
            value : ''
        },
        body : {
            validator : Y.Lang.isString,
            value : ''
        },
        footer : {
            validator : Y.Lang.isString,
            value : ''
        },
        linkid : {
            validator : Y.Lang.isString,
            value : ''
        }
    }
});

/**
 * Assignfeedback edit pdf namespace.
 * @static
 * @class assignfeedback_editpdf
 */
M.assignfeedback_editpdf = M.assignfeedback_editpdf || {};

/**
 * Editor namespace
 * @namespace M.assignfeedback_editpdf.editor
 * @class editor
 * @static
 */
M.assignfeedback_editpdf.editor = M.assignfeedback_editpdf.editor || {};

/**
 * Init function - will create a new instance every time.
 * @method init
 * @static
 * @param {Object} params
 */
M.assignfeedback_editpdf.editor.init = M.assignfeedback_editpdf.editor.init || function(params) {
    return new EDITOR(params);
};


}, '@VERSION@', {
    "requires": [
        "base",
        "event",
        "node",
        "io",
        "graphics",
        "querystring-stringify-simple",
        "moodle-core-notification-dialog",
        "moodle-core-notification-exception",
        "moodle-core-notification-ajaxexception"
    ]
});
