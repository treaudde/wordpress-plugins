<?php
require_once(WP_PLUGIN_DIR.'/woocommerce-filepreviews-io/vendor/filepreviews/filepreviews/src/FilePreviews/FilePreviews.php');
require_once(WP_PLUGIN_DIR.'/woocommerce-filepreviews-io/vendor/filepreviews/filepreviews/src/FilePreviews/FilePreviewsClient.php');
/**
 * FilePreviewsIO
 *
 * This is a class to submit to and access file from Filepreviews.io
 * This is run when a product is saved in WooCommerce
 */
class FilePreviewsIO
{
    /**
     *
     * @var string
     */
    protected $apiKey;
    
    /**
     *
     * @var string
     */
    protected $apiSecret;

    
    /**
     *
     * @var FilePreviewsIO
     */
    protected $filePreviewIOClient;
    
    /**
     *
     * @var string
     */
    protected $productSecurityString;
    
    /**
     * Construct the class the key and secret
     * 
     * @param type $apiKey
     * @param type $apiSecret
     */
    public function __construct($apiKey, $apiSecret) 
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        
        $this->filePreviewIOClient = new FilePreviews\FilePreviews([
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret
        ]);
        $this->productSecurityString = md5(base64_encode('product_'.$apiKey.'_'.date('Y-m')));
    }
    
    /**
     * This is the hook that is run when a product is saved in WP admin interface
     *
     * @param type $postID
     */
    public function productSaveHook($postID) 
    {
        $postMeta = get_post_meta($postID);
        $post = get_post($postID);
        
        //unserialize the downloadble files meta
        $downloadbleFiles = unserialize($postMeta['_downloadable_files'][0]);
        $fileUrl = array_pop($downloadbleFiles)['file'];
        
        $filePreviewsDataField = get_field('document_preview_link');
        //only run this if post type = product and it hasn't already run
        if($post->post_type == 'product' && empty($filePreviewsDataField)) {
            $postData = $this->constructPostData($post, $postMeta);
            $response = $this->sendRequest($fileUrl, $postData);
            
            //save response
            $this->saveProductPreviewMeta($response, $post->ID);
        }
    }

    /**
     * Construct the post request to be sent over to the webservice
     *
     * @param $post
     * @param $postMeta
     *
     * @return array
     */
    private function constructPostData($post, $postMeta)
    {
        //TODO find better way to do this
        return array(
            'sizes' => ['1200x900'],
            'format' => 'png',
            'data' => [
                'product_id' => $post->ID,
                'product_security_string' => $this->productSecurityString
            ],
            'pages' => '1-2',
            'metadata' => ['ocr']
        );
    }

    /**
     * Sends the request to filepreviews.io
     * need to work on better exception handling that is wordpress compatible
     *
     * @param $documentUrl
     * @param $generationData
     * @return mixed
     */
    private function sendRequest($documentUrl, $generationData)
    {
        try{
            return $this->filePreviewIOClient->generate($documentUrl, $generationData);
        } catch (Exception $ex) {
            var_dump($ex); // find better way to handle failure
        }
        
    }

    /**
     * Saves the meta value in wordpress
     *
     * @param $data
     * @param $postID
     */
    private function saveProductPreviewMeta($data, $postID)
    {
        //update fields
        update_field('document_preview_link', json_encode($data), $postID);
    }
}
