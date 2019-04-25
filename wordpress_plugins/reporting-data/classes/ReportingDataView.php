<?php
//include wp_head


class ReportingDataView {

    protected $databaseFields = [];

    protected $databaseFieldsFormat = [];

    protected $dbConnection;

    protected $tableName;

    protected $fiscalYear;

    protected $portalId;

    protected $marketingData;

    protected $dateInformation;

    public function __construct($dbConnectionObject)
    {
        $this->dbConnection = $dbConnectionObject;
        $this->tableName = $this->dbConnection->prefix . 'crestcom_aggregated_reporting_data';

        //set up the marketing data to be used in the methods below
        $this->dateInformation = $this->getDateInformation();

        $this->databaseFields = [
            'portal_id' => null,
            'first_name' => null,
            'last_name' => null,
            'country' => null,
            'yearly_revenue_goal' => null,
            'ytd_revenue' => null,
            'ytd_plan_variance' => null,
            'mmrs_due' => null,
            'open_sales' => null,
            'past_due_open_sales',
            'activity_reporting_percentage' => null,
            'eo_retention' => null,
            'eo_to_lsw' => null,
            'lsw_retention' => null,
            'lsw_to_sales' => null
        ];

        $this->databaseFieldsFormat = [
            '%d',
            '%s',
            '%s',
            '%s',

            '%f',
            '%f',
            '%f',

            '%f',
            '%f',
            '%f',

            '%f',
            '%f',
            '%f',

            '%f',
            '%f',
        ];
    }

    public function deleteView()
    {
        //delete the table to reset
        $sql = "DELETE FROM {$this->tableName}";
        $this->dbConnection->query($sql);
    }

    public function setPortalId($portalId)
    {
         $this->portalId = $portalId;

         //get the marketing data
         $this->marketingData = $this->getMarketingData();

         return $this->portalId;
    }

//    public function setFiscalYear($fiscalYear)
//    {
//        $this->fiscalYear = $fiscalYear;
//        return $this->fiscalYear;
//    }

    public function insertViewRow()
    {
        $this->databaseFields = [
            'portal_id' => $this->portalId,
            'first_name' => $this->setFirstName(),
            'last_name' => $this->setLastName(),
            'country' => $this->setCountry(),
            'yearly_revenue_goal' => $this->setYearlyRevenueGoal(),
            'ytd_revenue' => $this->setYtdRevenue(),
            'ytd_plan_variance' => $this->setYtdPlanVariance(),
            'mmrs_due' => $this->setMmrsDue(),
            'open_sales' => $this->setOpenSales(),
            'past_due_open_sales' => $this->setPastDueOpenSales(),
            'activity_reporting_percentage' => $this->setActivityReportingPercentage(),
            'eo_retention' => $this->setEoRetention(),
            'eo_to_lsw' => $this->setEoToLsw(),
            'lsw_retention' => $this->setLswRetention(),
            'lsw_to_sales' => $this->setLswToSales()
        ];

        return $this->dbConnection->insert($this->tableName, $this->databaseFields, $this->databaseFieldsFormat);


    }

    /**
     * EACH METHOD REPRESENTS A DATABASE FIELD
     */

    protected function setFirstName()
    {
        $sql = "SELECT meta_value FROM wp_usermeta WHERE user_id = {$this->portalId} AND meta_key = 'first_name'";
        $result = $this->dbConnection->get_results($sql);

        if(count($result)) {
            return array_shift($result)->meta_value;
        }

        return '';
    }

    protected function setLastName()
    {
        $sql = "SELECT meta_value FROM wp_usermeta WHERE user_id = {$this->portalId} AND meta_key = 'last_name'";
        $result = $this->dbConnection->get_results($sql);

        if(count($result)) {
            return array_shift($result)->meta_value;
        }

        return '';
    }


    protected function setCountry()
    {
        $sql = "SELECT meta_value FROM wp_usermeta WHERE user_id = {$this->portalId} AND meta_key = 'default_country'";
        $result = $this->dbConnection->get_results($sql);

        if(count($result)) {
            return array_shift($result)->meta_value;
        }

        return '';
    }

    protected function setYearlyRevenueGoal()
    {
        $sql = "SELECT SUM(yearly_sales_goal_bpm) AS yearly_sales_goal_bpm,"
            . " SUM(yearly_sales_goal_cce) AS yearly_sales_goal_cce,"
            . " SUM(yearly_sales_goal_other) AS yearly_sales_goal_other"
            . " FROM wp_crestcom_revenue_goals"
            . " WHERE user_id = {$this->portalId}"
            . " AND plan_year = '{$this->dateInformation->fiscal_year}'";

        $result = $this->dbConnection->get_results($sql);

        if(count($result)) {
            $goalValues = array_shift($result);
            return array_sum(
                [
                    $goalValues->yearly_sales_goal_bpm,
                    $goalValues->yearly_sales_goal_cce,
                    $goalValues->yearly_sales_goal_other
                ]
            );

        }

        return 0;
    }

