<?php
/*
	This file is part of Classroombookings.

	Classroombookings is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	Classroombookings is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with Classroombookings.  If not, see <http://www.gnu.org/licenses/>.
*/


class Security extends Model{


	var $lasterr;
	
	
	function Security(){
		parent::Model();
	}
	
	
	
	
	/**
	 * get one or more users (optionally by group)
	 *
	 * @param int user_id
	 * @param int group_id
	 * @param arr pagination limit,start
	 * @return mixed (object on success, false on failure)
	 *
	 * Example - get one user
	 *   get_user(42);
	 *
	 * Example - get all users
	 *   get_user();
	 *
	 * Example - get all users in a group
	 *  get_user(NULL, 4);
	 */
	function get_user($user_id = NULL, $group_id = NULL, $page = NULL){
		
		if ($user_id == NULL) {
		
			// Getting all users
			$this->db->select('users.*, groups.name AS groupname', FALSE);
			$this->db->from('users');
			$this->db->join('groups', 'users.group_id = groups.group_id', 'left');
			
			// Filter to group if necessary
			if ($group_id != NULL && is_numeric($group_id)) {
				$this->db->where('users.group_id', $group_id);
			}
			
			$this->db->orderby('users.username ASC');
			
			if (isset($page) && is_array($page)) {
				$this->db->limit($page[0], $page[1]);
			}
			
			$query = $this->db->get();
			if ($query->num_rows() > 0){
				return $query->result();
			} else {
				$this->lasterr = 'This group is empty!';
				return 0;
			}
			
		} else {
			
			if (!is_numeric($user_id)) {
				return FALSE;
			}
			
			// Getting one user
			$sql = 'SELECT * FROM users WHERE user_id = ? LIMIT 1';
			$query = $this->db->query($sql, array($user_id));
			
			if($query->num_rows() == 1){
				$user = $query->row();
				$user->display2 = ($user->displayname) ? $user->displayname : $user->username;
				return $user;
			} else {
				return FALSE;
			}
			
		}
		
	}
	
	
	
	
	function add_user($data){
		$data['created'] = date("Y-m-d");
		$add = $this->db->insert('users', $data);
		return $add;
	}
	
	
	
	
	function edit_user($user_id = NULL, $data){
		if($user_id == NULL){
			$this->lasterr = 'Cannot update a user without their ID.';
			return FALSE;
		}
		$this->db->where('user_id', $user_id);
		$edit = $this->db->update('users', $data);
		return $edit;
	}
	
	
	
	
	function delete_user($user_id){
		
		$sql = 'DELETE FROM users WHERE user_id = ? LIMIT 1';
		$query = $this->db->query($sql, array($user_id));
		
		if($query == FALSE){
			
			$this->lasterr = 'Could not delete user. Do they exist?';
			return FALSE;
			
		} else {
			
			/* $sql = 'DELETE FROM bookings WHERE user_id = ?';
			$query = $this->db->query($sql, array($user_id));
			if($query == FALSE){ $failed[] = 'bookings'; }*/
			
			$sql = 'UPDATE rooms SET user_id = NULL where user_id = ?';
			$query = $this->db->query($sql, array($user_id));
			if($query == FALSE){ $failed[] = 'rooms'; }
			
			if(isset($failed)){
				$this->lasterr = 'The user was deleted successfully, but an error occured while removing their bookings and/or updating any rooms they owned.';
			}
			
			return TRUE;
			
		}

	}
	
	
	
	
	function get_group($group_id = NULL, $page = NULL){
		if ($group_id == NULL) {
		
			// Getting all groups
			$this->db->select('
				groups.*,
				(
					SELECT COUNT(user_id)
					FROM users
					WHERE groups.group_id = users.group_id
					LIMIT 1
				) AS usercount',
				FALSE
			);
			$this->db->from('groups');
						
			$this->db->orderby('groups.name ASC');
			
			if (isset($page) && is_array($page)) {
				$this->db->limit($page[0], $page[1]);
			}
			
			$query = $this->db->get();
			if ($query->num_rows() > 0){
				return $query->result();
			} else {
				$this->lasterr = 'No groups available.';
				return 0;
			}
			
		} else {
			
			if (!is_numeric($user_id)) {
				return FALSE;
			}
			
			// Getting one user
			$sql = 'SELECT * FROM groups WHERE group_id = ? LIMIT 1';
			$query = $this->db->query($sql, array($user_id));
			
			if($query->num_rows() == 1){
				return $query->result();
			} else {
				return FALSE;
			}
			
		}
	}
	
	
	
	
	function get_groups_dropdown(){
		$sql = 'SELECT group_id, name FROM groups ORDER BY name ASC';
		$query = $this->db->query($sql);
		if($query->num_rows() > 0){
			$result = $query->result();
			$groups = array();
			foreach($result as $group){
				$groups[$group->group_id] = $group->name;
			}
			return $groups;
		} else {
			$this->lasterr = 'No groups found';
			return FALSE;
		}
	}
	
	
	
	
	function get_group_name($group_id){
		if($group_id == NULL || !is_numeric($group_id)){
			$this->lasterr = 'No group_id given or invalid data type.';
			return FALSE;
		}
		
		$sql = 'SELECT name FROM groups WHERE group_id = ? LIMIT 1';
		$query = $this->db->query($sql, array($group_id));
		
		if($query->num_rows() == 1){
			$row = $query->row();
			return $row->name;
		} else {
			$this->lasterr = sprintf('The group supplied (ID: %d) does not exist.', $group_id);
			return FALSE;
		}
	}
	
	
	
	
	/*function get_user($user_id = NULL){
	
		// Base query information
		$select =  'users.user_id, '
							.'users.school_id, '
							.'users.username, '
							.'users.displayname, '
							.'users.email, '
							.'users.authlevel, '
							.'users.lastlogin, '
							.'users.enabled';
		
			
		// Check for getting one record or all
		if($user_id == NULL){
			// All users
			$select .= ', schools.name AS schoolname ';
			$this->db->select($select);
			$this->db->from('users');
			$this->db->join('schools', 'users.school_id=schools.school_id', 'left');
			if($school_id != NULL){
				$this->db->where('schools.school_id', $school_id);
			}
			$this->db->orderby('authlevel asc, username asc');

			// Run query
			$query = $this->db->get();
			if($query->num_rows() > 0){
				// Got rows!
				$return = $query->result();
			} else {
				// No rows :(
				$return = false;
			}
		} else {
			// One user
			$this->db->select($select);
			$this->db->from('users');
			$this->db->where('users.user_id', $user_id);
			$this->db->limit(1);
			// Run query
			$query = $this->db->get();
			if($query->num_rows() == 1){
				// Got one row exactly!
				$return = $query->row();
			} else {
				// No rows :(
				$return = false;
			}
		}
		return $return;
	}*/
	
	
	
}
?>