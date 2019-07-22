<?php

class Orders_model extends CI_Model
{

    private $table = 'orders';
    private $table_view = 'orders_view';
    private $column_search = array('name', 'email', 'contact', 'total', 'status', 'updated_at');
    private $currectDatetime = '';

    public function __construct()
    {
        parent::__construct();
        $this->currectDatetime = date('Y-m-d h:i:s');
        $this->query_lib->table = $this->table;
        $this->query_lib->table_view = $this->table_view;
        $this->query_lib->column_search = $this->column_search;
        $this->load->model('order/carts_model');
        $this->load->model('product/products_model');
        $this->load->model('customer/customer_addresses_model');
    }

    private function _getTablesQuery()
    {
        $this->db->from($this->table_view);
        $this->query_lib->where();
        $this->query_lib->like();
        $this->query_lib->getSearch();
        $this->query_lib->getSort();
    }

    public function getTables()
    {
        $this->_getTablesQuery();
        $this->query_lib->getPaginate();
        $query = $this->db->get();
        // print_r($this->db->last_query());
        // exit;
        return $query->result_array();
    }

    public function countFiltered()
    {
        $this->_getTablesQuery();
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function countAll()
    {
        $this->db->from($this->table_view);
        return $this->db->count_all_results();
    }

    public function getById($id)
    {
        $this->db->from($this->table_view);
        $this->db->where('id', $id);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function deleteById($id)
    {
        $this->db->trans_start();
        $this->db->where('id', $id);
        $this->db->delete($this->table);
        $this->db->trans_complete();
        if ($this->db->trans_status() === false) :
            $this->db->trans_rollback();
            return false;
        else :
            $this->db->trans_commit();
            return true;
        endif;
    }

    public function save()
    {
        $this->db->trans_start();

        $customer_id = $this->input->post('customer_id');

        if ($this->input->post('order_type_id')) {
            $this->db->set('order_type_id', $this->input->post('order_type_id'));
        } else {
            $this->db->set('order_type_id', 1);
        }
        if ($this->input->post('order_status_id')) {
            $this->db->set('order_status_id', $this->input->post('order_status_id'));
        } else {
            $this->db->set('order_status_id', 1);
        }
        $this->db->set('customer_id', $customer_id);
        $this->db->set('address_id', $this->input->post('address_id'));

        $address = $this->customer_addresses_model->getById($this->input->post('address_id'));

        $this->db->set('person_name', $address['name']);
        $this->db->set('person_contact', $address['contact']);
        $this->db->set('country_id', $address['country_id']);
        $this->db->set('zone_id', $address['zone_id']);
        $this->db->set('city_id', $address['city_id']);
        $this->db->set('postcode', $address['postcode']);
        $this->db->set('address', $address['address']);

        $this->db->set('comment', $this->input->post('comment'));

        if ($this->input->post('status')) :
            $this->db->set('status', $this->input->post('status'));
        else :
            $this->db->set('status', 1);
        endif;

        $filter['token'] = $this->input->post('token');
        $filter['customer_id'] = $customer_id;


        if ($this->input->post('id')) :
            $this->db->set('updated_at', $this->currectDatetime);
            $id = $this->input->post('id');
            $this->db->where('id', $id);
            $this->db->update($this->table);
        else :
            $this->db->set('created_at', $this->currectDatetime);
            $this->db->insert($this->table);
            $id = $this->db->insert_id();
        endif;

        $this->setProducts($id);
        $this->setTotals($id);


        if ($this->db->trans_status() === false) :
            $this->db->trans_rollback();
            return false;
        else :
            $this->db->trans_commit();
            $this->clearCart($this->input->post('token'));
            return true;
        endif;
    }



    public function getProducts($id)
    {
        $this->db->from('order_products_view');
        $this->db->where('order_id', $id);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function getTotals($id)
    {
        $this->db->from('order_totals');
        $this->db->where('order_id', $id);
        $this->db->order_by('sort_order', 'asc');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function setProducts($id)
    {
        $this->db->where('order_id', $id);
        $this->db->delete('order_products');

        $filter = [];
        $filter['token'] = $this->input->post('token');
        $filter['customer_id'] = $this->input->post('customer_id');
        $products = $this->carts_model->getProducts($filter);
        // print_r($products);
        // exit;
        if ($products) :
            foreach ($products as $value) :
                $subTotal = ($value['price'] * $value['quantity']);
                $totalTax = $this->products_model->getTotalTax($value['product_id'], $subTotal);
                $total = $subTotal + $totalTax;

                $this->db->set('order_id', $id);
                $this->db->set('product_id', $value['product_id']);
                $this->db->set('quantity', $value['quantity']);
                $this->db->set('price', $value['price']);
                $this->db->set('tax', $totalTax);
                $this->db->set('total', $total);

                $this->db->insert('order_products');
            endforeach;
        endif;
    }

    public function setTotals($id)
    {
        $this->db->where('order_id', $id);
        $this->db->delete('order_totals');

        $totals = [];
        $taxes = $this->carts_model->getTaxTotal();
        $total = 0;

        $total_data = [
            'totals' => &$totals,
            'taxes' => &$taxes,
            'total' => &$total
        ];

        $extensions = [
            'total_mrp',
            'total_price',
            'total_special_price',
            'sub_total',
            'total_tax',
            'coupon',
            'total',
        ];

        foreach ($extensions as $extension) :
            $this->load->model('order/total/' . $extension . '_model');
            $this->{$extension . '_model'}->getTotal($total_data);
        endforeach;

        foreach ($totals as $totalValue) :
            if ($totalValue['title'] == 'Total') :
                $total = $totalValue['value'];
            endif;

            $this->db->set('order_id', $id);
            $this->db->set('code', $totalValue['code']);
            $this->db->set('title', $totalValue['title']);
            $this->db->set('value', $totalValue['value']);
            $this->db->set('sort_order', $totalValue['sort_order']);
            $this->db->insert('order_totals');
        endforeach;

        $this->db->set('total', $total);
        $this->db->where('id', $id);
        $this->db->update('orders');
    }


    public function clearCart($token)
    {
        $this->db->where('token', $token);
        $this->db->delete('carts');
    }
}
