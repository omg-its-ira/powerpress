(function ($) {

	jQuery(document).ready(function($) {
		jQuery('.powerpress-mejs-audio, .powerpress-mejs-video').mediaelementplayer();
		
		
		jQuery('.mejs-play button').click( function(e) {
					
				var container = jQuery(this).closest('.mejs-container');
				var html5player = container.find('video,audio');
				if( html5player.attr('src') )
				{
					//alert( html5player.attr('id') );
					//var mejsplayer = window[ html5player.attr('id') ];
					var srcTmp = html5player.attr('src');
					if( srcTmp.indexOf('#') != -1 ) {
						var tmp = srcTmp.replace(/^[^#]*#/, '');
						if( tmp != srcTmp ) {
							// Stop the play from happening in this event
							e.preventDefault();
							
							var mejsplayer = window[  html5player.attr('id') ];
							if( mejsplayer ) {
									mejsplayer.setAttribute('src', tmp);
									mejsplayer.load();
									setTimeout( function(){ mejsplayer.play(); }, 900  );
									return false;
							}
						}
					}
				}
		});
	
	});

}(jQuery));