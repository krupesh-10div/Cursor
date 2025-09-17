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
	var ComboboxControl = wp.components.ComboboxControl;
	var useState = wp.element.useState;
	var useSelect = wp.data.useSelect;

	function useTaxonomies(postType){
		return useSelect(function(select){
			var core = select('core');
			var taxos = core.getTaxonomies({ type: postType }) || [];
			return taxos.map(function(t){ return { label: t.name, value: t.slug }; });
		}, [postType]);
	}

	function useTermSearch(taxonomy, search){
		return useSelect(function(select){
			if (!taxonomy) return [];
			var core = select('core');
			var terms = core.getEntityRecords('taxonomy', taxonomy, { search: search, per_page: 20 }) || [];
			return terms.map(function(term){ return { label: term.name + ' (' + term.id + ')', value: term.id }; });
		}, [taxonomy, search]);
	}

	registerBlockType('custom/whats-on-grid', {
		title: __("What's On Grid", 'whats-on-grid'),
		description: __('Dynamic grid of posts with server-side pagination', 'whats-on-grid'),
		edit: function(props){
			var a = props.attributes;
			function set(attr){ return function(value){ var o={}; o[attr]=value; props.setAttributes(o); }; }

			var taxOptions = useTaxonomies(a.postType || 'post');
			var termQuery = useState('');
			var termSearch = termQuery[0];
			var setTermSearch = termQuery[1];
			var termOptions = useTermSearch(a.taxonomy || 'category', termSearch);

			function addTerm(id){
				var current = Array.isArray(a.termIds) ? a.termIds.slice() : [];
				id = parseInt(id, 10);
				if (!isNaN(id) && current.indexOf(id) === -1){ current.push(id); props.setAttributes({ termIds: current }); }
			}
			function removeTerm(id){
				var current = Array.isArray(a.termIds) ? a.termIds.slice() : [];
				props.setAttributes({ termIds: current.filter(function(x){ return x !== id; }) });
			}

			return el(
				Fragment,
				null,
				el(InspectorControls, null,
					el(PanelBody, { title: __('Settings', 'whats-on-grid'), initialOpen: true },
						el(SelectControl, { label: __('Post Type', 'whats-on-grid'), value: a.postType || 'post', options: [ { label: 'Posts', value: 'post' }, { label: 'Pages', value: 'page' } ], onChange: set('postType') }),
						el(SelectControl, { label: __('Taxonomy', 'whats-on-grid'), value: a.taxonomy || 'category', options: taxOptions, onChange: function(val){ props.setAttributes({ taxonomy: val, termIds: [] }); } }),
						el(ComboboxControl, { label: __('Search terms', 'whats-on-grid'), value: '', onChange: function(val){ if (val) { addTerm(val); setTermSearch(''); } }, onInputChange: setTermSearch, options: termOptions }),
						el('div', { className: 'whats-on-grid__selected-terms' },
							(Array.isArray(a.termIds) ? a.termIds : []).map(function(id){
								return el('span', { key: id, className: 'token' },
									String(id),
									el('button', { onClick: function(){ removeTerm(id); }, type: 'button', className: 'remove' }, '×')
								);
							})
						),
						el(TextControl, { label: __('Posts per page', 'whats-on-grid'), type: 'number', min: 1, value: a.perPage || 30, onChange: set('perPage') }),
						el(ToggleControl, { label: __('Include child terms', 'whats-on-grid'), checked: !!a.includeChildren, onChange: set('includeChildren') }),
						el(RangeControl, { label: __('Columns', 'whats-on-grid'), value: a.columns || 3, min: 1, max: 6, onChange: set('columns') }),
						el(TextControl, { label: __('Base URL (page path)', 'whats-on-grid'), value: a.baseUrl || '/whats-on/', onChange: set('baseUrl') })
					)
				),
				el(ServerSideRender, { block: 'custom/whats-on-grid', attributes: props.attributes })
			);
		},
		save: function(){ return null; }
	});
})(window.wp);
