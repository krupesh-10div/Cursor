( function( wpBlocks, wpBlockEditor, wpI18n, wpElement ) {
\tconst { registerBlockType } = wpBlocks;
\tconst { __ } = wpI18n;
\tconst { useBlockProps, InnerBlocks } = wpBlockEditor;
\tconst { createElement: el, Fragment } = wpElement;

\t// Preload the structure shown in the screenshot:
\t// Group (container) > Group (container) > Query (grid, 3 columns)
\t// with Post Template rendering Featured Image above linked Title.
\tconst TEMPLATE = [
\t\t[ 'core/group', {}, [
\t\t\t[ 'core/group', {}, [
\t\t\t\t[
\t\t\t\t\t'core/query',
\t\t\t\t\t{
\t\t\t\t\t\tquery: {
\t\t\t\t\t\t\tperPage: 30,
\t\t\t\t\t\t\tpostType: 'post',
\t\t\t\t\t\t\t// Filter by categories by term IDs and include children.
\t\t\t\t\t\t\ttaxQuery: [
\t\t\t\t\t\t\t\t{
\t\t\t\t\t\t\t\t\ttaxonomy: 'category',
\t\t\t\t\t\t\t\t\tfield: 'term_id',
\t\t\t\t\t\t\t\t\tterms: [ 429, 615, 450, 614, 434, 511 ],
\t\t\t\t\t\t\t\t\tincludeChildren: true
\t\t\t\t\t\t\t\t}
\t\t\t\t\t\t\t],
\t\t\t\t\t\t\tinherit: false
\t\t\t\t\t\t},
\t\t\t\t\t\tdisplayLayout: { type: 'grid', columns: 3 }
\t\t\t\t\t},
\t\t\t\t\t[
\t\t\t\t\t\t[ 'core/post-template', {}, [
\t\t\t\t\t\t\t[ 'core/post-featured-image', { isLink: true, sizeSlug: 'large' } ],
\t\t\t\t\t\t\t[ 'core/post-title', { isLink: true } ]
\t\t\t\t\t\t] ]
\t\t\t\t\t]
\t\t\t\t]
\t\t\t] ]
\t];

\tconst Edit = () => {
\t\tconst blockProps = useBlockProps();
\t\treturn el(
\t\t\t'div',
\t\t\tblockProps,
\t\t\tel( InnerBlocks, { template: TEMPLATE, templateLock: 'all' } )
\t\t);
\t};

\tconst Save = () => {
\t\tconst blockProps = useBlockProps.save();
\t\treturn el( 'div', blockProps, el( InnerBlocks.Content, null ) );
\t};

\tregisterBlockType( 'custom/whats-on-grid', {
\t\ttitle: __( "What's On Grid", 'whats-on-grid' ),
\t\tedit: Edit,
\t\tsave: Save
\t} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.i18n, window.wp.element );

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
