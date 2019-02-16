<?php

class Categories_model extends CI_Model {

    private $table = 'categories';
    private $table_view = 'categories';
    private $column_order = array(null, 'name', 'updated_at', null);
    private $column_search = array('name', 'updated_at');
    private $order = array('updated_at' => 'desc');
    private $currectDatetime = '';

    public function __construct() {
        parent::__construct();
        $this->currectDatetime = date('Y-m-d h:i:s');
    }

    private function _getTablesQuery() {
        $this->db->from($this->table_view);
        if ($this->input->post('name')):
            $this->db->where('name', $this->input->post('name'));
        endif;
        $status = 1;
        if ($this->input->post('status') && $this->input->post('status') == 'false'):
            $status = 0;
        endif;
        $this->db->where('status', $status);
        $i = 0;
        foreach ($this->column_search as $item) :
            if (isset($_POST['length'])) :
                if (isset($_POST['search']['value'])) :
                    if ($i === 0) :
                        $this->db->group_start();
                        $this->db->like($item, $_POST['search']['value']);
                    else :
                        $this->db->or_like($item, $_POST['search']['value']);
                    endif;
                    if (count($this->column_search) - 1 == $i):
                        $this->db->group_end();
                    endif;
                endif;
            endif;
            $i++;
        endforeach;
        if (isset($_POST['order'])) :
            $this->db->order_by($this->column_order[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
        elseif (isset($this->order)) :
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        endif;
    }

    public function getTables() {
        $this->_getTablesQuery();
        if ($this->input->post('length')) :
            if ($this->input->post('length') != -1):
                if ($this->input->post('start')):
                    $start = $this->input->post('start');
                else:
                    $start = 0;
                endif;
                $this->db->limit($this->input->post('length'), $start);
            endif;
        endif;
        $query = $this->db->get();
//        print_r($this->db->last_query());
//        exit;
        return $query->result_array();
    }

    public function countFiltered() {
        $this->_getTablesQuery();
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function countAll() {
        $this->db->from($this->table_view);
        return $this->db->count_all_results();
    }

    public function getById($id) {
        $this->db->from($this->table_view);
        $this->db->where('id', $id);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function getByType($id) {
        $this->db->from($this->table_view);
        $this->db->where('type_id', $id);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function deleteById($id) {
        $this->db->trans_start();
        $this->db->where('id', $id);
        $this->db->delete($this->table);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) :
            $this->db->trans_rollback();
            return FALSE;
        else :
            $this->db->trans_commit();
            return TRUE;
        endif;
    }

    public function save() {
        $this->db->trans_start();

        $this->db->set('type_id', $this->input->post('type_id'));
//        $this->db->set('parent_id', $this->input->post('parent_id'));
        $this->db->set('name', $this->input->post('name'));
//        $this->db->set('image', $this->input->post('image'));
        $this->db->set('sort_order', $this->input->post('sort_order'));
        $this->db->set('status', $this->input->post('status'));

        if ($this->input->post('id')):
            $this->db->set('updated_at', $this->currectDatetime);
            $id = $this->input->post('id');
            $this->db->where('id', $id);
            $this->db->update($this->table);
        else:
            $this->db->set('created_at', $this->currectDatetime);
            $this->db->insert($this->table);
            $id = $this->db->insert_id();
        endif;

        if ($this->db->trans_status() === FALSE) :
            $this->db->trans_rollback();
            return FALSE;
        else :
            $this->db->trans_commit();
            return TRUE;
        endif;
    }

}
