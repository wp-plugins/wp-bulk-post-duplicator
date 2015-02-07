<?php
/**
 * Plugin Name: WP Bulk Post Duplicator.
 * Plugin URI: http://www.webhocks.com
 * Description: Duplicate all your post, page and custom post types.Duplicate based on year of post created.
 * Version: 1.0
 * Author: Rajesh Kumar
 * Author URI: http://www.webhocks.com
 * License: GPL2
 */
 
 
function load_manage_allowed_psd_form_js() {
  wp_enqueue_script('jquery','1.4.2');
}

class WPPostsDuplicator {
  var $wp_selected_rar_roles;
  var $wp_rar_role_label;
  var $all_post_types; 
	  function WPPostsDuplicator() {	 
		$this->all_post_types = get_post_types( array('public'=> true), 'names' ); 
	  }
	  
	  function available_post_types() {	
		global $wpdb;
		?>
		<script type="text/javascript">
		<!--
		  jQuery(function(){
			jQuery(".wp_psd_posts_cb").click(function(){
			  if(jQuery(this).is(":checked")) {
				jQuery(this).parents("label.chooseable:first").addClass("role_selected");
			  } else {
				jQuery(this).parents("label.chooseable:first").removeClass("role_selected");
			  }
			})
			
			
			jQuery("input.button-primary").click(function(){
			   var pt_checked = [];
			   var ps_checked = [];
			   
				jQuery("input[name='wp_psd_types[]']:checked").each(function (){
					pt_checked.push(parseInt(jQuery(this).val()));
				});
					
				if (pt_checked.length === 0) {
						jQuery('div#error_message').html("Please select post type");
						return false;
				} else {
					jQuery('div#error_message').html("");
				}
				
				jQuery("input[name='wp_psd_status[]']:checked").each(function (){
					ps_checked.push(parseInt(jQuery(this).val()));
				});
				
				if (ps_checked.length === 0) {
						jQuery('div#sts_error_message').html("Please select Post Status");
						return false;
				} else {
					jQuery('div#sts_error_message').html("");
				}
				
				if(jQuery("input[name='psd_year']").val() != ""){
					var dt_psd=jQuery("input[name='psd_year']").val();
					var dt_psd_lg=jQuery("input[name='psd_year']").val().length;
					if(dt_psd_lg < 4){						
						jQuery('div#dt_error_message').html("Please enter valid date format");
						return false;
					}
					else if(!jQuery.isNumeric(dt_psd)){
						jQuery('div#dt_error_message').html("Please enter valid date format");
						return false;
					}
					else {
						return true;
					}
				}
			})
		  });
		-->
		</script>
		<style type="text/css">
		<!--
		.choose_psd {
		  width: 250px;
		  height: 150px;
		  overflow: auto;
		}
		.choose_psd_wrap {
		  background: #FFF;
		  border: 1px solid #CCC;
		  padding: 5px;
		  width: 250px;
		  -moz-border-radius: 5px;
		  -webkit-border-radius: 5px;
		  border-radius: 5px;
		}
		.choose_psd_wrap label.chooseable {
		  display: block;
		  padding: 3px;
		  margin: 1px;
		}
		.choose_psd_wrap label.role_selected {
		  background-color: #D0E8FA;
		  -moz-border-radius: 3px;
		  -webkit-border-radius: 3px;
		  border-radius: 3px;
		}
		.default_role_marker {
		  font-size: 8px;
		  font-weight: bold;
		  color: #254156;
		  vertical-align: middle;
		}
		-->
		</style>
		<?php
		
		if($_POST['wp_psd_types']) { ?>
			<div class="wrap">
			<h2>Duplicate Posts</h2>
			</div>
		<?php				
				$i_dupli=0;
				if($_POST['psd_year'] != ""){
				$psd_year=$_POST['psd_year'];
				} else{
				$psd_year=':';
				}
				
				if($_POST['wp_psd_db_status']!= ""){
					$chn_status=$_POST['wp_psd_db_status'];
				} else{
					$chn_status='draft';
				}
				
				foreach ( $_POST['wp_psd_types'] as $cus_psd ) : 
				
					$psd_args = array( 
					'post_type' => $cus_psd,
					'orderby' => 'post_date',
					'order' => 'DESC',
					'post_status'  => $_POST['wp_psd_status'],
					'posts_per_page'   => 9999);
					
					$psd_posts = get_posts( $psd_args );
					foreach ( $psd_posts as $post ) : 
					
					$new_post_author = $post->post_author;
					
					if (strpos($post->post_date,$psd_year) !== false) {					
					
						$p_n=$post->post_name;
						preg_match('/-v[\d]+/', $p_n, $matches);
						
						if($matches[0] != ""){
							preg_match('/[\d]+/', $matches[0], $rev_num );		
							$rev_n=$rev_num[0]+1;
							
							$p_nm = str_replace($matches[0], "", $p_n);
							$post_name = $p_nm.'-v'.$rev_n;
						}
						else{
							$post_name=$p_n.'-v1';
						}
						
						if($chn_status	== "nochange"){
							$pt_status = $post->post_status;
						} else {
							$pt_status = $chn_status;
						}
						
						$args = array(
							'comment_status' => $post->comment_status,
							'ping_status'    => $post->ping_status,
							'post_author'    => $new_post_author,
							'post_content'   => $post->post_content,
							'post_excerpt'   => $post->post_excerpt,
							'post_name'      => $post_name,
							'post_parent'    => $post->post_parent,
							'post_password'  => $post->post_password,
							'post_status'    => $pt_status,
							'post_title'     => $post->post_title,
							'post_type'      => $post->post_type,
							'to_ping'        => $post->to_ping,
							'menu_order'     => $post->menu_order
						);
				 
					
						$new_post_id = wp_insert_post( $args );
				 
						
						$taxonomies = get_object_taxonomies($post->post_type);
						foreach ($taxonomies as $taxonomy) {
							$post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
							wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
						}
				 
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
						
						$i_dupli++;
						
					}			
					endforeach;					
					
				endforeach;
				if($i_dupli == 0){
					echo "<p>No posts Duplicated.please try again</p>";
				}
				else{
				echo "<p>Congrats!! <b>".$i_dupli."</b> Posts duplicated Succesfully</p>";
				}
		}
		else {
		?>		
		  <div class="wrap">
			<h2>Duplicate Posts</h2>
			<p>This plugin will duplicate all post types selected below with status:draft by default</p>
			<form method="post" action="" class="form-table">
			  <table>
				<tr valign="top">
				  <th>
					Choose Post Type
				  </th>
				  <td>
					<div class="choose_psd_wrap">
					<div class="choose_psd">
					<?php foreach($this->all_post_types as $a_post_type): 
					if($a_post_type != "attachment") {
					?>
					  <div>                  
						<label for="wp-rar-roles-<?php echo $a_post_type; ?>" class="chooseable">
						<input type="checkbox" value="<?php echo $a_post_type; ?>" name="wp_psd_types[]" class="wp_psd_posts_cb"/> <?php echo $a_post_type; ?>               
						</label>
					  </div>
					<?php 
					}
					endforeach; ?>
					</div>
				
					</div>					
				  </td>			
				  <td><div id="error_message" style="color:red"></div></td>				  
				</tr>				
				<tr valign="top">
				  <th>
					Choose Post Status
				  </th>
				  <td>
					<div class="choose_psd_wrap">
						<div class="choose_psd">					
						  <div>                  
							<label class="chooseable">
							<input type="checkbox" value="publish" name="wp_psd_status[]" class="wp_psd_status_cb"/> Published              
							</label>
							<label class="chooseable">
							<input type="checkbox" value="draft" name="wp_psd_status[]" class="wp_psd_status_cb"/> Draft             
							</label>
							<label class="chooseable">
							<input type="checkbox" value="pending" name="wp_psd_status[]" class="wp_psd_status_cb"/> Pending Review                 
							</label>
						  </div>
						
						</div>
					</div>					
				  </td>			
				  <td><div id="sts_error_message" style="color:red"></div></td>				  
				</tr>
				<tr valign="middle">
				  <th>
					Year of Posts Created(optional)
					<p style="font-size: 13px;font-weight:normal">(ex:2014 will duplicate only posts from the year 2014)</p>
				  </th>
				  <td valign="middle">      
						<label>
						<input type="text" value="" name="psd_year"/>               
						</label>	
				  </td>	
				  <td><div id="dt_error_message" style="color:red"></div></td>		
				</tr> 
				<tr valign="top"> 
				  <th>
					Choose Post Duplicate Status(optional)<p style="font-size: 13px;font-weight:normal">(Default will be in draft)</p>
				  </th>
				  <td>
					<div class="choose_psd_wrap">
						<div class="choose_psd">					
						  <div>                  
							<label class="chooseable">
							<input type="radio" value="publish" name="wp_psd_db_status" class="wp_psd_status_db"/> Published              
							</label>
							<label class="chooseable">
							<input type="radio" value="draft" name="wp_psd_db_status" class="wp_psd_status_db"/> Draft             
							</label>
							<label class="chooseable">
							<input type="radio" value="pending" name="wp_psd_db_status" class="wp_psd_status_db"/> Pending Review                 
							</label>							
							<label class="chooseable">
							<input type="radio" value="nochange" name="wp_psd_db_status" class="wp_psd_status_cb"/> No Change                 
							</label>
						  </div>						
						</div>
					</div>					
				  </td>			
				  <td><div id="psd_sts__error_message" style="color:red"></div></td>				  
				</tr>				
			  </table>
			  <p class="submit">
				<input type="submit" value="Duplicate" class="button-primary" name="Submit" />
			  </p>
			</form>
		  </div>
		<?php
	  }
	}
}

function wp_psd_admin_menu() {
  $wp_psd_plugin = new WPPostsDuplicator;
  add_options_page('WP Bulk Posts Duplicator', 'WP Bulk Posts Duplicator', 9, 'wp-blk-posts-duplicator', array($wp_psd_plugin, 'available_post_types'));
}

function init_psd() {
  $wp_psd_plugin = new WPPostsDuplicator;
}

add_action('init', 'init_psd');
add_action('admin_menu', 'wp_psd_admin_menu');
add_action('admin_print_scripts-settings_page_wp-posts-duplicator', 'load_manage_allowed_psd_form_js');
?>