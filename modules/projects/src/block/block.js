( function() {

	const { registerBlockType } = wp.blocks;
	const { createElement } = wp.element;
	const { InspectorControls } = wp.blockEditor;
	const {
		RangeControl,
		RadioControl,
		PanelBody,
		CheckboxControl
	} = wp.components;
	const ServerSideRender = wp.serverSideRender;
	const { __, _x } = wp.i18n;

	registerBlockType(
		'toolbelt/portfolio',
		{
			title: __( 'Portfolio', 'wp-toolbelt' ),

			icon: 'portfolio',

			description: __( 'Display a grid of Toolbelt Projects.', 'wp-toolbelt' ),

			category: 'wp-toolbelt',

			keywords: [
				_x( 'projects', 'block search term', 'wp-toolbelt' ),
				_x( 'toolbelt', 'block search term', 'wp-toolbelt' )
			],

			supports: {
				align: [ 'full', 'wide' ],
			},

			attributes: {
				rows: {
					default: 2,
				},
				columns: {
					default: 2
				},
				orderby: {
					default: 'date'
				},
				categories: {
					default: ''
				}
			},

			edit( props ) {

				const attributes = props.attributes;
				const setAttributes = props.setAttributes;
				let categoriesArray = [];

				if ( attributes.categories.length > 0 ) {
					categoriesArray = attributes.categories.split( ',' );
				}

				// Function to update the number of rows.
				const changeRows = ( rows ) => {
					setAttributes( { rows } );
				}

				// Function to update the number of columns.
				const changeColumns = ( columns ) => {
					setAttributes( { columns } );
				}

				// Function to update the testimonial order.
				const changeOrderby = ( orderby ) => {
					setAttributes( { orderby } );
				}

				// Add a category to the active list.
				function categoriesAdd( term ) {

					if ( !categorySelected( term ) ) {
						categoriesArray.push( term.id );
					}
					setAttributes( { categories: categoriesArray.join( ',' ) } );

				}

				// Remove a category from the active list.
				function categoriesRemove( term ) {

					categoriesArray = categoriesArray.filter( item => parseInt( item ) !== term.id );
					setAttributes( { categories: categoriesArray.join( ',' ) } );

				}

				// Is the specified category currently enabled?
				function categorySelected( term ) {

					if ( categoriesArray.findIndex( v => parseInt( v ) === term.id ) > -1 ) {
						return true;
					}
					return false;

				}

				// Get the list of categories as checkboxes.
				function getCategoryCheckboxes() {

					let categoryElements = [];

					if ( !toolbelt_portfolio_categories ) {
						return categoryElements;
					}

					Object.keys( toolbelt_portfolio_categories ).forEach(
						( key ) => {
							let term = toolbelt_portfolio_categories[ key ];
							categoryElements.push(
								<CheckboxControl
									label={term.name}
									onChange={
										( state ) => {
											if ( state ) {
												categoriesAdd( term );
											} else {
												categoriesRemove( term );
											}
										}
									}
									vale={term}
									checked={categorySelected( term )}
								/>
							);
						}
					);

					return categoryElements;

				}

				return [
					<ServerSideRender
						block="toolbelt/portfolio"
						attributes={attributes}
					/>,
					<InspectorControls>
						<PanelBody
							title={__( 'Layout', 'wp-toolbelt' )}
							initialOpen={true}
						>

							<RangeControl
								value={attributes.rows}
								label={__( 'Rows', 'wp-toolbelt' )}
								onChange={changeRows}
								min={1}
								max={10}
							/>

							<RangeControl
								value={attributes.columns}
								label={__( 'Columns', 'wp-toolbelt' )}
								onChange={changeColumns}
								min={1}
								max={4}
							/>

							<RadioControl
								selected={attributes.orderby}
								label={__( 'Order by', 'wp-toolbelt' )}
								onChange={changeOrderby}
								options={
									[
										{ value: 'date', label: __( 'date', 'wp-toolbelt' ) },
										{ value: 'rand', label: __( 'random', 'wp-toolbelt' ) },
									]
								}
							/>

						</PanelBody>

						<PanelBody
							title={__( 'Project Types', 'wp-toolbelt' )}
							initialOpen={true}
						>
							{getCategoryCheckboxes()}
						</PanelBody>
					</InspectorControls>
				];

			},

			save() {
				return null;
			}
		}
	);

} )();
