(function(wp){
	var registerBlockType = wp.blocks.registerBlockType;
	var __ = wp.i18n.__;
	var be = wp.blockEditor || wp.editor;
	var InspectorControls = be.InspectorControls;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
	var ToggleControl = wp.components.ToggleControl;
	var RangeControl = wp.components.RangeControl;
	var ServerSideRender = wp.serverSideRender || wp.components.ServerSideRender;

	registerBlockType('custom/whats-on-grid', {
		title: __("What's On Grid", 'whats-on-grid'),
		description: __('Dynamic grid of posts with server-side pagination', 'whats-on-grid'),
		edit: function(props){
			var a = props.attributes;
			function set(attr){ return function(value){ var o={}; o[attr]=value; props.setAttributes(o); }; }

			return el(
				Fragment,
				null,
				el(InspectorControls, null,
					el(PanelBody, { title: __('Settings', 'whats-on-grid'), initialOpen: true },
						el(SelectControl, {
							label: __('Post Type', 'whats-on-grid'),
							value: a.postType || 'post',
							options: [
								{ label: 'Posts', value: 'post' },
								{ label: 'Pages', value: 'page' }
							],
							onChange: set('postType')
						}),
						el(TextControl, { label: __('Posts per page', 'whats-on-grid'), type: 'number', min: 1, value: a.perPage || 30, onChange: set('perPage') }),
						el(TextControl, { label: __('Category IDs (comma-separated)', 'whats-on-grid'), value: a.idsString || '', onChange: set('idsString') }),
						el(ToggleControl, { label: __('Include child terms', 'whats-on-grid'), checked: !!a.includeChildren, onChange: set('includeChildren') }),
						el(RangeControl, { label: __('Columns', 'whats-on-grid'), value: a.columns || 3, min: 1, max: 6, onChange: set('columns') }),
						el(TextControl, { label: __('Base URL for pagination', 'whats-on-grid'), value: a.baseUrl || '/whats-on/', onChange: set('baseUrl') }),
						el(TextControl, { label: __('Query var name', 'whats-on-grid'), value: a.queryVar || 'page', onChange: set('queryVar') })
					)
				),
				el(ServerSideRender, { block: 'custom/whats-on-grid', attributes: props.attributes })
			);
		},
		save: function(){ return null; }
	});
})(window.wp);
