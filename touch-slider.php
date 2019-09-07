<?php
/*
Plugin Name:  Touch Slider
Description:  Slider pour Wordpress
Version:      1.0.0
Author:       Christophe Lagorce
 */

/**
 * Display slider
 *
 * @return void
 */
function touchslider_show()
{
	wp_enqueue_style('swiper-css', plugins_url() . '/touch-slider/css/swiper.css', theme_version, 'all');
	wp_enqueue_script('swiper', plugins_url() . '/touch-slider/js/swiper.js', array('jquery'), theme_version, true);
	// $slides = new WP_query("post_type=slide&posts_per_page=$limit");
	$args = array(
		'post_type' => 'slide',
		'tax_query' => array(
			array(
				 'taxonomy' => 'type-slide',
				 'field'    => 'slug',
				 'terms'    => 'slide-header',
			),
	  ),
	);
	$slides = new WP_query($args);
	$slid_off_class = '-off';
	if ($slides->post_count > 1) {
		$slid_off_class = '';
		add_action('wp_footer', 'touchslider_script', 30);	// dernière valeur correspond à la priorité de lancement, on lance le script que si plusieurs slides
	}
	?>
<div id="touch-slider" class="swiper-container">
	<div class="swiper-wrapper">
		<?php
			while ($slides->have_posts()) {
				$slides->the_post();
				global $post;
				?>
		<div class="swiper-slide<?php echo $slid_off_class; ?>">
			<div class="image-wrapper" data-swiper-parallax="80%" data-swiper-parallax-opacity="1">
				<div class="image" style="background-image: url('<?php the_post_thumbnail_url('slider', array('class' => 'img-fluid')); ?>');">
					<!-- <a href="<?php echo esc_attr(get_post_meta($post->ID, '_link', true)); ?>"></a> -->
				</div>
				<div class="slider-title"  data-swiper-parallax="80%" data-swiper-parallax-opacity="0">
					<span class="<?php echo esc_attr(get_post_meta($post->ID, '_class_slider_title', true)); ?>"  style="color:<?php echo esc_attr(get_post_meta($post->ID, '_slider_color', true)); ?>"><?php echo esc_attr(get_post_meta($post->ID, '_slider_title', true)); ?></span>
				</div>
			</div>
			<div class="title">
			<?php
				if (get_post_meta($post->ID, '_class_title', true) != '' || get_post_meta($post->ID, '_class_text', true) != ''): 
			?>
				<div class="title-wrapper">
					<div>
						<span class="<?php echo esc_attr(get_post_meta($post->ID, '_class_title', true)); ?>" style="color:<?php echo esc_attr(get_post_meta($post->ID, '_color', true)); ?>"><?php echo esc_attr(get_post_meta($post->ID, '_title', true)); ?></span>
					</div>
					<div>
						<p class="<?php echo esc_attr(get_post_meta($post->ID, '_class_text', true)); ?>" style="color:<?php echo esc_attr(get_post_meta($post->ID, '_color', true)); ?>"><?php echo esc_attr(get_post_meta($post->ID, '_text', true)); ?></p>
					</div>
				</div>
			<?php
				endif;
			?>

			</div>
		</div>
		<?php
			}
			?>
	</div>
	<!-- <div class="swiper-pagination"></div>
			<div class="swiper-button-prev"></div>
			<div class="swiper-button-next"></div>
	<div class="swiper-scrollbar"></div> -->
</div>

<?php

}

/**
 * Call swiper script
 *
 * @return void
 */
