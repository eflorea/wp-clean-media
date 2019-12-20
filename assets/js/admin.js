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

		scanning = document.getElementById( 'wpcm-scanning' );

		scan_now = document.getElementById( 'wpcm-scan-now' );

		if ( scan_now ) {
			scan_now.addEventListener( 'click', function( e ) {
				start_scan( true );
			} );
		}

		stop_now = document.getElementById( 'wpcm-stop-now' );
		if ( stop_now ) {
			stop_now.addEventListener( 'click', function() {
				stop_scan();
			} );
		}

		reset_now = document.getElementById( 'wpcm-reset-now' );
		if ( reset_now ) {
			reset_now.addEventListener( 'click', function ( e ) {
				if ( confirm( 'Are you sure? This will delete any data associated with this plugin and stop the scanning if it\'s running.' ) ) {
					reset_scan();
				}
			} );
		}

		if ( reset_now && '1' === wpcm_is_running ) {
			start_scan();
		}
	};

	const start_scan = function( first_run ) {
		if ( first_run ) {
			if ( confirm( 'Are you sure?' ) ) {
				scan_now.classList.add( 'hide' );
				stop_now.classList.remove( 'hide' );
				scanning.classList.remove( 'hide' );
				scan_files( first_run );

			}
		} else {
			scan_files( first_run );
		}
	};

	const stop_scan = function( reset_all ) {
		if ( ! reset_all ) {
			scan_now.classList.remove('hide');
			stop_now.classList.add('hide');
			scanning.classList.remove('hide');
		}
		let data       = [];
		data['action'] = 'wpcm_stop_scan';
		if ( reset_all ) {
			data['action'] = 'wpcm_reset_scan';
		}
		post_call( data, function() {
			document.location.reload();
		} );
	};

	const reset_scan = function() {
		stop_scan( true );
	};

	const scan_files = function( first_run ) {
		let data       = [];
		data['action'] = 'wpcm_scan';
		if ( first_run ) {
			data['wpcm_initiate_scan'] = 1;
		}

		post_call( data, function( response ) {
			try {
				const data = JSON.parse(response );
				if ( data.total_posts ) {
					document.getElementById( 'wpcm-total-attachments' ).innerHTML = data.total_posts;
				}
				if ( data.total_scanned ) {
					document.getElementById( 'wpcm-total-scanned' ).innerHTML = data.total_scanned;
				}
				if ( data.next_page ) {
					scan_files();
				} else {
					wpcm_is_running = false;
					stop_scan();
				}
			} catch (e) {
				wpcm_is_running = false;
			}
		} );
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

	const post_call = function( data, callback ) {
		let xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject( 'Microsoft.XMLHTTP' );
		xhr.open( 'POST', ajaxurl );
		xhr.onreadystatechange = function() {
			if ( this.readyState == 4 ) {
				return callback( xhr.responseText );
			}
		};
		xhr.setRequestHeader( 'X-Requested-With', 'XMLHttpRequest' );
		xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
		const params = typeof data == 'string' ? data : Object.keys( data ).map(
			function( k ){ return encodeURIComponent( k ) + '=' + encodeURIComponent( data[k] ) }
		).join( '&' );
		xhr.send( params );
		return xhr;
	}

	if (
		document.readyState === "complete" ||
		( document.readyState !== "loading" && ! document.documentElement.doScroll )
	) {
		init();
	} else {
		document.addEventListener( "DOMContentLoaded", init );
	}
} )( window, document );
