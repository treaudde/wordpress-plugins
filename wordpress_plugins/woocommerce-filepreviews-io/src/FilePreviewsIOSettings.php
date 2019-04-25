<?php
/**
 * Handles creation of the settings page in wordpress
 */
class FilePreviewsIOSettings 
{

    /**
     * FilePreviewsIOSettings constructor.
     */
    public function __construct(){
        
        if(is_admin()) {
            //add the menu page
            add_action('admin_menu', array($this, 'createMenu'));
            //call register settings function
            add_action('admin_init', array($this, 'registerSettings'));
        }
        
    }
    
    /**
     *  Creates the menu item
     */
    public function createMenu() {
	//create new top-level menu
	add_menu_page(
                'FilePreviews.io', 
                'FilePreviews.io', 
                'administrator', 
                __FILE__, 
                [$this, 'settingsPage'] , 
                plugins_url('/images/icon.png', __FILE__) 
        );
    }
    
    /**
     * Creates the settings items in the database
     */
    public function registerSettings (){
        register_setting( 'filepreviews-io', 'filepreviews_io_api_key' );
	    register_setting( 'filepreviews-io', 'filepreviews_io_api_secret' );

    }

    /**
     * Displays the settings page in the wordpress backend
     */
    public function settingsPage() {
        ?>
        <div class="wrap">
        <h1>FilePreviews.io Settings</h1>
        
        <p>TODO: show message on save</p>

        <form method="post" action="options.php">
            <?php settings_fields( 'filepreviews-io' ); ?>
            <?php do_settings_sections( 'filepreviews-io' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Key</th>
                    <td>
                        <input type="text" name="filepreviews_io_api_key" 
                               value="<?php echo esc_attr( get_option('filepreviews_io_api_key') ); ?>" size="50" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">API Secret</th>
                    <td>
                        <input type="text" name="filepreviews_io_api_secret" size="50"
                               value="<?php echo esc_attr( get_option('filepreviews_io_api_secret') ); ?>" />
                    </td>
                </tr>
                
            </table>

            <?php submit_button(); ?>
        </form>
        </div>
<?php
    }
    
}
