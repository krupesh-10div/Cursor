
(function(wp){
  var registerBlockType = wp.blocks.registerBlockType;
  var __ = wp.i18n.__;
  var be = wp.blockEditor || wp.editor;
  var useBlockProps = be.useBlockProps;
  var ServerSideRender = wp.serverSideRender || wp.components.ServerSideRender;
  var InspectorControls = be.InspectorControls;
  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var components = wp.components;
  var PanelBody = components.PanelBody;
  var TextControl = components.TextControl;
  var ToggleControl = components.ToggleControl;
  var SelectControl = components.SelectControl;
  var RangeControl = components.RangeControl;
  var useState = wp.element.useState;

  registerBlockType('custom/whats-on-grid', {
    title: __("What's On Grid", 'whats-on-grid'),
    description: __('Displays a 3-column grid of posts from selected categories', 'whats-on-grid'),
    attributes: {},
    edit: function(props){
      var a = props.attributes;
      function set(attr){ return function(value){ var o={}; o[attr]=value; props.setAttributes(o); }; }

      return el(
        Fragment,
        null,
        el(InspectorControls, null,
          el(PanelBody, { title: __('Query Settings', 'whats-on-grid'), initialOpen: true },
            el(TextControl, { label: __('Posts per page', 'whats-on-grid'), type: 'number', min: 1, value: a.perPage || 30, onChange: set('perPage') }),
            el(TextControl, { label: __('Category IDs (comma-separated)', 'whats-on-grid'), value: a.idsString || '', onChange: set('idsString') }),
            el(ToggleControl, { label: __('Include child terms', 'whats-on-grid'), checked: !!a.includeChildren, onChange: set('includeChildren') }),
            el(RangeControl, { label: __('Columns', 'whats-on-grid'), value: a.columns || 3, min: 1, max: 6, onChange: set('columns') }),
            el(TextControl, { label: __('Base URL for Next link', 'whats-on-grid'), value: a.baseUrl || '/whats-on/', onChange: set('baseUrl') }),
            el(TextControl, { label: __('Query var for page', 'whats-on-grid'), value: a.queryVar || 'page', onChange: set('queryVar') })
          )
        ),
        el(ServerSideRender, { block: 'custom/whats-on-grid', attributes: props.attributes })
      );
    },
    save: function(){ return null; }
  });
})(window.wp);