function touchslider_script()
{
	?>
<script type="text/javascript">
	(function($) {
		$(document).ready(function() {
			var effectOption = "slide";
			var parallaxOption = true;
			if (/Edge/.test(navigator.userAgent)) {
				effectOption = "fade";
				parallaxOption = false;
			}
			var swiper = new Swiper('.swiper-container', {
				init: false,
				effect: effectOption,
				parallax: parallaxOption,
				loop: true,
				slidesPerView: 1,
				speed: 3000,
				grabCursor: false,
				keyboard: true,
				// simulateTouch: false,
				autoplay: {
					delay: 7000,
					disableOnInteraction: false,
				},
				fadeEffect: {
					crossFade: true
				},
				preventInteractionOnTransition: true,
				// pagination: {
				// 	el: '.swiper-pagination',
				// 	clickable: true,
				// },
				// navigation: {
				// 	nextEl: '.swiper-button-next',
				// 	prevEl: '.swiper-button-prev',
				// },
			});
			function animatedCss() {
				$('.swiper-wrapper').find('.swiper-slide').each(function(){
					if($(this).hasClass('swiper-slide-active')){
						$(this).find('.title-wrapper span').addClass('animated');
						$(this).find('.title-wrapper p').addClass('animated');
						$(this).find('.title').addClass('animated');

					}
					else{
						$(this).find('.title-wrapper span.animated').removeClass('animated');
						$(this).find('.title-wrapper p.animated').removeClass('animated');
						$(this).find('.title').removeClass('animated');

					}
				});
			}
			swiper.on('init', animatedCss);
			swiper.init();
			swiper.on('slideChangeTransitionEnd', animatedCss);
		});
	})(jQuery);
</script>
<?php
};

new TouchSlider();

class TouchSlider
{

	public function __construct()
	{

		add_action('init', array($this, 'touchslider_init'));
		add_action('admin_print_scripts-post.php', array($this, 'load_admin_scripts'));
		add_action('add_meta_boxes', array($this, 'touchslider_metaboxes')); // Ajoute une metaboxe
		add_action('save_post_slide', array($this, 'touchslider_savepost'), 10, 2); // Pour récupérer le contenu de la metaboxe

		add_filter('manage_edit-slide_columns', array($this, 'slide_new_colum')); // Ajoute une colonne dans l'admin
		add_action('manage_slide_posts_custom_column', array($this, 'slider_content_show'), 10, 2); // affiche le contenu de la colonne dans l'admin
		add_filter('manage_edit-slide_sortable_columns', array($this, 'sortable_menu_order'));
	}

	/**
	 * Création du nouveau post_type "Slides"
	 * 
	 * Création d'une taxonomie associée
	 * 
	 * Création d'un nouveau format d'image

	 * @return void
	 */
	public function touchslider_init()
	{
		$labels = array(
			'name' => 'Slide',
			'singular_name' => 'Slide',
			'add_new' => 'Ajouter un Slide',
			'add_new_item' => 'Ajouter un nouveau Slide',
			'edit_item' => 'Editer un Slide',
			'new_item' => 'Nouveau Slide',
			'view_item' => 'Voir le Slide',
			'search_items' => 'Rechercher un Slide',
			'not_found' => 'Aucun Slide',
			'not_found_in_trash' => 'Aucun Slide dans la corbeille',
			'parent_item_colon' => '',
			'menu_name' => 'Slides',
		);
		$args = array(
			'public' => true,
			'labels' => $labels,
			'menu_position' => 9,
			'menu_icon' => 'dashicons-images-alt2',
			'capability_type' => 'post',
			'supports' => array('title', 'thumbnail', 'page-attributes'),
		);
		register_post_type('slide', $args);

		// Declaration de la nouvelle taille d'image pour les slides
		add_image_size('slider', 1920, 1080, true);

		// Declaration de la nouvelle Taxonomie pour les slides
		$labels = array(
			'name' => 'Type de Slide',
			'new_item_name' => 'Nom du nouveau Slide',
			'parent_item' => 'Type de Slide parent',
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'show_in_rest' => true,
			'hierarchical' => true,
			'query_var' => true
		);
		register_taxonomy('type-slide', 'slide', $args);
	}