    protected function setYtdRevenue()
    {
        $sql = "SELECT SUM(us_dollars) AS total"
            . " FROM wp_crestcom_netsuite_customsearch906"
            . " WHERE user_id = {$this->portalId} ";

        $result = $this->dbConnection->get_results($sql);

        if(count($result)) {
            $ytd_revenue = array_shift($result)->total;
            return (!is_null($ytd_revenue)) ? $ytd_revenue : 0;
        }

        return 0;

    }

    protected function setYtdPlanVariance()
    {
        return 0;
    }

    protected function setMmrsDue()
    {
        return 0;
    }

    protected function setOpenSales()
    {
        return 0;
    }

    protected function setPastDueOpenSales()
    {
        return 0;
    }

    protected function setActivityReportingPercentage()
    {
        $number_weeks_reporting = $this->dbConnection->get_var("SELECT COUNT(*)"
            . " FROM wp_crestcom_activity_reports"
            . " WHERE user_id = {$this->portalId}"
            . " AND fiscal_year = '{$this->dateInformation->fiscal_year}'");

        return ($number_weeks_reporting / $this->dateInformation->week) * 100;
    }

    protected function setEoRetention()
    {
        if($this->marketingData === false) {
            return 0;
        }

        //EO Retention Rate
        return (!empty($this->marketingData->total_eo_added_to_calendar)) ?
            (($this->marketingData->total_eo_conducted / $this->marketingData->total_eo_added_to_calendar ) * 100) : 0;

    }

    protected function setEoToLsw()
    {
        if($this->marketingData === false) {
            return 0;
        }

        //EOs to LSW's added to calendar
        return (!empty($this->marketingData->total_eo_conducted)) ?
            (($this->marketingData->total_lsw_added_to_calendar / $this->marketingData->total_eo_conducted ) * 100) : 0;

    }

    protected function setLswRetention()
    {
        if($this->marketingData === false) {
            return 0;
        }

        return (!empty($this->marketingData->total_lsw_added_to_calendar)) ?
            (($this->marketingData->total_lsw_conducted / $this->marketingData->total_lsw_added_to_calendar ) * 100) : 0;

    }

    protected function setLswToSales()
    {

        if($this->marketingData === false) {
            return 0;
        }

        $actual_total_sales = $this->dbConnection->get_results( "SELECT SUM(id) AS total_number_sales, SUM(number_of_seats) AS total_number_of_seats,"
            . " SUM(calculated_exchange_rate) AS total_sales "
            . " FROM `wp_crestcom_netsuite_customsearch_737_741`"
            . " WHERE (salesperson = {$this->portalId} OR portal_id = {$this->portalId})"
            . " AND type = 'BPM'");

        if (count($actual_total_sales)) {
            $actual_total_sales_data = array_shift($actual_total_sales);

            return (!empty($this->marketingData->total_lsw_conducted))
                ? (($actual_total_sales_data->total_number_sales / $this->marketingData->total_lsw_conducted ) * 100) : 0;

        }

        //didn't work, return 0
        return 0;
    }


    private function getMarketingData(){
        $sql = "SELECT SUM(follow_up_calls) AS total_follow_up_calls,"
            . " SUM(eo_planned) AS total_eo_added_to_calendar,"
            . " SUM(follow_up_emails) AS total_follow_up_emails,"
            . " SUM(eo_conducted) AS total_eo_conducted,"
            . " SUM(lsw_booked) AS total_lsw_added_to_calendar,"
            . " SUM(lsw_conducted) AS total_lsw_conducted, "
            . " SUM(eo_rescheduled) AS total_eo_rescheduled"
            . " FROM wp_crestcom_activity_reports"
            . " WHERE user_id = {$this->portalId}"
            . " AND fiscal_year = '{$this->dateInformation->fiscal_year}'";

        $result = $this->dbConnection->get_results($sql);

        if(count($result)) {
            return array_shift($result);
        }

        return false;
    }

    private function getDateInformation()
    {
        $current_date = date('Y-m-d 00:00:00');
        $date_result = $this->dbConnection->get_results(
            "SELECT * FROM wp_crestcom_netsuite_dates WHERE date = '{$current_date}'"
        );

        if(count($date_result)){
            return array_shift($date_result);
        }

        //TODO find better way to gracefully terminate
        die('Date Information not set');

    }



    /**
     * DATA RETRIEVAL METHODS
     */

    public function getAllData(){
        $sql = "SELECT * FROM {$this->tableName}";

        $results = $this->dbConnection->get_results($sql);

        //format data
        $results_transformed = [];
        foreach($results as $result) {
            array_push($results_transformed, [
                $result->portal_id,
                $result->first_name,
                $result->last_name,
                $result->country,
                ceil($result->yearly_revenue_goal),
                ceil($result->activity_reporting_percentage),
                ceil($result->eo_retention),
                ceil($result->eo_to_lsw),
                ceil($result->lsw_retention),
                ceil($result->lsw_to_sales)
            ]);
        }

        return $results_transformed;
    }


    public function getDataByPortalId()
    {
        $sql = "SELECT * FROM {$this->tableName}"
            ." WHERE portal_id = {$this->portalId}";

        return $this->dbConnection->get_results($sql);
    }
}


