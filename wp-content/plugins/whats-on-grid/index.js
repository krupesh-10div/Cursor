( function( wp ) {
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { useBlockProps, InnerBlocks } = wp.blockEditor || wp.editor;
    const el = wp.element.createElement;

    const TEMPLATE = [
        [ 'core/group', { className: 'whats-on-container' }, [
            [ 'core/group', { className: 'whats-on-inner-container' }, [
                [ 'core/query', {
                    query: {
                        perPage: 30,
                        postType: 'post',
                        taxQuery: [
                            {
                                taxonomy: 'category',
                                field: 'term_id',
                                terms: [ 429, 615, 450, 614, 434, 511 ],
                                includeChildren: true,
                                operator: 'IN'
                            }
                        ]
                    },
                    displayLayout: { type: 'grid', columns: 3 },
                    tagName: 'div'
                }, [
                    [ 'core/post-template', {}, [
                        [ 'core/post-featured-image', { isLink: false } ],
                        [ 'core/post-title', { isLink: true } ],
                    ] ],
                    [ 'core/query-no-results' ]
                ] ]
            ] ]
        ] ]
    ];

    registerBlockType( 'custom/whats-on-grid', {
        title: "What's On Grid",
        description: __( 'Displays a 3-column grid of posts from selected categories', 'whats-on-grid' ),
        edit: function Edit() {
            const blockProps = useBlockProps();
            return el(
                'div',
                blockProps,
                el( InnerBlocks, { template: TEMPLATE, templateLock: 'all' } )
            );
        },
        save: function Save() {
            return el( InnerBlocks.Content, null );
        }
    } );
} )( window.wp );