	/**
	 *   Charge le script color picker de wordpress
	 */
	function load_admin_scripts( ) {
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('myplugin-script', plugins_url('js/script.js', __FILE__), array('wp-color-picker'), false, true );
	}

	/**
	 * permet de gérer les métaboxes
	 *
	 * @return void
	 */
	public function touchslider_metaboxes()
	{
		add_meta_box('touchslider', 'lien', array($this, 'touchslider_link_metabox'), 'slide', 'normal', 'high');
		add_meta_box('touchslider-slogan', 'Textes', array($this, 'touchslider_slogan_metabox'), 'slide', 'normal', 'high');
		// add_meta_box('touchslider-params', 'paramètres du slide', array($this, 'touchslider_metabox_params'), 'type-slide', 'normal', 'high');

	}

	/**
	 * Metabox pour gérer le lien
	 *
	 * @param [type] $object
	 * @return void
	 */
	public function touchslider_link_metabox($object)
	{
		wp_nonce_field('touchslider', 'touchslider_nonce');
?>
	<div class="meta-box-item-title">
		<h4>Lien de ce slide</h4>
	</div>
	<div class="meta-box-item-content">
		<input type="text" name="touchslider_link" style="width:100%;" value="<?php echo esc_attr(get_post_meta($object->ID, '_link', true)); ?>">
	</div>
<?php
	}

	/**
	 * Metabox pour gérer le slogan
	 *
	 * @param [type] $object
	 * @return void
	 */
	public function touchslider_slogan_metabox($object)
	{
		wp_nonce_field('touchslider', 'touchslider_nonce_slogan');
?>
	<div class="meta-box-item-title">
		<h4>Titre du slide</h4>
	</div>
	<div class="meta-box-item-content">
		<label for="touchslider_slide_title">titre</label>
		<input id="touchslider_slide_title" type="text" name="touchslider_slide_title" style="width:100%;" value="<?php echo esc_attr(get_post_meta($object->ID, '_slider_title', true)); ?>">
		<label for="touchslider_slide_class_title">classe CSS</label>
		<input id="touchslider_slide_class_title" type="text" name="touchslider_slide_class_title" style="width:100%;" value="<?php echo esc_attr(get_post_meta($object->ID, '_class_slider_title', true)); ?>">
		<label for="touchslider_slide_class_text">couleur</label>
		<input id="touchslider_slide_color" name="touchslider_slide_color" type='text' class='color-field' value="<?php echo esc_attr(get_post_meta($object->ID, '_slider_color', true)); ?>">
		</div>
	<div class="meta-box-item-title">
		<h4>slogan du slide</h4>
	</div>
	<div class="meta-box-item-content">
		<label for="touchslider_slogan_title">titre</label>
		<input id="touchslider_slogan_title" type="text" name="touchslider_slogan_title" style="width:100%;" value="<?php echo esc_attr(get_post_meta($object->ID, '_title', true)); ?>">
		<label for="touchslider_slogan_class_title">classe CSS</label>
		<input id="touchslider_slogan_class_title" type="text" name="touchslider_slogan_class_title" style="width:100%;" value="<?php echo esc_attr(get_post_meta($object->ID, '_class_title', true)); ?>">
		<label for="touchslider_slogan_text">texte</label>
		<textarea id="touchslider_slogan_text" type="text" name="touchslider_slogan_text" style="width:100%;"><?php echo esc_attr(get_post_meta($object->ID, '_text', true)); ?></textarea>
		<label for="touchslider_slogan_class_text">classe CSS</label>
		<input id="touchslider_slogan_class_text" type="text" name="touchslider_slogan_class_text" style="width:100%;" value="<?php echo esc_attr(get_post_meta($object->ID, '_class_text', true)); ?>">
		<label for="touchslider_slogan_class_text">couleur</label>
		<input id="touchslider_slogan_color" name="touchslider_slogan_color" type='text' class='color-field' value="<?php echo esc_attr(get_post_meta($object->ID, '_color', true)); ?>">
	</div>
<?php
	}

