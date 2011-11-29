YUI.add('moodle-form-autocompletetext', function(Y) {
    var AUTOCOMPLETETEXT = function() {
        AUTOCOMPLETETEXT.superclass.constructor.apply(this, arguments);
    }

    Y.extend(AUTOCOMPLETETEXT, Y.Base, {
        //Initialize checkbox if id is passed
        initializer : function(params) {
            if (params && params.formid && params.source) {
                
              //  var total = 0;
                
                // HTML template string that will be used for each user result.
                var userTemplate =
                  '<div class="autocompletionresultuser">' +
                    '<div class="autocompletionresultuserimg">' +
                      '<img src="{profileimage}" class="autocompletionresultuserimage" ' +
                        'alt="Profile photo for {firstname} {lastname}">' +
                    '</div>' +
                    '<div class="autocompletionresultuserinfo">' +
                      '<div class="autocompletionresultuserfullname">{highlighted}</div>' +
                      '<div class="autocompletionresultuserusername">{username}</div>' +
                    '</div>' +
                  '</div><br/>';

                // Custom formatter for user.
                function userFormatter(query, results) {
                    
                  // Iterate over the array of user result objects and return an
                  // array of HTML strings.
                  return Y.Array.map(results, function (result) {
                    var user = result.raw;

                    // Use string substitution to fill out the tweet template and
                    // return an HTML string for this result.
                    return Y.Lang.sub(userTemplate, {
                      firstname       : user.firstname,
                      lastname        : user.lastname,
                      username        : user.username,
                      highlighted      : result.highlighted,
                      profileimage     : user.profileimage 
                    });
                  });
                }


                Y.one('#'+params.formid).plug(Y.Plugin.AutoComplete, {
                    maxResults: 10,
                    resultHighlighter: 'phraseMatch',
                    minQueryLength: 2,
                    resultTextLocator: function (result) {
                        return result.firstname + ' ' +  result.lastname;
                    },
                    queryDelay: 100,

                    source: params.source,

                    resultListLocator: function (response) {
                        //console.log(response.result);
                        
                        //total = response.total;
                        
                        return response.result;
                    },
                    
                    resultFormatter: userFormatter, //special user display
                    
                    on : {
                        select : function(e) {
                            //set the selected value to the hidden field
                            Y.one('#'+params.setelementid).set('value', e.result.raw.id); //id is the user id
                        
                            //display the selected user
                            var selectuserhtml = Y.Lang.sub(userTemplate, {
                              firstname       : e.result.raw.firstname,
                              lastname        : e.result.raw.lastname,
                              username        : e.result.raw.username,
                              highlighted      : e.result.raw.firstname + ' ' + e.result.raw.lastname,
                              profileimage     : e.result.raw.profileimage 
                            });
                            Y.one('#displayedresult_'+params.elementname).setContent(selectuserhtml);
                        },
                        
                        query : function(e) {
                            //if more than two words indicate that you can use comma to seprate firstname and lastname
                            words = e.query.split(' ');
                            
                            searchtermtotal = 0;
                            for (indice in words)
                              {
                                  //console.log('searchterm: '+searchterm);
                                  searchterm = words[indice].replace(/^\s+|\s+$/g,"");
                                  //console.log('searchterm: '+searchterm);
                                  if (searchterm != '') {
                                      searchtermtotal++;
                                  }
                              }
                            if (searchtermtotal > 2) {
                                Y.one('#displayedresult_'+params.elementname).setContent('Tips: complex name can be searched separating firstname and lastname by a comma. Example: \'Jean Francois,Smith\'');
                            } else {
                                Y.one('#displayedresult_'+params.elementname).setContent('');
                            }
                        }
                    }
                    
                });
            }
        }
    });

    M.form = M.form || {};
    M.form.autocompletetext = function(params) {
        return new AUTOCOMPLETETEXT(params);
    }
}, '@VERSION@', {requires:['base', 'node', 'autocomplete', 'autocomplete-highlighters', 'datasource-get']});