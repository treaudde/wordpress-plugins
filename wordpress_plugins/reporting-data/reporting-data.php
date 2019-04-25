<?php
/**
 * Plugin Name: Reporting Data
 * Plugin URI:
 * Description: This plugin holds the logic for the aggregate reporting data displayed through the site
 * Version: 1.0.0
 * Author: Ralph Harris
 * Author URI: http://ralphharris3.com
 * License: GPL2
 */


//TODO add install / uninstall functions

class ReportingData {

    /**
     * @var
     */
    protected $ReportingDataView;

    protected $dbConnection;

    public function __construct()
    {
        global $wpdb;
        $this->dbConnection = $wpdb;

        //include the classes needed
        $this->includeClasses();


        //include the styles and scripts
        $this->includeAssets();

        //create the reporting object
        $this->ReportingDataView = new ReportingDataView($this->dbConnection);

        //initialize cron
        $this->initializeCron();

        //initialize shortcode
        $this->initializeShortCode();
    }

    protected function includeClasses(){

        require_once(__DIR__ . '/classes/ReportingDataView.php');
    }


    protected function includeAssets()
    {
        wp_enqueue_style('crd_datatables_style', 'https://cdn.datatables.net/v/dt/dt-1.10.18/datatables.min.css');
        wp_enqueue_script('crd_datatables_script', 'https://cdn.datatables.net/v/dt/dt-1.10.18/datatables.min.js');
    }


    /*
     * Get  access to the reporting view
     */
    public function getReportingDataViewObject()
    {
        return $this->ReportingDataView;
    }

    protected function initializeCron()
    {
        add_action('reporting_data_refresh', [$this, 'refreshReportingDataView']);
    }


    public function refreshReportingDataView()
    {
        //delete the currentView
        $this->ReportingDataView->deleteView();

        //get the users and refresh the data
        $sql = <<<SQL
SELECT * FROM `wp_users`
WHERE ID IN
(
   SELECT user_id FROM `wp_usermeta`
   WHERE (meta_key = 'wp_capabilities' AND meta_value LIKE '%franchise%'
   OR meta_key = 'wp_capabilities' AND meta_value LIKE '%area_rep%'
   OR meta_key = 'wp_capabilities' AND meta_value LIKE '%salesperson%')
)

AND ID IN
(
   SELECT user_id FROM `wp_usermeta`
   WHERE meta_key = 'user_meta_user_status' AND meta_value = 'active'
)
SQL;

        $users = $this->dbConnection->get_results($sql);

        //TODO add error handling

        foreach($users as $user) {
            $this->ReportingDataView->setPortalId($user->ID);
            $result = $this->ReportingDataView->insertViewRow();

            if(!$result) {
                return false;
            }
        }

        return true;
    }


    protected function initializeShortCode()
    {
        add_shortcode( 'reporting-data-grid', [$this, 'reportingDataGridShortcode']);
    }


    public function reportingDataGridShortcode()
    {
        $gridData = $this->ReportingDataView->getAllData();

        $gridColumns = [
            ['title' => 'Portal ID'],
            ['title' => 'First Name'],
            ['title' => 'Last Name'],
            ['title' => 'Country'],

            [
                'title' => 'Yearly Revenue Goal',


            ],
            ['title' => 'Activity Reporting %', 'className' => 'reporting-center'],
            ['title' => 'EO Retention', 'className' => 'reporting-center'],

            ['title' => 'EO to LSW', 'className' => 'reporting-center'],
            ['title' => 'LSW Retention', 'className' => 'reporting-center'],
            ['title' => 'LSW to Sale', 'className' => 'reporting-center'],
        ];

        return include_once('views/reporting-view-datatable.php');
    }

}

$reportingData = new ReportingData();