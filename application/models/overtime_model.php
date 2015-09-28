<?php
/*
 * This file is part of Jorani.
 *
 * Jorani is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jorani is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jorani.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @copyright  Copyright (c) 2014 - 2015 Benjamin BALET
 */

class Overtime_model extends CI_Model {

    /**
     * Default constructor
     */
    public function __construct() {
        
    }

    /**
     * Get the list of all overtime requests or one overtime request
     * @param int $id Id of the overtime request
     * @return array list of records
     * @author Benjamin BALET <benjamin.balet@gmail.com>
     */
    public function getExtras($id = 0) {
        $this->db->select('overtime.*');
        $this->db->select('status.name as status_name');
        $this->db->from('overtime');
        $this->db->join('status', 'overtime.status = status.id');
        if ($id === 0) {
            return $this->db->get()->result_array();
        }
        $this->db->where('overtime.id', $id);
        return $this->db->get()->row_array();
    }

    /**
     * Get the the list of overtime requested by a given employee
     * @param int $employee ID of the employee
     * @return array list of records
     * @author Benjamin BALET <benjamin.balet@gmail.com>
     */
    public function getExtrasOfEmployee($employee) {
        $this->db->select('overtime.*');
        $this->db->select('status.name as status_name');
        $this->db->from('overtime');
        $this->db->join('status', 'overtime.status = status.id');
        $this->db->where('overtime.employee', $employee);
        $this->db->order_by('overtime.id', 'desc');
        return $this->db->get()->result_array();
    }
    
    /**
     * Create an overtime request. Data are coming from an HTTP POSTed form
     * @return int id of the overtime request into the db
     * @author Benjamin BALET <benjamin.balet@gmail.com>
     */
    public function setExtra() {
        $data = array(
            'date' => $this->input->post('date'),
            'employee' => $this->session->userdata('id'),
            'duration' => $this->input->post('duration'),
            'cause' => $this->input->post('cause'),
            'status' => $this->input->post('status')
        );
        $this->db->insert('overtime', $data);
        return $this->db->insert_id();
    }

    /**
     * Update an overtime request in the database. Data are coming from an HTTP POSTed form
     * @param int $id overtime request identifier
     * @return bool result of update in database
     */
    public function updateExtra($id) {
        $data = array(
            'date' => $this->input->post('date'),
            'duration' => $this->input->post('duration'),
            'cause' => $this->input->post('cause'),
            'status' => $this->input->post('status')
        );
        $this->db->where('id', $id);
        $this->db->update('overtime', $data);
    }
    
    /**
     * Accept an overtime request
     * @param int $id overtime request identifier
     * @author Benjamin BALET <benjamin.balet@gmail.com>
     */
    public function acceptExtra($id) {
        $data = array(
            'status' => 3
        );
        $this->db->where('id', $id);
        return $this->db->update('overtime', $data);
    }

    /**
     * Reject an overtime request
     * @param int $id overtime request identifier
     * @return bool result of update in database
     * @author Benjamin BALET <benjamin.balet@gmail.com>
     */
    public function rejectExtra($id) {
        $data = array(
            'status' => 4
        );
        $this->db->where('id', $id);
        return $this->db->update('overtime', $data);
    }
    
    /**
     * Delete an overtime request from the database
     * @param int $id overtime request identifier
     * @return bool result of update in database
     * @author Benjamin BALET <benjamin.balet@gmail.com>
     */
    public function deleteExtra($id) {
        return $this->db->delete('overtime', array('id' => $id));
    }
    
    /**
     * Delete overtime rquests attached to a user (when it is deleted)
     * @param int $id identifier of an employee
     * @author Benjamin BALET <benjamin.balet@gmail.com>
     */
    public function deleteExtrasCascadeUser($id) {
        $this->db->delete('overtime', array('employee' => $id));
    }
        
    /**
     * List all overtime requests submitted to the connected user (or if delegate of a manager)
     * Can be filtered with "Requested" status.
     * @param int $user_id connected user
     * @param bool $all TRUE all requests, FALSE otherwise
     * @return array Recordset (can be empty if no requests or not a manager)
     */
    public function requests($user_id, $all = FALSE) {
        $this->load->model('delegations_model');
        $ids = $this->delegations_model->listManagersGivingDelegation($user_id);
        $this->db->select('overtime.id as id, users.*, overtime.*');
        $this->db->select('status.name as status_name');
        $this->db->join('status', 'overtime.status = status.id');
        $this->db->join('users', 'users.id = overtime.employee');
        if (count($ids) > 0) {
            array_push($ids, $user_id);
            $this->db->where_in('users.manager', $ids);
        } else {
            $this->db->where('users.manager', $user_id);
        }
        if ($all == FALSE) {
            $this->db->where('status', 2);
        }
        $this->db->order_by('date', 'desc');
        $query = $this->db->get('overtime');
        return $query->result_array();
    }
    
    /**
     * Purge the table by deleting the records prior $toDate
     * @param date $toDate 
     * @return int number of affected rows
     * @author Benjamin BALET <benjamin.balet@gmail.com>
     */
    public function purgeOvertime($toDate) {
        $this->db->where(' <= ', $toDate);
        return $this->db->delete('overtime');
    }

    /**
     * Count the number of rows into the table
     * @return int number of rows
     * @author Benjamin BALET <benjamin.balet@gmail.com>
     */
    public function count() {
        $this->db->select('count(*) as number', FALSE);
        $this->db->from('overtime');
        $result = $this->db->get();
        return $result->row()->number;
    }
}
