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
  var InnerBlocks = be.InnerBlocks;
  var InspectorControls = be.InspectorControls;
  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var useSelect = wp.data.useSelect;
  var blockEditorDispatch = wp.data.dispatch('core/block-editor');
  var components = wp.components;
  var PanelBody = components.PanelBody;
  var TextControl = components.TextControl;
  var ToggleControl = components.ToggleControl;
  var SelectControl = components.SelectControl;
  var RangeControl = components.RangeControl;

  // Template: Group > Group > Query (grid 3 columns) with featured image and linked title
  var TEMPLATE = [
    ['core/group', {}, [
      ['core/group', {}, [
        ['core/query', {
          query: {
            perPage: 30,
            postType: 'post',
            taxQuery: [
              {
                taxonomy: 'category',
                field: 'term_id',
                terms: [429,615,450,614,434,511],
                includeChildren: true,
                operator: 'IN'
              }
            ],
            inherit: false,
            sticky: 'exclude'
          },
          displayLayout: { type: 'grid', columns: 3 },
          tagName: 'div'
        }, [
          ['core/post-template', {}, [
            ['core/post-featured-image', { isLink: false }],
            ['core/post-title', { isLink: true }]
          ]],
          ['core/query-pagination', {}, [
            ['core/query-pagination-previous', { label: 'Previous' }],
            ['core/query-pagination-numbers'],
            ['core/query-pagination-next', { label: 'Next' }]
          ]],
          ['core/query-no-results']
        ]]
      ]]
    ]]
  ];

  function findQueryId(startId, select){
    var children = select('core/block-editor').getBlocks(startId) || [];
    for (var i=0; i<children.length; i++){
      var b = children[i];
      if (b.name === 'core/query') return b.clientId;
      var nested = findQueryId(b.clientId, select);
      if (nested) return nested;
    }
    return null;
  }

  function parseIds(value){
    if (!value) return [];
    return String(value)
      .split(',')
      .map(function(s){ return parseInt(s.trim(),10); })
      .filter(function(n){ return !isNaN(n); });
  }

  registerBlockType('custom/whats-on-grid', {
    title: __("What's On Grid", 'whats-on-grid'),
    description: __('Displays a 3-column grid of posts from selected categories', 'whats-on-grid'),
    edit: function(props){
      var blockProps = useBlockProps();

      var sel = useSelect(function(select){
        var qid = findQueryId(props.clientId, select);
        var attrs = qid ? select('core/block-editor').getBlockAttributes(qid) : null;
        return { qid: qid, attrs: attrs };
      }, [ props.clientId ]);

      function updateQuery(partial){
        if (!sel.qid || !sel.attrs) return;
        var currentQuery = sel.attrs.query || {};
        var newQuery = Object.assign(
          {},
          currentQuery,
          { inherit: false, sticky: 'exclude' },
          partial
        );
        blockEditorDispatch.updateBlockAttributes(sel.qid, { query: newQuery });
      }

      function updateTaxonomy(idsString, includeChildren){
        var terms = parseIds(idsString);
        var tx = [ { taxonomy: 'category', field: 'term_id', terms: terms, includeChildren: !!includeChildren, operator: 'IN' } ];
        updateQuery({ taxQuery: tx });
      }

      function setColumns(cols){
        if (!sel.qid) return;
        blockEditorDispatch.updateBlockAttributes(sel.qid, { displayLayout: { type: 'grid', columns: parseInt(cols,10) || 3 } });
      }

      var perPage = sel.attrs && sel.attrs.query ? sel.attrs.query.perPage : 30;
      var taxQuery = sel.attrs && sel.attrs.query ? (sel.attrs.query.taxQuery || []) : [];
      var tax = taxQuery[0] || {};
      var idsString = (tax.terms || []).join(',');
      var includeChildren = !!tax.includeChildren;
      var columns = sel.attrs && sel.attrs.displayLayout ? sel.attrs.displayLayout.columns || 3 : 3;

      return el(
        Fragment,
        null,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: __('Query Settings', 'whats-on-grid'), initialOpen: true },
            el(SelectControl, {
              label: __('Post Type', 'whats-on-grid'),
              value: 'post',
              options: [ { label: 'Posts', value: 'post' } ],
              onChange: function(){ updateQuery({ postType: 'post' }); },
              help: __('Currently fixed to Posts', 'whats-on-grid')
            }),
            el(TextControl, {
              label: __('Posts per page', 'whats-on-grid'),
              type: 'number',
              min: 1,
              value: perPage,
              onChange: function(v){ updateQuery({ perPage: parseInt(v,10) || 1 }); }
            }),
            el(TextControl, {
              label: __('Category IDs (comma-separated)', 'whats-on-grid'),
              value: idsString,
              onChange: function(v){ updateTaxonomy(v, includeChildren); }
            }),
            el(ToggleControl, {
              label: __('Include child terms', 'whats-on-grid'),
              checked: includeChildren,
              onChange: function(val){ updateTaxonomy(idsString, val); }
            }),
            el(RangeControl, {
              label: __('Columns', 'whats-on-grid'),
              value: columns,
              min: 1,
              max: 6,
              onChange: function(v){ setColumns(v); }
            })
          )
        ),
        el('div', blockProps, el(InnerBlocks, { template: TEMPLATE, templateLock: 'all' }))
      );
    },
    save: function(){
      return el(InnerBlocks.Content, null);
    }
  });
})(window.wp);
