import times from 'lodash.times';
import memize from 'memize';

import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import {
	RichText,
	AlignmentToolbar,
	BlockControls,
	InspectorControls,
	PanelColorSettings,
	InnerBlocks,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import {
	TextControl,
	PanelBody,
	PanelRow,
	RangeControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';

// Parent Accordion Block (adr/custom-accordion)
registerBlockType('adr/custom-accordion', {
	title: __('Custom Accordion'),
	description: __('This is custom accordion block.'),
	icon: 'editor-justify',
	category: 'formatting',
	keywords: [ __('accordion'), __('gutenberg'), __('faq') ],
	attributes: {
		noOfAccordion: { type: 'number', default: 1 },
		blockId: { type: 'string' },
	},
	edit: (props) => {
		const { attributes: { noOfAccordion }, className, setAttributes, clientId } = props;
		setAttributes({ blockId: clientId });

		const ALLOWBLOCKS = ['adr/accordion'];
		const getChildAccordionBlock = memize((accordion) => (
			times(accordion, (n) => ['adr/accordion', { id: n + 1 }])
		));

		return (
			<div className={className}>
				<div className="accordionParentWrapper">
					<InnerBlocks
						template={getChildAccordionBlock(noOfAccordion)}
						templateLock="all"
						allowedBlocks={ALLOWBLOCKS}
						renderAppender={false}
					/>
					<span
						className="dashicons dashicons-plus"
						onClick={() => setAttributes({ noOfAccordion: noOfAccordion + 1 })}
					/>
					<span
						className="dashicons dashicons-minus"
						onClick={() => setAttributes({ noOfAccordion: noOfAccordion - 1 })}
					/>
				</div>
			</div>
		);
	},
	save: () => {
		return <InnerBlocks.Content />;
	},
});

// Accordion Block (adr/accordion)
registerBlockType('adr/accordion', {
	title: __('Accordion Block'),
	description: __('This is custom accordion block with multiple setting.'),
	category: 'formatting',
	parent: ['adr/custom-accordion'],
	supports: {
		inserter: false,
	},
	attributes: {
		title: { type: 'string' },
		acc_id: { type: 'string' },
		open: { type: 'boolean', default: false },
		alignment: { type: 'string', default: 'unset' },
		headerTextFontSize: { type: 'string', default: '20px' },
		headerTextColor: { type: 'string', default: '#2B2E31' },
		titleBackgroundColor: { type: 'string', default: '#ffffff' },
		titlePaddingTop: { type: 'string', default: 25 },
		titlePaddingRight: { type: 'string', default: 45 },
		titlePaddingBottom: { type: 'string', default: 25 },
		titlePaddingLeft: { type: 'string', default: 25 },
		bodyTextColor: { type: 'string', default: '#888888' },
		bodyBgColor: { type: 'string', default: '#ffffff' },
		borderWidth: { type: 'number', default: 0 },
		borderType: { type: 'string', default: 'solid' },
		borderColor: { type: 'string', default: '#61b23f' },
		headerTag: { type: 'string', default: 'h2' },
		borderRadius: { type: 'string', default: 25 },
		hasContent: { type: 'boolean', default: false },
	},
	edit: (props) => {
		const { attributes, setAttributes, className, clientId } = props;
		const {
			title,
			open,
			alignment,
			headerTextFontSize,
			headerTextColor,
			titleBackgroundColor,
			titlePaddingTop,
			titlePaddingRight,
			titlePaddingBottom,
			titlePaddingLeft,
			bodyTextColor,
			bodyBgColor,
			borderWidth,
			borderType,
			borderColor,
			acc_id,
			headerTag = 'h2',
			borderRadius,
			hasContent,
		} = attributes;

		const div_id = title ? title.replace(/[^A-Z0-9]/gi, '') : 'accordionHeaderId';

		// Track whether this accordion has any body content
		const innerBlockCount = useSelect(
			(select) => select('core/block-editor').getBlocks(clientId).length,
			[clientId]
		);
		useEffect(() => {
			const contentPresent = (!!title && title.trim() !== '') || innerBlockCount > 0;
			if (contentPresent !== hasContent) {
				setAttributes({ hasContent: contentPresent });
			}
		}, [title, innerBlockCount]);

		return (
			<div className={className}>
				<BlockControls>
					<AlignmentToolbar
						value={alignment}
						onChange={(value) => setAttributes({ alignment: value })}
					/>
				</BlockControls>
				<div
					className="accordionWrapper"
					style={{
						borderWidth: borderWidth + 'px',
						borderStyle: borderType,
						borderColor: borderColor,
						borderRadius: borderRadius + 'px',
					}}
				>
					<div className="accordionHeader">
						<RichText
							tagName={headerTag}
							value={title}
							id={acc_id ? acc_id : div_id}
							style={{
								fontSize: headerTextFontSize,
								textAlign: alignment,
								color: headerTextColor,
								backgroundColor: titleBackgroundColor,
								paddingTop: titlePaddingTop + 'px',
								paddingRight: titlePaddingRight + 'px',
								paddingBottom: titlePaddingBottom + 'px',
								paddingLeft: titlePaddingLeft + 'px',
							}}
							onChange={(value) => setAttributes({ title: value })}
							placeholder={__('Accordion Header')}
						/>
					</div>
					<div className="accordionBody" style={{ backgroundColor: bodyBgColor, color: bodyTextColor }}>
						<InnerBlocks templateLock={false} />
					</div>
				</div>

				<InspectorControls>
					<PanelBody title={__('Accordion Title Settings')} initialOpen={true}>
						<ToggleControl
							label={__('Accordion Open')}
							checked={!!open}
							onChange={() => setAttributes({ open: !open })}
						/>
						<TextControl
							type="string"
							label={__('Header Font Size')}
							value={headerTextFontSize}
							onChange={(value) => setAttributes({ headerTextFontSize: value })}
						/>
						<TextControl
							type="string"
							label={__('Header Tag')}
							value={headerTag}
							onChange={(value) => setAttributes({ headerTag: value })}
						/>
					</PanelBody>

					<PanelBody title={__('Title Colors')} initialOpen={false}>
						<PanelColorSettings
							title={__('Title Colors')}
							initialOpen={true}
							colorSettings={[
								{
									label: __('Background Color'),
									value: titleBackgroundColor,
									onChange: (value) => setAttributes({ titleBackgroundColor: value ? value : '#26466d' }),
								},
								{
									label: __('Text Color'),
									value: headerTextColor,
									onChange: (value) => setAttributes({ headerTextColor: value ? value : '#fff' }),
								},
							]}
						/>
					</PanelBody>

					<PanelBody title={__('Header Padding')} initialOpen={false}>
						<TextControl type="number" label={__('Padding Top')} value={titlePaddingTop} onChange={(value) => setAttributes({ titlePaddingTop: value })} />
						<TextControl type="number" label={__('Padding Right')} value={titlePaddingRight} onChange={(value) => setAttributes({ titlePaddingRight: value })} />
						<TextControl type="number" label={__('Padding Bottom')} value={titlePaddingBottom} onChange={(value) => setAttributes({ titlePaddingBottom: value })} />
						<TextControl type="number" label={__('Padding Left')} value={titlePaddingLeft} onChange={(value) => setAttributes({ titlePaddingLeft: value })} />
					</PanelBody>

					<PanelBody title={__('Accordion Body Settings')} initialOpen={false}>
						<PanelColorSettings
							title={__('Body Colors')}
							initialOpen={true}
							colorSettings={[
								{
									label: __('Background Color'),
									value: bodyBgColor,
									onChange: (value) => setAttributes({ bodyBgColor: value ? value : '#f7f7f7' }),
								},
								{
									label: __('Text Color'),
									value: bodyTextColor,
									onChange: (value) => setAttributes({ bodyTextColor: value ? value : '#26466d' }),
								},
							]}
						/>
						<RangeControl label={__('Border Width')} value={borderWidth} min={0} max={100} step={1} onChange={(value) => setAttributes({ borderWidth: value })} />
						<SelectControl
							label={__('Border Type')}
							value={borderType}
							options={[
								{ label: __('Border Type'), value: '' },
								{ label: __('Solid'), value: 'solid' },
								{ label: __('Dashed'), value: 'dashed' },
								{ label: __('Dotted'), value: 'dotted' },
							]}
							onChange={(value) => setAttributes({ borderType: value })}
						/>
						<PanelColorSettings
							title={__('Border Color')}
							initialOpen={false}
							colorSettings={[
								{
									label: __('Border Color'),
									value: borderColor,
									onChange: (value) => setAttributes({ borderColor: value }),
								},
							]}
						/>
						<TextControl type="number" label={__('Border Radius')} min={3} value={borderRadius} onChange={(value) => setAttributes({ borderRadius: value })} />
						<TextControl type="string" label={__('ID')} value={acc_id} onChange={(value) => setAttributes({ acc_id: value })} />
					</PanelBody>
				</InspectorControls>
			</div>
		);
	},
	save: (props) => {
		const { attributes } = props;
		const {
			title,
			open,
			alignment,
			headerTextFontSize,
			headerTextColor,
			titleBackgroundColor,
			titlePaddingTop,
			titlePaddingRight,
			titlePaddingBottom,
			titlePaddingLeft,
			bodyTextColor,
			bodyBgColor,
			borderWidth,
			borderType,
			borderColor,
			acc_id,
			headerTag = 'h2',
			borderRadius,
			hasContent,
		} = attributes;

		// Prevent rendering an empty accordion
		if (!hasContent) {
			return null;
		}

		const tabOpen = open ? 'tabOpen' : 'tabClose';
		const bodyDisplay = open ? 'block' : 'none';
		const div_id = title ? title.replace(/[^A-Z0-9]/gi, '') : 'accordionHeaderId';

		return (
			<div
				className={`accordionWrapper ${tabOpen}`}
				style={{
					borderWidth: borderWidth + 'px',
					borderStyle: borderType,
					borderColor: borderColor,
					borderRadius: borderRadius + 'px',
				}}
			>
				<div className="accordionHeader">
					<RichText.Content
						tagName={headerTag}
						value={title}
						id={acc_id ? acc_id : div_id}
						style={{
							fontSize: headerTextFontSize,
							textAlign: alignment,
							color: headerTextColor,
							backgroundColor: titleBackgroundColor,
							paddingTop: titlePaddingTop + 'px',
							paddingRight: titlePaddingRight + 'px',
							paddingBottom: titlePaddingBottom + 'px',
							paddingLeft: titlePaddingLeft + 'px',
						}}
					/>
				</div>
				<div
					className="accordionBody"
					style={{
						backgroundColor: bodyBgColor,
						color: bodyTextColor,
						display: bodyDisplay,
					}}
				>
					<InnerBlocks.Content />
				</div>
			</div>
		);
	},
	deprecated: [
		// Immediately previous version (before hasContent gating). Always renders markup
		{
			attributes: {
				title: { type: 'string' },
				acc_id: { type: 'string' },
				open: { type: 'boolean', default: false },
				alignment: { type: 'string', default: 'unset' },
				headerTextFontSize: { type: 'string', default: '20px' },
				headerTextColor: { type: 'string', default: '#2B2E31' },
				titleBackgroundColor: { type: 'string', default: '#ffffff' },
				titlePaddingTop: { type: 'string', default: 25 },
				titlePaddingRight: { type: 'string', default: 45 },
				titlePaddingBottom: { type: 'string', default: 25 },
				titlePaddingLeft: { type: 'string', default: 25 },
				bodyTextColor: { type: 'string', default: '#888888' },
				bodyBgColor: { type: 'string', default: '#ffffff' },
				borderWidth: { type: 'number', default: 0 },
				borderType: { type: 'string', default: 'solid' },
				borderColor: { type: 'string', default: '#61b23f' },
				headerTag: { type: 'string', default: 'h2' },
				borderRadius: { type: 'string', default: 25 },
			},
			save(props) {
				const { attributes } = props;
				const {
					title,
					open,
					alignment,
					headerTextFontSize,
					headerTextColor,
					titleBackgroundColor,
					titlePaddingTop,
					titlePaddingRight,
					titlePaddingBottom,
					titlePaddingLeft,
					bodyTextColor,
					bodyBgColor,
					borderWidth,
					borderType,
					borderColor,
					acc_id,
					headerTag = 'h2',
					borderRadius,
				} = attributes;
				const tabOpen = open ? 'tabOpen' : 'tabClose';
				const bodyDisplay = open ? 'block' : 'none';
				const div_id = title ? title.replace(/[^A-Z0-9]/gi, '') : 'accordionHeaderId';
				return (
					<div
						className={`accordionWrapper ${tabOpen}`}
						style={{
							borderWidth: borderWidth + 'px',
							borderStyle: borderType,
							borderColor: borderColor,
							borderRadius: borderRadius + 'px',
						}}
					>
						<div className="accordionHeader">
							<RichText.Content
								tagName={headerTag}
								value={title}
								id={acc_id ? acc_id : div_id}
								style={{
									fontSize: headerTextFontSize,
									textAlign: alignment,
									color: headerTextColor,
									backgroundColor: titleBackgroundColor,
									paddingTop: titlePaddingTop + 'px',
									paddingRight: titlePaddingRight + 'px',
									paddingBottom: titlePaddingBottom + 'px',
									paddingLeft: titlePaddingLeft + 'px',
								}}
							/>
						</div>
						<div
							className="accordionBody"
							style={{
								backgroundColor: bodyBgColor,
								color: bodyTextColor,
								display: bodyDisplay,
							}}
						>
							<InnerBlocks.Content />
						</div>
					</div>
				);
			},
		},
		{
			attributes: {
				titlePaddingTop: { type: 'number', default: 10 },
				titlePaddingRight: { type: 'number', default: 40 },
				titlePaddingBottom: { type: 'number', default: 10 },
				titlePaddingLeft: { type: 'number', default: 10 },
			},
			save(props) {
				const { attributes } = props;
				const {
					titlePaddingTop,
					titlePaddingRight,
					titlePaddingBottom,
					titlePaddingLeft,
					borderWidth,
					borderType,
					borderColor,
					borderRadius,
					headerTextFontSize,
					alignment,
					headerTextColor,
					titleBackgroundColor,
					title,
					bodyBgColor,
					bodyTextColor,
				} = attributes;
				const tabOpen = open ? 'tabOpen' : 'tabClose';
				const bodyDisplay = open ? 'block' : 'none';
				return (
					<div
						className={`accordionWrapper ${tabOpen}`}
						style={{
							borderWidth: borderWidth + 'px',
							borderStyle: borderType,
							borderColor: borderColor,
							borderRadius: borderRadius + 'px',
						}}
					>
						<div className="accordionHeader">
							<h4
								style={{
									fontSize: headerTextFontSize,
									textAlign: alignment,
									color: headerTextColor,
									backgroundColor: titleBackgroundColor,
									paddingTop: titlePaddingTop + 'px',
									paddingRight: titlePaddingRight + 'px',
									paddingBottom: titlePaddingBottom + 'px',
									paddingLeft: titlePaddingLeft + 'px',
								}}
							>
								{title}
							</h4>
						</div>
						<div
							className="accordionBody"
							style={{
								backgroundColor: bodyBgColor,
								color: bodyTextColor,
								display: bodyDisplay,
							}}
						>
							<InnerBlocks.Content />
						</div>
					</div>
				);
			},
		},
		{
			attributes: {
				title: { type: 'string', selector: 'h4' },
				open: { type: 'boolean', default: false },
				alignment: { type: 'string', default: 'unset' },
				headerTextFontSize: { type: 'string', default: '22px' },
				headerTextColor: { type: 'string', default: '#fff' },
				titleBackgroundColor: { type: 'string', default: '#26466d' },
				titlePaddingTop: { type: 'string', default: 10 },
				titlePaddingRight: { type: 'string', default: 40 },
				titlePaddingBottom: { type: 'string', default: 10 },
				titlePaddingLeft: { type: 'string', default: 10 },
				bodyTextColor: { type: 'string', default: '#26466d' },
				bodyBgColor: { type: 'string', default: '#f7f7f7' },
				borderWidth: { type: 'number', default: 0 },
				borderType: { type: 'string' },
				borderColor: { type: 'string', default: '#000' },
				borderRadius: { type: 'number', default: 3 },
			},
			save(props) {
				const { attributes } = props;
				const {
					title,
					open,
					alignment,
					headerTextFontSize,
					headerTextColor,
					titleBackgroundColor,
					titlePaddingTop,
					titlePaddingRight,
					titlePaddingBottom,
					titlePaddingLeft,
					bodyTextColor,
					bodyBgColor,
					borderWidth,
					borderType,
					borderColor,
					borderRadius,
				} = attributes;
				const tabOpen = open ? 'tabOpen' : 'tabClose';
				const bodyDisplay = open ? 'block' : 'none';
				return (
					<div
						className={`accordionWrapper ${tabOpen}`}
						style={{
							borderWidth: borderWidth + 'px',
							borderStyle: borderType,
							borderColor: borderColor,
							borderRadius: borderRadius + 'px',
						}}
					>
						<div className="accordionHeader">
							<RichText.Content
								tagName="h4"
								value={title}
								style={{
									fontSize: headerTextFontSize,
									textAlign: alignment,
									color: headerTextColor,
									backgroundColor: titleBackgroundColor,
									paddingTop: titlePaddingTop + 'px',
									paddingRight: titlePaddingRight + 'px',
									paddingBottom: titlePaddingBottom + 'px',
									paddingLeft: titlePaddingLeft + 'px',
								}}
							/>
						</div>
						<div
							className="accordionBody"
							style={{
								backgroundColor: bodyBgColor,
								color: bodyTextColor,
								display: bodyDisplay,
							}}
						>
							<InnerBlocks.Content />
						</div>
					</div>
				);
			},
		},
	],
});

// Example simple block in your requested format
// gutenreact/webkul-cards
registerBlockType('gutenreact/webkul-cards', {
	title: 'Webkul Card Section',
	icon: 'columns',
	category: 'widgets',
	attributes: {
		content: {
			type: 'string',
			source: 'html',
			selector: 'p',
		},
	},
	edit: (props) => {
		const { attributes, setAttributes } = props;
		const onChangeContent = (newContent) => {
			setAttributes({ content: newContent });
		};
		return (
			<RichText tagName="p" onChange={onChangeContent} value={attributes.content} />
		);
	},
	save: (props) => {
		return (
			<RichText.Content tagName="p" value={props.attributes.content} />
		);
	},
});

