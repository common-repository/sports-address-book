<?php
/**
 * @link              https://infinitumform.com/
 * @since             1.0.0
 * @package           Sports_Address_Book
 *
 * @wordpress-plugin
 * Plugin Name:       Address Book
 * Plugin URI:        https://infinitumform.com/
 * Description:       Simple address book plugin for easy search of the sport clubs, schools, institutions, etc.
 * Version:           1.1.6
 * Author:            INFINITUM FORM
 * Author URI:        https://infinitumform.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       sports-address-book
 * Domain Path:       /languages
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
 
 // If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) { die( "Don't mess with us." ); }
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Debug mode enabled
if ( defined( 'WP_CF_SAB_DEBUG' ) ){
	error_reporting( E_ALL );
	if(function_exists('ini_set'))
	{
		ini_set('display_startup_errors',1);
		ini_set('display_errors',1);
	}
}

if ( ! defined( 'WP_CF_SAB_FILE' ) )	define('WP_CF_SAB_FILE', __FILE__ );
if ( ! defined( 'WP_CF_SAB_URL' ) )		define('WP_CF_SAB_URL', plugin_dir_url( __FILE__ ) );

// Find and place plugin version
$sab_version = NULL;
if(function_exists('get_file_data') && $plugin_data = get_file_data( WP_CF_SAB_FILE, array('Version' => 'Version'), false ))
	$sab_version = $plugin_data['Version'];
if(!$sab_version && preg_match('/\*[\s\t]+?version:[\s\t]+?([0-9.]+)/i', file_get_contents( WP_CF_SAB_FILE ), $v))
	$sab_version = $v[1];
if ( ! defined( 'WP_CF_SAB_VERSION' ) )	define('WP_CF_SAB_VERSION', $sab_version);

if ( ! defined( 'WP_CF_SAB' ) )			define('WP_CF_SAB', 'sports-address-book');
if ( ! defined( 'WP_CF_SAB_METABOX' ) )	define('WP_CF_SAB_METABOX', '_sports_address_book_');
if ( ! defined( 'WP_CF_SAB_PREFIX' ) )	define('WP_CF_SAB_PREFIX', 'sports_address_book_'.preg_replace("/[^0-9]/Ui",'',WP_CF_SAB_VERSION).'_');

class WP_address_book{
	
	public function __construct(){
		$this->includes();
		
		$this->add_action( 'plugins_loaded', $this, 'load_plugin_textdomain' );
		$this->add_action( 'admin_head', $this, 'head_hook' );
		
		$this->add_action( 'init', $this, 'register_taxonomy' );
		$this->add_action( 'init', $this, 'register_post', 1, 9);
		$this->add_action( 'init', $this, 'initialize_meta_boxes', 9999 );
		
		$this->add_filter( 'post_type_link', $this, 'sport_taxonomy_rewrite', 1, 3 );
		$this->add_filter( 'post_type_link', $this, 'city_taxonomy_rewrite', 1, 3 );
		$this->add_filter( 'post_type_link', $this, 'institution_taxonomy_rewrite', 1, 3 );
		
		$this->add_filter( 'cmb_meta_boxes', $this, 'metaboxes' );
		
		$this->add_filter( 'plugin_action_links', $this,'action_links', 10, 5 );
		
		if(is_admin()===true)
		{
			$this->add_action( 'admin_enqueue_scripts', $this, 'enqueue_styles', 9999, 1 );
			$this->add_action( 'admin_enqueue_scripts', $this, 'enqueue_scripts', 9999, 1 );
			
			$this->add_filter('manage_posts_columns', $this, 'columns_head');
			$this->add_action('manage_posts_custom_column', $this, 'columns_content', 10, 2);
			
			if(isset($_GET['post_type']) && $_GET['post_type']=='address-book' || isset($_GET['action']) && $_GET['action']=='sab_duplicate_post_as_draft')
			{
				$this->add_action( 'admin_action_sab_duplicate_post_as_draft', $this, 'sab_duplicate_post_as_draft' );
				$this->add_filter( 'post_row_actions', $this, 'sab_duplicate_post_link', 10, 2 );
			}
		}
		else
		{
			$this->add_action( 'wp_enqueue_scripts', $this, 'enqueue_styles', 9999, 1 );
			$this->add_action( 'wp_enqueue_scripts', $this, 'enqueue_scripts', 9999, 1 );
			$this->add_action('wp_head', $this, 'custom_style', 9999, 1);
		}
		
		$this->add_shortcode( 'address_book_search', $this,'shortcode_address_book_search');
		$this->add_shortcode( 'address_book', $this,'shortcode_address_book');
		
		// Deprecated from version 1.1.5
		$this->add_shortcode( 'sport_address_book_search', $this,'shortcode_sport_address_book_search');
		$this->add_shortcode( 'sport_address_book', $this,'shortcode_sport_address_book');
	}
	
	private function includes()
	{
		require_once plugin_dir_path( __FILE__  ) . 'include/sports-address-book-options.php';
		register_activation_hook( __FILE__, 'sports_address_book_settings_section_init' );
	}
	
	/* 
	* Generate and clean POST
	* @name          GET name
	* @option        string, int, float, bool, html, encoded, url, email
	* @default       default value
	*/
	private function post($name, $option="string", $default=''){
		$option = trim((string)$option);
		if(isset($_POST[$name]) && !empty($_POST[$name]))
		{        
			if(is_array($_POST[$name]))
				$is_array=true;
			else
				$is_array=false;
			
			$sanitize = array(
				'email'        =>    FILTER_SANITIZE_STRING,
				'string'    =>    FILTER_SANITIZE_STRING,
				'bool'        =>    FILTER_SANITIZE_STRING,
				'int'        =>    FILTER_SANITIZE_NUMBER_INT,
				'float'        =>    FILTER_SANITIZE_NUMBER_FLOAT,
				'html'        =>    FILTER_SANITIZE_SPECIAL_CHARS,
				'encoded'    =>    FILTER_SANITIZE_ENCODED,
				'url'        =>    FILTER_SANITIZE_URL,
				'none'        =>    'none',
				'false'        =>    'none'
			);
			
			if(is_numeric($option))
				$sanitize[$option]='none';
			
			
			if($sanitize[$option] == 'none')
			{
				if($is_array)
					$input = array_map("trim",$_POST[$name]);
				else
					$input = trim($_POST[$name]);
			}
			else
			{
				if($is_array)
				{
					$input = filter_input(INPUT_POST, $name, $sanitize[$option], FILTER_REQUIRE_ARRAY);
				}
				else
				{
					$input = filter_input(INPUT_POST, $name, $sanitize[$option]);
				}
			}
			
			switch($option)
			{
				default:
				case 'string':
				case 'html':
					$set=array(
						'options' => array('default' => $default)
					);
					if($is_array) $set['flags']=FILTER_REQUIRE_ARRAY;
					
					return filter_var($input, FILTER_SANITIZE_STRING, $set);
				break;
				case 'encoded':
					return (!empty($input)?$input:$default);
				break;
				case 'url':
					$set=array(
						'options' => array('default' => $default)
					);
					if($is_array) $set['flags']=FILTER_REQUIRE_ARRAY;
					
					return filter_var($input, FILTER_VALIDATE_URL, $set);
				break;
				case 'email':
					$set=array(
						'options' => array('default' => $default)
					);
					if($is_array) $set['flags']=FILTER_REQUIRE_ARRAY;
					
					return filter_var($input, FILTER_VALIDATE_EMAIL, $set);
				break;
				case 'int':
					$set=array(
						'options' => array('default' => $default, 'min_range' => 0)
					);
					if($is_array) $set['flags']=FILTER_FLAG_ALLOW_OCTAL | FILTER_REQUIRE_ARRAY;
					
					return filter_var($input, FILTER_VALIDATE_INT, $set);
				break;
				case 'float':
					$set=array(
						'options' => array('default' => $default)
					);
					if($is_array) $set['flags']=FILTER_REQUIRE_ARRAY;
					
					return filter_var($input, FILTER_VALIDATE_FLOAT, $set);
				break;
				case 'bool':
					$set=array(
						'options' => array('default' => $default)
					);
					if($is_array) $set['flags']=FILTER_REQUIRE_ARRAY;
					
					return filter_var($input, FILTER_VALIDATE_BOOLEAN, $set);
				break;
				case 'none':
					return $input;
				break;
			}
		}
		else
		{
			return $default;
		}
	}

	
	/* 
	* Generate and clean GET
	* @name          GET name
	* @option        string, int, float, bool, html, encoded, url, email
	* @default       default value
	*/
	private function get($name, $option="string", $default=''){
        $option = trim((string)$option);
        if(isset($_GET[$name]) && !empty($_GET[$name]))
        {           
            if(is_array($_GET[$name]))
                $is_array=true;
            else
                $is_array=false;
            
            $sanitize = array(
                'email'        =>    FILTER_SANITIZE_STRING,
                'string'    =>    FILTER_SANITIZE_STRING,
                'bool'        =>    FILTER_SANITIZE_STRING,
                'int'        =>    FILTER_SANITIZE_NUMBER_INT,
                'float'        =>    FILTER_SANITIZE_NUMBER_FLOAT,
                'html'        =>    FILTER_SANITIZE_SPECIAL_CHARS,
                'encoded'    =>    FILTER_SANITIZE_ENCODED,
                'url'        =>    FILTER_SANITIZE_URL,
                'none'        =>    'none',
                'false'        =>    'none'
            );
            
            if(is_numeric($option))
                $sanitize[$option]='none';
            
            
            if($sanitize[$option] == 'none')
            {
                if($is_array)
                    $input = array_map("trim",$_GET[$name]);
                else
                    $input = trim($_GET[$name]);
            }
            else
            {
                if($is_array)
                {
                    $input = filter_input(INPUT_GET, $name, $sanitize[$option], FILTER_REQUIRE_ARRAY);
                }
                else
                {
                    $input = filter_input(INPUT_GET, $name, $sanitize[$option]);
                }
            }
            
            switch($option)
            {
                default:
                case 'string':
                case 'html':
                    $set=array(
                        'options' => array('default' => $default)
                    );
                    if($is_array) $set['flags']=FILTER_REQUIRE_ARRAY;
                    
                    return filter_var($input, FILTER_SANITIZE_STRING, $set);
                break;
                case 'encoded':
                    return (!empty($input)?$input:$default);
                break;
                case 'url':
                    $set=array(
                        'options' => array('default' => $default)
                    );
                    if($is_array) $set['flags']=FILTER_REQUIRE_ARRAY;
                    
                    return filter_var($input, FILTER_VALIDATE_URL, $set);
                break;
                case 'email':
                    $set=array(
                        'options' => array('default' => $default)
                    );
                    if($is_array) $set['flags']=FILTER_REQUIRE_ARRAY;
                    
                    return filter_var($input, FILTER_VALIDATE_EMAIL, $set);
                break;
                case 'int':
                    $set=array(
                        'options' => array('default' => $default, 'min_range' => 0)
                    );
                    if($is_array) $set['flags']=FILTER_FLAG_ALLOW_OCTAL | FILTER_REQUIRE_ARRAY;
                    
                    return filter_var($input, FILTER_VALIDATE_INT, $set);
                break;
                case 'float':
                    $set=array(
                        'options' => array('default' => $default)
                    );
                    if($is_array) $set['flags']=FILTER_REQUIRE_ARRAY;
                    
                    return filter_var($input, FILTER_VALIDATE_FLOAT, $set);
                break;
                case 'bool':
                    $set=array(
                        'options' => array('default' => $default)
                    );
                    if($is_array) $set['flags']=FILTER_REQUIRE_ARRAY;
                    
                    return filter_var($input, FILTER_VALIDATE_BOOLEAN, $set);
                break;
                case 'none':
                    return $input;
                break;
            }
        }
        else
        {
            return $default;
        }
    }
	
	/* Get Terms */
	private function get_terms($term, $hide=false, $orderby='name', $order='ASC'){
		return get_terms( array(
			'taxonomy' => (string)$term,
			'hide_empty' => (bool)$hide,
			'order' => (string)strtoupper($order),
			'orderby' => (string)$orderby
		) );
	}
	
	/* Shortcode Sport Address Book Search */
	public function shortcode_address_book_search($attr){	
		global $post;
		
		$option = get_option( 'sports_address_book_settings' );
		
		extract(shortcode_atts( array(
			'id'				=>  'sab-search',
			'class'				=>  false,
			'label_institution'	=>  $option['sports_address_book_text_field_3'],
			'label_city'		=>  $option['sports_address_book_text_field_4'],
			'label_sport'		=>  $option['sports_address_book_text_field_5'],
			'label_search'		=>  $option['sports_address_book_text_field_6'],
			'label_button'		=>  $option['sports_address_book_text_field_7']
        ), $attr ));
		
		$institution = $this->get_terms('institution',false);
		$sport = $this->get_terms('sport',false);
		$city = $this->get_terms('city',false);
		
		ob_start(); ?>
		<form name="sab-search" class="sab-search-form<?php echo (!empty($class)?' '.$class:''); ?>" method='get' id="sab-search">
			<div class="sab-search<?php echo (!empty($class)?' '.$class:''); ?>" id="<?php echo $id; ?>">
				<div class="coll">
					<select name="institution">
						<option value=""><?php echo $label_institution; ?></option>
						<?php
							foreach($institution as $i=>$fetch)
							{
								$selected=($this->get('institution','string')==$fetch->slug?' selected':'');
								printf('<option value="%s"%s>%s</option>',esc_attr($fetch->slug), esc_attr($selected),$fetch->name);
							}
						?>
					</select>
				</div>
				<div class="coll">
					<select name="city">
						<option value=""><?php echo $label_city; ?></option>
						<?php
							foreach($city as $i=>$fetch)
							{
								$selected=($this->get('city','string')==$fetch->slug?' selected':'');
								printf('<option value="%s"%s>%s</option>',esc_attr($fetch->slug), esc_attr($selected),$fetch->name);
							}
						?>
					</select>
				</div>
				<div class="coll">
					<select name="sport">
						<option value=""><?php echo $label_sport; ?></option>
						<?php
							foreach($sport as $i=>$fetch)
							{
								$selected=($this->get('sport','string')==$fetch->slug?' selected':'');
								printf('<option value="%s"%s>%s</option>',esc_attr($fetch->slug), esc_attr($selected),$fetch->name);
							}
						?>
					</select>
				</div>
				<div class="coll">
					<input type="text" name="keyword" placeholder="<?php echo esc_attr($label_search); ?>" value="<?php echo esc_attr($this->get('keyword','string')); ?>">
					<input type="hidden" value="<?php echo $this->get('page_id','int',$post->ID); ?>" name="page_id">
					<input type="hidden" value="<?php echo $this->get('pgd','int', 1); ?>" name="pgd">
					<input type="hidden" value="<?php echo $this->get('p','int', $post->ID); ?>" name="p">
				</div>
				<div class="coll-full">
					<button class="sab-search-submit" type="submit"><?php echo $label_button; ?></button>
				</div>
			</div>
		</form>
		<?php return ob_get_clean();
	}
	
	/* Shortcode Sport Address Book */
	public function shortcode_address_book($attr){
		global $post;
		
		$option = get_option( 'sports_address_book_settings' );
		extract(shortcode_atts( array(
			'id'		=>  'sab-search',
			'class'		=>  false,
			'search'	=>  true,
			'orderby'	=>	$option['sports_address_book_select_field_0'],
			'order'		=>	$option['sports_address_book_select_field_1'],
			'posts_per_page'	=> $option['sports_address_book_text_field_2'],
			'label_institution'	=>  $option['sports_address_book_text_field_3'],
			'label_city'		=>  $option['sports_address_book_text_field_4'],
			'label_sport'		=>  $option['sports_address_book_text_field_5'],
			'label_search'		=>  $option['sports_address_book_text_field_6'],
			'label_button'		=>  $option['sports_address_book_text_field_7'],
			'label_no_results'	=>	$option['sports_address_book_text_field_8'],
			'label_name'		=>	$option['sports_address_book_text_field_9'],
			'label_address'		=>	$option['sports_address_book_text_field_10'],
			'label_phone'		=>	$option['sports_address_book_text_field_11'],
			'label_email'		=>	$option['sports_address_book_text_field_12'],
			'label_url'			=>	$option['sports_address_book_text_field_13'],
			'label_more_info'	=>	$option['sports_address_book_text_field_14'],
			'label_prev'		=>	$option['sports_address_book_text_field_15'],
			'label_next'		=>	$option['sports_address_book_text_field_16'],
        ), $attr ));
		
		$prefix = WP_CF_SAB_METABOX;
		
		$accept = array('institution','city','sport','keyword','page_id','pgd','p');
		
		$get=array();
		foreach($accept as $name)
		{
			if($name=='page_id' || $name=='p' || $name=='pgd')
				$get[$name]=$this->get($name,'int',false);
			else
				$get[$name]=$this->get($name,'string',false);
		}
		$get=(object)$get;
		
		ob_start(); ?>
		<?php if($search===true): ?>
			<?php echo do_shortcode(sprintf('[address_book_search label_institution="%s" label_city="%s" label_sport="%s" label_search="%s" label_button="%s" id="%s" class="%s"]',$label_institution,$label_city,$label_sport,$label_search,$label_button,$id,$class)); ?>
		<?php endif; ?>
		
		<?php
			$args = array(
			  'post_type'		=> 'address-book',
			  'posts_per_page'	=>	$posts_per_page,
			  'paged'			=> (int)$get->pgd,
			  'post_status'		=> 'publish',
			  'orderby'			=>	$orderby,
			  'order'			=>	$order,
			);
			
			if($get->institution !== false || $get->city !== false  || $get->sport !== false )
				$args['tax_query']['relation']='AND';
			
			if($get->institution !== false)
			{
				$args['tax_query'][]=array(
					'taxonomy'	=> 'institution',
					'field'		=> 'slug',
					'terms'		=> array($get->institution)
				);
			}
			if($get->city !== false)
			{
				$args['tax_query'][]=array(
					'taxonomy'	=> 'city',
					'field'		=> 'slug',
					'terms'		=> array($get->city)
				);
			}
			if($get->sport !== false)
			{
				$args['tax_query'][]=array(
					'taxonomy'	=> 'sport',
					'field'		=> 'slug',
					'terms'		=> array($get->sport)
				);
			}
			if($get->keyword !== false)
			{
				$args['meta_query']['relation']='OR';
				$args['meta_query'][]=array(
					'key'     => $prefix.'content',
					'value'   => $get->keyword,
					'compare' => 'LIKE',
				);
				$args['meta_query'][]=array(
					'key'     => $prefix.'phone',
					'value'   => $get->keyword,
					'compare' => 'LIKE',
				);
				$args['meta_query'][]=array(
					'key'     => $prefix.'email',
					'value'   => $get->keyword,
					'compare' => 'LIKE',
				);
				$args['meta_query'][]=array(
					'key'     => $prefix.'address',
					'value'   => $get->keyword,
					'compare' => 'LIKE',
				);
				$args['meta_query'][]=array(
					'key'     => 'title',
					'value'   => $get->keyword,
					'compare' => 'LIKE',
				);
				$args['meta_query'][]=array(
					'key'     => 'post_title',
					'value'   => $get->keyword,
					'compare' => 'LIKE',
				);
				$args['meta_query'][]=array(
					'key'     => 'name',
					'value'   => $get->keyword,
					'compare' => 'LIKE',
				);
				$args['meta_query'][]=array(
					'key'     => $prefix.'keyword',
					'value'   => $get->keyword,
					'compare' => 'LIKE',
				);
			}
			
			echo "<div id='sab-result-container'>";
			
			$p=$post->ID;
			
			$query = new WP_Query( $args );
			if ( $query->have_posts() )
			{				
				$save=array();
				$max = 0;
				while ( $query->have_posts() )
				{
					$max++;
					$query->the_post();
					$post_id = get_the_id();
					$image=$this->featured_image('large');
					$attr_title=the_title_attribute(array('echo'=>false,'post'=>$post_id));
					$title=get_the_title();
					$address=$this->get_meta('address',$post_id);
					$phone=$this->get_meta('phone',$post_id);
					$email=$this->get_meta('email',$post_id);
					$url=$this->get_meta('url',$post_id);
					$content=do_shortcode(apply_filters('the_content', $this->get_meta('content',$post_id)));
					echo "
					<section class='sab-result'>
						<div class='coll'>
							<img src='${image}' alt='${attr_title}' class='img-responsive'>
						</div>
						<address class='coll'>
							<h2>${title}</h2>
							<p class='sab-data-container'><strong>${label_address}:</strong> <span class='sab-data'>${address}</span><p>
							<p class='sab-data-container'><strong>${label_phone}:</strong> <a class='sab-data' rel='nofollow' href='tel:".preg_replace("/[^0-9\+]/Ui","",$phone)."'>${phone}</a><p>
							<p class='sab-data-container'><strong>${label_email}:</strong> <a class='sab-data' rel='nofollow' href='mailto:${email}'>${email}</a><p>
							<p class='sab-data-container'><strong>${label_url}:</strong> <a class='sab-data' rel='nofollow' target='_blank' href='${url}'>${url}</a><p>
							".(!empty($label_more_info) && $label_more_info!==null ? "<p class='sab-data-button'><button type='button' class='sab-parallax' data-id='#sab-parallax-${post_id}'>${label_more_info} [<span class='sab-parallax-${post_id}-plus'>+</span>]</button></p>":"")."
						</address>
						<div class='coll-parallax' id='sab-parallax-${post_id}'>${content}</div>
					</section>
					";
				}
				
				?>
				<nav id="sab-pagination">
				<?php
					if($prev_init = $this->pagination_previous($p)):
					$prev = home_url( '/?' ).$prev_init;
				?>
					<a class="sab-pagination prev" href="<?php echo $prev; ?>"><?php echo $label_prev; ?></a>
				<?php endif; ?>
				<?php
					if($next_init = $this->pagination_next($p,$max, $posts_per_page)):
					$next = home_url( '/?' ).$next_init;
				?>
					<a class="sab-pagination next" href="<?php echo $next; ?>"><?php echo $label_next; ?></a>
				<?php endif; ?>
				</nav>
				<?php
				wp_reset_postdata();
			}
			else
			{
				echo "<h3>{$label_no_results}</h3>";
			}
			echo '</div>';
			
		?>
		
		<?php return ob_get_clean();
	}
	
	/* Custom CSS Style */
	public function custom_style(){
		$option = get_option( 'sports_address_book_settings' );
if(isset($option['sports_address_book_text_field_17']) && !empty($option['sports_address_book_text_field_17'])):?>
<style>
<?php echo $option['sports_address_book_text_field_17']; ?>
</style>
	<?php endif; }
	
	private function pagination_next($p, $max, $posts_per_page)
	{
		$accept = array('institution','city','sport','keyword','page_id','pgd','p');
		
		$get=array();
		foreach($accept as $name)
		{
			if($name=='page_id' || $name=='p' || $name=='pgd')
				$get[$name]=$this->get($name,'int',false);
			else
				$get[$name]=$this->get($name,'string',false);
		}
		
		$part=array();
		foreach($get as $name=>$val)
		{
			if($name == 'pgd')
			{
				$next=(int)($this->get('pgd','int',0)!==0?$val:1)+1;
				if(($next-1)>$max || $posts_per_page > $max)
					return false;
				else
					$part[]=$name.'='.($next);
			}
			else if($name == 'p')
				$part[]=$name.'='.$p;
			else if($name == 'page_id')
				$part[]=$name.'='.$p;
			else
				$part[]=$name.'='.rawurlencode($val);
		}
		
		return join("&",$part);
		
	}
	
	private function pagination_previous($p)
	{
		$accept = array('institution','city','sport','keyword','page_id','pgd','p');
		
		$get=array();
		foreach($accept as $name)
		{
			if($name=='page_id' || $name=='p' || $name=='pgd')
				$get[$name]=$this->get($name,'int',false);
			else
				$get[$name]=$this->get($name,'string',false);
		}
		
		$part=array();
		foreach($get as $name=>$val)
		{
			if($name == 'pgd')
			{
				$prev=(int)$val-1;
				if(($prev)<1)
					return false;
				else
					$part[]=$name.'='.($prev);
			}
			else if($name == 'p')
				$part[]=$name.'='.$p;
			else if($name == 'page_id')
				$part[]=$name.'='.$p;
			else
				$part[]=$name.'='.rawurlencode($val);
		}
		
		return join("&",$part);
		
	}
	
	/* For Translations */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			WP_CF_SAB,
			false,
			plugin_basename( __FILE__ ) . '/languages/'
		);

	}
	
	/* Register New Post Type */
	public function register_post(){
		$post   = array(
			'labels'				=> array(
				'name'               		=> __( 'Address Book',WP_CF_SAB ),
				'singular_name'      		=> __( 'Address Book',WP_CF_SAB ),
				'add_new'            		=> __( 'Add Address',WP_CF_SAB),
				'add_new_item'       		=> __( "Add Address",WP_CF_SAB),
				'edit_item'          		=> __( "Edit Address",WP_CF_SAB),
				'new_item'           		=> __( "New Address",WP_CF_SAB),
				'view_item'          		=> __( "View Address",WP_CF_SAB),
				'search_items'       		=> __( "Search Addresses",WP_CF_SAB),
				'not_found'          		=> __( 'No Address Found',WP_CF_SAB),
				'not_found_in_trash' 		=> __( 'No Address Found in Trash',WP_CF_SAB),
				'parent_item_colon'  		=> '',
				'featured_image'	 		=> __('Institution Logo',WP_CF_SAB),
				'set_featured_image'		=> __('Select Institution Logo',WP_CF_SAB),
				'remove_featured_image'		=> __('Remove Institution Logo',WP_CF_SAB),
				'use_featured_image'		=> __('Use Institution Logo',WP_CF_SAB),
				'insert_into_item'			=> __('Insert Into Address Book',WP_CF_SAB)
			),
			'public'            	=> false,
			'exclude_from_search'	=> true,
			'publicly_queryable'	=> false,
			'show_in_nav_menus'   	=> false,
			'show_ui'           	=> true,
			'query_var'         	=> true,
			'hierarchical'      	=> false,
			'menu_position'     	=> 10,
			'capability_type'   	=> "post",
			'supports'          	=> array( 'title', 'thumbnail', 'tags' ),
			'menu_icon'         	=> 'dashicons-awards',
			'taxonomies' 			=> array( 'institution', 'sport', 'city' ),
		);
		register_post_type( 'address-book', $post );
	}
	
	/* Register New Taxonomy */
	public function register_taxonomy(){
		register_taxonomy(
			'institution', 'address-book',
			array(
				'labels'=>array(
					'name' 				=> __('Institutions',WP_CF_SAB),
					'singular_name' 		=> __('Institution',WP_CF_SAB),
					'menu_name' 			=> __('Institutions',WP_CF_SAB),
					'all_items' 			=> __('All Institutions',WP_CF_SAB),
					'edit_item' 			=> __('Edit Institution',WP_CF_SAB),
					'view_item' 			=> __('View Institution',WP_CF_SAB),
					'update_item' 		=> __('Update Institution',WP_CF_SAB),
					'add_new_item' 		=> __('Add New Institution',WP_CF_SAB),
					'new_item_name' 		=> __('New Institution Name',WP_CF_SAB),
					'parent_item' 		=> __('Parent Institution',WP_CF_SAB),
					'parent_item_colon' 	=> __('Parent Institution',WP_CF_SAB),
				),
				'hierarchical'	=> true,
				'public'		 => false,
				'exclude_from_search'	=> true,
				'publicly_queryable'	=> false,
				'show_in_nav_menus'   	=> false,
				'show_ui'           	=> true,
				'label'          => __('Institutions',WP_CF_SAB),
				'singular_label' => __('Institution',WP_CF_SAB),
				'rewrite'        => true,
				'query_var'		=> false,
				'show_tagcloud'	=>	false,
				'show_in_nav_menus'=>false,
			)
		);
		register_taxonomy(
			'sport', 'address-book',
			array(
				'labels'=>array(
					'name' 					=> __('Sports',WP_CF_SAB),
					'singular_name' 		=> __('Sport',WP_CF_SAB),
					'menu_name' 			=> __('Sports',WP_CF_SAB),
					'all_items' 			=> __('All Sports',WP_CF_SAB),
					'edit_item' 			=> __('Edit Sport',WP_CF_SAB),
					'view_item' 			=> __('View Sport',WP_CF_SAB),
					'update_item' 			=> __('Update Sport',WP_CF_SAB),
					'add_new_item' 			=> __('Add New Sport',WP_CF_SAB),
					'new_item_name' 		=> __('New Sport Name',WP_CF_SAB),
					'parent_item' 			=> __('Parent Sport',WP_CF_SAB),
					'parent_item_colon' 	=> __('Parent Sport',WP_CF_SAB),
				),
				'hierarchical'   => true,
				'public'		 => false,
				'exclude_from_search'	=> true,
				'publicly_queryable'	=> false,
				'show_in_nav_menus'   	=> false,
				'show_ui'           	=> true,
				'label'          => __('Sports',WP_CF_SAB),
				'singular_label' => __('Sport',WP_CF_SAB),
				'rewrite'        => true,
				'query_var'		=> false,
				'show_tagcloud'	=>	false,
				'show_in_nav_menus'=>false
			)
		);
		register_taxonomy(
			'city', 'address-book',
			array(
				'labels'=>array(
					'name' 					=> __('Cities',WP_CF_SAB),
					'singular_name' 		=> __('City',WP_CF_SAB),
					'menu_name' 			=> __('Cities',WP_CF_SAB),
					'all_items' 			=> __('All Cities',WP_CF_SAB),
					'edit_item' 			=> __('Edit City',WP_CF_SAB),
					'view_item' 			=> __('View City',WP_CF_SAB),
					'update_item' 			=> __('Update City',WP_CF_SAB),
					'add_new_item' 			=> __('Add New City',WP_CF_SAB),
					'new_item_name' 		=> __('New City Name',WP_CF_SAB),
					'parent_item' 			=> __('Parent City',WP_CF_SAB),
					'parent_item_colon' 	=> __('Parent City',WP_CF_SAB),
				),
				'hierarchical'   => true,
				'public'		 => false,
				'exclude_from_search'	=> true,
				'publicly_queryable'	=> false,
				'show_in_nav_menus'   	=> false,
				'show_ui'           	=> true,
				'label'          => __('Cities',WP_CF_SAB),
				'singular_label' => __('City',WP_CF_SAB),
				'rewrite'        => true,
				'query_var'		=> false,
				'show_tagcloud'	=>	false,
				'show_in_nav_menus'=>false
			)
		);
	}
	
	/* Table Column Head */
	public function columns_head($column_name) {
		if($this->get('post_type','string') == 'address-book')
		{
			$column_name['address_book_info'] = __('Info',WP_CF_SAB);
			$column_name['address_book_logo'] = __('Logo',WP_CF_SAB);
		}
		return $column_name;
	}
	
	/* Get Featured Image */
	public function featured_image($type='thumbnail', $default=NULL, $id=false) {
		global $post;
		if($id!==false && !empty($id) && $id > 0)
			$post_thumbnail_id = get_post_thumbnail_id($id);
		else if(NULL!==get_the_id() && false!==get_the_id())
			$post_thumbnail_id = get_post_thumbnail_id(get_the_id());
		else if(isset($post->ID) && $post->ID > 0)
			$post_thumbnail_id = get_post_thumbnail_id($post->ID);
		else if('page' == get_option( 'show_on_front' ))
			$post_thumbnail_id = get_post_thumbnail_id(get_option( 'page_for_posts' ));
		else if(is_home() || is_front_page() || get_queried_object_id() > 0)
			$post_thumbnail_id = get_post_thumbnail_id(get_queried_object_id());
		else
			$post_thumbnail_id = 0;
	
		if ($post_thumbnail_id!==false && $post_thumbnail_id > 0) {
			$post_thumbnail_img = wp_get_attachment_image_src($post_thumbnail_id, $type);
			if(isset($post_thumbnail_img[0]) && !empty($post_thumbnail_img[0]))
				return trim($post_thumbnail_img[0]);
			else
				return $default;
		}else
			return $default;
	}
	
	/* Table Column Content */
	public function columns_content($column_name, $post_ID) {
		if($this->get('post_type','string') == 'address-book')
		{
			if ($column_name == 'address_book_info')
			{
				// get all taxonomies
				$taxonomy_list = array(
					__('City',WP_CF_SAB)		=>	'city',					
					__('Sport',WP_CF_SAB)		=>	'sport',
					__('Institution',WP_CF_SAB)	=>	'institution',
					
				);
				$print=array();
				// list taxonomies
				foreach($taxonomy_list as $name=>$taxonomy)
				{
					// list all terms
					$all_terms = wp_get_post_terms($post_ID, $taxonomy, array("fields" => "all"));
					$part=array();
					foreach($all_terms as $i=>$fetch)
					{
						$edit_link = esc_url( get_edit_term_link( $fetch->term_id, $taxonomy, 'address-book' ) );
						$part[]='<a href="'.$edit_link.'">'.$fetch->name.'</a>';
					}
					if(count($part)>0)
					{
						$print[]='<li><strong>'.$name.':</strong> ';
							$print[]=join(", ",$part);
						$print[]='<li>';
					}
				}
				// print terms
				if(count($print)>0)
				{
					echo '<ul>'.join("\r\n",$print).'</ul>';
				}
				else
				{
					echo '( '.__('not defined',WP_CF_SAB).' )';
				}
			}
			else if ($column_name == 'address_book_logo')
			{
				$post_featured_image = $this->featured_image('thumbnail', WP_CF_SAB_URL.'images/noimage.png', $post_ID);
				echo '<img src="' . $post_featured_image . '" title="'.the_title_attribute(array('echo'=>false,'post'=>$post_ID)).'" alt="'.the_title_attribute(array('echo'=>false,'post'=>$post_ID)).'" style="max-height:95px; margin: 0 auto; display:block" />';
			}
		}
	}
	
	/* Rewrite Institution */
	public function institution_taxonomy_rewrite( $post_link, $id = 0 ){
		$post = get_post($id);  
		if ( is_object( $post ) ){
			$terms = wp_get_object_terms( $post->ID, 'institution' );
			if( $terms ){
				return str_replace( '%institution%' , $terms[0]->slug , $post_link );
			}
		}
		return $post_link;  
	}
	
	/* Rewrite Sport */
	public function sport_taxonomy_rewrite( $post_link, $id = 0 ){
		$post = get_post($id);  
		if ( is_object( $post ) ){
			$terms = wp_get_object_terms( $post->ID, 'sport' );
			if( $terms ){
				return str_replace( '%sport%' , $terms[0]->slug , $post_link );
			}
		}
		return $post_link;  
	}
	
	/* Rewrite City */
	public function city_taxonomy_rewrite( $post_link, $id = 0 ){
		$post = get_post($id);  
		if ( is_object( $post ) ){
			$terms = wp_get_object_terms( $post->ID, 'city' );
			if( $terms ){
				return str_replace( '%city%' , $terms[0]->slug , $post_link );
			}
		}
		return $post_link;  
	}
	
	/* Hook for add_action() */
	protected function add_action($tag, $class, $function_to_add, $priority = 10, $accepted_args = 1){
		return add_action( (string)$tag, array($class, $function_to_add), (int)$priority, (int)$accepted_args );
	}
	
	/* Hook for add_filter() */
	protected function add_filter($tag, $class, $function_to_add, $priority = 10, $accepted_args = 1){
		return add_filter( (string)$tag, array($class, $function_to_add), (int)$priority, (int)$accepted_args );
	}
	
	/* Hook for add_action() */
	protected function add_shortcode($tag, $class, $function_to_add){
		if(shortcode_exists($tag)) return; // - stop if is already defined
		return add_shortcode( (string)$tag, array($class, $function_to_add) );
	}
	
	/* WP HEAD Hooks */
	public function head_hook(){ ?>
		<script type="text/javascript" >
		/* <![CDATA[ */
			var WP_CF_SDA = {
				label : {
					filter_title : "<?php echo __('Filter %s',WP_CF_SAB); ?>"
				},
				host : window.location.hostname,
				protocol : window.location.protocol.replace(/\:/g,'')
			};
		/* ]]> */
		</script>
		<?php
	}
	
	/* Hook for JavaScripts */
	public function enqueue_scripts() {
		if(is_admin()===true)
		{
			$screen = get_current_screen();
			if($this->get('post_type','string')=='address-book' || $screen->post_type == 'address-book')
			{
				wp_enqueue_script(
					'debounce',
					plugin_dir_url( __FILE__ ) . 'assets/js/debounce.js',
					array( 'jquery' ),
					WP_CF_SAB_VERSION,
					true
				);
				wp_enqueue_script(
					WP_CF_SAB.'-category-filter',
					plugin_dir_url( __FILE__ ) . 'assets/js/category-filter.js',
					array( 'jquery',  'debounce'),
					WP_CF_SAB_VERSION,
					true
				);
			}
		}
		else
		{
			wp_enqueue_script(
				WP_CF_SAB,
				plugin_dir_url( __FILE__ ) . 'assets/js/sports-address-book.js',
				array( 'jquery' ),
				WP_CF_SAB_VERSION,
				true
			);
		}
	}
	
	/* Hook for CSS */
	public function enqueue_styles() {
		if(is_admin()===true)
		{
			wp_enqueue_style(
				WP_CF_SAB,
				plugin_dir_url( __FILE__ ) . 'assets/css/sports-address-book-admin.css',
				array(),
				WP_CF_SAB_VERSION,
				'all'
			);
		}
		else
		{
			wp_enqueue_style(
				WP_CF_SAB,
				plugin_dir_url( __FILE__ ) . 'assets/css/sports-address-book.css',
				array(),
				WP_CF_SAB_VERSION,
				'all'
			);
		}
	}
	
	/* Get Metabox Value */
	private function get_meta($name, $id=false, $single=true){
		global $post_type, $post;
		
		$name=trim($name);
		$prefix=WP_CF_SAB_METABOX;
		$data=NULL;
	
		if($id!==false && !empty($id) && $id > 0)
			$getMeta=get_post_meta((int)$id, $prefix.$name, $single);
		else if(NULL!==get_the_id() && false!==get_the_id())
			$getMeta=get_post_meta(get_the_id(),$prefix.$name, $single);
		else if(isset($post->ID) && $post->ID > 0)
			$getMeta=get_post_meta($post->ID,$prefix.$name, $single);
		else if('page' == get_option( 'show_on_front' ))
			$getMeta=get_post_meta(get_option( 'page_for_posts' ),$prefix.$name, $single);
		else if(is_home() || is_front_page() || get_queried_object_id() > 0)
			$getMeta=get_post_meta(get_queried_object_id(),$prefix.$name, $single);
		else
			$getMeta=false;
		
		return (!empty($getMeta)?$getMeta:NULL);
	}
	
	/* Setup Metabox */
	public function metaboxes( array $meta_boxes ) {

		// Start with an underscore to hide fields from custom fields list
		$prefix = WP_CF_SAB_METABOX;

		/**
		 * Sample metabox to demonstrate each field type included
		 */
		$meta_boxes[WP_CF_SAB_METABOX.'metabox'] = array(
			'id'         => 'club_metabox',
			'title'      => __( 'Sport Institution Informations & Details',WP_CF_SAB ),
			'pages'      => array( 'address-book' ), // Post type
			'context'    => 'normal',
			'priority'   => 'high',
			'show_names' => true, // Show field names on the left
			// 'cmb_styles' => true, // Enqueue the CMB stylesheet on the frontend
			'fields'     => array(
				array(
					'name' => __( 'Address',WP_CF_SAB ),
					'desc' => __( 'Insert address of the sport institution',WP_CF_SAB ),
					'id'   => $prefix . 'address',
					'type' => 'text_medium',
					// 'repeatable' => true,
				),
				array(
					'name' => __( 'Contact Phone',WP_CF_SAB ),
					'desc' => __( 'Insert contact phone of the sport institution',WP_CF_SAB ),
					'id'   => $prefix . 'phone',
					'type' => 'text_medium',
					// 'repeatable' => true,
				),
				array(
					'name' => __( 'Email address',WP_CF_SAB ),
					'desc' => __( 'Insert email address of the sport institution',WP_CF_SAB ),
					'id'   => $prefix . 'email',
					'type' => 'text_email',
					// 'repeatable' => true,
				),
				array(
					'name' => __( 'URL',WP_CF_SAB ),
					'desc' => __( 'Insert website URL address of the sport institution',WP_CF_SAB ),
					'id'   => $prefix . 'url',
					'type' => 'text_url',
					// 'repeatable' => true,
				),
				array(
					'name'    => __( 'Description',WP_CF_SAB ),
					'desc'    => __( 'Full description of the sport institution',WP_CF_SAB ),
					'id'      => $prefix . 'content',
					'type'    => 'wysiwyg',
					'options' => array( 'textarea_rows' => 40, ),
				),
				array(
					'name'    => __( 'Search Keywords',WP_CF_SAB ),
					'desc'    => __( 'Search terms, meta keywords for advanced search. Please separate with comma.',WP_CF_SAB ),
					'id'      => $prefix . 'keyword',
					'type'    => 'textarea_small',
					'options' => array( 'textarea_rows' => 2, ),
				),
			),
		);
		return $meta_boxes;
	}
	
	/* Initialize Metabox */
	public function initialize_meta_boxes() {

		if ( ! class_exists( 'cmb_Meta_Box' ) )
			require_once plugin_dir_path( __FILE__  ) . 'include/metabox/init.php';
	}
	
	/* Action links */
	public function action_links( $actions, $plugin_file ) 
	{
		static $plugin;

		if (!isset($plugin))
			$plugin = plugin_basename(WP_CF_SAB_FILE);
		if ($plugin == $plugin_file)
		{
			$settings = array('settings' => '<a href="'.admin_url( 'edit.php?post_type=address-book').'" target="_self" rel="noopener noreferrer">'.__('Open Address Book',WP_CF_SAB).'</a>');
			$donate = array('donate' => '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=creativform@gmail.com" target="_blank" rel="noopener noreferrer">'.__('Donate',WP_CF_SAB).'</a>');

			$actions = array_merge($donate, $actions);
			$actions = array_merge($settings, $actions);
		}		
		return $actions;
	}
	
	/* Duplicate Posts */
	public function sab_duplicate_post_as_draft(){
		global $wpdb;
		if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'sab_duplicate_post_as_draft' == sanitize_text_field($_REQUEST['action']) ) ) ) {
			wp_die(__('No post to duplicate has been supplied!',WP_CF_SAB));
		}
	 
		/*
		 * get the original post id
		 */
		$post_id = (isset($_GET['post']) ? $this->get('post','int') : $this->post('post','int') );
		/*
		 * and all the original post data then
		 */
		$post = get_post( $post_id );
	 
		/*
		 * if you don't want current user to be the new post author,
		 * then change next couple of lines to this: $new_post_author = $post->post_author;
		 */
		$current_user = wp_get_current_user();
		$new_post_author = $current_user->ID;
	 
		/*
		 * if post data exists, create the post duplicate
		 */
		if (isset( $post ) && $post != null) {
	 
			/*
			 * new post data array
			 */
			$args = array(
				'comment_status' => $post->comment_status,
				'ping_status'    => $post->ping_status,
				'post_author'    => $new_post_author,
				'post_content'   => $post->post_content,
				'post_excerpt'   => $post->post_excerpt,
				'post_name'      => $post->post_name,
				'post_parent'    => $post->post_parent,
				'post_password'  => $post->post_password,
				'post_status'    => 'draft',
				'post_title'     => $post->post_title,
				'post_type'      => $post->post_type,
				'to_ping'        => $post->to_ping,
				'menu_order'     => $post->menu_order
			);
	 
			/*
			 * insert the post by wp_insert_post() function
			 */
			$new_post_id = wp_insert_post( $args );
	 
			/*
			 * get all current post terms ad set them to the new post draft
			 */
			$taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
			foreach ($taxonomies as $taxonomy) {
				$post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
				wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
			}
	 
			/*
			 * duplicate all post meta just in two SQL queries
			 */
			$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
			if (count($post_meta_infos)!=0) {
				$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
				foreach ($post_meta_infos as $meta_info) {
					$meta_key = $meta_info->meta_key;
					$meta_value = addslashes($meta_info->meta_value);
					$sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
				}
				$sql_query.= implode(" UNION ALL ", $sql_query_sel);
				$wpdb->query($sql_query);
			}
	 
	 
			/*
			 * finally, redirect to the edit post screen for the new draft
			 */
			wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
			exit;
		} else {
			wp_die(__('Post creation failed, could not find original post: ',WP_CF_SAB) . $post_id);
		}
	}
	
	public function sab_duplicate_post_link( $actions, $post ) {
		if (current_user_can('edit_posts')) {
			$actions['duplicate'] = '<a href="admin.php?action=sab_duplicate_post_as_draft&amp;post=' . $post->ID . '" title="'.__('Duplicate this item',WP_CF_SAB).'" rel="permalink">'.__('Duplicate',WP_CF_SAB).'</a>';
		}
		return $actions;
	}
}

if(class_exists("WP_address_book")){
	new WP_address_book;
}
