jQuery( function( $ ) {
	if( $( '#multiple-content-blocks-box-inactive' ).length) {
		$( '#multiple-content-blocks-box-inactive .mcb-show' ).click( function() {
			$( this ).closest( 'tr' ).next().toggle();

			if( 'none' == $( this ).closest( 'tr' ).next().css('display') )
				$( this ).text( MCB['show'] );
			else
				$( this ).text( MCB['hide'] );
			
			return false;
		} );
		
		$( '#multiple-content-blocks-box-inactive .mcb-delete' ).click( function() {
			return confirm( MCB['confirm_delete'] );
		} );
	}
} );