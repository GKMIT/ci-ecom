
<?php

use Restserver\Libraries\REST_Controller;

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

class Product_reviews extends REST_Controller {

    private $data = [];
    private $error = [];
    private $filter = [];
    private $validations = [];
    private $datetime_format = 'Y-d-m h:i:s';

    public function __construct() {
        parent::__construct();
        $this->load->model('product/product_reviews_model');
        $this->load->library('form_validation');

        $this->form_validation->set_error_delimiters('', '');
    }

    public function index_post() {
        $this->data = [];
        $this->data['data'] = [];
        $this->data['status'] = true;

        $list = $this->product_reviews_model->getTables();

        $result = [];
        if ($list):
            foreach ($list as $object) :
                $result[] = [
                    'id' => $object['id'],
                    'product_id' => $object['product_id'],
                    'customer_id' => $object['customer_id'],
                    'rating_id' => $object['rating_id'],
                    'name' => $object['name'],
                    'text' => $object['text'],
                    'status' => $object['status'],
                    'status_text' => $object['status'] ? $this->lang->line('text_enable') : $this->lang->line('text_disable'),
                    'created_at' => date($this->datetime_format, strtotime($object['created_at'])),
                    'updated_at' => date($this->datetime_format, strtotime($object['updated_at'])),
                ];
            endforeach;
        else:
            $this->data['status'] = false;
        endif;


        $this->data['recordsTotal'] = $this->product_reviews_model->countAll();
        $this->data['recordsFiltered'] = $this->product_reviews_model->countFiltered();
        $this->data['data'] = $result;
        $this->data['message'] = $this->lang->line('text_loading');

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function delete_get($id) {
        $this->data = [];
        $this->data['data'] = [];
        $this->data['status'] = true;

        $object = $this->product_reviews_model->deleteById($id);

        $result = [];
        if ($object):
            $this->data['status'] = true;
            $this->data['message'] = sprintf($this->lang->line('success_delete'), $this->lang->line('text_country'));
            $result = $object;
        else:
            $this->data['status'] = false;
            $this->data['error'] = sprintf($this->lang->line('error_delete'), $this->lang->line('text_country'));
        endif;

        $this->data['data'] = $result;

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function deleteAll_post() {
        $this->data = [];
        $this->data['data'] = [];
        $this->data['status'] = true;

        $list = json2arr($this->post('list'));

        $result = [];
        if ($list):
            foreach ($list as $id) :
                $object = $this->product_reviews_model->deleteById($id);
            endforeach;
            $this->data['status'] = true;
            $this->data['message'] = sprintf($this->lang->line('success_delete'), $this->lang->line('text_country'));
        else:
            $this->data['status'] = false;
            $this->data['error'] = sprintf($this->lang->line('error_delete'), $this->lang->line('text_country'));
        endif;

        $this->data['data'] = $result;

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function detail_post() {
        $this->data = [];
        $this->data['data'] = [];


        $id = $this->post('id');

        $object = $this->product_reviews_model->getById($id);

        $result = [];
        if ($object):
            $result[] = [
                'id' => $object['id'],
                'product_id' => $object['product_id'],
                'customer_id' => $object['customer_id'],
                'rating_id' => $object['rating_id'],
                'name' => $object['name'],
                'text' => $object['text'],
                'status' => $object['status'],
                'status_text' => $object['status'] ? $this->lang->line('text_enable') : $this->lang->line('text_disable'),
                'created_at' => date($this->datetime_format, strtotime($object['created_at'])),
                'updated_at' => date($this->datetime_format, strtotime($object['updated_at'])),
            ];
            $this->data['status'] = true;
            $this->data['message'] = $this->lang->line('text_loading');
        else:
            $this->data['status'] = false;
            $this->data['error'] = sprintf($this->lang->line('error_not_found'), $this->lang->line('text_country'));
        endif;

        $this->data['data'] = $result;

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function save_post() {
        $this->validation();

        $this->data = [];
        $this->data['data'] = [];

        $object = $this->product_reviews_model->save();

        $result = [];
        if ($object):
            $this->data['status'] = true;
            $this->data['message'] = sprintf($this->lang->line('success_save'), $this->lang->line('text_country'));
            $result = $object;
        else:
            $this->data['status'] = false;
            $this->data['error'] = sprintf($this->lang->line('error_save'), $this->lang->line('text_country'));
        endif;

        $this->data['data'] = $result;

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function validation() {
        $this->validations = array(
            'product_id' => 'required',
            'rating_id' => 'required',
            'name' => 'required',
        );
        $this->_validation();
    }

    private function _validation() {
        $this->data = [];
        foreach ($this->validations as $key => $validation) :
            $field = '';
            if ($this->lang->line('text_' . $key)):
                $field = $this->lang->line('text_' . $key);
            else:
                $field = humanize($key);
            endif;
            $this->form_validation->set_rules($key, $field, $validation);
        endforeach;

        if ($this->form_validation->run() == FALSE):
            foreach ($this->validations as $key => $validation) :
                if (form_error($key, '', '')):
                    $this->error[] = array(
                        'id' => $key,
                        'text' => form_error($key, '', '')
                    );
                endif;
            endforeach;

            $this->data['status'] = FALSE;
            $this->data['message'] = $this->lang->line('error_validation');
            $this->data['result'] = $this->error;
            echo json_encode($this->data);
            exit;
        endif;
    }

}