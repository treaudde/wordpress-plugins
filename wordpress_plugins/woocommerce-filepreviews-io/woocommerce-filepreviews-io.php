<?php
//require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/src/FilePreviewsIOSettings.php';
require_once __DIR__.'/src/FilePreviewsIO.php';
/**
 * Plugin Name: Woocommerce Filepreviews.io
 * Description: Hooks into the postmeta save for woocommerce products to generate filepreviews
 * Version: 1.0
 * Author: Ralph Harris
 * Author URI: http://shompton.com
 */

class WoocommerceFilePreviewsIO 
{
    /**
     *
     * @var FilePreviewsIOSettings
     */
    protected $settings;
    
    /**
     *
     * @var FilePreviewsIO
     */
    protected $filePreviewsIO;
    
    /**
     * Initialize the plugin
     */
    public function init()
    {
        //create settings page and resources
        $this->registerSettings();
        $this->registerWebHook();
        $this->registerSaveHook();
    }
    
    /**
     * registers the settings
     */
    private function registerSettings()
    {
        $this->settings = new FilePreviewsIOSettings();   
    }
    
    /**
     * Registers the webhook to be called
     */
    private function registerWebHook()
    {
        $apiKey = get_option('filepreviews_io_api_key');
        $apiSecret = get_option('filepreviews_io_api_secret');
        
        $this->filePreviewsIO = new FilePreviewsIO($apiKey, $apiSecret);
        
        add_action( 'rest_api_init', function () {
                register_rest_route( 
                    'woocommerce-filepreviews-io/v1', 
                    '/receive-data', 
                    array(
                        'methods' => 'POST',
                        'callback' => [$this->filePreviewsIO, 
                            'filepreviewsIOWebhook'],
                    ) 
                );
            } 
        );//end add action
    }

    /**
     *  Registers the webhook to be called
     */
    private function registerSaveHook()
    {
        add_action(
            'save_post', 
            [$this->filePreviewsIO, 'productSaveHook']
        );
    }
}

// Create an instance of our class to kick off the whole thing
$wooCommerceFilePreviewsIO = new WoocommerceFilePreviewsIO();
$wooCommerceFilePreviewsIO->init();