( function( blocks, components, i18n, element){
	var el                 = wp.element.createElement,
	registerBlockType      = wp.blocks.registerBlockType;
	source                 = wp.blocks.source;
	var __                 = wp.i18n.__;
	const bookingpresslogo = el( 'img',{ src:__BOOKINGPRESSIMAGEURL + '/bookingpress_menu_icon.png' } );
	registerBlockType(
		'bookingpress/bookingpress-appointment-form-popup',
		{
			title:__( 'Booking Forms Popup - WordPress Booking Plugin' ),
			icon:bookingpresslogo,
			category:'bookingpress',
			keywords:[__( 'appointment' ),__( 'bookingpress' ),__( 'booking calendar' ),__( 'booking system' ),__( 'scheduling' ),__( 'reservation' ), __( 'popup' )],			
			attributes: {
				short_code: {
					type: 'string',
					default: '[bookingpress_form_popup]'
				},
			},
			edit:function(props){
				if (props.name == 'bookingpress/bookingpress-appointment-form-popup') {
					return 	el(
						'div',
						{},
						props.attributes.short_code
					)
				}
			},
			save:function(props) {
				return (
				el(
					'div',
					{},
					props.attributes.short_code
				)
				)
			}
		}
	);
})(
	window.wp.blocks,
	window.wp.components,
	window.wp.i18n,
	window.wp.element,
	window.wp.editor
);
