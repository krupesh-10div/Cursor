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
  var el = wp.element.createElement;

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
          ['core/query-no-results']
        ]]
      ]]
    ]]
  ];

  registerBlockType('custom/whats-on-grid', {
    title: __("What's On Grid", 'whats-on-grid'),
    description: __('Displays a 3-column grid of posts from selected categories', 'whats-on-grid'),
    edit: function(){
      var blockProps = useBlockProps();
      return el('div', blockProps, el(InnerBlocks, { template: TEMPLATE, templateLock: 'all' }));
    },
    save: function(){
      return el(InnerBlocks.Content, null);
    }
  });
})(window.wp);