	/**
	 * Récupére le contenu de la metaboxe des slides
	 *
	 * @param [type] $post_id
	 * @param [type] $post
	 * @return void
	 */
	public function touchslider_savepost($post_id, $post)
	{
		if ( !(wp_verify_nonce($_POST['touchslider_nonce'], 'touchslider') || wp_verify_nonce($_POST['touchslider_nonce_slogan'], 'touchslider'))) {
			return $post_id;
		}
		if ( isset( $_POST['touchslider_link'] ) ) {
			update_post_meta($post_id, '_link', sanitize_text_field($_POST['touchslider_link']));
		}

		if ( isset( $_POST['touchslider_slide_title'] ) ) {
			update_post_meta($post_id, '_slider_title', sanitize_text_field($_POST['touchslider_slide_title']));
		}
		if ( isset( $_POST['touchslider_slide_class_title'] ) ) {
			update_post_meta($post_id, '_class_slider_title', sanitize_text_field($_POST['touchslider_slide_class_title']));
		}
		if ( isset( $_POST['touchslider_slide_color'] ) ) {
			update_post_meta($post_id, '_slider_color', sanitize_text_field($_POST['touchslider_slide_color']));
		}



		if ( isset( $_POST['touchslider_slogan_title'] ) ) {
			update_post_meta($post_id, '_title', sanitize_text_field($_POST['touchslider_slogan_title']));
		}
		if ( isset( $_POST['touchslider_slogan_class_title'] ) ) {
			update_post_meta($post_id, '_class_title', sanitize_text_field($_POST['touchslider_slogan_class_title']));
		}
		if ( isset( $_POST['touchslider_slogan_text'] ) ) {
			update_post_meta($post_id, '_text', sanitize_text_field($_POST['touchslider_slogan_text']));
		}
		if ( isset( $_POST['touchslider_slogan_class_text'] ) ) {
			update_post_meta($post_id, '_class_text', sanitize_text_field($_POST['touchslider_slogan_class_text']));
		}
		if ( isset( $_POST['touchslider_slogan_color'] ) ) {
			update_post_meta($post_id, '_color', sanitize_text_field($_POST['touchslider_slogan_color']));
		}
	}


	/**
	 * Crée les 2 nouvelles colonnes dans l'admin des slides
	 *
	 * @param [type] $column
	 * @return void
	 */
	public function slide_new_colum(array $column)
	{
		// $column['slide_thumbs_order'] = 'ordre';
		// $column['slide_thumbs'] = 'Image';
		if ($column['wpseo-links'])
			unset($column['wpseo-links']);
		$column = array_slice($column, 0, 2) + array('slide_type' => 'Type de silde') + array('slide_thumbs' => 'Image') + array('slide_thumbs_order' => 'Ordre') + array_slice($column, 2);

		return $column;
	}

	/**
	 * Rend la colonne triable
	 *
	 * @param [type] $columns
	 * @return void
	 */
	public function sortable_menu_order(array $column)
	{
		$column['slide_thumbs_order'] = 'ordre';
		return $column;
	}

	/**
	 * affiche le contenu de la colonne dans l'admin
	 *
	 * @param [type] $column
	 * @return void
	 */
	public function slider_content_show($column)
	{
		global $post;
		if ($column == 'slide_type') {
			$taxonomies = get_the_terms($post, 'type-slide');
			if (is_array($taxonomies)) {
				foreach ($taxonomies as $taxonomy) {
					echo '- ' . $taxonomy->name .'<br>';
				}
			} else
				echo '-';
		}
		if ($column == 'slide_thumbs') {
			echo edit_post_link(get_the_post_thumbnail($post->ID, 'thumbnail'), null, null, $post->ID); //lien sur l'image
		}
		if ($column == 'slide_thumbs_order') {
			echo $post->menu_order;
		}
	}
}
