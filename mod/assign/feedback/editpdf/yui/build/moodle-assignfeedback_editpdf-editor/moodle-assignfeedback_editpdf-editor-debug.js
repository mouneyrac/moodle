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
        CANCEL : '.' + CSS.DIALOGUE + ' .cancelbutton',
        SAVE : '.' + CSS.DIALOGUE + ' .savebutton',
        COLOURBUTTON : '.' + CSS.DIALOGUE + ' .pdfbutton_colour',
        DIALOGUE : '.' + CSS.DIALOGUE
    },
    COLOUR = {
        'red' : 'rgb(255,176,176)',
        'green' : 'rgb(176,255,176)',
        'blue' : 'rgb(208,208,255)',
        'white' : 'rgb(255,255,255)',
        'yellow' : 'rgb(255,255,176)'
    },
    CLICKTIMEOUT = 300,
    TOOLSELECTOR = {
        'comment': '.' + CSS.DIALOGUE + ' .pdfbutton_comment',
        'pen': '.' + CSS.DIALOGUE + ' .pdfbutton_pen',
        'line': '.' + CSS.DIALOGUE + ' .pdfbutton_line',
        'rectangle': '.' + CSS.DIALOGUE + ' .pdfbutton_rectangle',
        'oval': '.' + CSS.DIALOGUE + ' .pdfbutton_oval',
        'stamp': '.' + CSS.DIALOGUE + ' .pdfbutton_stamp',
        'eraser': '.' + CSS.DIALOGUE + ' .pdfbutton_eraser'
    },
    STROKEWEIGHT = 4;

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
     * Current colour.
     * @property type
     * @type string
     * @protected
     */
    currentcolour : 'yellow',

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
     * The colour picker dialogue box.
     */
    colourpicker : null,

    /**
     * The pen tool position (also known as the mouse position :P).
     */
    currentpenposition : {x:null,y:null},

    /**
     * The pen tool path being drawn.
     */
    currentpenpath : [],

    /**
     * Called during the initialisation process of the object.
     * @method initializer
     */
    initializer : function() {
        var link, deletelink;
        Y.log('Initialising M.assignfeedback_editpdf.editor');
        link = Y.one('#' + this.get('linkid'));

        link.on('click', this.link_handler, this);
        link.on('key', this.link_handler, 'down:13', this);

        Y.log(this.get('deletelinkid'));
        deletelink = Y.one('#' + this.get('deletelinkid'));
        deletelink.on('click', this.delete_link_handler, this);
        deletelink.on('key', this.delete_link_handler, 'down:13', this);

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
     * Called to delete the last generated pdf.
     * @method link_handler
     */
    delete_link_handler : function(e) {
        var downloadlink,
            deletelink;

        Y.log('Delete generated pdf');
        e.preventDefault();

        var ajaxurl = AJAXBASE,
            config;

        config = {
            method: 'get',
            context: this,
            sync: false,
            data : {
                'sesskey' : M.cfg.sesskey,
                'action' : 'deletefeedbackdocument',
                'userid' : this.get('userid'),
                'attemptnumber' : this.get('attemptnumber'),
                'assignmentid' : this.get('assignmentid')
            },
            on: {
                success: function() {
                    downloadlink = Y.one('#' + this.get('downloadlinkid'));
                    deletelink = Y.one('#' + this.get('deletelinkid'));

                    downloadlink.addClass('hidden');
                    deletelink.addClass('hidden');
                },
                failure: function(tid, response) {
                    return M.core.exception(response.responseText);
                }
            }
        };

        Y.io(ajaxurl, config);

    },

    /**
     * Called to load the information and annotations for all pages.
     * @method load_all_pages
     */
    load_all_pages : function() {
        var ajaxurl = AJAXBASE,
            config;

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
                    this.all_pages_loaded(response.responseText);
                },
                failure: function(tid, response) {
                    return M.core.exception(response.responseText);
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
             return new M.core.exception(e);
        }

        this.pagecount = data.pagecount;
        this.pages = data.pages;

        // Update the ui.
        this.setup_navigation();
        this.change_page();
        this.setup_save_cancel();
        this.setup_toolbar();
    },

    /**
     * Attach listeners and enable the color picker buttons.
     * @protected
     * @method setup_save_cancel
     */
    setup_toolbar : function() {

        // Setup the tool buttons.
        Y.each(TOOLSELECTOR, function(selector, tool) {
            var toolnode = Y.one(selector);
            toolnode.on('click', this.handle_toolbutton, this, tool);
            toolnode.on('key', this.handle_toolbutton, 'down:13', this, tool);
            toolnode.setAttribute('aria-pressed', 'false');
        }, this);

         // Set the default tool.
        var currenttoolnode = Y.one(TOOLSELECTOR[this.currenttool]);
        currenttoolnode.addClass('selectedbutton');
        currenttoolnode.setAttribute('aria-pressed', 'true');

        // Setup the color button.
        var colourbutton = Y.one(SELECTOR.COLOURBUTTON);
        colourbutton.on('click', this.handle_colourbutton, this);
        colourbutton.on('key', this.handle_colourbutton, 'down:13', this);
        colourbutton.setAttribute('title', this.currentcolour + 'color');

        // Generate the color picker content.
        var colordivs = '';
        Y.each(COLOUR, function(rgb, color) {
            colordivs = colordivs + '<div class=\"square '+ color +'\" title=\"'+ color +'\" role=\"button\" tabIndex=0></div>';
        }, this);

        if (!this.colourpicker) {
            // Create the color picker.
            this.colourpicker = new M.core.dialogue({
                width: 307,
                extraClasses : ['colourpicker'],
                draggable: false,
                center: false,
                lightbox: false,
                headerContent : M.util.get_string('colourpicker', 'assignfeedback_editpdf'),
                bodyContent:"<div id=\"colorpicker\" class=\"\" style=\"\">" + colordivs + "</div>",
                footerContent: '',
                zIndex:60000,
            });
        }
    },

    /**
     * Handle a click on the colour button.
     * @protected
     * @method handle_colourbutton
     */
    handle_colourbutton : function(e) {
        e.preventDefault();

        // Display the color picker.
        this.colourpicker.show();
        this.colourpicker.render();

        // Position the colourpicker at the bottom on the colour button.
        var colourbuttonxy = Y.one(SELECTOR.COLOURBUTTON).getXY();
        var colourpickerxy = Y.one('.moodle-dialogue-base .colourpicker').getXY();
        this.colourpicker.move(colourpickerxy[0],colourbuttonxy[1]+40);

        // Add on click event to all colors.
        Y.each(COLOUR, function(rgb, color) {
            Y.one('.'+color).on("click", this.changecolor, null, color, this, this.colourpicker);
        }, this);

        // Automatically close the color picker when we click something else (except the color button).
        Y.on("click",  Y.bind(this.colourpicker.show, this.colourpicker) , Y.one(SELECTOR.COLOURBUTTON));
        Y.one(document).on('click', function(event, colourpicker) {
            // Below code is causing the dialogue to close as soon as it is open. Need to detect the state of overlay.
            // When it is already open then the below code should fire.
            var buttonchildnodes = Y.one(SELECTOR.COLOURBUTTON).get('childNodes');
            if(event.target.ancestor('#colorpicker')=== null && event.target.get('id') != buttonchildnodes.item(0).get('id') && event.target.get('id') != Y.one(SELECTOR.COLOURBUTTON).get('id') && colourpicker.get('visible') == true)  {
               colourpicker.hide();
            }
        }, null, this.colourpicker);

        // Focus on the dialogue for aria purpose - to set focus by js on a div we set tabIndex to -1.
        // We'll put the tabindex on the div being set as role=dialog.
        Y.one('.colourpicker').get('childNodes').item(0).setAttribute('tabIndex', '-1');
        Y.one('.colourpicker').get('childNodes').item(0).focus();
    },

    /**
     * Change the current tool.
     * @protected
     * @method handle_toolbutton
     */
    handle_toolbutton : function(e, tool) {
        e.preventDefault();

        // Change style of the pressed button.
        var currenttoolnode = Y.one(TOOLSELECTOR[this.currenttool]);
        currenttoolnode.removeClass('selectedbutton');
        currenttoolnode.setAttribute('aria-pressed', 'false');
        var newtoolnode = Y.one(TOOLSELECTOR[tool]);
        newtoolnode.addClass('selectedbutton');
        newtoolnode.setAttribute('aria-pressed', 'true');

        // Change the rool.
        this.currenttool = tool;
    },

    /**
     * Change the current color.
     * @protected
     * @method changecolor
     */
    changecolor : function(e, color, editor, colourpicker) {
        var imgcoloururl = M.cfg.wwwroot + '/theme/image.php?theme=standard&component=assignfeedback_editpdf&image=';
        Y.one('.pdfbutton_colour').get('childNodes').item(0).setAttribute('src', imgcoloururl+color);
        editor.currentcolour = color;
        var colourbutton = Y.one(SELECTOR.COLOURBUTTON);
        colourbutton.setAttribute('title', color + 'color');
        colourpicker.hide();
        // Restoring focus to the color button.
        colourbutton.focus();
    },

    /**
     * Attach listeners and enable the save/cancel buttons.
     * @protected
     * @method setup_save_cancel
     */
    setup_save_cancel : function() {
        var cancel = Y.one(SELECTOR.CANCEL),
            save = Y.one(SELECTOR.SAVE);

        cancel.on('mousedown', this.handle_cancel, this);
        cancel.on('key', this.handle_cancel, 'down:13', this);
        cancel.removeAttribute('disabled');

        save.on('mousedown', this.handle_save, this);
        save.on('key', this.handle_save, 'down:13', this);
        save.removeAttribute('disabled');
    },

    /**
     * Hide the popup - but don't save anything anyqhere.
     * @protected
     * @method handle_cancel
     */
    handle_cancel : function(e) {
        e.preventDefault();
        this.dialogue.hide();
    },

    /**
     * JSON encode the pages data - stripping out drawable references which cannot be encoded.
     * @protected
     * @method stringify_pages
     * @return string
     */
    stringify_pages : function() {
        var page, i;
        for (page = 0; page < this.pages.length; page++) {
            for (i = 0; i < this.pages[page].comments.length; i++) {
                delete this.pages[page].comments[i].drawable;
            }
            for (i = 0; i < this.pages[page].annotations.length; i++) {
                delete this.pages[page].annotations[i].drawable;
            }
        }

        return Y.JSON.stringify(this.pages);
    },

    /**
     * Hide the popup - after saving all the edits.
     * @protected
     * @method handle_save
     */
    handle_save : function(e) {
        e.preventDefault();

        var ajaxurl = AJAXBASE,
            config;

        config = {
            method: 'post',
            context: this,
            sync: false,
            data : {
                'sesskey' : M.cfg.sesskey,
                'action' : 'saveallpages',
                'userid' : this.get('userid'),
                'attemptnumber' : this.get('attemptnumber'),
                'assignmentid' : this.get('assignmentid'),
                'pages' : this.stringify_pages()
            },
            on: {
                success: function(tid, response) {
                    var jsondata, downloadlink, deletelink, downloadfilename;
                    Y.log(response.responseText);
                    try {
                        jsondata = Y.JSON.parse(response.responseText);
                        if (jsondata.error) {
                            return new M.core.ajaxException(jsondata);
                        } else {

                            if (jsondata.url) {
                                // We got a valid response with a url and filename for the generated pdf.
                                downloadlink = Y.one('#' + this.get('downloadlinkid'));
                                downloadfilename = Y.one('#' + this.get('downloadlinkid') + ' span');
                                deletelink = Y.one('#' + this.get('deletelinkid'));

                                // Update the filename and show the download and delete links.
                                downloadfilename.setHTML(jsondata.filename);
                                downloadlink.setAttribute('href', jsondata.url);
                                downloadlink.removeClass('hidden');
                                deletelink.removeClass('hidden');

                            }
                            this.dialogue.hide();
                        }
                    } catch (e) {
                        return new M.core.exception(e);
                    }
                },
                failure: function(tid, response) {
                    return M.core.exception(response.responseText);
                }
            }
        };

        Y.io(ajaxurl, config);
    },

    /**
     * Event handler for mousedown or touchstart
     * @protected
     * @param Event
     * @method edit_start
     */
    edit_start : function(e) {
        var offset = Y.one(SELECTOR.DRAWINGCANVAS).getXY(),
            scrolltop = document.body.scrollTop,
            scrollleft = document.body.scrollLeft,
            point = {x : e.clientX - offset[0] + scrollleft,
                     y : e.clientY - offset[1] + scrolltop};

        if (this.currentedit.starttime) {
            return;
        }

        this.currentedit.starttime = new Date().getTime();
        this.currentedit.start = point;
        this.currentedit.end = {x : point.x, y : point.y};
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

        if (this.currenttool === 'pen') {

            shape = this.graphic.addShape({
               type: Y.Path,
                fill: {
                    color: COLOUR[this.currentcolour]
                },
                stroke: {
                    weight: STROKEWEIGHT,
                    color: COLOUR[this.currentcolour]
                },
            });

            // If position is different from last position
            if (!this.currentpenposition.x || !this.currentpenposition.y || this.currentpenposition.x != this.currentedit.end.x || this.currentpenposition.y != this.currentedit.end.y) {
                // save the mouse postion to the list of position
                if (this.currentpenpath.length == 0) {
                    this.currentpenpath.push({x:this.currentedit.start.x,y:this.currentedit.start.y});
                }
                this.currentpenpath.push({x:this.currentedit.end.x,y:this.currentedit.end.y});

                // redraw all the lines
                var previousposition = {x:null,y:null};
                Y.each(this.currentpenpath, function(position, key) {
                    if (!previousposition.x) {
                        previousposition.x = this.currentedit.start.x;
                        previousposition.y = this.currentedit.start.y;
                    }
                    shape.moveTo(previousposition.x, previousposition.y);
                    shape.lineTo(position.x, position.y);
                    previousposition.x = position.x;
                    previousposition.y = position.y;
                }, this);
                shape.end();

                // save the mouse position as the current one
                this.currentpenposition.x = this.currentedit.end.x;
                this.currentpenposition.y = this.currentedit.end.y;
            }
        }

        if (this.currenttool === 'comment' || this.currenttool === 'rectangle' || this.currenttool === 'oval' ) {
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
        }

        if (this.currenttool === 'comment') {
            // We will draw a box with the current background colour.
            shape = this.graphic.addShape({
                type: Y.Rect,
                width: width,
                height: height,
                fill: {
                   color: COLOUR[this.currentcolour]
                },
                x: x,
                y: y
            });
        }

        if (this.currenttool === 'line') {
            shape = this.graphic.addShape({
               type: Y.Path,
                fill: {
                    color: COLOUR[this.currentcolour]
                },
                stroke: {
                    weight: STROKEWEIGHT,
                    color: COLOUR[this.currentcolour]
                },
            });

            shape.moveTo(this.currentedit.start.x, this.currentedit.start.y);
            shape.lineTo(this.currentedit.end.x, this.currentedit.end.y);
            shape.end();
        }

        if (this.currenttool === 'rectangle' || this.currenttool === 'oval') {

            if (this.currenttool === 'rectangle') {
                tooltype = Y.Rect;
            } if (this.currenttool === 'oval') {
                tooltype = Y.Ellipse;
            }

            shape = this.graphic.addShape({
                type: tooltype,
                width: width,
                height: height,
                stroke: {
                   weight: STROKEWEIGHT,
                   color: COLOUR[this.currentcolour]
                },
                x: x,
                y: y
            });
        }

        drawable.shapes.push(shape);

        return drawable;
    },

    /**
     * Delete the shapes from the drawable.
     * @protected
     * @method erase_drawable
     */
    erase_drawable : function(drawable) {
        if (drawable.shapes) {
            while (drawable.shapes.length > 0) {
                this.graphic.removeShape(drawable.shapes.pop());
            }
        }
        if (drawable.nodes) {
            while (drawable.nodes.length > 0) {
                drawable.nodes.pop().remove();
            }
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
            scrolltop = document.body.scrollTop,
            scrollleft = document.body.scrollLeft,
            point = {x : e.clientX - offset[0] + scrollleft,
                     y : e.clientY - offset[1] + scrolltop};

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
            y,
            duration;

        duration = new Date().getTime() - this.currentedit.start;

        if (duration < CLICKTIMEOUT) {
            return;
        }

        if (this.currenttool === 'comment') {
            if (width < 100) {
                width = 100;
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

            // Save the current edit to the server and the current page list.

            data = {
                gradeid : this.get('gradeid'),
                x : x,
                y : y,
                width : width,
                rawtext : '',
                pageno : this.currentpage,
                colour : this.currentcolour
            };

            this.pages[this.currentpage].comments.push(data);
            this.drawables.push(this.draw_comment(data));
            this.erase_drawable(this.currentdrawable);
        } else if (this.currenttool === 'pen') {
            // Create the path string.
            var thepath = '';
                Y.each(this.currentpenpath, function(position, key) {
                thepath = thepath + position.x + "," + position.y + ":";
                // Remove the last ":".
            }, this);
            thepath = thepath.substring(0, thepath.length - 1);

            data = {
                gradeid : this.get('gradeid'),
                path : thepath,
                type : 'pen',
                pageno : this.currentpage,
                colour : this.currentcolour
            };

            this.pages[this.currentpage].annotations.push(data);

            // reset the mouse position for the pen tool.
            this.currentpenposition.x = null;
            this.currentpenposition.y = null;
            this.currentpenpath = [];
        } else {

            if (this.currenttool === 'line') {
                tooltype = 'line';
            } else if (this.currenttool === 'rectangle') {
                tooltype = 'rectangle';
            } else if (this.currenttool === 'oval') {
                tooltype = 'oval';
            }

            data = {
                    gradeid : this.get('gradeid'),
                    x : this.currentedit.start.x,
                    y : this.currentedit.start.y,
                    endx : this.currentedit.end.x,
                    endy : this.currentedit.end.y,
                    type : tooltype,
                    pageno : this.currentpage,
                    colour : this.currentcolour
                };

            this.pages[this.currentpage].annotations.push(data);
            //this.drawables.push(this.draw_annotation(data));
        }

        this.currentedit.starttime = 0;
        this.currentedit.start = false;
        this.currentedit.end = false;
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

        if (annotation.type === 'line') {
            shape = this.graphic.addShape({
                type: Y.Path,
                fill: {
                    color: COLOUR[annotation.colour]
                },
                stroke: {
                    weight: STROKEWEIGHT,
                    color: COLOUR[annotation.colour]
                },
            });

            shape.moveTo(annotation.x, annotation.y);
            shape.lineTo(annotation.endx, annotation.endy);
            shape.end();
        }

        if (annotation.type === 'pen') {
            shape = this.graphic.addShape({
               type: Y.Path,
                fill: {
                    color: COLOUR[annotation.colour]
                },
                stroke: {
                    weight: STROKEWEIGHT,
                    color: COLOUR[annotation.colour]
                },
            });

            // Recreate the pen path array
            var positions = annotation.path.split(':');
            // redraw all the lines
            var previousposition = {x:null,y:null};
            Y.each(positions, function(position, key) {
                var xy = position.split(',');
                if (!previousposition.x) {
                    previousposition.x = xy[0];
                    previousposition.y = xy[1];
                }
                shape.moveTo(previousposition.x, previousposition.y);
                shape.lineTo(xy[0], xy[1]);
                previousposition.x = xy[0];
                previousposition.y = xy[1];
            }, this);

            shape.end();
        }

        if (annotation.type === 'rectangle' || annotation.type === 'oval' ) {

            var width,
                height,
                topleftx,
                toplefty,
                annotationtype;

            if (annotation.type === 'rectangle') {
                annotationtype = Y.Rect;
            } if (annotation.type === 'oval') {
                annotationtype = Y.Ellipse;
            }

            // Convert data to integer to avoid wrong > or < results.
            annotation.x = parseInt(annotation.x);
            annotation.y = parseInt(annotation.y);
            annotation.endx = parseInt(annotation.endx);
            annotation.endy = parseInt(annotation.endy);

            // Work out the boundary box.
            topleftx = annotation.x;
            if (annotation.endx > topleftx) {
                width = annotation.endx - topleftx;
            } else {
                topleftx = annotation.endx;
                width = annotation.x - topleftx;
            }
            toplefty = annotation.y;

            if (annotation.endy > toplefty) {
                height = annotation.endy - toplefty;
            } else {
                toplefty = annotation.endy;
                height = annotation.y - toplefty;
            }

            shape = this.graphic.addShape({
                type: annotationtype,
                width: width,
                height: height,
                stroke: {
                   weight: STROKEWEIGHT,
                   color: COLOUR[annotation.colour]
                },
                x: topleftx,
                y: toplefty
            });
        }

        drawable.shapes.push(shape);

        return drawable;
    },

    /**
     * Delete a comment from the current page.
     * @protected
     * @method delete_comment
     * @param comment
     */
    delete_comment : function(comment) {
        var i = 0, comments;

        comments = this.pages[this.currentpage].comments;
        for (i = 0; i < comments.length; i++) {
            if (comments[i] === comment) {
                comments.splice(i, 1);
                this.erase_drawable(comment.drawable);
                return;
            }
        }
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
        node = Y.Node.create('<textarea/>');
        if (comment.width < 60) {
            comment.width = 60;
        }
        node.setStyles({
            position: 'absolute',
            left: (parseInt(comment.x, 10) + offsetleft) + 'px',
            top: (parseInt(comment.y, 10) + offsettop) + 'px',
            width: comment.width + 'px',
            backgroundColor: COLOUR[comment.colour],
            color: 'black',
            border: '2px solid black',
            fontSize: '12pt',
            fontFamily: 'helvetica',
            minHeight: '1.2em'
        });

        drawingregion.append(node);
        drawable.nodes.push(node);
        node.set('value', comment.rawtext);
        //node.focus();
        node.on('blur', function() {
            // Save the changes back to the comment.
            comment.rawtext = node.get('value');
            comment.width = parseInt(node.getStyle('width'), 10);
            // Trim.
            if (comment.rawtext.replace(/^\s+|\s+$/g, "") === '') {
                // Delete empty comments.
                this.delete_comment(comment);
            }

        }, this);

        comment.drawable = drawable;

        return drawable;
    },

    /**
     * Redraw all the comments and annotations.
     * @protected
     * @method redraw
     */
    redraw : function() {
        var i,
            page;

        page = this.pages[this.currentpage];
        while (this.drawables.length > 0) {
            this.erase_drawable(this.drawables.pop());
        }

        for (i = 0; i < page.annotations.length; i++) {
            this.drawables.push(this.draw_annotation(page.annotations[i]));
        }
        for (i = 0; i < page.comments.length; i++) {
            this.drawables.push(this.draw_comment(page.comments[i]));
        }
    },

    /**
     * Load the image for this pdf page and remove the loading icon (if there).
     * @protected
     * @method all_pages_loaded
     */
    change_page : function() {
        var drawingcanvas = Y.one(SELECTOR.DRAWINGCANVAS),
            page;

        page = this.pages[this.currentpage];
        this.loadingicon.hide();
        drawingcanvas.setStyle('backgroundImage', 'url("' + page.url + '")');

        this.redraw();
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
        },
        deletelinkid : {
            validator : Y.Lang.isString,
            value : ''
        },
        downloadlinkid : {
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
        "json",
        "querystring-stringify-simple",
        "moodle-core-notification-dialog",
        "moodle-core-notification-exception",
        "moodle-core-notification-ajaxexception"
    ]
});
