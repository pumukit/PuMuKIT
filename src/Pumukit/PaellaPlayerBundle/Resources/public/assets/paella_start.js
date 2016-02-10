// startsWith function
        if ( typeof String.prototype.startsWith != 'function' ) {
            String.prototype.startsWith = function( str ) {
                return this.substring( 0, str.length ) === str;
            }
        };

var MyAccessControl = Class.create(paella.AccessControl,{
    checkAccess:function(onSuccess) {
        this.permissions.canRead = true;
        this.permissions.canWrite = true;
        this.permissions.canContribute = true;
        this.permissions.loadError = false;
        this.permissions.isAnonymous = true;
        this.userData.login = 'anonymous';
        this.userData.name = 'Anonymous';
        this.userData.avatar = 'resources/images/default_avatar.png';
        onSuccess(this.permissions);
    }
});

var MyVideoLoader = Class.create(paella.DefaultVideoLoader, {
    ref2IntRe:/.*;time=T(\d*?):(\d*?):(\d*?):(\d*?)F1000/i,
    
    ref2Int:function(ref) {
        var match = this.ref2IntRe.exec(ref);
        return parseInt(match[1]) * 3600 + parseInt(match[2]) * 60 + parseInt(match[3]);
    },
    
    loadVideo:function(videoId,onSuccess) {
        if (videoId) {
            that = this;
            $.get('/paellarepository/' + videoId)
                .done(function(data){
                    var This = that;
                    if (data.streams) {
                        data.streams.forEach(function(stream) {
                            This.loadStream(stream);
                        });
                    }
                    if (data.frameList) {
                        that.loadFrameData(data);
                    }
                    if (data.captions) {
                        that.loadCaptions(data.captions);
                    }
                    if (data.blackboard) {
                        that.loadBlackboard(data.streams[0],data.blackboard);
                    }
                    that.streams = data.streams;
                    that.frameList = data.frameList;
                    that.loadStatus = that.streams.length>0;
                    onSuccess();
                })
                .fail(function(data){
                    console.log("error loading mediapackage");
                });
        }
    },

    loadStream:function(stream) {
	var This=this;
	if (stream.preview && ! /^[a-zA-Z]+:\/\//.test(stream.preview)) {
	    stream.preview = This._url + stream.preview;
	}

	if (stream.sources.image) {
	    stream.sources.image.forEach(function(image) {
		if (image.frames.forEach) {
		    var newFrames = {};
		    image.frames.forEach(function(frame) {
			if (frame.src && ! /^[a-zA-Z]+:\/\//.test(frame.src)) {
			    frame.src = This._url + frame.src;
			}
			if (frame.thumb && ! /^[a-zA-Z]+:\/\//.test(frame.thumb)) {
			    frame.thumb = This._url + frame.thumb;
			}
			var id = "frame_" + frame.time;
			newFrames[id] = frame.src;
		    });
		    image.frames = newFrames;
		}
	    });
	}
	for (var type in stream.sources) {
	    if (stream.sources[type]) {
		if (type != 'image') {
		    var source = stream.sources[type];
		    source.forEach(function(sourceItem) {
			var pattern = /^[a-zA-Z\:]+\:\/\//gi;
			if (typeof(sourceItem.src)=="string") {
			    if(sourceItem.src.match(pattern) == null){
				sourceItem.src = This._url + sourceItem.src;
			    }
			}
			sourceItem.type = sourceItem.mimetype;
		    });
		}
	    }
	    else {
		delete stream.sources[type];
	    }
	}
    }
});

function loadPaella(containerId, videoId) {
    var initDelegate = new paella.InitDelegate({accessControl:new MyAccessControl(),videoLoader:new MyVideoLoader()});
    initPaellaEngage(containerId,initDelegate);
}

paella.dataDelegates.UserDataDelegate = Class.create(paella.DataDelegate,{
    initialize:function() {
    },
    
    read:function(context, params, onSuccess) {
        var value = {
            userName:"userName",
            name: "Name",
            lastname: "Lastname",
            avatar:"plugins/silhouette32.png"
        };
        
        if (typeof(onSuccess)=='function') { onSuccess(value,true); }
    }
    
});
