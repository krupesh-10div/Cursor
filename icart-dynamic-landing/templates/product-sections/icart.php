<?php
/**
 * ICart Dynamic Landing Template
 *
 * @package iDentixweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$hero_title = ! empty( $content['title'] )
	? $content['title']
	: 'Best Shopify Upsell App to Increase AOV';

$hero_description = ! empty( $content['short_description'] )
	? $content['short_description']
	: "Boost your store's revenue instantly with pre- and post-purchase upsells — no coding required. Start converting more customers today.";
$rating           = get_post_meta( 18375, 'ratings', true );

?>
<section class="iw-dyc-hero-section iw-dyc-space">
	<div class="container">
		<div class="iw-dyc-hero-content">
			<div class="iw-dyc-hero-left">
				<span>
					<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/upsell.svg" alt="upsell image">
					#1 Shopify Upsell App
				</span>
				<h1><?php echo esc_html( $hero_title ); ?></h1>
				<p><?php echo esc_html( $hero_description ); ?></p>
				<div class="iw-dyc-hero-btn">
					<a href="#" class="iw-dyc-blue-btn">Start Free Trial <img src="/wp-content/plugins/icart-dynamic-landing/assets/image/right-small-arrow.svg" alt="arrow"></a>
					<a href="#" class="iw-dyc-border-btn">Boost Your AOV Now</a>
				</div>
				<div class="iw-dyc-info">
					<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/user.avif" alt="user img">
					<p>1000+ happy stores</p>
					<div class="Stars" style="--rating: <?php echo esc_attr( $rating ); ?>;"></div>
					<p><?php echo esc_html( $rating ); ?>/5 rating</p>
				</div>
			</div>
			<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/dyc-hero-content.avif" alt="banner image">
		</div>
	</div>
</section>
<section class="iw-dyc-why-icart-section iw-dyc-space">
	<div class="container">
		<div class="iw-dyc-why-icart-content">
			<h2 class="iw-dyc-heading">Why Choose <span>iCart</span>?</h2>
			<p class="iw-dyc-para">Join thousands of successful Shopify stores already boosting their revenue with
				our
				proven upsell strategies.</p>
			<div class="iw-why-icart row">
				<div class="col-lg-3 col-md-6">
					<div class="iw-why-icart-inner">
						<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/box-up-arrow.svg" alt="up-arrow">
						<h3>Increase AOV Instantly</h3>
						<p>See immediate revenue growth with smart upsell recommendations that convert.</p>
					</div>
				</div>
				<div class="col-lg-3 col-md-6">
					<div class="iw-why-icart-inner">
						<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/set-up.svg" alt="up-arrow">
						<h3>Easy to Set Up</h3>
						<p>Get started in minutes with our intuitive setup wizard and smart defaults.</p>
					</div>
				</div>
				<div class="col-lg-3 col-md-6">
					<div class="iw-why-icart-inner">
						<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/coding.svg" alt="up-arrow">
						<h3>No Coding Required</h3>
						<p>Drag-and-drop interface makes creating upsells as simple as point and click.</p>
					</div>
				</div>
				<div class="col-lg-3 col-md-6">
					<div class="iw-why-icart-inner">
						<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/customizable.svg" alt="up-arrow">
						<h3>Fully Customizable</h3>
						<p>Match your brand perfectly with unlimited customization options.</p>
					</div>
				</div>
			</div>
			<a href="#" class="iw-dyc-border-btn">Learn More <img src="/wp-content/plugins/icart-dynamic-landing/assets/image/right-blue-small-arrow.svg"
					alt="arrow"></a>
		</div>
	</div>
</section>
<section class="iw-dyc-features-section iw-dyc-space">
	<div class="container">
		<div class="iw-dyc-features-content">
			<h2 class="iw-dyc-heading">Powerful <span>Features</span> That Convert</h2>
			<p class="iw-dyc-para">Every feature is designed to maximize your revenue with minimal effort. Set up
				once and watch your AOV grow automatically.</p>
			<div class="iw-features row">
				<div class="col-lg-4 col-md-6">
					<div class="iw-features-inner">
						<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/pre-purchase.svg" alt="pre purchase image">
						<h3>Pre-purchase Upsells</h3>
						<p>Show relevant product suggestions before checkout to increase cart value instantly.</p>
						<a href="#" class="iw-dyc-blue-btn">Enable Pre-purchase <img
								src="/wp-content/plugins/icart-dynamic-landing/assets/image/right-small-arrow.svg" alt="right arrow"></a>
					</div>
				</div>
				<div class="col-lg-4 col-md-6">
					<div class="iw-features-inner">
						<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/post-purchase.svg" alt="post purchase image">
						<h3>Post-purchase Upsells</h3>
						<p>Capture additional revenue with strategic offers right after the initial purchase.</p>
						<a href="#" class="iw-dyc-blue-btn">Set Up Post-purchase <img
								src="/wp-content/plugins/icart-dynamic-landing/assets/image/right-small-arrow.svg" alt="right arrow"></a>
					</div>
				</div>
				<div class="col-lg-4 col-md-6">
					<div class="iw-features-inner">
						<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/cart-drawer.svg" alt="cart drawer image">
						<h3>Cart Drawer Upsells</h3>
						<p>Transform your cart drawer into a revenue- generating machine with smart recommendations.
						</p>
						<a href="#" class="iw-dyc-blue-btn">Customize Cart Drawer <img
								src="/wp-content/plugins/icart-dynamic-landing/assets/image/right-small-arrow.svg" alt="right arrow"></a>
					</div>
				</div>
			</div>
			<div class="iw-features-info-main row">
				<div class="col-md-4 col-sm-6">
					<div class="iw-features-info">
						<h3>47%</h3>
						<p>Average AOV increase</p>
					</div>
				</div>
				<div class="col-md-4 col-sm-6">
					<div class="iw-features-info">
						<h3>2.3x</h3>
						<p>Revenue multiplier</p>
					</div>
				</div>
				<div class="col-md-4 col-sm-6">
					<div class="iw-features-info">
						<h3>5min</h3>
						<p>Setup time</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<section class="iw-dyc-review-section iw-dyc-space">
	<div class="container">
		<div class="iw-dyc-review-content">
			<h2 class="iw-dyc-heading">Loved by <span>1000+ Stores</span></h2>
			<p class="iw-dyc-para">See why successful Shopify store owners trust iCart to grow their revenue.</p>
			<div class="iw-dyc-review row">
				<div class="col-lg-4 col-md-6">
					<div class="iw-dyc-review-inner">
						<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/qoute.svg" alt="qoute">
						<div class="iw-review-star">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/star.svg" alt="star">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/star.svg" alt="star">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/star.svg" alt="star">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/star.svg" alt="star">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/star.svg" alt="star">
						</div>
						<p>"iCart increased our AOV by 52% in just two months. The setup was incredibly easy and the
							results speak for themselves."</p>
						<div class="iw-dyc-review-profile">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/profile.avif" alt="profile">
							<div class="iw-dyc-review-profile-text">
								<p>Sarah Chen</p>
								<p>E-commerce Manager, TechStyle Fashion</p>
							</div>
						</div>
					</div>
				</div>
				<div class="col-lg-4 col-md-6">
					<div class="iw-dyc-review-inner">
						<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/qoute.svg" alt="qoute">
						<div class="iw-review-star">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/star.svg" alt="star">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/star.svg" alt="star">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/star.svg" alt="star">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/star.svg" alt="star">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/star.svg" alt="star">
						</div>
						<p>"The post-purchase upsells are game-changing. We're generating an extra $15K monthly
							revenue
							with minimal effort."</p>
						<div class="iw-dyc-review-profile">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/profile-1.avif" alt="profile">
							<div class="iw-dyc-review-profile-text">
								<p>Marcus Rodriguez</p>
								<p>Store Owner, Outdoor Gear Co</p>
							</div>
						</div>
					</div>
				</div>
				<div class="col-lg-4 col-md-6">
					<div class="iw-dyc-review-inner">
						<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/qoute.svg" alt="qoute">
						<div class="iw-review-star">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/star.svg" alt="star">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/star.svg" alt="star">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/star.svg" alt="star">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/star.svg" alt="star">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/star.svg" alt="star">
						</div>
						<p>"Best investment we've made for our Shopify store. Customer support is outstanding and
							the
							features just work."</p>
						<div class="iw-dyc-review-profile">
							<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/profile-2.avif" alt="profile">
							<div class="iw-dyc-review-profile-text">
								<p>Emily Watson</p>
								<p>Marketing Director, Beauty Essentials</p>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="iw-dyc-industries">
				<p class="iw-dyc-para">Trusted by leading Shopify stores across all industries</p>
				<div class="iw-dyc-industries-inner row">
					<div class="col-md-2 col-sm-6">
						<div>🛍️</div>
						<p>Shopify Plus</p>
					</div>
					<div class="col-md-2 col-sm-6">
						<div>👗</div>
						<p>Fashion Forward</p>
					</div>
					<div class="col-md-2 col-sm-6">
						<div>📱</div>
						<p>Tech Gadgets</p>
					</div>
					<div class="col-md-2 col-sm-6">
						<div>🏡</div>
						<p>Home & Garden</p>
					</div>
					<div class="col-md-2 col-sm-6">
						<div>⚽</div>
						<p>Sports Zone</p>
					</div>
					<div class="col-md-2 col-sm-6">
						<div>💄</div>
						<p>Beauty Hub</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<section class="iw-dyc-step-section iw-dyc-space">
	<div class="container">
		<div class="iw-dyc-step-content">
			<h2 class="iw-dyc-heading">How It <span>Works</span></h2>
			<p class="iw-dyc-para">Get started in minutes and start seeing results immediately. Our proven 3-step
				process
				makes growing your revenue effortless.</p>
			<div class="iw-dyc-step row">
				<div class="iw-dyc-step-inner col-md-4">
					<div class="iw-dyc-step-number">01</div>
					<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/Install.svg" alt="Install">
					<h3>Install iCart App</h3>
					<p>Install our app from the Shopify App Store with one click. No technical setup required.</p>
				</div>
				<div class="iw-dyc-step-inner col-md-4">
					<div class="iw-dyc-step-number">02</div>
					<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/customize.svg" alt="customize">
					<h3>Customize Upsell Offers</h3>
					<p>Use our drag-and-drop builder to create compelling upsell offers that match your brand.</p>
				</div>
				<div class="iw-dyc-step-inner col-md-4">
					<div class="iw-dyc-step-number">03</div>
					<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/Increase.svg" alt="Increase">
					<h3>See AOV Increase</h3>
					<p>Watch your average order value grow as customers discover and purchase additional products.
					</p>
				</div>
			</div>
		</div>
		<div class="iw-ready-content">
			<div>Ready to Get Started?</div>
			<p>Join thousands of stores already boosting their revenue with iCart.</p>
			<p>✅ 14-day free trial • ✅ No setup fees • ✅ Cancel anytime</p>
		</div>
	</div>
</section>
<section class="iw-dyc-faq-accordion-section iw-dyc-space">
	<div class="container">
		<div class="iw-dyc-faq-accordion-content">
			<h2 class="iw-dyc-heading">Frequently Asked <span>Questions</span></h2>
			<p class="iw-dyc-para">Everything you need to know about iCart and how it can transform your Shopify
				store's revenue.</p>
			<div class="iw-dyc-faq-accordion">
				<div class="iw-dyc-faq-accordion-title">How can I increase store AOV without coding?
					<span class="accordion-icon">
						<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/down-arrow.svg" alt="down icon">
					</span>
				</div>
				<div class="iw-dyc-faq-accordion-text iw-heading-para active">Yes there is limit. We have different
					pricing
					plans. You can check our pricing plan to know the number of tables you can create.</div>
			</div>
			<div class="iw-dyc-faq-accordion">
				<div class="iw-dyc-faq-accordion-title">What is a post-purchase upsell?
					<span class="accordion-icon">
						<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/down-arrow.svg" alt="down icon">
					</span>
				</div>
				<div class="iw-dyc-faq-accordion-text iw-heading-para">You can embed the created tables using a
					shortcode to
					any of the pages of your store.</div>
			</div>
			<div class="iw-dyc-faq-accordion">
				<div class="iw-dyc-faq-accordion-title">Does iCart work with all Shopify themes?
					<span class="accordion-icon">
						<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/down-arrow.svg" alt="down icon">
					</span>
				</div>
				<div class="iw-dyc-faq-accordion-text iw-heading-para">You can create different tables like product
					specification table, pricing table, size chart, comparison table, etc.</div>
			</div>
			<div class="iw-dyc-faq-accordion">
				<div class="iw-dyc-faq-accordion-title">How quickly can I see results with iCart?
					<span class="accordion-icon">
						<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/down-arrow.svg" alt="down icon">
					</span>
				</div>
				<div class="iw-dyc-faq-accordion-text iw-heading-para">You can create different tables like product
					specification table, pricing table, size chart, comparison table, etc.</div>
			</div>
			<div class="iw-dyc-faq-accordion">
				<div class="iw-dyc-faq-accordion-title">Is there a free trial available?
					<span class="accordion-icon">
						<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/down-arrow.svg" alt="down icon">
					</span>
				</div>
				<div class="iw-dyc-faq-accordion-text iw-heading-para">You can create different tables like product
					specification table, pricing table, size chart, comparison table, etc.</div>
			</div>
			<div class="iw-dyc-faq-accordion">
				<div class="iw-dyc-faq-accordion-title">Can I customize the appearance of upsell offers?
					<span class="accordion-icon">
						<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/down-arrow.svg" alt="down icon">
					</span>
				</div>
				<div class="iw-dyc-faq-accordion-text iw-heading-para">You can create different tables like product
					specification table, pricing table, size chart, comparison table, etc.</div>
			</div>
		</div>
		<div class="iw-still-qestion">
			<div>Still have questions?</div>
			<p>Our support team is here to help you succeed with iCart.</p>
			<ul>
				<li><a href="#">Contact Support →</a></li>
				<li>•</li>
				<li><a href="#">View Documentation →</a></li>
			</ul>
		</div>
	</div>
</section>
<section class="iw-dyc-boost-section iw-dyc-space">
	<div class="container">
		<div class="iw-dyc-boost-content">
			<h2 class="iw-dyc-heading">Ready to <span>boost</span> your store's revenue?</h2>
			<p class="iw-dyc-para">Join thousands of successful Shopify stores already using iCart to increase their
				AOV. Start your free trial today and see results within days.</p>
			<a href="#" class="iw-dyc-blue-btn">Try iCart Today <img src="/wp-content/plugins/icart-dynamic-landing/assets/image/right-small-arrow.svg"
					alt="right arrow"></a>
			<p class="iw-dyc-boost-text">14-day free trial • No credit card required • Cancel anytime</p>
			<div class="iw-dyc-boost row">
				<div class="iw-dyc-boost-inner col-md-4 col-sm-6">
					<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/set-up.svg" alt="set up">
					<h3>Instant Setup</h3>
					<p>Start seeing results in minutes</p>
				</div>
				<div class="iw-dyc-boost-inner col-md-4 col-sm-6">
					<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/free-trial.svg" alt="free trial">
					<h3>Risk-Free Trial</h3>
					<p>Money-back guarantee</p>
				</div>
				<div class="iw-dyc-boost-inner col-md-4 col-sm-6">
					<img src="/wp-content/plugins/icart-dynamic-landing/assets/image/expert.svg" alt="expert">
					<h3>Expert Support</h3>
					<p>24/7 dedicated assistance</p>
				</div>
			</div>
			<div class="iw-dyc-rateing row">
				<div class="iw-dyc-rateing-inner col-md-3 col-sm-6">
					<h3>1000+</h3>
					<p>Active Stores</p>
				</div>
				<div class="iw-dyc-rateing-inner col-md-3 col-sm-6">
					<h3>47%</h3>
					<p>Avg AOV Increase</p>
				</div>
				<div class="iw-dyc-rateing-inner col-md-3 col-sm-6">
					<h3>$2M+</h3>
					<p>Revenue Generated</p>
				</div>
				<div class="iw-dyc-rateing-inner col-md-3 col-sm-6">
					<h3>4.9 ★</h3>
					<p>App Store Rating</p>
				</div>
			</div>
		</div>
	</div>
</section>


