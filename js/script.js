/**
 * Let's make a jQuery plugin for handling our form.
 */
jQuery( document ).ready( function() {

	var prefix = sjfWpApiShortcode.prefix;

	// Select the form.
	var el = jQuery( '#' + prefix + '-form' );

	// Apply the jQuery plugin to our form.
	jQuery( el ).sjfWpApiShortcode();

});

// I don't get what this does.
( function ( $ ) {

	// We don't have any options to pass in but someday we could.
	$.fn.sjfWpApiShortcode = function( options ) {

		// For each of our forms ( though there probably is just one )...
		return this.each( function() {

			// Store "this" for later in "this" gets used for something else.
			var that = this;

			// Some handy strings from php-land.
			var prefix          = sjfWpApiShortcode.prefix;
			var route_prefix    = sjfWpApiShortcode.route_prefix;
			var always          = sjfWpApiShortcode.always;
			var done            = sjfWpApiShortcode.done;
			var fail            = sjfWpApiShortcode.fail;
			var discover_always = sjfWpApiShortcode.discover_always;
			var discover_done   = sjfWpApiShortcode.discover_done;
			var discover_fail   = sjfWpApiShortcode.discover_fail;

			/**
			 * Wrap a title and chunk of content for output.
			 * 
			 * @param  {string} subtitle A subtitle for this element.
			 * @param  {string} content  A brief paragraph of text.
			 * @return {string} The subtitle and content, wrapped with out standard markup.
			 */
			function wrap( subtitle, content ) {

				var subtitle = '<h3 class="' + prefix + '-output-subtitle">' + subtitle + '</h3>';
				var content  = '<p class="' + prefix + '-output-content">' + content + '</p>';

				var out = '<div class="' + prefix + '-output-node">' + subtitle + content + '</div>';
			
				return out;

			}

			/**
			 * Make an ajax call to a domain.
			 * 
			 * This is a long story.  So, whatever domain we are making our
			 * API call to, we first need to call it and dig through its dom
			 * in order to discover the <link> that tells us the WP API url
			 * for that install.  This function allows us to make that first
			 * call, and await for the response, before proceeding with the 
			 * actual API call.
			 * 
			 * @return {object} The result of a jQuery ajax() call.
			 */
			function callDomain( domain ) {

		        return jQuery.ajax({
					url: domain
				});

			}

			/**
			 * Change the submit button to reflect a new status.
				 * 
			 * @param {string} Text to relay to the user.
			 */
			function sayStatus( text ) {
				jQuery( '.' + prefix + '-button' ).text( text );
			}

			/**
			 * Take the result of an ajax() call and convert it into a
			 * user-friendly output message.
			 *
			 * @param {object} A call to jQuery.ajax().
			 * @return {string} A message explaining the ajax call.
			 */
			function getOutput( jqxhr ) {
				
				// Start by grabbing the readyState, status, and statusText.
				var output = wrap( 'readyState', jqxhr.readyState );
				output += wrap( 'status', jqxhr.status );
				output += wrap( 'statusText', jqxhr.statusText );

				// If we got responseText, great, use it.
				var responseText = jQuery( '<div/>' ).text( jqxhr.responseText ).html();
				if ( responseText != '' ) {
					output += wrap( 'responseText', responseText );
				}

				// If we got responseJSON, great, use it.
				var responseJSON = jQuery( '<div/>' ).text( JSON.stringify( jqxhr.responseJSON ) ).html();
				if ( responseJSON != '' ) {
					output += wrap( 'responseJSON', responseJSON );	
				}

				return output;

			}

			/**
			 * Fade the output element in and give it display block as
			 * opposed to it's default display inline.
			 */
			function showOutput( output ) {
				jQuery( '#' + prefix + '-output' ).html( output ).removeClass( '' + prefix + '-hide' ).addClass( '' + prefix + '-show' );	
			}

			/**
			 * Strip the trailing slash from a url, if it has one.
			 *
			 * @param  {string} url A url.
			 * @return {string} A url with no trailing slash.
			 */
			function removeTrailingSlash( url ) {
				return url.replace( /^\/|\/$/g, '' );
			}

			/**
			 * Determine is a url is external to the current window.
			 *
			 * @param  {string}  A url.
			 * @return {boolean} True if a request is external, else false.
			 */
			function isExternalRequest( apiUrl ) {

			    // Grab the current domain.
			    var hostname = window.location.hostname;

			    // If this is an external request...
			    if ( apiUrl.indexOf( hostname ) < 1 ) { return true; }

			    return false;

			}

			/**
			 * Make a call to a WP API url and insert the results into the DOM.
			 *
			 * @param {string} The url for the WP API.
			 */
			function theApiResponse( apiUrl ) {
    
				// Grab the request data, nonce, and type.
				var data   = jQuery( '#' + prefix + '-data' ).val();
				data = jQuery.parseJSON( data );
			    var nonce  = jQuery( '#' + prefix + '-nonce' ).val();
				var type   = jQuery( '#' + prefix + '-method' ).val();

				// Convert the request type to an uppercase verb.
				var method = type.toUpperCase();

			    // Get the route, sans trailing slash.
			    var route = jQuery( '#' + prefix + '-route' ).val();
			    route = removeTrailingSlash( route );

        		// Build the url to which we'll send our API request.
        		var url = apiUrl + route_prefix + route;

			    // Let's assume for now that the dataType should be JSON.
			    var dataType = 'json';
			    
			    // But, if it's an external request...
			    if( isExternalRequest( apiUrl ) ) {
			    	
			    	// And if it's a get request...
			    	if( method == 'GET' ) {

			    		// The dataType actually should be jsonp.
    			        dataType = 'jsonp';

    			        // And we have to add this to the url.
	        		   	url += '?_jsonp=?';	

        			}

        		}

			    // Send our ajax request.
				var apiCall = jQuery.ajax({
					url:	    url,
					type: 	    type,
					data: 	    data,
					dataType:   dataType,
					beforeSend: function( xhr ) {
    					xhr.setRequestHeader( 'X-WP-Nonce', nonce );
   					}
				})
				.always( function() {

					// Tell the user the api call is happening.
					console.log( data );
					console.log( always );
					sayStatus( always );
					
				})
				.fail( function() {

					// Tell the user the API call failed.
					console.log( fail );
					console.log( apiCall );
					sayStatus( fail );

					// Show the call in detail.
					var output = getOutput( apiCall );												
					showOutput( output );

				})
				.done( function() {
					
					// Tell the user we finished making the call and it worked.
					console.log( done );
					console.log( apiCall );
					sayStatus( done );

					// Show the call in detail.
					var output = getOutput( apiCall );												
					showOutput( output );
				
				});

			}

			// When the form is submit...
			jQuery( that ).on( 'submit', function( event ) {
	
				// Don't actually submit the form or reload the page.
				event.preventDefault();

				// Let the user know some things are happening.
				sayStatus( discover_always );

				// Get the domain, sans trailing slash.
			    var domain = jQuery( '#' + prefix + '-domain' ).val();
			    domain = removeTrailingSlash( domain );

				// We have to call the domain to discover the url for the WP API on that particular install.  Once that call is done...
			   	var preCall = callDomain( domain )
			   	.always( function() {

			   		// We're calling to discover the api.
					console.log( discover_always );
				
				})
				.done( function( preCall ) {
			   	
			   		// We finished calling and it worked!
			   		console.log( discover_done );		
			   		sayStatus( discover_done );

			   		/**
			   		 * Dig into the response and look for the <link> that contains the API url.
			   		 *
			   		 * @see http://v2.wp-api.org/guide/discovery/
			   		 */
			   		var apiLink = jQuery( preCall ).filter( 'link[rel="https://api.w.org/"]' );
			   		
			   		console.log( apiLink );

			   		// Grab the API url.
			   		var apiUrl = jQuery( apiLink ).attr( 'href' );


			   		// Okay, we have the API url.  Now we can call the API.
			   		theApiResponse( apiUrl );
			   		
			   	})
				.fail( function( preCall ) {
			   		
					// We finished calling and it failed!
			   		console.log( discover_fail );
			   		console.log( preCall );
			   		sayStatus( discover_fail );
					
			   		// Tell the user more details about the failure.
			   		var output = getOutput( preCall );			
			   		showOutput( output );

			   	});

			// End form submit.
			});
		
		// End this 'el' (meaning, just the form).
		});

	// End defining our plugin.
	}

// I don't get what this does either.
}( jQuery ) );