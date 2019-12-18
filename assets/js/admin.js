( function( window, document ) {
	let attachment_filter;
	let scan_now;
	let stop_now;
	let reset_now;
	let scanning;

	const init = function() {
		attachment_filter = document.getElementById( 'attachment-filter' );

		if ( attachment_filter ) {
			let option   = document.createElement( 'option' );
			option.text  = 'Not used';
			option.value = 'unused';
			if ( 'unused' === get_url_param( 'attachment-filter' ) ) {
				option.selected = 'selected';
			}
			attachment_filter.add( option );
		}

		scan_now = document.getElementById( 'wpcm-scan-now' );

		if ( scan_now ) {
			scan_now.addEventListener( 'click', function( e ) {
				if ( confirm( 'Are you sure?' ) ) {
					scan_files( true );
					scan_now.style.display = 'none';
					document.getElementById( 'wpcm-scanning' ).classList.add( 'active' );
				}
			} );
		}
	};

	const scan_files = function( first_run ) {
		let data = [];
		data['action'] = 'wpcm_scan';
		if ( first_run ) {
			data['wpcm_initiate_scan'] = 1;
		}

		let xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
		xhr.open( 'POST', ajaxurl );
		xhr.onreadystatechange = function() {
			console.log( xhr.responseText );
		};
		xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		const params = typeof data == 'string' ? data : Object.keys(data).map(
			function(k){ return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]) }
		).join('&');
		xhr.send( params );
		return xhr;
	}

	const get_url_param = function( parameter ) {
		let val = '';
		if ( window.location.href.indexOf( parameter ) > -1 ) {
			val = get_url_vars()[parameter];
		}
		return val;
	};

	const get_url_vars = function () {
		let vars = {};
		const parts = window.location.href.replace( /[?&]+([^=&]+)=([^&]*)/gi, function( m, key, value ) {
			vars[key] = value;
		} );
		return vars;
	};

	if (
		document.readyState === "complete" ||
		( document.readyState !== "loading" && ! document.documentElement.doScroll )
	) {
		init();
	} else {
		document.addEventListener( "DOMContentLoaded", init );
	}
} )( window, document );
