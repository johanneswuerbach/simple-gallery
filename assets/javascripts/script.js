var length = 0;
var current = 0;
var changing = false;
var dropbox;
var flickrKey = 'YOUR_FLICKR_KEY';
$(document).ready(function() {
    
    dropbox = $('#upload');
    
    $('#tags').tagit({
        onTagUpdate : updateTags
    });
    
    callBackend({action: 'start'}, function(data) {
        if (data.logged_in == 1) {
            buttons(true);
        }
        else {
            buttons(false);
        }
        display('picture');
        
        length = data.pictures;
        showImage(0);
    });
    
    
    $('#logout_btn').click(function(e) {
        e.preventDefault();
        callBackend({action: 'logout'}, function(data) {
            buttons(false);
            display('picture');
        });
    });
    
    $('#login_btn').click(function(e) {
        e.preventDefault();
        display('login');
    });
    
    $('#upload_btn').click(function(e) {
        e.preventDefault();
        display('upload');

    	dropbox.filedrop({
    		// The name of the $_FILES entry:
    		paramname: 'picture',
    		url: 'backend.php',

    		uploadFinished: function(i, file, response) {
    		    parseMessage(response);
    			if(response.status == "ok") {
    			    length = response.pictures;
    			    showImage(response.id);
    			    $('#upload_btn').show();
    			}
    		},
    		
    		data: {
                action: 'upload'
            },
            
        	error: function(err, file) {
    			switch(err) {
    				case 'BrowserNotSupported':
    					showMessage(false, 'No HTML5 file uploads!');
    					break;
    				case 'TooManyFiles':
    					showMessage(false, 'Too many files!');
    					break;
    				case 'FileTooLarge':
    					showMessage(false, 'File is too large!');
    					break;
    				default:
    					break;
    			}
    		},
    		
    		beforeEach: function(file){
    			if(!file.type.match(/^image\//)){
    				showMessage(false, 'No image!');
    				return false;
    			}
    		}
    	});
    });
    
    $('.next').click(function(e) {
        e.preventDefault();
        showImage(current + 1);
    });
    
    $('.previous').click(function(e) {
        e.preventDefault();
        showImage(current - 1);
    });
    
    $('#login_form').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        callBackend(form.serialize(), function(data) {
            form.find(".clear").val('');
            if (data.status == "ok") {
                $('.can_upload').show();
                $('.login').hide();
                display('picture');
            }
            else {
                $('.can_upload').hide();
                $('.login').show();
            }
        });
    });
});

function callBackend(data, callback) {
    $.post('backend.php', data, function(response) {
        parseMessage(response);
        if (typeof callback === "function") {
            callback(response);
        }
    }).error(function(message) {
        showMessage(false, message);
    });
}

function updateTags(e, tags) {
    if(changing) {
        return;
    }
    
    var data = {
        id: current,
        action: 'modify_tags',
        tags: tags.join(',')
    };
    callBackend(data);
}

function buttons(can_upload) {
    if(can_upload) {
        $('.can_upload').show();
        $('.login').hide();
    }
    else {
        $('.can_upload').hide();
        $('.login').show();
    }
}

function display(element) {
    if(element != "picture") {
        $('.if_picture').hide();
    }
    else {
        $('.if_picture').show();
    }
    
    if(element == "login" || element == "upload") {
        $('#' + element + '_btn').hide();
        $('#picture_btn').show();
        $('#picture_btn').unbind('click');
        $('#picture_btn').click(function(e) {
           e.preventDefault();
           $('#' + element + '_btn').show();
           display('picture'); 
        });
    }
    else {
        $('#picture_btn').hide();
    }
    
    $('.box >  *').hide();
    $('#' + element).show();
}

function parseMessage(data) {
    if (data.message) {
        showMessage((data.status == "ok"), data.message);
    }
}

function showMessage(correct, text) {
    var element = $('<h2></h2>');
    if (!correct) {
        element.addClass('fail');
    }
    element.html(text);
    $('#message').html(element);
    $('#message').animate({top: 5}, 'fast', 'linear', function() {
        window.setTimeout(function() {
            $('#message').animate({top: -80});
        }, 3000);
    });
}

function showImage(id) {
    current = id;
    $('.previous').show();
    $('.next').show();
    if(id == 0) {
        $('.previous').hide();
    }
    if(id == (length - 1)) {
        $('.next').hide();
    }
    $.post('backend.php', {action: 'show', id: id}, function (data) {
        if (data.status == "ok") {
            $('.headline').html('<strong>'+data.file+'</strong>');
            $('#picture_tag').attr('src', data.url);
            display('picture');
            $('#tag_form').find('[name="id"]').val(id);
            
            
            changing = true;
            $('#tags').tagit("removeAll");
            $.each(data.tags, function(index, tag) {
                $('#tags').tagit("createTag", tag);
            });
            changing = false;
            
            $('.related').html('');
            
            console.log(data.tags.length);
            if(data.tags.length > 0) {
                var params = {
                    method: 'flickr.photos.search',
                    api_key: flickrKey,
                    format: 'json',
                    tags: data.tags.join(','),
                    license: '1,2,3,4,5,6'
                };
                $.getJSON('http://api.flickr.com/services/rest/?jsoncallback=?', params, function(response) {
                    
                    var photos = response.photos.photo;
                    var length = (photos.length <= 5) ? photos.length : 5;
                    
                    for(var i = 0; i < length; i++) {
                        var photo = photos[i];
                        var img_src = "http://farm" + photo.farm + ".staticflickr.com/" + photo.server + "/" + photo.id + "_" + photo.secret +"_t.jpg";
                        var url = "http://www.flickr.com/photos/" + photo.owner + "/" + photo.id;
                        $('.related').append("<a href=\"" + url + "\" target=\"_blank\"><img src=\"" + img_src + "\"></a>");
                    }
                    console.log();
                });
            }
        }
        else {
            showMessage(data);
        }
    });
    
}