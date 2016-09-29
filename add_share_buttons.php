<?php
/*
   Plugin Name: Add Share Buttons
   Plugin URI: https://github.com/cabelotaina/add_share_buttons
   Description: Plugin for manage facebook, twitter and google plus share buttons
   Author: Maurilio Atila
   Version: 0.1
   Author URI: https://github.com/cabelotaina/
 */

class Add_Share_Buttons {

  function __construct() {
  	$this->init();
  }

  function init() {
  	add_action( 'wp_head', array( $this, 'add_facebook_meta') );
  	add_action( 'admin_post_update_settings', array( $this, 'update_settings' ) );
  	add_filter( 'the_content', array( $this, 'add_body_js' ) );
  	add_filter( 'the_content', array( $this, 'add_share_buttons' ) );
  	add_action( 'admin_menu', array( $this, 'admin_menu' ) );
  	add_action('admin_notices', array( $this, 'my_admin_notice' ) );
  }

  function add_facebook_meta() {
		$default_image = get_option('add_share_default_image');
	  ?>
	  <meta property="og:url" content="<?= get_permalink() ?>" />
	  <meta property="og:type" content="website" />
	  <meta property="og:title" content="<?= get_the_title() ?>" />
	  <meta property="og:description" content="<?= bloginfo("name") ?>" />
	  <?php if ( has_post_thumbnail() ) : ?>
	  <meta property="og:image" content="<?= the_post_thumbnail_url( 'large' ) ?>" />
	  <?php elseif ( has_header_image() ) : ?>
	  <meta property="og:image" content="<?= get_header_image() ?>" />
	  <?php elseif( $default_image ) : ?>
	  <meta property="og:image" content="<?= $default_image ?>" />
	  <?php endif; ?>
	  <?php
  }

  function add_share_buttons( $content ){
  	global $post;
  	if( is_singular( $this->post_types() ) ){
      $content .= '<div style="position:relative;">';
        //facebook
        $content .= '<div class="fb-share-button"  style="top:-7px;"  data-layout="button_count"></div>';
        //twitter
        $content .= '<a class="twitter-share-button" href="' . get_permalink() . '">Tweet</a>';
        //google plus
        $content .= '<div class="g-plus" data-action="share" data-href=""></div>';
      $content .= '</div>';
    }
    return $content;
  }

  function post_types(){
  	$post_types = get_option('add_share_post_types');
  	return $post_types;
  }


  function admin_menu() {
  	add_options_page(
		__('Botões de Compartilhamento', 'add_share_buttons' ),
		__('Botões de Compartilhamento', 'add_share_buttons' ),
		'manage_options',
		'add_share_options_page',
		array(
			$this,
			'settings_page'
		)
	);
  }

  function settings_page(){
  	?>
  	<form enctype="multipart/form-data" method="post" action="<?= site_url('/wp-admin/admin-post.php'); ?>">
  	  <input type="hidden" name="action" value="update_settings">
  	  <p>
  	  <?php _e( 'Há alguns conteúdos que podem estar sem uma imagem, faça o envio de uma imagem que será a padrão nesses casos.', 'add_share_buttons' ) ?>
  	  </p>
  	  <!-- image url -->
  	  <p>
  	  <input type="file" name="add_share_default_image" />
  	  </p>
  	    	  <p>
  	  <?php _e( 'Imagem atual', 'add_share_buttons' ) ?>: 
  	  </p>
  	  <p>
  	  <img src="<?= get_option('add_share_default_image') ?>">
  	  </p>
  	  <p>
  	  <?php _e( 'Selecione os conteudos em os botões de compartilhamento devem aparecer', 'add_share_buttons' ) ?>: 
  	  </p>
  	  <!-- select post types -->
  	  <?php $post_types = get_post_types( array( 'show_ui' => true ) , 'objects' ); ?>
  	  <p>
  	  <?php foreach ($post_types as $post_type) { 
  	  	?>
		<input type="checkbox" name="add_share_post_types[]" value="<?= $post_type->name; ?>"  <?php foreach (get_option( 'add_share_post_types', array() ) as $active ) {
			if ($active == $post_type->name) {
				echo 'checked';
				break;
			}
		}?>><?= $post_type->labels->name ?> </input>
      <?php } ?>
  	</p> 
  	<!-- send button -->
  	<?= submit_button( __( 'Salvar', 'add_share_buttons' ), 'primary' , 'add_share_settings' ); ?>
  	</form>
  	<?php
  }

  function update_settings(){
  	if(isset($_FILES['add_share_default_image'])){
	  	// add file
	  	if ( ! function_exists( 'wp_handle_upload' ) ) {
		    require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		$uploadedfile = $_FILES['add_share_default_image'];

		$upload_overrides = array( 'test_form' => false );

		$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

		if ( $movefile && ! isset( $movefile['error'] ) ) {
		    update_option( 'add_share_default_image', $movefile['url'] );
		}
		/*else{
			 wp_redirect(home_url('/wp-admin/options-general.php?page=add_share_options_page&success=0&error=' . $movefile['error']));
			 exit;
		}*/
  	}


	// add post type
	$old_post_types = get_option( 'add_share_post_types', '' );
	$post_types = isset($_POST['add_share_post_types']) ? $_POST['add_share_post_types'] : $old_post_types;

	update_option( 'add_share_post_types', $post_types );

	wp_redirect(home_url('/wp-admin/options-general.php?page=add_share_options_page&success=1'));
	exit;

  }

  function my_admin_notice(){
    global $pagenow;
    $page = get_current_screen()->base == 'settings_page_add_share_options_page';
    $success_response = isset($_GET['success']) ? $_GET['success'] : '';
    $success = ( $success_response == '1' )? true : false;
    if ( $pagenow == 'options-general.php'  && $page && $success ) {

         echo '<br><div class="update-nag settings-error notice is-dismissible">
             <p>' . __('Configurações adicionadas com sucesso' , 'add_share_buttons') . '</p>
         </div>';
    }elseif (!$success && isset($_GET['error'])) {
    	 echo '<br><div class="error settings-error notice is-dismissible">
             <p>' . __( $_GET['error'] , 'add_share_buttons') . '</p>
         </div>';
    }
  }

  function add_body_js( $content ){
    $content = '<div id="fb-root"></div><script>(function(d, s, id) {
      var js, fjs = d.getElementsByTagName(s)[0];
      if (d.getElementById(id)) return;
      js = d.createElement(s); js.id = id;
      js.src = "//connect.facebook.net/pt_BR/sdk.js#xfbml=1&version=v2.6";
      fjs.parentNode.insertBefore(js, fjs);
      }(document, \'script\', \'facebook-jssdk\'));
    </script><script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?\'http\':\'https\';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+\'://platform.twitter.com/widgets.js\';fjs.parentNode.insertBefore(js,fjs);}}(document, \'script\', \'twitter-wjs\');
    </script>
    <script src="https://apis.google.com/js/platform.js" async defer>
     {lang: \'pt-BR\'}
    </script>' . $content;
    return $content;
  }
}
new Add_Share_Buttons();