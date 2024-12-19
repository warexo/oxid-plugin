<?php

interface IConnector
{

    public function ping();

    public function get_products($page = false);

    public function add_product($data);

    public function remove_product($data);

    public function get_categories($page = false);

    public function set_categories($data);

    public function add_category($data);

    public function remove_non_existing_categories($data);

    public function remove_category($data);

    public function get_customers($page = false);

    public function add_customer($data);

    public function remove_customer($data);

    public function get_countries($page = false);

    public function add_country($data);

    public function remove_country($data);

    public function get_states($page = false);

    public function add_state($data);

    public function remove_state($data);

    public function get_vendors($page = false);

    public function add_vendor($data);

    public function remove_vendor($data);
}

?>