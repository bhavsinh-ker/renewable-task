<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package catalog_site
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- header -->
<header id="siteHeader" class="site-header py-3">
	<div class="container">
		<div class="row">
			<div class="col-9 col-md-3">
				<a href="<?php bloginfo('home'); ?>" class="text-dark text-decoration-none">
					<?php 
						$site_logo = get_custom_logo();
						if($site_logo!="") {
							echo $site_logo;
						} else {
							?>
							<h4 class="m-0 p-0 mt-1 text-uppercase"><?php bloginfo('name')?></h4>
							<?php
						}
					?>
				</a>
			</div>
			<div class="col-md-9 d-md-block d-none">
				<?php 
					wp_nav_menu(
						array(
							'theme_location'	=> 'primary-menu',
							'menu_id'        	=> 'primaryNavMenu',
							'menu_class'		=> 'nav justify-content-end'		
						)
					);
				?>
			</div>
			<div class="col-3 d-md-none text-end">
				<a href="#" class="btn btn-outline-primary renewable-mobile-menu-btn">
					<i class="bi bi-list"></i>
				</a>
			</div>
		</div>
	</div>
</header>
<!-- EOF header -->

<!-- Mobile Menu -->
<div class="renewable-mobile-menu">
	<div class="bg-light h-100 shadow">
		<a href="#" class="renewable-mobile-menu-close text-end"><i class="bi bi-x-lg"></i></a>
		<?php 
			wp_nav_menu(
				array(
					'theme_location'	=> 'mobile-menu',
					'menu_id'        	=> 'mobileNavMenu',
					'menu_class'		=> ''		
				)
			);
		?>
	</div>
</div>
<!-- EOF Mobile Menu -->