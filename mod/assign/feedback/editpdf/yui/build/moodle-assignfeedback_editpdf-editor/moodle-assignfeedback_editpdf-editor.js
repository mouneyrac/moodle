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

// Globals.
var AJAXBASE = M.cfg.wwwroot + '/mod/assign/feedback/editpdf/ajax.php',
    CSS = {
        DIALOGUE : 'assignfeedback_editpdf_widget'
    },
    SELECTOR = {
        PREVIOUSBUTTON : '.' + CSS.DIALOGUE + ' .navigate-previous-button',
        NEXTBUTTON : '.' + CSS.DIALOGUE + ' .navigate-next-button',
        SEARCHCOMMENTSBUTTON : '.' + CSS.DIALOGUE + ' .searchcommentsbutton',
        SEARCHFILTER : '.assignfeedback_editpdf_searchcomments input',
        SEARCHCOMMENTSLIST : '.assignfeedback_editpdf_searchcomments ul',
        PAGESELECT : '.' + CSS.DIALOGUE + ' .navigate-page-select',
        LOADINGICON : '.' + CSS.DIALOGUE + ' .loading',
        DRAWINGREGION : '.' + CSS.DIALOGUE + ' .drawingregion',
        DRAWINGCANVAS : '.' + CSS.DIALOGUE + ' .drawingcanvas',
        SAVE : '.' + CSS.DIALOGUE + ' .savebutton',
        COMMENTCOLOURBUTTON : '.' + CSS.DIALOGUE + ' .commentcolourbutton',
        COMMENTMENU : ' .commentdrawable a',
        ANNOTATIONCOLOURBUTTON : '.' + CSS.DIALOGUE + ' .annotationcolourbutton',
        STAMPSBUTTON : '.' + CSS.DIALOGUE + ' .currentstampbutton',
        DIALOGUE : '.' + CSS.DIALOGUE
    },
    COMMENTCOLOUR = {
        'white' : 'rgb(255,255,255)',
        'yellow' : 'rgb(255,255,176)',
        'red' : 'rgb(255,176,176)',
        'green' : 'rgb(176,255,176)',
        'blue' : 'rgb(208,208,255)',
        'clear' : 'rgba(255,255,255, 0)'
    },
    ANNOTATIONCOLOUR = {
        'white' : 'rgb(255,255,255)',
        'yellow' : 'rgb(255,255,0)',
        'red' : 'rgb(255,0,0)',
        'green' : 'rgb(0,255,0)',
        'blue' : 'rgb(0,0,255)',
        'black' : 'rgb(0,0,0)'
    },
    CLICKTIMEOUT = 300,
    TOOLSELECTOR = {
        'comment': '.' + CSS.DIALOGUE + ' .commentbutton',
        'pen': '.' + CSS.DIALOGUE + ' .penbutton',
        'line': '.' + CSS.DIALOGUE + ' .linebutton',
        'rectangle': '.' + CSS.DIALOGUE + ' .rectanglebutton',
        'oval': '.' + CSS.DIALOGUE + ' .ovalbutton',
        'stamp': '.' + CSS.DIALOGUE + ' .stampbutton',
        'select': '.' + CSS.DIALOGUE + ' .selectbutton',
        'highlight': '.' + CSS.DIALOGUE + ' .highlightbutton'
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
 * Stamp
 *
 * @namespace M.assignfeedback_editpdf.editor
 * @class Stamp
 */
Stamp = function() {
    /**
     * Stamp pluginfile url (without wwwroot)
     * @property type
     * @type String
     * @public
     */
    this.url = '';

    /**
     * Stamp width
     * @property type
     * @type Integer
     * @public
     */
    this.width = 0;

    /**
     * Stamp height
     * @property type
     * @type Integer
     * @public
     */
    this.height = 0;
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
     * Current comment colour.
     * @property type
     * @type string
     * @protected
     */
    currentcommentcolour : 'yellow',

    /**
     * Current annotation colour.
     * @property type
     * @type string
     * @protected
     */
    currentannotationcolour : 'red',

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
     * @property currentdrawable
     * @type Drawable (or false)
     * @protected
     */
    currentdrawable : false,

    /**
     * Current drawables.
     * @property drawables
     * @type array(Drawable)
     * @protected
     */
    drawables : [],

    /**
     * The colour picker dialogue box.
     * @property currentcolourpicker
     * @type M.core.dialogue
     * @protected
     */
    currentcolourpicker : null,

    /**
     * The comment menu dialogue.
     * @property commentmenu
     * @type M.core.dialogue
     * @protected
     */
    commentmenu : null,

    /**
     * Current comment when the comment menu is open.
     * @property currentcomment
     * @type Object
     * @protected
     */
    currentcomment : null,

    /**
     * The link that opened the current comment menu.
     * @property currentcommentmenulink
     * @type Y.Node
     * @protected
     */
    currentcommentmenulink : null,

    /**
     * The pen tool position (also known as the mouse position :P).
     * @property currentpenposition
     * @type Object
     * @protected
     */
    currentpenposition : {x:null,y:null},

    /**
     * The pen tool path being drawn.
     * @property currentpenpath
     * @type Array
     * @protected
     */
    currentpenpath : [],

    /**
     * The users comments quick list
     * @property quicklist
     * @type Array
     * @protected
     */
    quicklist : [],

    /**
     * The search comments window.
     * @property searchcommentswindow
     * @type M.core.dialogue
     * @protected
     */
    searchcommentswindow : null,


    /**
     * The selected stamp picture.
     * @property currentstamp
     * @type String
     * @protected
     */
    currentstamp : null,

    /**
     * The stamps.
     * @property stamps
     * @type Array
     * @protected
     */
    stamps : [],

    /**
     * The current stamp node id.
     * @property currentstampnodeid
     * @type Integer
     * @protected
     */
    currentstampnodeid : null,

    /**
     * The current stamp picker.
     * @property currentstamppicker
     * @type M.core.dialogue
     * @protected
     */
    currentstamppicker: null,

    /**
     * Called during the initialisation process of the object.
     * @method initializer
     */
    initializer : function() {
        var link,
            deletelink;

        link = Y.one('#' + this.get('linkid'));

        link.on('click', this.link_handler, this);
        link.on('key', this.link_handler, 'down:13', this);

        deletelink = Y.one('#' + this.get('deletelinkid'));
        deletelink.on('click', this.delete_link_handler, this);
        deletelink.on('key', this.delete_link_handler, 'down:13', this);

        this.currentedit.start = false;
        this.currentedit.end = false;
    },

    /**
     * Called to show/hide buttons and set the current colours/stamps.
     * @method refresh_button_state
     */
    refresh_button_state : function() {
        var button, currenttoolnode;
        // Initalise the colour buttons.
        button = Y.one(SELECTOR.COMMENTCOLOURBUTTON);
        button.setStyle('backgroundImage', 'none');
        button.setStyle('background', COMMENTCOLOUR[this.currentcommentcolour]);

        if (this.currentcommentcolour === 'clear') {
            button.setStyle('borderStyle', 'dashed');
        } else {
            button.setStyle('borderStyle', 'solid');
        }

        button = Y.one(SELECTOR.ANNOTATIONCOLOURBUTTON);
        button.setStyle('backgroundImage', 'none');
        button.setStyle('backgroundColor', ANNOTATIONCOLOUR[this.currentannotationcolour]);

        currenttoolnode = Y.one(TOOLSELECTOR[this.currenttool]);
        currenttoolnode.addClass('assignfeedback_editpdf_selectedbutton');
        currenttoolnode.setAttribute('aria-pressed', 'true');
    },

    /**
     * Called to open the pdf editing dialogue.
     * @method link_handler
     */
    link_handler : function(e) {
        var drawingcanvas, drawingregion;
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

            drawingcanvas.on('gesturemovestart', this.edit_start, null, this);
            drawingcanvas.on('gesturemove', this.edit_move, null, this);
            drawingcanvas.on('gesturemoveend', this.edit_end, null, this);

            drawingregion = Y.one(SELECTOR.DRAWINGREGION),
            drawingregion.delegate('click', this.open_comment_menu, SELECTOR.COMMENTMENU, this);
            drawingregion.delegate('key', this.open_comment_menu, 'down:13', SELECTOR.COMMENTMENU, this);

            this.refresh_button_state();
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

        e.preventDefault();

        var ajaxurl = AJAXBASE,
            config;

        config = {
            method: 'post',
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
        this.load_quicklist();
        this.setup_navigation();
        this.setup_toolbar();
        this.change_page();
        this.setup_save_cancel();
    },

    /**
     * Setup colour picker
     * @protected
     * @method setup_colour_picker
     * @param Y.Node button - The button to open the picker
     * @param colours - List of colours
     * @param {function} callback when a new colour is chosen.
     */
    setup_colour_picker : function(node, colours, callback) {
        var colourlist = Y.Node.create('<ul role="menu" class="assignfeedback_editpdf_menu"/>'),
            colourpicker,
            body,
            headertext,
            showhandler;

        Y.each(colours, function(rgb, colour) {
            var button, listitem;

            button = Y.Node.create('<button/>');
            button.setAttribute('title', M.util.get_string(colour, 'assignfeedback_editpdf'));
            button.setAttribute('data-colour', colour);
            button.setAttribute('data-rgb', rgb);
            button.addClass('colour_' + colour);
            button.setStyle('backgroundImage', 'none');
            button.setStyle('background', rgb);
            if (colour === 'clear') {
                button.setStyle('borderStyle', 'dashed');
            } else {
                button.setStyle('borderStyle', 'solid');
            }
            listitem = Y.Node.create('<li/>');
            listitem.append(button);
            colourlist.append(listitem);
        }, this);

        body = Y.Node.create('<div/>');

        colourlist.delegate('click', callback, 'button', this);
        colourlist.delegate('key', callback, 'down:13', 'button', this);
        headertext = Y.Node.create('<h3/>');
        headertext.addClass('accesshide');
        headertext.setHTML(M.util.get_string('colourpicker', 'assignfeedback_editpdf'));
        body.append(headertext);
        body.append(colourlist);

        colourpicker = new M.core.dialogue({
            extraClasses : ['assignfeedback_editpdf_colourpicker'],
            draggable: false,
            centered: false,
            width: 'auto',
            lightbox: false,
            visible: false,
            bodyContent: body,
            zIndex: 100,
            footerContent: '',
            align: {node: node, points: [Y.WidgetPositionAlign.TL, Y.WidgetPositionAlign.BL]}
        });

        body.on('clickoutside', function(e) {
            if (e.target !== node && e.target.ancestor() !== node) {
                e.preventDefault();
                colourpicker.hide();
            }
        });

        showhandler = function() {
            this.currentcolourpicker = colourpicker;
            colourpicker.show();
        };
        node.on('click', showhandler, this);
        node.on('key', showhandler, 'down:13', this);

    },

    /**
     * Setup stamp picker
     * @protected
     * @method setup_stamp_picker
     * @param Y.Node button - The button to open the picker
     * @param stamps - List of stamps (from this.stamps)
     * @param {function} callback when a new stamp is chosen.
     */
    setup_stamp_picker : function(node, stamps, callback) {
        var stamplist = Y.Node.create('<ul role="menu" class="assignfeedback_editpdf_menu"/>'),
            stamppicker,
            body,
            headertext,
            showhandler;

        Y.each(stamps, function(stamp, stampindex) {
            var button, listitem;

            button = Y.Node.create('<button/>');
            button.setAttribute('title', M.util.get_string('stamp', 'assignfeedback_editpdf', stampindex));
            button.setAttribute('stampindex', stampindex);
            button.setStyle('backgroundImage', 'url(\'' + stamp.url + '\')');
            button.setStyle('backgroundSize', '100% 100%');
            button.setStyle('backgroundRepeat', 'no-repeat');
            button.setStyle('borderStyle', 'solid');
            listitem = Y.Node.create('<li/>');
            listitem.append(button);
            stamplist.append(listitem);
        }, this);

        body = Y.Node.create('<div/>');

        stamplist.delegate('click', callback, 'button', this);
        stamplist.delegate('key', callback, 'down:13', 'button', this);
        headertext = Y.Node.create('<h3/>');
        headertext.addClass('accesshide');
        headertext.setHTML(M.util.get_string('stamppicker', 'assignfeedback_editpdf'));
        body.append(headertext);
        body.append(stamplist);

        stamppicker = new M.core.dialogue({
            extraClasses : ['assignfeedback_editpdf_colourpicker'],
            draggable: false,
            centered: false,
            width: 'auto',
            lightbox: false,
            visible: false,
            bodyContent: body,
            footerContent: '',
            align: {node: node, points: [Y.WidgetPositionAlign.TL, Y.WidgetPositionAlign.BL]}
        });

        body.on('clickoutside', function(e) {
            if (e.target !== node && e.target.ancestor() !== node) {
                e.preventDefault();
                stamppicker.hide();
            }
        });

        showhandler = function() {
            this.currentstamppicker = stamppicker;
            stamppicker.show();
        };
        node.on('click', showhandler, this);
        node.on('key', showhandler, 'down:13', this);

    },

    /**
     * Attach listeners and enable the color picker buttons.
     * @protected
     * @method setup_toolbar
     */
    setup_toolbar : function() {
        var toolnode,
            commentcolourbutton,
            annotationcolourbutton,
            searchcommentsbutton;

        // Setup the tool buttons.
        Y.each(TOOLSELECTOR, function(selector, tool) {
            toolnode = Y.one(selector);
            toolnode.on('click', this.handle_toolbutton, this, tool);
            toolnode.on('key', this.handle_toolbutton, 'down:13', this, tool);
            toolnode.setAttribute('aria-pressed', 'false');
        }, this);

        // Set the default tool.
        searchcommentsbutton = Y.one(SELECTOR.SEARCHCOMMENTSBUTTON);
        searchcommentsbutton.on('click', this.open_search_comments, this);
        searchcommentsbutton.on('key', this.open_search_comments, 'down:13', this);


        commentcolourbutton = Y.one(SELECTOR.COMMENTCOLOURBUTTON);
        this.setup_colour_picker(commentcolourbutton, COMMENTCOLOUR, function (e) {
            this.currentcommentcolour = e.target.getAttribute('data-colour');
            this.refresh_button_state();
            this.currentcolourpicker.hide();
        });
        annotationcolourbutton = Y.one(SELECTOR.ANNOTATIONCOLOURBUTTON);
        this.setup_colour_picker(annotationcolourbutton, ANNOTATIONCOLOUR, function (e) {
            this.currentannotationcolour = e.target.getAttribute('data-colour');
            this.refresh_button_state();
            this.currentcolourpicker.hide();
        });

        // Save all stamps into the stamps variable.
        var i =0;
        Y.each(this.get('stampfileurls'), function(stampfileurl) {

            var stamp = new Stamp();
            stamp.url = M.cfg.wwwroot + stampfileurl;
            this.stamps[i] = stamp;
            // Find out the image height/width.
            var img = new Image();
            img.src = M.cfg.wwwroot + stampfileurl;
            img.stampindex = i;
            img.rootcontext = this;
            img.onload = function() {
                this.rootcontext.stamps[this.stampindex].height = this.height;
                this.rootcontext.stamps[this.stampindex].width = this.width;
            };
            i=i+1;
        }, this);
        // Setup the stamp picker
        this.currentstamp = 0;
        //Y.one(".currentstampbutton .smallicon").setAttribute('src', this.stamps[0].url);
        stampsbutton = Y.one(SELECTOR.STAMPSBUTTON);
        stampsbutton.setStyle('backgroundImage', 'url(\'' + this.stamps[this.currentstamp].url + '\')');
        stampsbutton.setStyle('backgroundSize', '100% 100%');
        stampsbutton.setStyle('backgroundRepeat', 'no-repeat');
        this.setup_stamp_picker(stampsbutton, this.stamps, function (e) {
            this.currentstamp = e.target.getAttribute('stampindex');
            button = Y.one(SELECTOR.STAMPSBUTTON);
            button.setStyle('backgroundImage', 'url(\'' + this.stamps[this.currentstamp].url + '\')');
            button.setStyle('backgroundSize', '100% 100%');
            button.setStyle('backgroundRepeat', 'no-repeat');
            this.currentstamppicker.hide();
        });
    },

    /**
     * Change the current tool.
     * @protected
     * @method handle_toolbutton
     */
    handle_toolbutton : function(e, tool) {
        var currenttoolnode;

        e.preventDefault();

        // Change style of the pressed button.
        currenttoolnode = Y.one(TOOLSELECTOR[this.currenttool]);
        currenttoolnode.removeClass('assignfeedback_editpdf_selectedbutton');
        currenttoolnode.setAttribute('aria-pressed', 'false');
        this.currenttool = tool;
        this.refresh_button_state();
    },

    /**
     * Change the current color.
     * @protected
     * @method changecolor
     */
    changecolor : function(e, color, editor, colourpicker) {
        var imgcoloururl,
            colourbutton;

        imgcoloururl = M.cfg.wwwroot + '/theme/image.php?theme=standard&component=assignfeedback_editpdf&image=';
        Y.one('.pdfbutton_colour').get('childNodes').item(0).setAttribute('src', imgcoloururl+color);
        editor.currentcolour = color;
        colourbutton = Y.one(SELECTOR.COLOURBUTTON);
        colourbutton.setAttribute('title', M.util.get_string(color, 'assignfeedback_editpdf'));
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
        var save;

        save = Y.one(SELECTOR.SAVE);

        save.on('mousedown', this.handle_save, this);
        save.on('key', this.handle_save, 'down:13', this);
        save.removeAttribute('disabled');
    },

    /**
     * Hide the popup - but don't save anything anywhere.
     * @protected
     * @method handle_cancel
     */
    handle_cancel : function(e) {
        e.preventDefault();
        this.dialogue.hide();
    },

    /**
     * JSON encode the current page data - stripping out drawable references which cannot be encoded.
     * @protected
     * @method stringify_current_page
     * @return string
     */
    stringify_current_page : function() {
        var comments = [],
            annotations = [],
            page,
            i = 0;

        for (i = 0; i < this.pages[this.currentpage].comments.length; i++) {
            comments[i] = this.clean_comment_data(this.pages[this.currentpage].comments[i]);
        }
        for (i = 0; i < this.pages[this.currentpage].annotations.length; i++) {
            annotations[i] = this.clean_annotation_data(this.pages[this.currentpage].annotations[i]);
        }

        page = { comments : comments, annotations : annotations };

        return Y.JSON.stringify(page);
    },

    /**
     * Hide the popup - after generating a new pdf.
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
                'action' : 'generatepdf',
                'userid' : this.get('userid'),
                'attemptnumber' : this.get('attemptnumber'),
                'assignmentid' : this.get('assignmentid')
            },
            on: {
                success: function(tid, response) {
                    var jsondata, downloadlink, deletelink, downloadfilename;
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
     * Event handler for mousedown or touchstart.
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

        if (this.currenttool === 'stamp') {
            this.redraw_current_edit();
        }

    },

    /**
     * Generate a drawable from the current in progress edit.
     * @protected
     * @method get_current_drawable
     */
    get_current_drawable : function() {
        var drawable = new Drawable(),
            shape, width, height, x, y, highlightcolour;

        if (!this.currentedit.start || !this.currentedit.end) {
            return false;
        }

        if (this.currenttool === 'pen') {

            shape = this.graphic.addShape({
               type: Y.Path,
                fill: {
                    color: ANNOTATIONCOLOUR[this.currentannotationcolour]
                },
                stroke: {
                    weight: STROKEWEIGHT,
                    color: ANNOTATIONCOLOUR[this.currentannotationcolour]
                }
            });

            // If position is different from last position.
            if (!this.currentpenposition.x || !this.currentpenposition.y ||
                this.currentpenposition.x !== this.currentedit.end.x ||
                this.currentpenposition.y !== this.currentedit.end.y) {
                // Save the mouse postion to the list of position.
                if (this.currentpenpath.length === 0) {
                    this.currentpenpath.push({x:this.currentedit.start.x,y:this.currentedit.start.y});
                }
                this.currentpenpath.push({x:this.currentedit.end.x,y:this.currentedit.end.y});

                // Redraw all the lines.
                var previousposition = {x:null,y:null};
                Y.each(this.currentpenpath, function(position) {
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

                // Save the mouse position as the current one.
                this.currentpenposition.x = this.currentedit.end.x;
                this.currentpenposition.y = this.currentedit.end.y;
            }
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

        if (this.currenttool === 'stamp') {
            // Delete previous stamp if it exists.

            // Redraw stamp.
            this.currentstampnodeid = (Math.random()*10000000000000000)+1;
            // We need to put the image as background otherwise the browser will try to drag the image.
            // We don't want to disable the on dragstart event.
            stampnode = Y.Node.create('<div id="'+this.currentstampnodeid+
                '" class="stamp" style="background-image:url(\'' + this.stamps[this.currentstamp].url + '\')"/>');
            Y.one('.drawingcanvas').append(stampnode);
            stampnode.setStyles({
                position: "absolute",
                left: this.currentedit.end.x,
                top: this.currentedit.end.y,
                height: this.stamps[this.currentstamp].height,
                width: this.stamps[this.currentstamp].width
            });

            drawable.nodes.push(stampnode);
            return drawable;
        }

        if (this.currenttool === 'comment') {
            // We will draw a box with the current background colour.
            shape = this.graphic.addShape({
                type: Y.Rect,
                width: width,
                height: height,
                fill: {
                   color: COMMENTCOLOUR[this.currentcommentcolour]
                },
                x: x,
                y: y
            });
        }

        if (this.currenttool === 'line') {
            shape = this.graphic.addShape({
               type: Y.Path,
                fill: {
                    color: ANNOTATIONCOLOUR[this.currentannotationcolour]
                },
                stroke: {
                    weight: STROKEWEIGHT,
                    color: ANNOTATIONCOLOUR[this.currentannotationcolour]
                }
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
                   color: ANNOTATIONCOLOUR[this.currentannotationcolour]
                },
                x: x,
                y: y
            });
        }

        if (this.currenttool === 'highlight') {
            highlightcolour = ANNOTATIONCOLOUR[this.currentannotationcolour];

            // Add an alpha channel to the rgb colour.

            highlightcolour = highlightcolour.replace('rgb', 'rgba');
            highlightcolour = highlightcolour.replace(')', ',0.5)');

            shape = this.graphic.addShape({
                type: Y.Rect,
                width: width,
                height: 16,
                stroke: false,
                fill: {
                    color: highlightcolour
                },
                x: x,
                y: this.currentedit.start.y
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
        if (!drawable) {
            return;
        }
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
     * Event handler for mousemove.
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
     * Clean a comment record, returning an oject with only fields that are valid.
     * @protected
     * @method clean_comment_data
     * @param comment
     * @return string
     */
    clean_comment_data : function(comment) {
        return {
            gradeid : comment.gradeid,
            x : comment.x,
            y : comment.y,
            width : comment.width,
            rawtext : comment.rawtext,
            pageno : comment.currentpage,
            colour : comment.colour
        };
    },

    /**
     * Clean a annotation record, returning an oject with only fields that are valid.
     * @protected
     * @method clean_annotation_data
     * @param annotation
     * @return string
     */
    clean_annotation_data : function(annotation) {
        return {
            gradeid : annotation.gradeid,
            x : annotation.x,
            y : annotation.y,
            endx : annotation.endx,
            endy : annotation.endy,
            type : annotation.type,
            path : annotation.path,
            pageno : annotation.pageno,
            colour : annotation.colour
        };
    },

    /**
     * Event handler for mouseup or touchend.
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
            duration,
            thepath;

        duration = new Date().getTime() - this.currentedit.start;

        if (duration < CLICKTIMEOUT || this.currentedit.start === false) {
            return;
        }

        if (this.currenttool === 'comment') {
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
            if (width < 100) {
                width = 100;
            }

            // Save the current edit to the server and the current page list.

            data = {
                gradeid : this.get('gradeid'),
                x : x,
                y : y,
                width : width,
                rawtext : '',
                pageno : this.currentpage,
                colour : this.currentcommentcolour
            };

            this.pages[this.currentpage].comments.push(data);
            this.drawables.push(this.draw_comment(data, true));
            this.erase_drawable(this.currentdrawable);
        } else if (this.currenttool === 'pen') {
            // Create the path string.
            thepath = '';
            Y.each(this.currentpenpath, function(position) {
                thepath = thepath + position.x + "," + position.y + ":";
                // Remove the last ":".
            }, this);
            thepath = thepath.substring(0, thepath.length - 1);

            data = {
                gradeid : this.get('gradeid'),
                path : thepath,
                type : 'pen',
                pageno : this.currentpage,
                colour : this.currentannotationcolour
            };

            this.pages[this.currentpage].annotations.push(data);

            // Reset the mouse position for the pen tool.
            this.currentpenposition.x = null;
            this.currentpenposition.y = null;
            this.currentpenpath = [];
        } else if (this.currenttool === 'highlight') {
            // Work out the boundary box.
            x = this.currentedit.start.x;
            if (this.currentedit.end.x > x) {
                width = this.currentedit.end.x - x;
            } else {
                x = this.currentedit.end.x;
                width = this.currentedit.start.x - x;
            }
            y = this.currentedit.start.y;
            height = 16;

            data = {
                    gradeid : this.get('gradeid'),
                    x : x,
                    y : y,
                    endx : x + width,
                    endy : y + height,
                    type : this.currenttool,
                    pageno : this.currentpage,
                    colour : this.currentannotationcolour
                };

            this.pages[this.currentpage].annotations.push(data);
        } else if (this.currenttool === 'stamp') {
            // In path we will save the file name.
            data = {
                gradeid : this.get('gradeid'),
                x : this.currentedit.start.x,
                y : this.currentedit.start.y,
                endx : this.currentedit.end.x,
                endy : this.currentedit.end.y,
                type : this.currenttool,
                pageno : this.currentpage,
                colour : this.currentannotationcolour,
                path : this.stamps[this.currentstamp].url.replace(/^.*[\\\/]/, '')
            };

            this.pages[this.currentpage].annotations.push(data);
        } else {
            data = {
                gradeid : this.get('gradeid'),
                x : this.currentedit.start.x,
                y : this.currentedit.start.y,
                endx : this.currentedit.end.x,
                endy : this.currentedit.end.y,
                type : this.currenttool,
                pageno : this.currentpage,
                colour : this.currentannotationcolour
            };

            this.pages[this.currentpage].annotations.push(data);
        }

        this.save_current_page();

        this.currentedit.starttime = 0;
        this.currentedit.start = false;
        this.currentedit.end = false;
        this.currentdrawable = false;
    },

    /**
     * Save all the annotations and comments for the current page.
     * @protected
     * @method save_current_page
     */
    save_current_page : function() {
        var ajaxurl = AJAXBASE,
            config;

        config = {
            method: 'post',
            context: this,
            sync: false,
            data : {
                'sesskey' : M.cfg.sesskey,
                'action' : 'savepage',
                'index' : this.currentpage,
                'userid' : this.get('userid'),
                'attemptnumber' : this.get('attemptnumber'),
                'assignmentid' : this.get('assignmentid'),
                'page' : this.stringify_current_page()
            },
            on: {
                success: function(tid, response) {
                    var jsondata;
                    try {
                        jsondata = Y.JSON.parse(response.responseText);
                        if (jsondata.error) {
                            return new M.core.ajaxException(jsondata);
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
     * Draw an annotation
     * @protected
     * @method draw_annotation
     * @param annotation
     * @return Drawable
     */
    draw_annotation : function(annotation) {
        var drawable,
            positions,
            previousposition,
            xy,
            width,
            height,
            topleftx,
            toplefty,
            annotationtype;


        drawable = new Drawable();

        if (annotation.type === 'stamp') {
            // Find the matching stamp
            Y.each(this.stamps, function(stamp) {
                if (annotation.path === stamp.url.replace(/^.*[\\\/]/, '')) {
                    // Redraw stamp.
                    this.currentstampnodeid = (Math.random()*10000000000000000)+1;
                    // We need to put the image as background otherwise the browser will try to drag the image.
                    // Also we don't want to disable the image drag event (dragstart event), so we use background image.
                    stampnode = Y.Node.create('<div id="'+this.currentstampnodeid+
                        '" class="stamp" style="background-image:url(\'' + stamp.url + '\')"/>');
                    Y.one('.drawingcanvas').append(stampnode);
                    stampnode.setStyles({
                        position: "absolute",
                        left: annotation.x,
                        top: annotation.y,
                        height: stamp.height,
                        width: stamp.width
                    });

                    // Resize the stamp to the correct heigth/width.
                    var img = new Image();
                    img.src = stamp.url;
                    img.stampnode = stampnode;
                    img.onload = function() {
                        this.stampnode.setStyles({
                            height: this.height,
                            width: this.width
                        });
                    };

                    drawable.nodes.push(stampnode);
                    return drawable;
                }
            }, this);
        }

        if (annotation.type === 'line') {
            shape = this.graphic.addShape({
                type: Y.Path,
                fill: {
                    color: ANNOTATIONCOLOUR[annotation.colour]
                },
                stroke: {
                    weight: STROKEWEIGHT,
                    color: ANNOTATIONCOLOUR[annotation.colour]
                }
            });

            shape.moveTo(annotation.x, annotation.y);
            shape.lineTo(annotation.endx, annotation.endy);
            shape.end();
        }

        if (annotation.type === 'pen') {
            shape = this.graphic.addShape({
               type: Y.Path,
                fill: {
                    color: ANNOTATIONCOLOUR[annotation.colour]
                },
                stroke: {
                    weight: STROKEWEIGHT,
                    color: ANNOTATIONCOLOUR[annotation.colour]
                }
            });

            // Recreate the pen path array.
            positions = annotation.path.split(':');
            // Redraw all the lines.
            previousposition = {x:null,y:null};
            Y.each(positions, function(position) {
                xy = position.split(',');
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
            if (annotation.type === 'rectangle') {
                annotationtype = Y.Rect;
            } if (annotation.type === 'oval') {
                annotationtype = Y.Ellipse;
            }

            // Convert data to integer to avoid wrong > or < results.
            annotation.x = parseInt(annotation.x, 10);
            annotation.y = parseInt(annotation.y, 10);
            annotation.endx = parseInt(annotation.endx, 10);
            annotation.endy = parseInt(annotation.endy, 10);

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
                   color: ANNOTATIONCOLOUR[annotation.colour]
                },
                x: topleftx,
                y: toplefty
            });
        }
        if (annotation.type === 'highlight' ) {
            // Convert data to integer to avoid wrong > or < results.
            annotation.x = parseInt(annotation.x, 10);
            annotation.y = parseInt(annotation.y, 10);
            annotation.endx = parseInt(annotation.endx, 10);
            annotation.endy = parseInt(annotation.endy, 10);

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

            highlightcolour = ANNOTATIONCOLOUR[annotation.colour];

            // Add an alpha channel to the rgb colour.

            highlightcolour = highlightcolour.replace('rgb', 'rgba');
            highlightcolour = highlightcolour.replace(')', ',0.5)');

            shape = this.graphic.addShape({
                type: Y.Rect,
                width: width,
                height: height,
                stroke: false,
                fill: {
                    color: highlightcolour
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
                this.save_current_page();
                return;
            }
        }
    },

    /**
     * Draw a comment.
     * @protected
     * @method draw_comment
     * @param comment
     * @param boolean focus - Set the keyboard focus to the new comment if true
     * @return Drawable
     */
    draw_comment : function(comment, focus) {
        var drawable = new Drawable(),
            node,
            drawingregion = Y.one(SELECTOR.DRAWINGREGION),
            offsetcanvas = Y.one(SELECTOR.DRAWINGCANVAS).getXY(),
            offsetdialogue = Y.one(SELECTOR.DIALOGUE).getXY(),
            offsetleft = offsetcanvas[0] - offsetdialogue[0],
            offsettop = offsetcanvas[1] - offsetdialogue[1],
            container,
            menu;

        // Lets add a contenteditable div.
        node = Y.Node.create('<textarea/>');
        container = Y.Node.create('<div class="commentdrawable"/>');
        menu = Y.Node.create('<a href="#"><img src="' + this.get('menuicon') + '"/></a>');
        container.append(node);
        container.append(menu);
        if (comment.width < 100) {
            comment.width = 100;
        }
        container.setStyles({
            position: 'absolute',
            left: (parseInt(comment.x, 10) + offsetleft) + 'px',
            top: (parseInt(comment.y, 10) + offsettop) + 'px'
        });
        node.setStyles({
            width: comment.width + 'px',
            backgroundColor: COMMENTCOLOUR[comment.colour]
        });

        drawingregion.append(container);
        drawable.nodes.push(container);
        node.set('value', comment.rawtext);
        node.setStyle('height', node.get('scrollHeight') - 8 + 'px');
        this.attach_comment_events(comment, node, menu);
        if (focus) {
            node.focus();
        }
        comment.drawable = drawable;

        return drawable;
    },

    /**
     * Event handler to load the users quick comments list.
     *
     * @protected
     * @method load_quicklist
     */
    load_quicklist : function() {
        var ajaxurl = AJAXBASE,
            config;

        config = {
            method: 'get',
            context: this,
            sync: false,
            data : {
                'sesskey' : M.cfg.sesskey,
                'action' : 'loadquicklist',
                'userid' : this.get('userid'),
                'attemptnumber' : this.get('attemptnumber'),
                'assignmentid' : this.get('assignmentid')
            },
            on: {
                success: function(tid, response) {
                    var jsondata;
                    try {
                        jsondata = Y.JSON.parse(response.responseText);
                        if (jsondata.error) {
                            return new M.core.ajaxException(jsondata);
                        } else {
                            this.quicklist = [];
                            Y.each(jsondata, function(comment) {
                                this.quicklist.push(comment);
                            }, this);
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
     * Event handler to add a comment to the users quicklist.
     *
     * @protected
     * @method add_to_quicklist
     */
    add_to_quicklist : function() {
        var ajaxurl = AJAXBASE,
            config;

        this.commentmenu.hide();
        // Do not save empty comments.
        if (this.currentcomment.rawtext === '') {
            return;
        }

        config = {
            method: 'post',
            context: this,
            sync: false,
            data : {
                'sesskey' : M.cfg.sesskey,
                'action' : 'addtoquicklist',
                'userid' : this.get('userid'),
                'commenttext' : this.currentcomment.rawtext,
                'width' : this.currentcomment.width,
                'colour' : this.currentcomment.colour,
                'attemptnumber' : this.get('attemptnumber'),
                'assignmentid' : this.get('assignmentid')
            },
            on: {
                success: function(tid, response) {
                    var jsondata;
                    try {
                        jsondata = Y.JSON.parse(response.responseText);
                        if (jsondata.error) {
                            return new M.core.ajaxException(jsondata);
                        } else {
                            this.quicklist.push(jsondata);
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
     * Event handler to remove a comment from the users quicklist.
     *
     * @protected
     * @method remove_from_quicklist
     */
    remove_from_quicklist : function(e) {
        var target = e.target,
            comment = target.getData('comment'),
            ajaxurl = AJAXBASE,
            config;

        this.commentmenu.hide();

        if (!comment) {
            target = target.ancestor();
            comment = target.getData('comment');
        }

        // Should not happen.
        if (!comment) {
            return;
        }

        config = {
            method: 'post',
            context: this,
            sync: false,
            data : {
                'sesskey' : M.cfg.sesskey,
                'action' : 'removefromquicklist',
                'userid' : this.get('userid'),
                'commentid' : comment.id,
                'attemptnumber' : this.get('attemptnumber'),
                'assignmentid' : this.get('assignmentid')
            },
            on: {
                success: function() {
                    var i;

                    // Find and remove the comment from the quicklist.
                    i = this.quicklist.indexOf(comment);
                    if (i >= 0) {
                        this.quicklist.splice(i, 1);
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
     * A quick comment was selected in the list, update the active comment and redraw the page.
     *
     * @param Event e
     * @protected
     * @method set_comment_from_quick_comment
     */
    set_comment_from_quick_comment : function(e) {
        var target = e.target,
            comment = target.getData('comment');

        this.commentmenu.hide();

        if (!comment) {
            target = target.ancestor();
            comment = target.getData('comment');
        }

        // Should not happen.
        if (!comment || !this.currentcomment) {
            return;
        }
        this.currentcomment.rawtext = comment.rawtext;
        this.currentcomment.width = comment.width;
        this.currentcomment.colour = comment.colour;

        this.save_current_page();

        this.redraw();
    },

    /**
     * Event handler to filter the list of comments.
     *
     * @protected
     * @method filter_search_comments
     */
    filter_search_comments : function() {
        var filternode,
            commentslist,
            filtertext;

        filternode = Y.one(SELECTOR.SEARCHFILTER);
        commentslist = Y.one(SELECTOR.SEARCHCOMMENTSLIST);

        filtertext = filternode.get('value');

        commentslist.all('li').each(function (node) {
            if (node.get('text').indexOf(filtertext) !== -1) {
                node.show();
            } else {
                node.hide();
            }
        });


    },

    /**
     * Event handler to focus on a selected comment.
     *
     * @param Event e
     * @protected
     * @method focus_on_comment
     */
    focus_on_comment : function(e) {
        var target = e.target.ancestor('li'),
            comment = target.getData('comment');

        this.searchcommentswindow.hide();

        if (comment.pageno === this.currentpage) {
            comment.drawable.nodes[0].one('textarea').focus();
        } else {
            // comment is on a different page.
            this.currentpage = comment.pageno;
            this.change_page();
            comment.drawable.nodes[0].one('textarea').focus();
        }
    },

    /**
     * Event handler to open the comment search interface.
     *
     * @param Event e
     * @protected
     * @method open_search_comments
     */
    open_search_comments : function(e) {
        var commentlist, commentfilter, container, placeholder;

        if (!this.searchcommentswindow) {
            container = Y.Node.create('<div/>');

            placeholder = M.util.get_string('filter', 'assignfeedback_editpdf');
            commentfilter = Y.Node.create('<input type="text" size="20" placeholder="' + placeholder + '"/>');
            container.append(commentfilter);
            commentlist = Y.Node.create('<ul role="menu" class="assignfeedback_editpdf_menu"/>');
            container.append(commentlist);

            this.searchcommentswindow = new M.core.dialogue({
                extraClasses : ['assignfeedback_editpdf_searchcomments'],
                draggable: false,
                centered: true,
                lightbox: true,
                width: '400px',
                visible: false,
                zIndex: 100,
                headerContent: M.util.get_string('searchcomments', 'assignfeedback_editpdf'),
                bodyContent: container,
                footerContent: ''
            });

            commentfilter.on('keyup', this.filter_search_comments, null, this);

            commentlist.delegate('click', this.focus_on_comment, 'a', this);
            commentlist.delegate('key', this.focus_on_comment, 'enter,space', 'a', this);
        } else {
            commentlist = this.searchcommentswindow.get('boundingBox').one('ul');
            commentlist.all('li').remove(true);
        }

        // Rebuild the latest list of comments.
        Y.each(this.pages, function(page) {
            Y.each(page.comments, function(comment) {
                var commentnode = Y.Node.create('<li><a href="#" tabindex="-1"><pre>' + comment.rawtext + '</pre></a></li>');
                commentlist.append(commentnode);
                commentnode.setData('comment', comment);
            }, this);
        }, this);

        this.searchcommentswindow.centerDialogue();
        this.searchcommentswindow.show();
        e.preventDefault();
    },

    /**
     * Event handler to open the quicklist/delete menu for a comment.
     *
     * @param Event e
     * @protected
     * @method open_comment_menu
     */
    open_comment_menu : function(e) {
        var target = e.target,
            comment = e.target.getData('comment'),
            commentlinks,
            link;

        // Cancel deleting of empty comment.
        this.commenttodelete = null;

        if (!comment) {
            // The event triggered on the img tag, not the a.
            target = target.ancestor();
            comment = target.getData('comment');
        }

        this.currentcomment = comment;
        this.currentcommentmenulink = target;

        // Build the comment menu only the first time.
        if (!this.commentmenu) {
            // Build the list of comments.
            commentlinks = Y.Node.create('<ul role="menu" class="assignfeedback_editpdf_menu"/>');

            link = Y.Node.create('<li><a tabindex="-1" href="#">' + M.util.get_string('addtoquicklist', 'assignfeedback_editpdf') + '</a></li>');
            link.on('click', this.add_to_quicklist, this);
            link.on('key', this.add_to_quicklist, 'enter,space', this);

            commentlinks.append(link);

            link = Y.Node.create('<li><a tabindex="-1" href="#">' + M.util.get_string('deletecomment', 'assignfeedback_editpdf') + '</a></li>');
            link.on('click', function() { this.commentmenu.hide(); this.delete_comment(this.currentcomment); }, this);
            link.on('key', function() { this.commentmenu.hide(); this.delete_comment(this.currentcomment); }, 'enter,space', this);

            commentlinks.append(link);

            link = Y.Node.create('<li><hr/></li>');
            commentlinks.append(link);

            this.commentmenu = new M.core.dialogue({
                extraClasses : ['assignfeedback_editpdf_commentmenu'],
                draggable: false,
                centered: false,
                lightbox: false,
                width: 'auto',
                visible: false,
                zIndex: 100,
                bodyContent: commentlinks,
                footerContent: '',
                align: {node: target, points: [Y.WidgetPositionAlign.TL, Y.WidgetPositionAlign.BL]}
            });
            // Close the menu on click outside.
            commentlinks.on('clickoutside', function(e) {
                if (e.target !== this.currentcommentmenulink && e.target.ancestor() !== this.currentcommentmenulink) {
                    e.preventDefault();
                    this.commentmenu.hide();
                }
            }, this);
        } else {
            this.commentmenu.align( target, [Y.WidgetPositionAlign.TL, Y.WidgetPositionAlign.BL]);
            commentlinks = this.commentmenu.get('boundingBox').one('ul');
            commentlinks.all('.quicklist_comment').remove(true);
        }

        // Now build the list of quicklist comments.
        Y.each(this.quicklist, function(comment) {
            var listitem = Y.Node.create('<li class="quicklist_comment"></li>'),
                linkitem = Y.Node.create('<a href="#" tabindex="-1">' + comment.rawtext + '</a>'),
                deletelinkitem = Y.Node.create('<a href="#" tabindex="-1" class="delete_quicklist_comment">' +
                                               '<img src="' + M.util.image_url('t/delete', 'core') + '" ' +
                                               'alt="' + M.util.get_string('deletecomment', 'assignfeedback_editpdf') + '"/>' +
                                               '</a>');
            listitem.append(linkitem);
            listitem.append(deletelinkitem);
            listitem.setData('comment', comment);

            commentlinks.append(listitem);

            linkitem.on('click', this.set_comment_from_quick_comment, this);
            linkitem.on('key', this.set_comment_from_quick_comment, 'space,enter', this);

            deletelinkitem.setData('comment', comment);

            deletelinkitem.on('click', this.remove_from_quicklist, this);
            deletelinkitem.on('key', this.remove_from_quicklist, 'space,enter', this);
        }, this);

        this.commentmenu.show();
    },

    /**
     * Delete an empty comment if it's menu hasn't been opened in time.
     * @method delete_comment_later
     */
    delete_comment_later : function() {
        if (this.commenttodelete !== null) {
            this.delete_comment(this.commenttodelete);
        }
    },

    /**
     * Comment nodes have a bunch of event handlers attached to them directly.
     * This is all done here for neatness.
     *
     * @protected
     * @method attach_comment_events
     * @param comment - The comment structure
     * @param node - The Y.Node representing the comment.
     */
    attach_comment_events : function(comment, node, menu) {
        // Save the text on blur.
        node.on('blur', function() {
            // Save the changes back to the comment.
            comment.rawtext = node.get('value');
            comment.width = parseInt(node.getStyle('width'), 10);

            // Trim.
            if (comment.rawtext.replace(/^\s+|\s+$/g, "") === '') {
                // Delete empty comments.
                this.commenttodelete = comment;
                Y.later(400, this, this.delete_comment_later);
            }
            this.save_current_page();
        }, this);

        // For delegated event handler.
        menu.setData('comment', comment);

        node.on('keyup', function() {
            var scrollHeight = node.get('scrollHeight') - 8;
            this.setStyle('height', scrollHeight + 'px');
        });

        node.on('gesturemovestart', function(e) {
            node.setData('dragging', true);
            node.setData('offsetx', e.clientX - node.getX());
            node.setData('offsety', e.clientY - node.getY());
        });
        node.on('gesturemoveend', function() {
            node.setData('dragging', false);
            this.save_current_page();
        }, null, this);
        node.on('gesturemove', function(e) {
            var x = e.clientX - node.getData('offsetx'),
                y = e.clientY - node.getData('offsety'),
                canvas = Y.one(SELECTOR.DRAWINGCANVAS),
                offsetcanvas = canvas.getXY(),
                canvaswidth,
                canvasheight,
                nodewidth,
                nodeheight,
                offsetleft = offsetcanvas[0],
                offsettop = offsetcanvas[1];

            canvaswidth = parseInt(canvas.getStyle('width'), 10);
            canvasheight = parseInt(canvas.getStyle('height'), 10);
            nodewidth = parseInt(node.getStyle('width'), 10);
            nodeheight = parseInt(node.getStyle('height'), 10);

            // Constrain the comment to the canvas.
            if (x < offsetleft) {
                x = offsetleft;
            }
            if (y < offsettop) {
                y = offsettop;
            }
            if (x - offsetleft + nodewidth > canvaswidth) {
                x = offsetleft + canvaswidth - nodewidth;
            }
            if (y - offsettop + nodeheight > canvasheight) {
                y = offsettop + canvasheight - nodeheight;
            }

            comment.x = x - offsetleft;
            comment.y = y - offsettop;

            node.ancestor().setX(x);
            node.ancestor().setY(y);
        });
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
            this.drawables.push(this.draw_comment(page.comments[i], false));
        }
    },

    /**
     * Load the image for this pdf page and remove the loading icon (if there).
     * @protected
     * @method change_page
     */
    change_page : function() {
        var drawingcanvas = Y.one(SELECTOR.DRAWINGCANVAS),
            page,
            previousbutton,
            nextbutton;

        previousbutton = Y.one(SELECTOR.PREVIOUSBUTTON);
        nextbutton = Y.one(SELECTOR.NEXTBUTTON);

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

        page = this.pages[this.currentpage];
        this.loadingicon.hide();
        drawingcanvas.setStyle('backgroundImage', 'url("' + page.url + '")');

        this.redraw();
    },

    /**
     * Now we know how many pages there are,
     * we can enable the navigation controls.
     * @protected
     * @method setup_navigation
     */
    setup_navigation : function() {
        var pageselect,
            i,
            option,
            previousbutton,
            nextbutton;

        pageselect = Y.one(SELECTOR.PAGESELECT);

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
        pageselect.on('change', function() {
            this.currentpage = pageselect.get('value');
            this.change_page();
        }, this);

        previousbutton = Y.one(SELECTOR.PREVIOUSBUTTON);
        nextbutton = Y.one(SELECTOR.NEXTBUTTON);

        previousbutton.on('click', this.previous_page, this);
        previousbutton.on('key', this.previous_page, 'down:13', this);
        nextbutton.on('click', this.next_page, this);
        nextbutton.on('key', this.next_page, 'down:13', this);
    },

    /**
     * Navigate to the previous page.
     * @protected
     * @method previous_page
     */
    previous_page : function() {
        this.currentpage--;
        if (this.currentpage < 0) {
            this.currentpage = 0;
        }
        this.change_page();
    },

    /**
     * Navigate to the next page.
     * @protected
     * @method next_page
     */
    next_page : function() {
        this.currentpage++;
        if (this.currentpage >= this.pages.length) {
            this.currentpage = this.pages.length - 1;
        }
        this.change_page();
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
        },
        menuicon : {
            validator : Y.Lang.isString,
            value : ''
        },
        stampfileurls : {
            validator : Y.Lang.isArray,
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
        "event-move",
        "querystring-stringify-simple",
        "moodle-core-notification-dialog",
        "moodle-core-notification-exception",
        "moodle-core-notification-ajaxexception"
    ]
});
